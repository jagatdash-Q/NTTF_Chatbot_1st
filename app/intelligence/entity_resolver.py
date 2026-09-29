"""Parameter/concept extraction: turning language into (name, value) pairs.

This module owns *extraction only* - "what concept and value did the user
mention?" It must never know a physical table or column; that is decided
afterwards, deterministically, by app.intelligence.parameter_resolution
against the generated schema.

Two interchangeable extractors implement the same contract:

* RegexConceptExtractor (default) - deterministic, offline, zero LLM calls.
  Curated Truth Layer patterns handle business-specific/irregular phrasing
  (identifiers, "Fitter program"); a schema-agnostic generic grammar (shared
  connector handling, per app.intelligence.parameter_resolution's design)
  handles anything of the form "<concept> <connector> <value>" for ANY
  concept name, with no predefined vocabulary required; a small hint
  vocabulary additionally supports bare juxtaposition ("batch 2024") where
  there is no connector to disambiguate concept from filler words.

* LLMConceptExtractor (optional) - for language too complex for regex
  (temporal expressions, negatives, comparatives, ranges). It receives only
  a compact list of known domain concept words, never the database schema.

Both return the same ``list[ExtractedConcept]``, so QueryAnalyzer can swap
extractors without changing anything downstream.
"""

from __future__ import annotations

import json
import re
from dataclasses import dataclass
from typing import Any, Protocol
from urllib.error import HTTPError, URLError
from urllib.request import Request, urlopen

from app.config.settings import Settings
# from app.intelligence.temporal_resolver import resolve_temporal_expression
from app.metadata.models import StudentTruthLayer

# Connector words/symbols a user may place between a concept and its value.
# Defined once and shared by every extraction path, so fixing a
# connector-handling defect (e.g. "status: active") fixes it everywhere
# instead of requiring a per-parameter patch.
CONNECTORS = ("is", "as", "=", ":", "#")
_CONNECTOR_GROUP = "(?:" + "|".join(re.escape(connector) for connector in CONNECTORS) + ")"

# A value may span a couple of words (e.g. core_year stores "2026 JUN", not
# just "2026"). A word after the first is only folded into the value if it
# is short - this targets date/code-style suffixes (month abbreviations,
# short codes) without swallowing ordinary sentence prose ("...batch 2024
# enrolled in..." must still stop at "2024": "enrolled" is 8 letters). It
# must also not be a known stop word - a generic function word, connector,
# or (per-call) a word that is itself part of another recognized concept's
# name/alias - so a value never swallows the next filter's name even when
# nothing but whitespace separates them.
_MAX_EXTRA_VALUE_TOKENS = 2
_MAX_EXTRA_VALUE_TOKEN_LENGTH = 5

# A concept may be given several values for the same filter ("academic year
# 2026 JUN and 2025 JUN" -> an IN-list, not two separate concepts). Each
# additional list item after the first is guarded by the same stop-word
# check as continuation words, but ALSO on its own first token - unlike the
# primary value, a list item has no connector/concept-name to anchor it, so
# without this "...batch 2024 and status active" would misread "status" as
# a second batch value.
_LIST_SEPARATOR = r"(?:\s*,\s*(?:and\s+)?|\s+and\s+)"
_MAX_VALUE_LIST_ITEMS = 5


def _lookahead(stop_words: frozenset[str]) -> str:
    if not stop_words:
        return ""
    stop_alternation = "|".join(re.escape(word) for word in sorted(stop_words, key=len, reverse=True))
    return f"(?!(?:{stop_alternation})\\b)"


def _value_unit(stop_words: frozenset[str], guard_first_token: bool, first_token_stop_words: frozenset[str] | None = None) -> str:
    continuation_lookahead = _lookahead(stop_words)
    short_token = rf"[A-Za-z0-9][\w&-]{{0,{_MAX_EXTRA_VALUE_TOKEN_LENGTH - 1}}}\b"
    continuation = rf"(?:\s+{continuation_lookahead}{short_token})"
    first_lookahead = _lookahead(first_token_stop_words if first_token_stop_words is not None else stop_words) if guard_first_token else ""
    first_token = rf"{first_lookahead}[A-Za-z0-9][\w&-]*"
    return rf"{first_token}{continuation}{{0,{_MAX_EXTRA_VALUE_TOKENS}}}"


