"""Select a minimal, verified schema subset for SQL generation."""

from __future__ import annotations

import json
from dataclasses import dataclass, field
from typing import Any

from app.intelligence.query_analyzer import QueryAnalysis
from app.metadata.models import ColumnSemanticsMetadata, RelationshipMetadata, SchemaMetadata, StudentTruthLayer


@dataclass(frozen=True)
class SchemaSelection:
    module: str
    intent: str
    tables: dict[str, list[dict[str, Any]]]
    relationships: list[RelationshipMetadata]
    parameters: dict[str, str | list[str]]
    total_available_tables: int
    candidate_tables: int
    decision_trace: list[dict[str, str]]
    # Question-type-specific SQL-writing rules from the matched catalog
    # entries' `sql_hints` (see core_student.yaml); appended to the LLM's
    # instructions by app.sql.generator.
    sql_hints: list[str] = field(default_factory=list)

    @property
    def selected_columns(self) -> int:
        return sum(len(columns) for columns in self.tables.values())

    def as_llm_context(self) -> dict:
        payload = {
            "module": self.module,
            "intent": self.intent,
            "parameters": self.parameters,
            "tables": self.tables,
            "relationships": [relationship.model_dump(by_alias=True) for relationship in self.relationships],
        }
        payload["context_metrics"] = {
            "total_available_tables": self.total_available_tables,
            "candidate_tables": self.candidate_tables,
            "selected_tables": len(self.tables),
            "selected_columns": self.selected_columns,
            "approximate_schema_tokens": len(json.dumps(payload)) // 4,
        }
        return payload


class SchemaRetriever:
    def __init__(
        self, schema: SchemaMetadata, truth: StudentTruthLayer,
        column_semantics: ColumnSemanticsMetadata | None = None,
    ):
        self.schema = schema
        self.truth = truth
        self.column_semantics = column_semantics or ColumnSemanticsMetadata()

    def retrieve(self, analysis: QueryAnalysis) -> SchemaSelection:
        if not analysis.is_ready:
            raise ValueError("Cannot retrieve schema for an unresolved query analysis.")
        # A query may match more than one catalog entry at once (e.g. a
        # request for both identity fields and cgpa/semester results) -
        # every matched intent's tables/columns/relationships are unioned
        # rather than only using the single best match, so a legitimate
        # cross-intent question doesn't get validation-rejected for
        # selecting real columns that simply weren't in one intent's list.
        intents = analysis.intents or ([analysis.intent] if analysis.intent else [])
        catalogs = [self.truth.query_catalog[intent] for intent in intents]
        configured_tables: set[str] = set()
        columns_by_table: dict[str, list[str]] = {}
        required_relationship_names: list[str] = []
        sql_hints: list[str] = []
        for catalog in catalogs:
            configured_tables.update(catalog["tables"])
            for hint in catalog.get("sql_hints", []):
                if hint not in sql_hints:
                    sql_hints.append(hint)
            for table_name, columns in catalog["output_columns"].items():
                columns_by_table.setdefault(table_name, [])
                columns_by_table[table_name].extend(columns)
            for name in catalog.get("required_relationships", []):
                if name not in required_relationship_names:
                    required_relationship_names.append(name)
        for parameter in analysis.parameters:
            if parameter.table in configured_tables:
                columns_by_table.setdefault(parameter.table, []).append(parameter.column)

        tables = {
            table_name: self._selected_columns(table_name, columns)
            for table_name, columns in columns_by_table.items()
        }
        relationships = [
            self.truth.relationships[name]
            for name in required_relationship_names
            if self.truth.relationships[name].verified
        ]
        return SchemaSelection(
            module=analysis.module or self.truth.module,
            intent=analysis.intent,
            tables=tables,
            relationships=relationships,
            parameters={
                parameter.name: (parameter.values if parameter.values else parameter.value)
                for parameter in analysis.parameters
            },
            total_available_tables=len(self.schema.tables),
            candidate_tables=len(configured_tables),
            sql_hints=sql_hints,
            decision_trace=[{
                "stage": "schema_selection",
                "decision": ", ".join(sorted(tables)),
                "evidence": "Selected only tables, columns, and verified relationships configured for the resolved intent.",
            }],
        )

    def _selected_columns(self, table_name: str, names: list[str]) -> list[dict[str, Any]]:
        table = next(table for table in self.schema.tables if table.table == table_name)
        by_name = {column.name: column for column in table.columns}
        missing = set(names) - set(by_name)
        if missing:
            raise ValueError(f"Metadata selects unknown columns for {table_name}: {sorted(missing)}")
        columns = []
        for name in dict.fromkeys(names):
            column = by_name[name]
            entry: dict[str, Any] = {"name": name, "type": column.type}
            # Ground the LLM in the column's real allowed values instead of
            # letting it guess/copy a plausible-sounding literal - live-
            # repro'd bug: it reused "temporary_enrolled" (the status enum's
            # value) for payment_type too, whose real value is the
            # differently-spelled "temporary_enroll". A true DB ENUM's
            # values are known from schema extraction; a VARCHAR column
            # with a de facto fixed vocabulary (schema extraction can't
            # discover that automatically) falls back to a curated list.
            allowed_values = column.enum_values or self.column_semantics.allowed_values_for(table_name, name)
            if allowed_values:
                entry["allowed_values"] = allowed_values
            # A curated description exists specifically for columns whose
            # physical name isn't self-explanatory (e.g. core_year meaning
            # "academic year") or that need a disambiguating warning (e.g.
            # a lookalike neighbor column that's actually unreliable) -
            # surfaced here so the LLM gets the same grounding a human
            # reading the YAML would.
            description = self.column_semantics.description_for(table_name, name)
            if description:
                entry["description"] = " ".join(description.split())
            columns.append(entry)
        return columns
