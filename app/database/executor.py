"""Read-only execution of SQL that has already passed deterministic validation."""

from __future__ import annotations

import re
from dataclasses import dataclass
from datetime import date, datetime
from decimal import Decimal
from typing import Any

from sqlalchemy import bindparam, text

from app.config.settings import Settings
from app.database.connection import engine


@dataclass
class QueryExecutionResult:
    columns: list[str]
    rows: list[dict[str, Any]]
    truncated: bool


class SafeQueryExecutor:
    def __init__(self, settings: Settings | None = None):
        self.settings = settings or Settings()

    def execute(self, sql: str, parameters: dict[str, Any]) -> QueryExecutionResult:
        """Fetch at most QUERY_MAX_ROWS + 1 rows; this class never accepts non-SELECT SQL."""
        if not sql.lstrip().lower().startswith("select"):
            raise ValueError("SafeQueryExecutor accepts SELECT statements only.")
        # A list-valued parameter means a multi-value filter (e.g. two
        # academic years). SQLAlchemy's expanding bind supplies its own
        # parentheses around the expanded list, so `IN (:param)` in the SQL
        # text would double up into invalid `IN ((a, b))`; the generator is
        # instructed to write `IN :param` without them, but an LLM might
        # still write the more familiar `IN (:param)` anyway - stripped
        # defensively here rather than trusting instruction-following.
        list_params = [name for name, value in parameters.items() if isinstance(value, list)]
        for name in list_params:
            sql = re.sub(rf"\(\s*(:{re.escape(name)})\s*\)", r"\1", sql)
        statement = text(sql)
        if list_params:
            statement = statement.bindparams(*(bindparam(name, expanding=True) for name in list_params))
        with engine.connect() as connection:
            result = connection.execute(statement, parameters)
            records = result.fetchmany(self.settings.QUERY_MAX_ROWS + 1)
            columns = list(result.keys())
        truncated = len(records) > self.settings.QUERY_MAX_ROWS
        records = records[:self.settings.QUERY_MAX_ROWS]
        return QueryExecutionResult(
            columns=columns,
            rows=[{key: self._json_value(value) for key, value in row._mapping.items()} for row in records],
            truncated=truncated,
        )

    @staticmethod
    def _json_value(value: Any) -> Any:
        if isinstance(value, (datetime, date, Decimal)):
            return str(value)
        return value