def _value_group(stop_words: frozenset[str], guard_primary_first_token: bool = True) -> str:
    # The primary value's first token is guarded only against the small,
    # fixed set of generic function words (_STOPWORDS) - not the full
    # per-query widened set, which also contains fragments of real schema
    # column phrases (e.g. "is_active" contributes the word "active"). Using
    # the widened set here would block legitimate single-word values that
    # happen to coincide with such a fragment (e.g. status "active").
    # Continuation/list-item tokens keep the full widened guard: their job
    # is specifically to stop swallowing the *next* filter's name, which is
    # exactly what the widened set models.
    #
    # This guard is skipped entirely (guard_primary_first_token=False) when
    # a real connector (=/:/is/as/#) was already matched immediately before
    # the value - that's an explicit, unambiguous "concept <connector>
    # value" signal from the user, so even a value that happens to collide
    # with a stop word (e.g. a single-letter grade "A" colliding with the
    # article "a") must still be accepted.
    primary = _value_unit(
        stop_words, guard_first_token=guard_primary_first_token, first_token_stop_words=_STOPWORDS,
    )
    list_item = _value_unit(stop_words, guard_first_token=True)
    return rf"(?P<value>{primary}(?:{_LIST_SEPARATOR}{list_item}){{0,{_MAX_VALUE_LIST_ITEMS}}})"


def _split_value_list(raw_value: str) -> list[str]:
    parts = [part.strip() for part in re.split(_LIST_SEPARATOR, raw_value, flags=re.IGNORECASE)]
    return [part for part in parts if part]


# Function words that must never be treated as a concept name by the
# schema-agnostic generic scan, and that a value must never extend into.
# Without this guard "who is active" would extract concept="who" purely
# because a connector follows it.
_STOPWORDS = frozenset({
    "who", "that", "which", "this", "these", "those", "it", "what",
    "is", "was", "are", "were", "the", "a", "an", "of", "to", "for",
    "and", "or", "in", "on", "at", "with", "from", "by", "students",
    "along", "there", "but", "no", "not", "has", "have", "done",
    # Words that describe the *shape of the answer wanted* ("subject wise
    # marks", "student details"), never a filter value - without these a
    # column-derived concept word right before them ("subject", "employee")
    # swallows them as if they were its value.
    "wise", "detail", "details", "data", "info", "information", "list",
    "report", "marks", "mark",
    # Request verbs / filler that follow a value in ordinary prose
    # ("... E25-000006 and show me ...").
    "show", "give", "tell", "display", "also", "me", "please",
})


def _generic_connector_pattern(stop_words: frozenset[str]) -> re.Pattern[str]:
    return re.compile(
        rf"\b(?P<concept>[a-z][a-z_]{{2,}})\b\s*{_CONNECTOR_GROUP}\s*"
        f"{_value_group(stop_words, guard_primary_first_token=False)}",
        re.IGNORECASE,
    )

# Relative temporal expressions must never be silently converted into a
# guessed value. They are extracted with requires_resolution=True so a
# downstream business rule (or clarification) - never a guess - decides
# the concrete value. Generic across any unit, not a "previous academic
# year"-only special case.
_TEMPORAL_RELATIVE = ("previous", "last", "current", "this", "next")
_TEMPORAL_UNIT = ("academic year", "academic session", "semester", "year", "month")
_TEMPORAL_PATTERN = re.compile(
    rf"\b(?P<relative>{'|'.join(_TEMPORAL_RELATIVE)})\s+(?P<unit>{'|'.join(_TEMPORAL_UNIT)})\b",
    re.IGNORECASE,
)

