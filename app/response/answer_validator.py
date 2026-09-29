"""Deterministic faithfulness check for the final natural-language answer.

Rule-based, not an LLM call - consistent with this project's "no accidental
extra LLM call" principle (see app.intelligence.query_analyzer /
app.intelligence.parameter_resolution docstrings for the same reasoning
applied earlier in the pipeline). Runs after CALL 2 (answer generation) and
never blocks the response - it only labels it, so a wrong-looking answer is
never silently returned as if it were verified.
"""

from __future__ import annotations

import re
from dataclasses import dataclass, field

from app.database.executor import QueryExecutionResult

_NO_RESULTS_PHRASES = (
    "no student", "no record", "no result", "no match", "none found",
    "0 student", "did not find", "could not find", "couldn't find",
    "no data", "no rows", "not find any",
)
# A single aggregate over zero matching rows (AVG/MAX/MIN of an empty group -
# e.g. "average late arrivals this week" when nobody was late all week) is
# SQL NULL, not a fabricated value - stringifying it ("none") rarely matches
# how the answer phrases an absent aggregate ("null", "zero", "n/a", ...).
_NULL_VALUE_PHRASES = _NO_RESULTS_PHRASES + ("null", "n/a", "not available", "zero", "unknown")

_NUMBER_PATTERN = re.compile(r"\b\d+(?:\.\d+)?\b")
# Thousands-grouping commas ("5,556") sit between two digits; strip only
# those before number-matching, so "5,556" reads as one number (5556) and
# "Ravi, Kumar" is untouched (comma isn't between two digits there).
_DIGIT_GROUPING_COMMA = re.compile(r"(?<=\d),(?=\d)")
# A calendar date restated from the question/SQL ("2026-09-01") is not a
# fabricated count - it must be dropped before number-matching, otherwise
# its hyphen-separated pieces (2026, 09, 01) are each read as an invented
# number (an attendance answer that names the date it's about would
# otherwise almost always fail this check).
_ISO_DATE = re.compile(r"\b\d{4}-\d{1,2}-\d{1,2}\b")

# Values shorter than this are too common (ids, single digits, "1", "no")
# to count as evidence the answer is grounded in a specific row.
_MIN_GROUNDING_VALUE_LENGTH = 3


def _degroup_numbers(text: str) -> str:
    return _DIGIT_GROUPING_COMMA.sub("", text)


@dataclass
class AnswerValidationResult:
    valid: bool
    reasons: list[str] = field(default_factory=list)


