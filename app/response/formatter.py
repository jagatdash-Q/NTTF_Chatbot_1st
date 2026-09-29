"""Turns a raw DB result back into a natural-language answer (CALL 2 of 2).

Mirrors app.sql.generator's shape deliberately: an injected client keeps
credentials/model selection out of this layer, and a `logger` hook lets a
caller capture the exact prompt and raw response for tracing without this
module knowing anything about how/where logs are stored.
"""

from __future__ import annotations

import json
from urllib.error import HTTPError, URLError
from urllib.request import Request, urlopen
from typing import Any, Callable

from app.config.settings import Settings
from app.database.executor import QueryExecutionResult

# Only a bounded sample of rows is ever sent to the LLM - row_count and
# truncated are passed alongside so both the model and the faithfulness
# check downstream know the true size, not just what fit in the prompt.
_MAX_ROWS_IN_PROMPT = 100

_INSTRUCTIONS = [
    "Answer the user's question in one or two natural-language sentences, "
    "using ONLY the provided rows.",
    "Never invent a name, number, or fact that is not present in the rows. Do NOT add "
    "up, average or otherwise calculate new numbers from the rows - only quote values "
    "that literally appear in them (describe ranges by their lowest/highest row value).",
    "If row_count is 0, clearly say no matching records were found - do not "
    "guess a plausible-sounding answer.",
    "If rows were truncated, mention that only the first rows are shown "
    "when the exact total matters to the question.",
    "State any count using the given row_count, not by counting the sample "
    "rows yourself if they were truncated.",
    "`rows` are arrays whose values follow the order of `columns`. When several rows "
    "belong to different groups (e.g. one per semester), cover EVERY group present - "
    "never say fewer groups than the rows show; if it is a long table, summarise it "
    "(counts, ranges, notable rows) and say the full table is shown alongside.",
    "Return a single JSON object: {\"answer\": string, \"reasoning\": string}. "
    "reasoning must name the specific column(s)/value(s) the answer was "
    "derived from.",
]


def build_answer_generation_context(
    user_query: str, sql: str, execution_result: QueryExecutionResult
) -> dict[str, Any]:
    return {
        "user_question": user_query,
        "executed_sql": sql,
        "columns": execution_result.columns,
        # Rows as arrays in `columns` order (not repeated key/value dicts):
        # several times fewer tokens, so up to QUERY_MAX_ROWS rows fit and
        # multi-row answers (e.g. every semester) are not cut short.
        "rows": [
            [row.get(column) for column in execution_result.columns]
            for row in execution_result.rows[:_MAX_ROWS_IN_PROMPT]
        ],
        "row_count": len(execution_result.rows),
        "rows_shown": min(len(execution_result.rows), _MAX_ROWS_IN_PROMPT),
        "truncated": execution_result.truncated,
        "instructions": _INSTRUCTIONS,
    }


class AnswerGenerator:
    def __init__(self, client: Callable[[dict[str, Any]], dict[str, Any]]):
        self.client = client

    def generate_answer(
        self,
        user_query: str,
        sql: str,
        execution_result: QueryExecutionResult,
        logger: Callable[[dict[str, Any], Any], None] | None = None,
    ) -> dict[str, Any]:
        context = build_answer_generation_context(user_query, sql, execution_result)
        response = self.client(context)
        if logger is not None:
            logger(context, response)
        if not isinstance(response, dict) or not isinstance(response.get("answer"), str) or not response["answer"].strip():
            raise ValueError("Answer generator response must be a JSON object with a non-empty 'answer' string.")
        response.setdefault("reasoning", "")
        return response


class SarvamAnswerClient:
    """Dependency-free adapter for Sarvam's chat-completions API (CALL 2)."""

    def __init__(self, settings: Settings | None = None):
        self.settings = settings or Settings()
        if self.settings.SARVAM_API_KEY is None:
            raise ValueError("SARVAM_API_KEY is required when LLM_PROVIDER is sarvam.")

    def __call__(self, context: dict[str, Any]) -> dict[str, Any]:
        payload = {
            "model": self.settings.SARVAM_MODEL,
            "temperature": 0,
            "max_tokens": 400,
            "reasoning_effort": None,
            "messages": [
                {
                    "role": "system",
                    "content": (
                        "You summarize database query results into a natural-language "
                        "answer for a non-technical user. Return a single JSON object only, "
                        "with exactly 'answer' and 'reasoning'."
                    ),
                },
                {"role": "user", "content": json.dumps(context, ensure_ascii=False, default=str)},
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
            raise RuntimeError("Sarvam did not return the required JSON answer response.") from error
        if not isinstance(result, dict):
            raise RuntimeError("Sarvam answer response must be a JSON object.")
        return result


class GeminiAnswerClient:
    """Gemini GenerateContent adapter using JSON structured output (CALL 2)."""

    _RESPONSE_SCHEMA = {
        "type": "object",
        "properties": {
            "answer": {"type": "string"},
            "reasoning": {"type": "string"},
        },
        "required": ["answer"],
    }

    def __init__(self, settings: Settings | None = None):
        self.settings = settings or Settings()
        if self.settings.GEMINI_API_KEY is None:
            raise ValueError("GEMINI_API_KEY is required when LLM_PROVIDER is gemini.")

    def __call__(self, context: dict[str, Any]) -> dict[str, Any]:
        instructions = (
            "You summarize database query results into a natural-language answer for a "
            "non-technical user, grounded strictly in the provided rows."
        )
        payload = {
            "systemInstruction": {"parts": [{"text": instructions}]},
            "contents": [{"role": "user", "parts": [{"text": json.dumps(context, default=str)}]}],
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
        return SarvamAnswerClient._parse_json(content)


def build_configured_answer_generator(settings: Settings | None = None) -> AnswerGenerator:
    configured = settings or Settings()
    if configured.LLM_PROVIDER.lower() == "sarvam":
        return AnswerGenerator(SarvamAnswerClient(configured))
    if configured.LLM_PROVIDER.lower() == "gemini":
        return AnswerGenerator(GeminiAnswerClient(configured))
    raise ValueError("LLM_PROVIDER must be either 'sarvam' or 'gemini'.")