# Live-repro'd gap: "...in 2026 jun and live in hostel" names no "academic
# year" concept word at all, so the existing dynamic alias-matching (which
# needs that phrase present) never anchors and the value is silently
# dropped. student_admissions.core_year / core_exam_results.academic_year
# are always stored as exactly this shape (year + 3-letter month
# abbreviation, e.g. "2026 JUN" - verified against every value in
# column_values.json), which nothing else in this domain looks like, so it
# is safe to recognize on its own without a concept-word anchor - the same
# principle as `_TEMPORAL_PATTERN` above, generalized to any year/month
# rather than only today's known values.
_ACADEMIC_YEAR_SHAPE = re.compile(
    r"\b(?P<value>20\d{2}\s*(?:JAN|FEB|MAR|APR|MAY|JUN|JUL|AUG|SEP|OCT|NOV|DEC))\b", re.IGNORECASE,
)


def build_concept_pattern(phrases: list[str], stop_words: frozenset[str] = _STOPWORDS) -> re.Pattern[str]:
    """The one shared grammar for every concept: ``<phrase> [connector] <value>``.

    Longer phrases are tried first so e.g. "hall ticket number" is matched
    in full rather than stopping at "hall ticket".
    """
    ordered = sorted(dict.fromkeys(phrases), key=len, reverse=True)
    phrase_group = "|".join(re.escape(phrase) for phrase in ordered)
    return re.compile(
        rf"\b(?:{phrase_group})\b\s*{_CONNECTOR_GROUP}?\s*{_value_group(stop_words)}",
        re.IGNORECASE,
    )


def _spans_overlap(span: tuple[int, int], others: list[tuple[int, int]]) -> bool:
    start, end = span
    return any(start < other_end and other_start < end for other_start, other_end in others)


@dataclass(frozen=True)
class ExtractedConcept:
    """Schema-agnostic extraction output - no table/column, by design.

    `values`/`operator="in"` represent the same concept given a list of
    values ("academic year 2026 JUN and 2025 JUN"); `value` still holds a
    display-friendly joined string so single-value call sites keep working
    unchanged.
    """

    name: str
    value: str
    operator: str = "="
    values: list[str] | None = None
    normalized_value: str | None = None
    requires_resolution: bool = False
    confidence: float | None = None
    source: str = "regex"


def _has_digit(text: str) -> bool:
    return any(character.isdigit() for character in text)


def _concept_from_match(name: str, match: re.Match[str], source: str = "regex") -> ExtractedConcept:
    parts = _split_value_list(match.group("value").strip())
    # Every item of a genuine value list has the same shape as the first: two
    # academic years ("2026 JUN and 2025 JUN") both carry digits, two names
    # ("Fitter and Turner") neither do. "E25-000006 and show me" is one
    # identifier followed by ordinary prose, not a two-item list - keep only
    # the leading items that share the first item's shape.
    while len(parts) > 1 and _has_digit(parts[-1]) != _has_digit(parts[0]):
        parts.pop()
    if len(parts) > 1:
        return ExtractedConcept(name=name, value=" and ".join(parts), values=parts, operator="in", source=source)
    return ExtractedConcept(name=name, value=parts[0] if parts else match.group("value").strip(), source=source)


class ConceptExtractor(Protocol):
    def extract(self, query: str, hint_vocabulary: list[str]) -> list[ExtractedConcept]: ...