class AnswerFaithfulnessValidator:
    """Checks the answer against the data it claims to summarize, not
    against the question's language - a wrong-but-fluent answer is exactly
    what this exists to catch."""

    def validate(
        self,
        execution_result: QueryExecutionResult,
        answer_text: str,
        filter_values: list[str] | None = None,
        question_text: str | None = None,
    ) -> AnswerValidationResult:
        """`filter_values` are the deterministically-resolved parameter
        values the query was filtered by (e.g. "2026 JUN" for an
        academic_year filter). An answer restating its own filter - "...in
        academic year 2026 JUN" - is legitimate even when that value was
        never itself selected as a row/column, so it's treated as
        known-true ground truth alongside the actual returned rows.

        `question_text` is the user's own original question. A number the
        USER named ("...absent continuously for 5 days", "arrived late
        after 10am") is legitimate for the answer to restate even when
        nothing extracted it as a bound parameter - e.g. the attendance
        query catalog writes such values as inline SQL literals rather than
        parameters (see core_student.yaml student_attendance), so they
        never reach `filter_values` otherwise."""
        reasons: list[str] = []
        row_count = len(execution_result.rows)
        lowered = answer_text.lower()
        lowered_degrouped = _degroup_numbers(lowered)
        known_values = [
            value for row in execution_result.rows for value in row.values()
        ] + list(filter_values or [])

        if row_count == 0:
            if not any(phrase in lowered for phrase in _NO_RESULTS_PHRASES):
                reasons.append(
                    "No rows were returned, but the answer does not indicate an empty result."
                )
            return AnswerValidationResult(valid=not reasons, reasons=reasons)

        if row_count == 1 and len(execution_result.columns) == 1 and next(iter(execution_result.rows[0].values())) is None:
            if not any(phrase in lowered for phrase in _NULL_VALUE_PHRASES):
                reasons.append(
                    "The single aggregate value is SQL NULL (nothing matched), but the answer does not say so."
                )
            return AnswerValidationResult(valid=not reasons, reasons=reasons)

        if not execution_result.truncated:
            without_dates = _ISO_DATE.sub("", lowered_degrouped)
            stated_numbers = {float(match) for match in _NUMBER_PATTERN.findall(without_dates)}
            known_numbers = self._numeric_values(known_values)
            if question_text:
                question_without_dates = _ISO_DATE.sub("", _degroup_numbers(question_text.lower()))
                known_numbers |= {float(match) for match in _NUMBER_PATTERN.findall(question_without_dates)}
            # A number is only suspicious as a *fabricated count* if it
            # doesn't match row_count AND isn't itself a value that
            # legitimately appears in the data or the query's own filters
            # (e.g. a batch year, a semester number) - otherwise every
            # answer that quotes a numeric column/filter value would be
            # wrongly flagged.
            suspicious = {n for n in stated_numbers if n != row_count and n not in known_numbers}
            if suspicious:
                reasons.append(
                    f"The answer states a number ({sorted(suspicious)}) that matches neither "
                    f"the actual row_count ({row_count}) nor any value in the retrieved rows/filters."
                )

        # A one-row, one-column result is an aggregate (a count, a total): its
        # value IS the answer even when it is short ("28"), which the generic
        # minimum-length rule below would otherwise discount as too common.
        is_single_aggregate = row_count == 1 and len(execution_result.columns) == 1
        if not self._shares_a_known_value(lowered_degrouped, known_values, allow_short_numbers=is_single_aggregate):
            reasons.append(
                "The answer does not reference any specific value present in the retrieved rows - "
                "it may be generic rather than grounded in this result."
            )

        return AnswerValidationResult(valid=not reasons, reasons=reasons)

    @staticmethod
    def _numeric_values(values: list) -> set[float]:
        """A decimal like "8.6" must read as one number, not split on the
        "." into {8, 6} - and "8.60"/"8.6" (the LLM may format trailing
        zeros differently than the stored value) must compare equal, so
        every number is compared as a float, never as separate int
        fragments or as a string."""
        numbers: set[float] = set()
        for value in values:
            text = _degroup_numbers(str(value).strip())
            try:
                numbers.add(float(text))
            except ValueError:
                numbers.update(float(match) for match in _NUMBER_PATTERN.findall(text))
        return numbers

    @staticmethod
    def _shares_a_known_value(lowered_answer: str, values: list, allow_short_numbers: bool = False) -> bool:
        for value in values:
            text = _degroup_numbers(str(value).strip().lower())
            if len(text) >= _MIN_GROUNDING_VALUE_LENGTH and text in lowered_answer:
                return True
            if allow_short_numbers and text.replace(".", "", 1).isdigit():
                # A single-aggregate zero ("strength: 0") is commonly worded
                # as "zero", "none", or a flat negation ("No trainees
                # arrived late today") rather than the digit "0"
                # (live-repro'd). Safe to accept broadly here because the
                # separate stated-number check below still catches a wrong
                # NUMBER the answer names - this only excuses phrasing a
                # true zero without writing "0".
                if text == "0" and any(phrase in lowered_answer for phrase in ("zero", "none", "no ")):
                    return True
                # Whole-number token match so "8" isn't found inside "28".
                if re.search(rf"(?<![\d.]){re.escape(text)}(?![\d])", lowered_answer):
                    return True
        return False
