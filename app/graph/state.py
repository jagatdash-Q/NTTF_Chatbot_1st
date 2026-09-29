from __future__ import annotations

from dataclasses import dataclass
from typing import Any

from app.intelligence.query_analyzer import QueryAnalysis
from app.intelligence.schema_retriever import SchemaSelection
from app.observability.query_audit import QueryAudit
from app.response.answer_validator import AnswerValidationResult
from app.response.presenter import Presentation
from app.sql.validator import SQLValidationResult
from app.database.executor import QueryExecutionResult


@dataclass
class QueryState:
    user_query: str
    analysis: QueryAnalysis | None = None
    schema_selection: SchemaSelection | None = None
    llm_context: dict[str, Any] | None = None
    generated_sql: dict[str, Any] | None = None
    validation: SQLValidationResult | None = None
    clarification: str | None = None
    query_result: QueryExecutionResult | None = None
    answer: dict[str, Any] | None = None
    answer_validation: AnswerValidationResult | None = None
    presentation: Presentation | None = None
    trace_id: str | None = None
    audit: QueryAudit | None = None