class RegexConceptExtractor:
    """Deterministic, offline extraction. The default, zero-LLM-call path."""

    def __init__(self, truth: StudentTruthLayer):
        self._curated: dict[str, dict[str, Any]] = truth.semantic.get("parameters", {})

    def extract(self, query: str, hint_vocabulary: list[str]) -> list[ExtractedConcept]:
        stop_words = self._stop_words(hint_vocabulary)
        claimed_spans: list[tuple[int, int]] = []
        matches: list[tuple[int, ExtractedConcept]] = []

        matches += self._curated_matches(query, claimed_spans, stop_words)
        matches += self._temporal_matches(query, claimed_spans)
        matches += self._generic_connector_matches(query, claimed_spans, stop_words)
        matches += self._hint_vocabulary_matches(query, claimed_spans, hint_vocabulary, stop_words)
        # Last: only fires on whatever text the phrase-anchored steps above
        # left unclaimed, so "academic year 2026 JUN" (already handled by
        # hint-vocabulary matching above) is never double-extracted.
        matches += self._academic_year_shape_matches(query, claimed_spans)

        matches.sort(key=lambda pair: pair[0])
        return [concept for _, concept in matches]

    def _stop_words(self, hint_vocabulary: list[str]) -> frozenset[str]:
        """Words a value must never extend into: generic stop words, plus
        every word that is itself part of a recognized concept name/alias
        for THIS query - so one filter's value can't swallow the next
        filter's name even without an explicit connector between them.

        Only short (<=2 word) hint-vocabulary phrases contribute their words
        - those are realistic filter-name phrases ("academic year",
        "batch"). A raw schema column can legitimately be a long compound
        name (e.g. active_core_student_fee_plan_details_log_id); splitting
        that into individual dictionary words would poison ordinary words
        like "active" as stop words query-wide, blocking them from ever
        being extracted as a value.
        """
        words = set(_STOPWORDS)
        for phrase in hint_vocabulary:
            tokens = phrase.lower().split()
            if len(tokens) <= 2:
                words.update(tokens)
        for name, config in self._curated.items():
            words.add(name.lower())
            for alias in config.get("aliases") or [config.get("concept", name)]:
                words.update(alias.lower().split())
        return frozenset(words)

    def _curated_matches(
        self, query: str, claimed_spans: list[tuple[int, int]], stop_words: frozenset[str]
    ) -> list[tuple[int, ExtractedConcept]]:
        found: list[tuple[int, ExtractedConcept]] = []
        for name, config in self._curated.items():
            match = self._first_curated_match(config, query, stop_words)
            if not match or _spans_overlap(match.span(), claimed_spans):
                continue
            found.append((match.start(), _concept_from_match(name, match)))
            claimed_spans.append(match.span())
        return found

    @staticmethod
    def _first_curated_match(
        config: dict[str, Any], query: str, stop_words: frozenset[str]
    ) -> re.Match[str] | None:
        """Try each curated pattern in order; the first match wins.

        A concept may have more than one natural word order (e.g. "Fitter
        program" vs "program Fitter"). Named groups can't be reused across
        `re` alternation branches, so alternative phrasings are separate
        compiled patterns rather than one combined regex.
        """
        hand_written = "patterns" in config or "pattern" in config
        for pattern in RegexConceptExtractor._curated_patterns(config, stop_words):
            for match in pattern.finditer(query):
                # A hand-written "<value> program" style pattern has no
                # stop-word guard of its own, so "for center Peenya program
                # Diploma" would read "center Peenya" as the program. A value
                # that *starts with* another concept's name (or any other
                # stop word) isn't a value - skip to the next match/pattern.
                if hand_written and RegexConceptExtractor._starts_with_stop_word(match, stop_words):
                    continue
                return match
        return None

    @staticmethod
    def _starts_with_stop_word(match: re.Match[str], stop_words: frozenset[str]) -> bool:
        value = match.groupdict().get("value")
        first_word = value.split()[0].lower() if value and value.split() else ""
        return first_word in stop_words

    @staticmethod
    def _curated_patterns(config: dict[str, Any], stop_words: frozenset[str]) -> list[re.Pattern[str]]:
        if "patterns" in config:
            patterns = [re.compile(pattern, re.IGNORECASE) for pattern in config["patterns"]]
        elif "pattern" in config:
            patterns = [re.compile(config["pattern"], re.IGNORECASE)]
        else:
            aliases = config.get("aliases") or [config["concept"]]
            patterns = [build_concept_pattern(aliases, stop_words)]
        # Optional, additive: a closed-enum concept's own stored word can BE
        # the value with no concept word anywhere nearby ("total ACTIVE
        # student strength" names no "status") - unlike `pattern(s)`, this
        # supplements whichever primary pattern(s) above were chosen (so
        # e.g. status keeps its full "status active and withdrawn"
        # multi-value connector grammar from `aliases`) rather than
        # replacing them. Tried last, after the more explicit forms.
        if "bare_value_pattern" in config:
            patterns.append(re.compile(config["bare_value_pattern"], re.IGNORECASE))
        return patterns

    @staticmethod
    def _temporal_matches(
        query: str, claimed_spans: list[tuple[int, int]]
    ) -> list[tuple[int, ExtractedConcept]]:
        found: list[tuple[int, ExtractedConcept]] = []
        # First check deterministic temporal resolver for dates, ranges, upcoming, next
        # res = resolve_temporal_expression(query)
        # if res:
        #     m = re.search(re.escape(res.raw_expression), query, re.IGNORECASE)
        #     span = m.span() if m else (0, len(query))
        #     if not _spans_overlap(span, claimed_spans):
        #         if res.kind == "exact_date" and res.exact_date:
        #             found.append((span[0], ExtractedConcept(
        #                 name="event_date",
        #                 value=res.exact_date,
        #                 source="temporal",
        #             )))
        #             claimed_spans.append(span)
        #         elif res.kind == "date_range" and res.start_date and res.end_date:
        #             found.append((span[0], ExtractedConcept(
        #                 name="event_date_start",
        #                 value=res.start_date,
        #                 source="temporal",
        #             )))
        #             found.append((span[0] + 1, ExtractedConcept(
        #                 name="event_date_end",
        #                 value=res.end_date,
        #                 source="temporal",
        #             )))
        #             claimed_spans.append(span)
        #         elif res.kind in ("upcoming", "next_one") and res.start_date:
        #             found.append((span[0], ExtractedConcept(
        #                 name="event_date_start",
        #                 value=res.start_date,
        #                 source="temporal",
        #             )))
        #             claimed_spans.append(span)

        for match in _TEMPORAL_PATTERN.finditer(query):
            if _spans_overlap(match.span(), claimed_spans):
                continue
            unit = match.group("unit").lower().replace(" ", "_")
            phrase = f"{match.group('relative')} {match.group('unit')}".lower()
            found.append((match.start(), ExtractedConcept(
                name=unit,
                value=phrase,
                normalized_value=None,
                requires_resolution=True,
                source="regex",
            )))
            claimed_spans.append(match.span())
        return found

    @staticmethod
    def _academic_year_shape_matches(
        query: str, claimed_spans: list[tuple[int, int]]
    ) -> list[tuple[int, ExtractedConcept]]:
        found: list[tuple[int, ExtractedConcept]] = []
        for match in _ACADEMIC_YEAR_SHAPE.finditer(query):
            if _spans_overlap(match.span(), claimed_spans):
                continue
            found.append((match.start(), ExtractedConcept(
                # Matches the existing convention (see _hint_vocabulary_matches
                # below): spaces become underscores so the name is always a
                # valid SQL bound-parameter identifier (":academic year" is
                # not legal syntax; ":academic_year" is).
                name="academic_year", value=" ".join(match.group("value").split()).upper(), source="regex",
            )))
            claimed_spans.append(match.span())
        return found

    @staticmethod
    def _generic_connector_matches(
        query: str, claimed_spans: list[tuple[int, int]], stop_words: frozenset[str]
    ) -> list[tuple[int, ExtractedConcept]]:
        found: list[tuple[int, ExtractedConcept]] = []
        for match in _generic_connector_pattern(stop_words).finditer(query):
            concept = match.group("concept").lower()
            if concept in _STOPWORDS or _spans_overlap(match.span(), claimed_spans):
                continue
            found.append((match.start(), _concept_from_match(concept, match)))
            claimed_spans.append(match.span())
        return found

    @staticmethod
    def _hint_vocabulary_matches(
        query: str, claimed_spans: list[tuple[int, int]], hint_vocabulary: list[str], stop_words: frozenset[str]
    ) -> list[tuple[int, ExtractedConcept]]:
        found: list[tuple[int, ExtractedConcept]] = []
        # Spans of every occurrence of a LONGER phrase, whether or not a value
        # follows it. Without this "grade and grade point" lets the shorter
        # phrase "grade" match inside "grade point" and take "point" as its
        # value, because the longer phrase found no value and so claimed
        # nothing.
        longer_phrase_spans: list[tuple[int, int]] = []
        for phrase in sorted(set(hint_vocabulary), key=len, reverse=True):
            occurrences = [
                occurrence.span()
                for occurrence in re.finditer(rf"\b{re.escape(phrase)}\b", query, re.IGNORECASE)
                if not any(start <= occurrence.start() and occurrence.end() <= end for start, end in longer_phrase_spans)
            ]
            anchors = {start for start, _ in occurrences}
            longer_phrase_spans.extend(occurrences)
            for match in build_concept_pattern([phrase], stop_words).finditer(query):
                if match.start() not in anchors or _spans_overlap(match.span(), claimed_spans):
                    continue
                found.append((match.start(), _concept_from_match(phrase.replace(" ", "_"), match)))
                claimed_spans.append(match.span())
                break
        return found


