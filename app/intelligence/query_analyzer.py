"""Deterministic, metadata-driven analysis before any LLM invocation."""

from __future__ import annotations

import re
from dataclasses import dataclass, field
from typing import Any

from app.intelligence.entity_resolver import (
    ConceptExtractor,
    ExtractedConcept,
    IntentClassifier,
    RegexConceptExtractor,
)
from app.intelligence.parameter_resolution import (
    AmbiguousParameter,
    ParameterResolver,
    candidate_concept_vocabulary,
)
from app.intelligence.result_scope import ResultScope, detect_result_scope
from app.metadata.models import ColumnSemanticsMetadata, SchemaMetadata, StudentTruthLayer, TableMetadata


@dataclass(frozen=True)
class ExtractedParameter:
    name: str
    value: str
    table: str
    column: str
    operator: str = "="
    values: list[str] | None = None


@dataclass
class QueryAnalysis:
    user_query: str
    # The question with domain synonyms mapped to their canonical word
    # (trainees -> students); None when nothing was rewritten.
    normalized_query: str | None = None
    module: str | None = None
    intent: str | None = None
    intents: list[str] = field(default_factory=list)
    entities: list[str] = field(default_factory=list)
    parameters: list[ExtractedParameter] = field(default_factory=list)
    ambiguous_parameters: list[AmbiguousParameter] = field(default_factory=list)
    # Which exam attempts a results question means (regular / supplementary n /
    # consolidated) - only set when the student_result intent matched.
    result_scope: ResultScope | None = None
    clarification: str | None = None
    decision_trace: list[dict[str, str]] = field(default_factory=list)

    @property
    def is_ready(self) -> bool:
        return self.intent is not None and self.clarification is None

    def as_dict(self) -> dict[str, Any]:
        return {
            "normalized_question": self.normalized_query,
            "module": self.module,
            "intent": self.intent,
            "intents": self.intents,
            "entities": self.entities,
            "parameters": [parameter.__dict__ for parameter in self.parameters],
            "result_scope": self.result_scope.as_dict() if self.result_scope else None,
            "clarification": self.clarification,
            "decision_trace": self.decision_trace,
        }


