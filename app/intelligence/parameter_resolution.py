"""Physical resolution: mapping an already-extracted concept to a column.

This module owns *resolution only* - "which table.column does this concept
refer to?" It never parses natural language (see
app.intelligence.entity_resolver for extraction); it only searches the
schema metadata of the small candidate-table set already selected for the
query's intent, never the full generated schema.

Curated concepts (``semantic.parameters`` table/column in the Truth Layer
YAML) are reserved for business semantics that cannot be derived reliably:
identity identifiers, and columns whose curated table is the deliberate
authority when more than one table could otherwise match (see ``status``).
Everything else is resolved dynamically from the candidate tables' own
columns - enriched by optional column_semantics aliases (e.g. "academic
year" for the oddly-named ``core_year`` column) - so a new filterable
column never requires a manual parameter-to-column entry.
"""

from __future__ import annotations

import re
from dataclasses import dataclass, field
from typing import Any, Sequence

from app.intelligence.entity_resolver import ExtractedConcept
from app.metadata.models import ColumnSemanticsMetadata, StudentTruthLayer, TableMetadata

# Columns that are structural/audit metadata rather than user-facing filter
# concepts. Intentionally small and generic - not a per-column mapping.
_NON_SEMANTIC_COLUMNS = {
    "id", "uuid", "created_at", "updated_at", "deleted_at",
    "created_by", "updated_by", "created_with", "remarks",
}

# Suffixes that name a *display* attribute distinct from a surrogate key,
# e.g. both batch_name and batch_id exist; a bare "batch" in a query means
# the display value unless the user explicitly says "batch id". Surrogate
# key suffixes (_id) are deliberately excluded so a bare concept phrase
# never resolves to an internal id column.
_STRIPPABLE_SUFFIXES = ("_name", "_code", "_no", "_number")

_NUMERIC_TYPE_MARKERS = ("INT", "DECIMAL", "NUMERIC", "FLOAT", "DOUBLE")


@dataclass(frozen=True)
class ColumnCandidate:
    table: str
    column: str
    column_type: str
    match_kind: str  # "alias" (curated) | "exact" (raw column name) | "normalized" (suffix stripped)
    enum_values: list[str] | None = None
    known_values: list[str] | None = None


@dataclass(frozen=True)
class ResolvedParameter:
    name: str
    value: str
    table: str
    column: str
    source: str  # "curated" | "dynamic"
    score: int
    operator: str = "="
    values: list[str] | None = None


@dataclass(frozen=True)
class AmbiguousParameter:
    concept: str
    value: str
    candidates: list[ColumnCandidate]


@dataclass
class ParameterResolutionOutcome:
    resolved: list[ResolvedParameter] = field(default_factory=list)
    ambiguous: list[AmbiguousParameter] = field(default_factory=list)
    requires_resolution: list[ExtractedConcept] = field(default_factory=list)


def _column_phrases(column_name: str) -> list[tuple[str, str]]:
    """Return (phrase, match_kind) pairs a column may be referred to by."""
    name = column_name.lower()
    if name in _NON_SEMANTIC_COLUMNS:
        return []
    phrases = [(name.replace("_", " "), "exact")]
    for suffix in _STRIPPABLE_SUFFIXES:
        if name.endswith(suffix) and len(name) > len(suffix):
            stripped = name[: -len(suffix)].replace("_", " ")
            if stripped:
                phrases.append((stripped, "normalized"))
            break
    return phrases


def candidate_concept_vocabulary(
    candidate_tables: list[TableMetadata], column_semantics: ColumnSemanticsMetadata | None = None
) -> list[str]:
    """Phrase vocabulary for bare-juxtaposition extraction hints (e.g. "batch 2024").

    Derived entirely from the candidate tables' own schema (and optional
    curated aliases) - never a manually maintained parameter list.
    """
    phrases: set[str] = set()
    for table in candidate_tables:
        for column in table.columns:
            phrases.update(phrase for phrase, _ in _column_phrases(column.name))
            if column_semantics:
                phrases.update(alias.lower() for alias in column_semantics.aliases_for(table.table, column.name))
    return sorted(phrases)


# A value given in word form ("semester one", "first semester") must still
# match a numeric-typed column ("1"), or the generated SQL compares a
# numeric column to a non-numeric string and silently matches nothing (or,
# for MySQL's numeric string coercion, the wrong row). Generic across any
# numeric concept - not specific to "semester".
_NUMBER_WORDS = {
    "one": "1", "two": "2", "three": "3", "four": "4", "five": "5",
    "six": "6", "seven": "7", "eight": "8", "nine": "9", "ten": "10",
    "eleven": "11", "twelve": "12",
    "first": "1", "second": "2", "third": "3", "fourth": "4", "fifth": "5",
    "sixth": "6", "seventh": "7", "eighth": "8", "ninth": "9", "tenth": "10",
    "eleventh": "11", "twelfth": "12",
}


def _looks_numeric(value: str) -> bool:
    return value.replace("-", "").isdigit()