class LLMConceptExtractor:
    """Optional CALL 1: natural-language parameter/concept extraction.

    Receives only a compact list of known domain concept words (never the
    generated schema) and returns the same ExtractedConcept contract as the
    deterministic extractor, so physical resolution is unaffected by which
    extractor produced the concepts.
    """

    _RESPONSE_INSTRUCTIONS = (
        "You extract structured filter parameters from a natural-language "
        "database question. Return a single JSON object only: "
        '{"intent_hint": string|null, "entities": [string], "parameters": '
        '[{"name": string, "value": string, "operator": "="|"!="|">"|"<"|">="|"<=", '
        '"normalized_value": string|null, "requires_resolution": boolean, '
        '"confidence": number}]}. '
        "Use requires_resolution=true and normalized_value=null for relative/vague "
        "expressions (e.g. \"previous academic year\") that cannot be turned into a "
        "concrete value from the question text alone. Never invent a table or column "
        "name in `name` - use the concept word the user actually said. Never invent a "
        "value that was not stated or clearly implied."
    )

    def __init__(self, client: Any, settings: Settings | None = None):
        self.client = client
        self.settings = settings or Settings()

    def extract(self, query: str, hint_vocabulary: list[str]) -> list[ExtractedConcept]:
        context = {
            "user_question": query,
            "known_domain_concepts": sorted(set(hint_vocabulary)),
            "instructions": self._RESPONSE_INSTRUCTIONS,
        }
        response = self.client(context)
        concepts = []
        for item in response.get("parameters", []):
            concepts.append(ExtractedConcept(
                name=str(item["name"]),
                value=str(item["value"]),
                operator=item.get("operator", "="),
                normalized_value=item.get("normalized_value"),
                requires_resolution=bool(item.get("requires_resolution", False)),
                confidence=item.get("confidence"),
                source="llm",
            ))
        return concepts


