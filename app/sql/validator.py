"""Deterministic allow-list validation for generated read-only SQL."""

from __future__ import annotations

import re
from dataclasses import dataclass, field

from app.intelligence.result_scope import ResultScope
from app.intelligence.schema_retriever import SchemaSelection


@dataclass
class SQLValidationResult:
    valid: bool
    errors: list[str] = field(default_factory=list)


class SQLValidator:
    _TABLE_PATTERN = re.compile(
        r"\b(?:from|join)\s+`?([a-zA-Z_][\w]*)`?"
        r"(?:\s+(?:as\s+)?([a-zA-Z_]\w*))?"
        # A table/alias may be followed by another join clause that itself
        # carries a type qualifier (INNER/LEFT/RIGHT/FULL/OUTER/CROSS JOIN),
        # not just a bare "JOIN" - without this the alias before such a
        # clause is silently dropped, and every later reference to it is
        # rejected as an "unknown alias" false positive.
        r"(?=\s+(?:(?:inner|left|right|full|outer|cross)\s+)?join\b|\s+(?:on|where|group|order|limit)\b|\s*$)",
        re.IGNORECASE,
    )
    _QUALIFIED_COLUMN = re.compile(r"\b([a-zA-Z_]\w*)\.([a-zA-Z_]\w*)\b")

    # A predicate on the exam-attempt columns (a comparison/IN/IS after the
    # column); merely SELECTing or ORDERing by them does not match.
    _ATTEMPT_PREDICATE = re.compile(
        r"\b(?:[a-z_]\w*\.)?(?:final_exam_type|suppl_no)\s*(?:=|<>|!=|<=|>=|<|>|\bin\b|\bis\b|\blike\b|\bbetween\b)",
        re.IGNORECASE,
    )

    def validate(
        self, sql: str, selection: SchemaSelection, result_scope: ResultScope | None = None,
    ) -> SQLValidationResult:
        errors: list[str] = []
        statement = sql.strip()
        if statement.endswith(";"):
            statement = statement[:-1].strip()
        if not re.match(r"^select\b", statement, re.IGNORECASE):
            errors.append("Only a SELECT statement is allowed.")
        if ";" in statement or re.search(r"--|/\*|\*/", statement):
            errors.append("Multiple statements and SQL comments are not allowed.")
        if re.search(r"\b(insert|update|delete|drop|alter|create|truncate|grant|revoke|into|outfile)\b", statement, re.IGNORECASE):
            errors.append("The statement contains a forbidden SQL operation.")

        # Every :name placeholder must be one of the deterministically
        # extracted parameters - the executor only ever binds those, so an
        # invented/renamed placeholder (e.g. :semester_id for the supplied
        # `semester`) would otherwise crash at execution time instead of
        # being caught, and retried, here. String literals are blanked first
        # so a ':' inside e.g. '10:30' is not mistaken for a placeholder.
        without_literals = re.sub(r"'(?:[^'\\]|\\.|'')*'", "''", statement)
        unknown_placeholders = sorted(
            {name for name in re.findall(r"(?<![:\w]):([a-zA-Z_]\w*)", without_literals)} - set(selection.parameters)
        )
        if unknown_placeholders:
            errors.append(
                f"The SQL uses parameter(s) {[':' + name for name in unknown_placeholders]} that were not supplied; "
                f"only these named parameters exist: {[':' + name for name in sorted(selection.parameters)]}."
            )

        # "Semester 1 result" / "regular and supplementary" / "consolidated"
        # mean EVERY attempt. The model has been seen to keep only the regular
        # exam anyway (dropping the supplementary rows for good), and nothing
        # downstream can add rows back - so it is rejected here and retried.
        if (
            result_scope is not None and result_scope.exam_type is None
            and self._ATTEMPT_PREDICATE.search(without_literals)
        ):
            errors.append(
                "The question covers every exam attempt (regular and supplementary), so do not filter on "
                "final_exam_type or suppl_no - select them and return all attempts."
            )

        if result_scope is not None and result_scope.consolidated and not re.search(
            r"\bsubject_(?:name|code)\b", without_literals
        ):
            errors.append(
                "A consolidated result is built from subject rows of every attempt: select subject_name and "
                "subject_code (with the marks, grade, final_exam_type and suppl_no), not per-semester summary rows."
            )

        aliases: dict[str, str] = {}
        selected_tables = set(selection.tables)
        for match in self._TABLE_PATTERN.finditer(statement):
            table_name, alias = match.groups()
            if table_name not in selected_tables:
                errors.append(f"Table '{table_name}' was not selected for this query.")
                # Deliberately not registered as a known alias below: doing
                # so let a qualified-column reference to this table pass the
                # "unknown alias" check and then crash with an unguarded
                # KeyError on `selection.tables[table_name]` instead of
                # reporting a clean validation error (live-repro'd bug).
                continue
            aliases[table_name] = table_name
            if alias and alias.lower() not in {"on", "where", "order", "group", "limit"}:
                aliases[alias] = table_name

        if not aliases:
            errors.append("The statement must contain a selected FROM table.")

        for qualifier, column in self._QUALIFIED_COLUMN.findall(statement):
            table_name = aliases.get(qualifier)
            if table_name is None:
                errors.append(f"Unknown table or alias '{qualifier}' used for column '{column}'.")
            elif column not in {item["name"] for item in selection.tables[table_name]}:
                errors.append(f"Column '{table_name}.{column}' was not selected for this query.")

        for left, right in re.findall(
            r"\bon\s+([a-zA-Z_]\w*\.[a-zA-Z_]\w*)\s*=\s*([a-zA-Z_]\w*\.[a-zA-Z_]\w*)",
            statement,
            re.IGNORECASE,
        ):
            if not self._is_allowed_join(left, right, aliases, selection):
                errors.append(f"Join '{left} = {right}' is not a verified selected relationship.")
        return SQLValidationResult(valid=not errors, errors=errors)

    @staticmethod
    def _is_allowed_join(left: str, right: str, aliases: dict[str, str], selection: SchemaSelection) -> bool:
        def endpoint(reference: str) -> tuple[str, str]:
            qualifier, column = reference.split(".", 1)
            return aliases.get(qualifier, qualifier), column

        actual = frozenset((endpoint(left), endpoint(right)))
        return any(actual == frozenset((
            (relationship.from_.table, relationship.from_.column),
            (relationship.to.table, relationship.to.column),
        )) for relationship in selection.relationships)