def _normalize_numeric_word(value: str, column_type: str) -> str:
    """Convert a word-form number to digits when the resolved column is
    numeric-typed; left unchanged otherwise (including for numeric-looking
    strings that already are digits)."""
    column_is_numeric = any(marker in column_type.upper() for marker in _NUMERIC_TYPE_MARKERS)
    if not column_is_numeric:
        return value
    return _NUMBER_WORDS.get(value.strip().lower(), value)


def _snap_to_enum_value(value: str, enum_values: list[str] | None) -> str:
    """Map a user's natural-language value to the exact stored ENUM token.

    Live-repro'd bug: the DB stores ENUM values as a single snake_case token
    ("temporary_enrolled"), but a user types it as ordinary words
    ("temporary enrolled") - and even when extraction captures every word,
    `status = 'temporary enrolled'` would still never match the stored
    `'temporary_enrolled'`. This also absorbs the (separate) case where
    extraction only captured a truncated prefix of a multi-word value:
    whatever text was captured is always a *prefix* of what the user typed
    (extraction only ever stops early, never starts late), so snapping to
    the one enum value that prefix uniquely identifies recovers the full,
    correct value regardless of how many words extraction kept.
    """
    if not enum_values:
        return value
    normalized_value = value.strip().lower().replace(" ", "_").replace("-", "_")
    if not normalized_value:
        return value
    matches = [
        candidate for candidate in enum_values
        if candidate.lower() == normalized_value or candidate.lower().startswith(normalized_value + "_")
    ]
    if len(matches) == 1:
        return matches[0]
    return value


# Wording like "Peenya" or "Electronic City" for a center whose stored value
# is "BTC - PEENYA" / "NEC - ELECTRONIC CITY". A partial term that hits many
# values (e.g. "2024" -> every 2024 session) is expanded to all of them, but
# only up to this many - beyond that the term is too generic to trust.
_MAX_EXPANDED_VALUES = 10
_NON_ALNUM = re.compile(r"[^a-z0-9]+")


def _plain(text: str) -> str:
    return _NON_ALNUM.sub(" ", text.lower()).strip()


def _snap_to_known_values(value: str, known_values: list[str] | None) -> list[str]:
    """Match a user's wording against a column's snapshot of real values.

    Exact (case/punctuation-insensitive) equality wins outright - "Diploma"
    is the program DIPLOMA, not also POST DIPLOMA. Otherwise every stored
    value containing the wording as whole words is returned (so "Jamshedpur"
    covers both Jamshedpur centers). No match, or too many, returns [] and
    the caller leaves the value untouched.
    """
    if not known_values:
        return []
    wanted = _plain(value)
    if not wanted:
        return []
    exact = [known for known in known_values if _plain(known) == wanted]
    if exact:
        return exact[:1]
    padded = f" {wanted} "
    contains = [known for known in known_values if padded in f" {_plain(known)} "]
    return contains if 0 < len(contains) <= _MAX_EXPANDED_VALUES else []


def _finalize_values(
    concept: ExtractedConcept, column_type: str,
    enum_values: list[str] | None, known_values: list[str] | None,
) -> tuple[str, list[str] | None, str]:
    """(value, values, operator) after mapping the extracted wording onto the
    resolved column's real vocabulary: true ENUM values, a value-catalog
    snapshot, or (for numeric columns) digits instead of number words."""
    out: list[str] = []
    for item in (concept.values or [concept.value]):
        if enum_values:
            out.append(_snap_to_enum_value(item, enum_values))
            continue
        matches = _snap_to_known_values(item, known_values)
        if matches:
            out.extend(matches)
            continue
        out.append(_normalize_numeric_word(item, column_type))
    out = list(dict.fromkeys(out))
    if len(out) > 1:
        return " and ".join(out), out, "in"
    return out[0], None, "="



def _type_score(value: str, column_type: str) -> int:
    """+1 when the value's shape agrees with the column's declared type.

    This never blocks a match - it only breaks ties between multiple
    equally-plausible candidate columns for the same concept.
    """
    column_is_numeric = any(marker in column_type.upper() for marker in _NUMERIC_TYPE_MARKERS)
    value_is_numeric = _looks_numeric(value) or value.strip().lower() in _NUMBER_WORDS
    return 1 if value_is_numeric == column_is_numeric else 0