class SarvamConceptClient:
    """Dependency-free adapter for Sarvam's chat-completions API.

    Reused for every small, bounded-context LLM call in this module (concept
    extraction, intent classification) - it just forwards whatever context
    dict it's given (minus `instructions`, which becomes the system prompt)
    as the user message, so it never needs to know the shape of a specific
    task's payload.
    """

    def __init__(self, settings: Settings | None = None):
        self.settings = settings or Settings()
        if self.settings.SARVAM_API_KEY is None:
            raise ValueError("SARVAM_API_KEY is required when LLM_PROVIDER is sarvam.")

    def __call__(self, context: dict[str, Any]) -> dict[str, Any]:
        user_payload = {key: value for key, value in context.items() if key != "instructions"}
        payload = {
            "model": self.settings.SARVAM_MODEL,
            "temperature": 0,
            "max_tokens": 500,
            "reasoning_effort": None,
            "messages": [
                {"role": "system", "content": context["instructions"]},
                {"role": "user", "content": json.dumps(user_payload, ensure_ascii=False, default=str)},
            ],
        }
        request = Request(
            self.settings.SARVAM_API_URL,
            data=json.dumps(payload).encode("utf-8"),
            headers={
                "Content-Type": "application/json",
                "api-subscription-key": self.settings.SARVAM_API_KEY.get_secret_value(),
            },
            method="POST",
        )
        try:
            with urlopen(request, timeout=45) as response:
                body = json.loads(response.read().decode("utf-8"))
        except HTTPError as error:
            raise RuntimeError(f"Sarvam request failed with HTTP {error.code}.") from error
        except URLError as error:
            raise RuntimeError("Sarvam request could not be reached.") from error
        try:
            content = body["choices"][0]["message"]["content"]
        except (KeyError, IndexError, TypeError) as error:
            raise RuntimeError("Sarvam returned an unexpected completion payload.") from error
        return self._parse_json(content)

    @staticmethod
    def _parse_json(content: str) -> dict[str, Any]:
        cleaned = content.strip()
        if cleaned.startswith("```"):
            cleaned = cleaned.split("\n", 1)[1].rsplit("```", 1)[0].strip()
        try:
            result = json.loads(cleaned)
        except json.JSONDecodeError as error:
            raise RuntimeError("Sarvam did not return the required JSON parameter response.") from error
        if not isinstance(result, dict):
            raise RuntimeError("Sarvam parameter-extraction response must be a JSON object.")
        return result


