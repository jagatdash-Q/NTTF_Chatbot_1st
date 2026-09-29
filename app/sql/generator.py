"""Provider-agnostic LLM request builder for constrained SQL generation."""

from __future__ import annotations

import json
from urllib.error import HTTPError, URLError
from urllib.request import Request, urlopen
from typing import Any, Callable

from app.config.settings import Settings
from app.intelligence.query_analyzer import QueryAnalysis
from app.intelligence.schema_retriever import SchemaSelection


def build_sql_generation_context(
    user_query: str, analysis: QueryAnalysis, selection: SchemaSelection,
    feedback: dict[str, Any] | None = None,
) -> dict[str, Any]:
    context = _build_base_context(user_query, analysis, selection)
    if feedback:
        # A retry: show the model exactly what the deterministic validator or
        # database rejected last time, so attempt 2 can actually fix it
        # instead of repeating the same mistake.
        context["previous_attempt_feedback"] = feedback
        context["instructions"] = [
            "Your previous attempt (see previous_attempt_feedback) was rejected. Fix exactly "
            "those problems and return a corrected statement.",
            *context["instructions"],
        ]
    return context


def _build_base_context(user_query: str, analysis: QueryAnalysis, selection: SchemaSelection) -> dict[str, Any]:
    return {
        "user_question": user_query,
        "interpreted_request": analysis.as_dict(),
        "minimal_schema": selection.as_llm_context(),
        "instructions": [
            "Generate exactly one read-only SELECT statement.",
            "Use only tables, columns, and verified relationships in minimal_schema.",
            "If interpreted_request.normalized_question is present it is the same question with "
            "domain synonyms mapped to their canonical words (trainee -> student, strength -> "
            "count); treat it as the authoritative wording.",
            "The user_question may list several distinct fields it wants back (e.g. "
            "\"name, program, stream, academic year, father name\"). Select every one of "
            "them that has a matching column in minimal_schema - re-check your SELECT "
            "list against that list before returning, and don't silently drop one just "
            "because several tables need to be joined to get them all.",
            "Use named parameters from minimal_schema.parameters for every value the user "
            "actually supplied; do not interpolate those. A value you determine yourself "
            "from a column's `allowed_values` (not something the user typed a value for) "
            "is different - see the allowed_values instruction below for how to write it.",
            "If a parameter's value in minimal_schema.parameters is a JSON array (not a "
            "string), it means the user asked for several values of that same filter "
            "(e.g. two academic years). Generate a single `column IN :param_name` clause "
            "using that exact parameter name once, WITHOUT parentheses around :param_name "
            "(the execution layer supplies them) - do NOT write `IN (:param_name)`, do NOT "
            "invent separate suffixed parameter names like param_name_1/param_name_2, and "
            "do NOT write the array's values out literally in the SQL.",
            "Qualify every selected and joined column with its table or alias.",
            "When a column in minimal_schema has `allowed_values` and the value you need "
            "for it is NOT already one of minimal_schema.parameters (the user never typed "
            "a value for it - you're inferring it, e.g. a payment_type implied by the "
            "question's wording but never given as its own explicit parameter), write it "
            "as a plain string literal directly in the SQL text (e.g. `payment_type = "
            "'temporary_enroll'`) - do NOT invent a new named bound parameter for it, only "
            "minimal_schema.parameters's own names may be used as `:param`. The literal "
            "MUST be exactly one of that column's `allowed_values` strings (when the column's "
            "description says several stored spellings mean the same thing, e.g. a Fail "
            "result stored as both 'Fail' and 'F', match all of them with IN) - never guess a "
            "spelling/wording, and never assume two different columns share the same value "
            "vocabulary even if the user's wording for both was identical (a status value "
            "and an unrelated payment_type value are not automatically the same stored "
            "string).",
            "minimal_schema.parameters entries are combined with AND by default. If the "
            "user_question itself says \"or\" between two of them (most often two alternate "
            "identifiers for the same person, e.g. \"enquiry number X or hall ticket number "
            "Y\"), combine those two specific conditions with OR instead - the intent is "
            "\"find the one record matching either identifier\", not \"match both at once\".",
            "If the question asks for records where something was NOT done / has NO "
            "matching row in a joined table (e.g. 'no payment made', 'without a "
            "certificate'), express that as a LEFT JOIN with the specific matching "
            "condition (e.g. payment_type = 'x') moved into the JOIN's ON clause, "
            "filtered by `related_table.<join_key> IS NULL` in the WHERE clause - "
            "never put that condition in WHERE by itself, or it silently excludes "
            "every row that has no match at all instead of finding the ones missing "
            "that specific match.",
            # Rules that only matter for one kind of question live in the
            # matched query_catalog entries' `sql_hints` (core_student.yaml),
            # not here - every question would otherwise pay for, and be
            # distracted by, instructions about tables it never touches.
            *selection.sql_hints,
            "Return JSON with sql, parameters, tables_used, and explanation.",
        ],
    }