class ParameterResolver:
    """Resolves already-extracted concepts to physical columns.

    Curated concepts are authoritative and resolved first via a direct
    name lookup (no schema search needed). Remaining concepts are resolved
    dynamically by searching the candidate tables' own columns - restricted
    to the small table set already selected for the query's intent.
    """

    def __init__(self, truth: StudentTruthLayer, column_semantics: ColumnSemanticsMetadata | None = None):
        self.truth = truth
        self.column_semantics = column_semantics or ColumnSemanticsMetadata()
        self._curated: dict[str, dict[str, Any]] = truth.semantic.get("parameters", {})

    def resolve(
        self,
        concepts: list[ExtractedConcept],
        candidate_tables: list[TableMetadata],
        preferred_tables: Sequence[str] = (),
    ) -> ParameterResolutionOutcome:
        """`preferred_tables` are the tables the matched query intent says
        its filters belong to (catalog key `preferred_filter_tables`), e.g.
        core_exam_results for a results question: a concept that exists there
        resolves there instead of tying with (or losing to) the same-named
        column on another candidate table such as student_admissions."""
        resolved: list[ResolvedParameter] = []
        ambiguous: list[AmbiguousParameter] = []
        requires_resolution: list[ExtractedConcept] = []
        columns_by_ref = {
            (table.table, column.name): column
            for table in candidate_tables for column in table.columns
        }

        for concept in concepts:
            if concept.requires_resolution:
                requires_resolution.append(concept)
                continue

            curated = self._curated.get(concept.name)
            if curated:
                table, column = self._curated_target(curated, preferred_tables, columns_by_ref)
                column_meta = columns_by_ref.get((table, column))
                column_type = column_meta.type if column_meta else ""
                value, values, operator = _finalize_values(
                    concept, column_type,
                    self._allowed_values(table, column, column_meta),
                    self.column_semantics.known_values_for(table, column),
                )
                resolved.append(ResolvedParameter(
                    name=concept.name, value=value, table=table, column=column,
                    source="curated", score=100, operator=operator, values=values,
                ))
                continue

            candidates = self._search_candidates(concept.name, candidate_tables)
            if preferred_tables:
                preferred = [candidate for candidate in candidates if candidate.table in preferred_tables]
                candidates = preferred or candidates
            if not candidates:
                # No verified evidence anywhere in the candidate schema for
                # this concept - dropped safely rather than guessed.
                continue

            # Score against one representative value (the first list item,
            # if this is a multi-value concept) - scoring only breaks ties
            # between candidate columns, it never blocks resolution.
            sample_value = concept.values[0] if concept.values else concept.value
            scored = [(self._score(candidate, sample_value), candidate) for candidate in candidates]
            best_score = max(score for score, _ in scored)
            winners = [candidate for score, candidate in scored if score == best_score]
            if len(winners) == 1:
                winner = winners[0]
                value, values, operator = _finalize_values(
                    concept, winner.column_type, winner.enum_values, winner.known_values,
                )
                resolved.append(ResolvedParameter(
                    name=concept.name, value=value,
                    table=winner.table, column=winner.column,
                    source="dynamic", score=best_score, operator=operator, values=values,
                ))
            else:
                ambiguous.append(AmbiguousParameter(
                    concept=concept.name, value=concept.value, candidates=winners,
                ))

        return ParameterResolutionOutcome(
            resolved=resolved, ambiguous=ambiguous, requires_resolution=requires_resolution,
        )

    @staticmethod
    def _curated_target(
        curated: dict[str, Any], preferred_tables: Sequence[str], columns_by_ref: dict,
    ) -> tuple[str, str]:
        """A curated concept has one default table.column; `alternates`
        ({table: column}) lets it resolve to a different table when the
        matched intent prefers that table (e.g. `program` -> the results
        table for a results question). Only ever an explicit, curated remap -
        never inferred."""
        for alternate_table, alternate_column in (curated.get("alternates") or {}).items():
            if alternate_table in preferred_tables and (alternate_table, alternate_column) in columns_by_ref:
                return alternate_table, alternate_column
        return curated["table"], curated["column"]

    def _search_candidates(self, concept_name: str, candidate_tables: list[TableMetadata]) -> list[ColumnCandidate]:
        normalized_concept = concept_name.lower().replace("_", " ").strip()
        # base_concept = normalized_concept
        # for suffix in (" start", " end", " from", " to"):
        #     if base_concept.endswith(suffix):
        #         base_concept = base_concept[:-len(suffix)].strip()
        #         break

        found: list[ColumnCandidate] = []
        for table in candidate_tables:
            for column in table.columns:
                phrases = list(_column_phrases(column.name))
                phrases += [
                    (alias.lower(), "alias")
                    for alias in self.column_semantics.aliases_for(table.table, column.name)
                ]
                matching = [kind for phrase, kind in phrases if phrase in (normalized_concept,)]
                if matching:
                    found.append(ColumnCandidate(
                        table.table, column.name, column.type, matching[0],
                        enum_values=self._allowed_values(table.table, column.name, column),
                        known_values=self.column_semantics.known_values_for(table.table, column.name),
                    ))
        return found

    def _allowed_values(self, table: str, column: str, column_meta: Any) -> list[str] | None:
        """A true DB ENUM's values are captured by schema extraction; a
        VARCHAR column with a de facto fixed vocabulary that extraction
        can't discover automatically falls back to a curated list."""
        enum_values = getattr(column_meta, "enum_values", None) if column_meta else None
        return enum_values or self.column_semantics.allowed_values_for(table, column)

    @staticmethod
    def _score(candidate: ColumnCandidate, value: str) -> int:
        base = {"alias": 4, "exact": 3, "normalized": 2}[candidate.match_kind]
        return base + _type_score(value, candidate.column_type)