class GeminiConceptClient:
    """Gemini GenerateContent adapter using JSON structured output (CALL 1)."""

    _RESPONSE_SCHEMA = {
        "type": "object",
        "properties": {
            "intent_hint": {"type": "string", "nullable": True},
            "entities": {"type": "array", "items": {"type": "string"}},
            "parameters": {
                "type": "array",
                "items": {
                    "type": "object",
                    "properties": {
                        "name": {"type": "string"},
                        "value": {"type": "string"},
                        "operator": {"type": "string"},
                        "normalized_value": {"type": "string", "nullable": True},
                        "requires_resolution": {"type": "boolean"},
                        "confidence": {"type": "number"},
                    },
                    "required": ["name", "value"],
                },
            },
        },
        "required": ["parameters"],
    }

    def __init__(self, settings: Settings | None = None):
        self.settings = settings or Settings()
        if self.settings.GEMINI_API_KEY is None:
            raise ValueError("GEMINI_API_KEY is required when LLM_PROVIDER is gemini.")

    def __call__(self, context: dict[str, Any]) -> dict[str, Any]:
        payload = {
            "systemInstruction": {"parts": [{"text": context["instructions"]}]},
            "contents": [{"role": "user", "parts": [{"text": json.dumps({
                "user_question": context["user_question"],
                "known_domain_concepts": context["known_domain_concepts"],
            })}]}],
            "generationConfig": {
                "temperature": 0,
                "responseMimeType": "application/json",
                "responseJsonSchema": self._RESPONSE_SCHEMA,
            },
        }
        request = Request(
            f"{self.settings.GEMINI_API_URL.rstrip('/')}/{self.settings.GEMINI_MODEL}:generateContent",
            data=json.dumps(payload).encode("utf-8"),
            headers={
                "Content-Type": "application/json",
                "x-goog-api-key": self.settings.GEMINI_API_KEY.get_secret_value(),
            },
            method="POST",
        )
        try:
            with urlopen(request, timeout=45) as response:
                body = json.loads(response.read().decode("utf-8"))
            content = body["candidates"][0]["content"]["parts"][0]["text"]
        except HTTPError as error:
            raise RuntimeError(f"Gemini request failed with HTTP {error.code}.") from error
        except URLError as error:
            raise RuntimeError("Gemini request could not be reached.") from error
        except (KeyError, IndexError, TypeError) as error:
            raise RuntimeError("Gemini returned an unexpected completion payload.") from error
        return SarvamConceptClient._parse_json(content)


def build_configured_concept_extractor(settings: Settings | None = None) -> LLMConceptExtractor:
    """Create the LLM-based extractor for the configured provider.

    Not used on the default path (see RegexConceptExtractor) - available
    for callers that explicitly want CALL 1 to be an LLM.
    """
    configured = settings or Settings()
    if configured.LLM_PROVIDER.lower() == "sarvam":
        return LLMConceptExtractor(SarvamConceptClient(configured), configured)
    if configured.LLM_PROVIDER.lower() == "gemini":
        return LLMConceptExtractor(GeminiConceptClient(configured), configured)
    raise ValueError("LLM_PROVIDER must be either 'sarvam' or 'gemini'.")