class QueryAnalyzer:
    """Deterministic, metadata-driven query analysis.

    Module/entity/intent detection use only curated semantic metadata.
    Parameter resolution additionally consults the generated schema for the
    small set of tables the resolved intent already selected, so physical
    columns are discovered rather than manually mapped one by one.
    """

    def __init__(
        self,
        truth: StudentTruthLayer,
        schema: SchemaMetadata,
        column_semantics: ColumnSemanticsMetadata | None = None,
        concept_extractor: ConceptExtractor | None = None,
        intent_classifier: IntentClassifier | None = None,
    ):
        self.truth = truth
        self.schema = schema
        self.column_semantics = column_semantics or ColumnSemanticsMetadata()
        self.semantic = truth.semantic
        self.catalog = truth.query_catalog
        # Default extractor is deterministic and offline (no LLM call on the
        # normal path); pass an LLMConceptExtractor explicitly to opt in to
        # CALL 1 for queries whose language is too complex for regex.
        self.concept_extractor = concept_extractor or RegexConceptExtractor(truth)
        # None by default (fully backward compatible: unresolved intent ->
        # clarification, exactly as before). Pass an LLMIntentClassifier to
        # let the LLM pick from the known intent catalog ONLY when
        # deterministic term-matching finds zero or more than one match -
        # e.g. "student detail" matches none of student_listing's literal
        # terms even though it's obviously that intent. Scales to new
        # modules without hand-growing a term list per phrasing.
        self.intent_classifier = intent_classifier
        # {canonical: compiled pattern of its variants, allowing a plural "s"}
        self._synonyms = {
            canonical: re.compile(
                r"\b(?:" + "|".join(re.escape(variant) for variant in variants) + r")(s?)\b", re.IGNORECASE
            )
            for canonical, variants in (self.semantic.get("synonyms") or {}).items()
        }

    def _canonicalize(self, query: str) -> str:
        for canonical, pattern in self._synonyms.items():
            query = pattern.sub(lambda match, word=canonical: word + match.group(1), query)
        return query

    def analyze(self, original_query: str) -> QueryAnalysis:
        user_query = self._canonicalize(original_query)
        normalized = " ".join(user_query.lower().split())
        analysis = QueryAnalysis(
            user_query=original_query,
            normalized_query=user_query if user_query != original_query else None,
        )

        if any(term in normalized for term in self.semantic["out_of_scope_terms"]):
            analysis.clarification = (
                "LEGS, and deployment queries are not in the current scope."
            )
            return analysis

        if not any(term in normalized for term in self.semantic["module_terms"]):
            analysis.clarification = "Please ask a Core Programme student question."
            return analysis

        analysis.module = self.truth.module
        analysis.decision_trace.append({
            "stage": "module_detection",
            "decision": "core",
            "evidence": "Matched configured Core-student module terms.",
        })
        analysis.entities = self._entities(normalized)
        analysis.intents, intent_evidence = self._intents(normalized, user_query)
        analysis.intent = analysis.intents[0] if analysis.intents else None
        analysis.decision_trace.append({
            "stage": "intent_detection",
            "decision": ", ".join(analysis.intents) or "unresolved",
            "evidence": intent_evidence,
        })

        if not analysis.intents:
            analysis.clarification = "Please specify whether you need students, results, or certificates."
            return analysis

        candidate_tables = self._candidate_tables(analysis.intents)
        preferred_tables = self._preferred_filter_tables(analysis.intents)
        hint_vocabulary = candidate_concept_vocabulary(candidate_tables, self.column_semantics)
        concepts = self.concept_extractor.extract(user_query, hint_vocabulary)
        analysis.decision_trace.append({
            "stage": "concept_extraction",
            "decision": ", ".join(f"{c.name}={c.value}" for c in concepts) or "none",
            "evidence": f"Extractor: {type(self.concept_extractor).__name__}.",
        })

        outcome = ParameterResolver(self.truth, self.column_semantics).resolve(
            concepts, candidate_tables, preferred_tables,
        )
        analysis.parameters = [
            ExtractedParameter(
                name=item.name, value=item.value, table=item.table, column=item.column,
                operator=item.operator, values=item.values,
            )
            for item in outcome.resolved
        ]
        analysis.ambiguous_parameters = outcome.ambiguous
        analysis.decision_trace.append({
            "stage": "parameter_resolution",
            "decision": ", ".join(f"{p.name}={p.table}.{p.column}" for p in analysis.parameters) or "none",
            "evidence": (
                "Curated Truth Layer names resolved first; remaining concepts resolved "
                "dynamically against the candidate tables' schema/alias metadata."
            ),
        })

        if outcome.requires_resolution:
            analysis.clarification = self._temporal_message(outcome.requires_resolution)
            return analysis

        if outcome.ambiguous:
            analysis.clarification = self._ambiguity_message(outcome.ambiguous)
            return analysis

        if "student_result" in analysis.intents:
            analysis.result_scope = detect_result_scope(user_query)
            has_semester = any(parameter.column == "semester_id" for parameter in analysis.parameters)
            if analysis.result_scope.consolidated and not has_semester and not analysis.result_scope.all_semesters:
                analysis.clarification = (
                    "For a consolidated result please mention the semester number (for example "
                    "\"semester 1\"), or say \"for all semesters\" if you want every semester."
                )
                return analysis

        if self._identity_missing(analysis):
            analysis.clarification = (
                "Please provide an enquiry number, hall-ticket number, or Core student code"
                + (
                    " - or at least one filter (e.g. center, program, stream, academic year, semester)."
                    if any(self.catalog[i].get("identity_optional_with_filters") for i in analysis.intents)
                    else "."
                )
            )
        return analysis

    def _identity_missing(self, analysis: QueryAnalysis) -> bool:
        """An intent with `requires_identity` needs a specific student's
        identifier - unless it also sets `identity_optional_with_filters`
        (e.g. results: one student by code, OR a whole cohort by center /
        program / stream / academic year / semester), in which case any one
        resolved filter is enough."""
        has_identity = self._has_identity(analysis)
        for intent in analysis.intents:
            config = self.catalog[intent]
            if not config.get("requires_identity") or has_identity:
                continue
            if config.get("identity_optional_with_filters") and analysis.parameters:
                continue
            return True
        return False

    def _entities(self, query: str) -> list[str]:
        return [
            name for name, config in self.semantic["entities"].items()
            if any(term in query for term in config["terms"])
        ]

    def _intents(self, normalized_query: str, original_query: str) -> tuple[list[str], str]:
        """A query may legitimately need more than one catalog entry at once
        (e.g. "student detail ... cgpa in all semester ... father name" spans
        student_result and student_family) - every catalog entry whose terms
        match is used together, not just one. The LLM classifier is only
        consulted as a fallback when deterministic term-matching finds
        nothing at all, and its answer is still validated against the known
        catalog before being accepted."""
        matches = [
            name for name, config in self.catalog.items()
            if any(term in normalized_query for term in config["terms"])
        ]
        if matches:
            return matches, "Matched configured query-catalog terms."

        if self.intent_classifier is not None:
            candidate_intents = {name: config["terms"] for name, config in self.catalog.items()}
            classified = self.intent_classifier.classify(original_query, candidate_intents)
            if classified:
                return (
                    [classified],
                    "No deterministic term match; LLM intent classifier selected "
                    f"'{classified}' from the known catalog.",
                )

        return [], "No deterministic term match, and no intent classifier resolved it."

    def _candidate_tables(self, intents: list[str]) -> list[TableMetadata]:
        """Tables whose columns may be treated as *filters* for parameter
        extraction/resolution. An intent may narrow this with `filter_tables`
        (default: all its `tables`) so lookup/output-only tables - e.g.
        employees or lead_master, joined just to display details - do not
        contribute their column names as filter vocabulary (which would turn
        ordinary words like "employee" into bogus filters)."""
        by_name = {table.table: table for table in self.schema.tables}
        names = dict.fromkeys(
            name for intent in intents
            for name in self.catalog[intent].get("filter_tables", self.catalog[intent]["tables"])
        )
        return [by_name[name] for name in names if name in by_name]

    def _preferred_filter_tables(self, intents: list[str]) -> list[str]:
        return list(dict.fromkeys(
            name for intent in intents
            for name in self.catalog[intent].get("preferred_filter_tables", [])
        ))

    @staticmethod
    def _ambiguity_message(ambiguous: list[AmbiguousParameter]) -> str:
        parts = [
            f"'{item.concept}' ({', '.join(f'{c.table}.{c.column}' for c in item.candidates)})"
            for item in ambiguous
        ]
        return "Please clarify which column you mean for: " + "; ".join(parts) + "."

    @staticmethod
    def _temporal_message(pending: list[ExtractedConcept]) -> str:
        parts = [f"'{item.value}' ({item.name})" for item in pending]
        return (
            "Please provide an exact value instead of a relative time expression for: "
            + "; ".join(parts) + "."
        )

    @staticmethod
    def _has_identity(analysis: QueryAnalysis) -> bool:
        return any(parameter.name in {
            "enquiry_number", "hall_ticket_number", "core_student_code",
        } for parameter in analysis.parameters)