class SQLGenerator:
    """An injected client keeps credentials and model selection outside this layer."""

    def __init__(self, client: Callable[[dict[str, Any]], dict[str, Any]]):
        self.client = client

    def generate_sql(
        self,
        user_query: str,
        analysis: QueryAnalysis,
        selection: SchemaSelection,
        logger: Callable[[dict[str, Any], Any], None] | None = None,
        feedback: dict[str, Any] | None = None,
    ) -> dict[str, Any]:
        context = build_sql_generation_context(user_query, analysis, selection, feedback=feedback)
        response = self.client(context)
        if logger is not None:
            logger(context, response)
        if isinstance(response, dict):
            response["parameters"] = self._normalize_parameters(response.get("parameters"))
        self._validate_response_shape(response)
        unknown_parameters = set(response["parameters"]) - set(selection.parameters)
        if unknown_parameters:
            raise ValueError(
                f"SQL generator returned unexpected parameters: {sorted(unknown_parameters)}"
            )
        # The deterministic extractor is authoritative for values. Never accept
        # a model-supplied value that could silently alter a user filter.
        response["parameters"] = selection.parameters
        return response

    @staticmethod
    def _normalize_parameters(parameters: Any) -> Any:
        """Sarvam has been observed (live testing) to sometimes return
        `parameters` as `[{"name": ..., "value": ...}, ...]` instead of the
        requested flat `{name: value}` object. That shape is unambiguous,
        so it's normalized rather than rejected - values are overwritten by
        the deterministic extractor's own values regardless (see below),
        this only affects whether the *names* survive to the unknown-
        parameter check.
        """
        if (
            isinstance(parameters, list)
            and all(isinstance(item, dict) and "name" in item for item in parameters)
        ):
            return {item["name"]: item.get("value") for item in parameters}
        return parameters

    @staticmethod
    def _validate_response_shape(response: Any) -> None:
        """Reject a malformed-but-plausible response cleanly instead of
        crashing downstream (e.g. `parameters` returned as a list of
        objects instead of the required flat {name: value} object)."""
        if not isinstance(response, dict):
            raise ValueError("SQL generator response must be a JSON object.")
        required = {"sql", "parameters", "tables_used", "explanation"}
        missing = required - set(response)
        if missing:
            raise ValueError(f"SQL generator response is missing: {sorted(missing)}")
        if not isinstance(response["sql"], str) or not response["sql"].strip():
            raise ValueError("SQL generator response 'sql' must be a non-empty string.")
        if not isinstance(response["parameters"], dict):
            raise ValueError("SQL generator response 'parameters' must be a JSON object, not a list.")
        if not isinstance(response["tables_used"], list):
            raise ValueError("SQL generator response 'tables_used' must be a JSON array.")


class SarvamSQLClient:
    """Small dependency-free adapter for Sarvam's chat-completions API."""

    def __init__(self, settings: Settings | None = None):
        self.settings = settings or Settings()
        if self.settings.SARVAM_API_KEY is None:
            raise ValueError("SARVAM_API_KEY is required when LLM_PROVIDER is sarvam.")

    def __call__(self, context: dict[str, Any]) -> dict[str, Any]:
        payload = {
            "model": self.settings.SARVAM_MODEL,
            "temperature": 0,
            "max_tokens": 700,
            "reasoning_effort": None,
            "messages": [
                {
                    "role": "system",
                    "content": (
                        "You generate safe MySQL SELECT queries. Return a single JSON object "
                        "only, with exactly sql, parameters, tables_used, and explanation. "
                        "Never use a table, column, or join outside the supplied minimal schema."
                    ),
                },
                {"role": "user", "content": json.dumps(context, ensure_ascii=False)},
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
            raise RuntimeError("Sarvam did not return the required JSON SQL response.") from error
        if not isinstance(result, dict):
            raise RuntimeError("Sarvam SQL response must be a JSON object.")
        return result


class GeminiSQLClient:
    """Gemini GenerateContent adapter using JSON structured output."""

    _RESPONSE_SCHEMA = {
        "type": "object",
        "properties": {
            "sql": {"type": "string"},
            "parameters": {"type": "object"},
            "tables_used": {"type": "array", "items": {"type": "string"}},
            "explanation": {"type": "string"},
        },
        "required": ["sql", "parameters", "tables_used", "explanation"],
    }

    def __init__(self, settings: Settings | None = None):
        self.settings = settings or Settings()
        if self.settings.GEMINI_API_KEY is None:
            raise ValueError("GEMINI_API_KEY is required when LLM_PROVIDER is gemini.")

    def __call__(self, context: dict[str, Any]) -> dict[str, Any]:
        instructions = (
            "You generate safe MySQL SELECT queries. Use only the supplied minimal schema, "
            "selected columns, and verified joins. Never introduce a new table or column."
        )
        payload = {
            "systemInstruction": {"parts": [{"text": instructions}]},
            "contents": [{"role": "user", "parts": [{"text": json.dumps(context)}]}],
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
        return self._parse_json(content)

    @staticmethod
    def _parse_json(content: str) -> dict[str, Any]:
        return SarvamSQLClient._parse_json(content)


def build_configured_sql_generator(settings: Settings | None = None) -> SQLGenerator:
    """Create the configured provider without exposing provider details to callers."""
    configured = settings or Settings()
    if configured.LLM_PROVIDER.lower() == "sarvam":
        return SQLGenerator(SarvamSQLClient(configured))
    if configured.LLM_PROVIDER.lower() == "gemini":
        return SQLGenerator(GeminiSQLClient(configured))
    raise ValueError("LLM_PROVIDER must be either 'sarvam' or 'gemini'.")