class IntentClassifier(Protocol):
    def classify(self, query: str, candidate_intents: dict[str, list[str]]) -> str | None: ...


class LLMIntentClassifier:
    """Fallback intent classification - deterministic term-matching
    (app.intelligence.query_analyzer.QueryAnalyzer._intent) is tried first
    and is free/instant; this only runs when that finds zero or more than
    one candidate, e.g. "student detail" doesn't literally contain any of
    student_listing's configured terms even though it's obviously that
    intent. Scales to new modules/intents without hand-growing a term
    list - it receives only the small catalog of known intent names and
    their existing terms (never the schema), and MUST return one of those
    exact names or null; the caller validates this and never trusts an
    invented intent.
    """

    _INSTRUCTIONS = (
        "Classify which ONE of the given intents best matches the user's question. "
        'Return a single JSON object: {"intent": string|null}. '
        "`intent` must be EXACTLY one of the keys in candidate_intents (copy it "
        "verbatim), or null if none plausibly fit. Each intent's value is a list of "
        "example terms/phrases associated with it, for guidance only - the user's "
        "actual wording will often differ. Never invent a new intent name."
    )

    def __init__(self, client: Any, settings: Settings | None = None):
        self.client = client
        self.settings = settings or Settings()

    def classify(self, query: str, candidate_intents: dict[str, list[str]]) -> str | None:
        context = {
            "user_question": query,
            "candidate_intents": candidate_intents,
            "instructions": self._INSTRUCTIONS,
        }
        response = self.client(context)
        intent = response.get("intent")
        return intent if intent in candidate_intents else None


class GeminiIntentClient:
    """Gemini GenerateContent adapter for intent classification."""

    _RESPONSE_SCHEMA = {
        "type": "object",
        "properties": {"intent": {"type": "string", "nullable": True}},
        "required": ["intent"],
    }

    def __init__(self, settings: Settings | None = None):
        self.settings = settings or Settings()
        if self.settings.GEMINI_API_KEY is None:
            raise ValueError("GEMINI_API_KEY is required when LLM_PROVIDER is gemini.")

    def __call__(self, context: dict[str, Any]) -> dict[str, Any]:
        payload = {
            "systemInstruction": {"parts": [{"text": context["instructions"]}]},
            "contents": [{"role": "user", "parts": [{"text": json.dumps({
                "user_question": context["user_question"],
                "candidate_intents": context["candidate_intents"],
            })}]}],
            "generationConfig": {
                "temperature": 0,
                "responseMimeType": "application/json",
                "responseJsonSchema": self._RESPONSE_SCHEMA,
            },
        }
        request = Request(
            f"{self.settings.GEMINI_API_URL.rstrip('/')}/{self.settings.GEMINI_MODEL}:generateContent",
            data=json.dumps(payload).encode("utf-8"),
            headers={
                "Content-Type": "application/json",
                "x-goog-api-key": self.settings.GEMINI_API_KEY.get_secret_value(),
            },
            method="POST",
        )
        try:
            with urlopen(request, timeout=45) as response:
                body = json.loads(response.read().decode("utf-8"))
            content = body["candidates"][0]["content"]["parts"][0]["text"]
        except HTTPError as error:
            raise RuntimeError(f"Gemini request failed with HTTP {error.code}.") from error
        except URLError as error:
            raise RuntimeError("Gemini request could not be reached.") from error
        except (KeyError, IndexError, TypeError) as error:
            raise RuntimeError("Gemini returned an unexpected completion payload.") from error
        return SarvamConceptClient._parse_json(content)


def build_configured_intent_classifier(settings: Settings | None = None) -> LLMIntentClassifier:
    configured = settings or Settings()
    if configured.LLM_PROVIDER.lower() == "sarvam":
        return LLMIntentClassifier(SarvamConceptClient(configured), configured)
    if configured.LLM_PROVIDER.lower() == "gemini":
        return LLMIntentClassifier(GeminiIntentClient(configured), configured)
    raise ValueError("LLM_PROVIDER must be either 'sarvam' or 'gemini'.")
