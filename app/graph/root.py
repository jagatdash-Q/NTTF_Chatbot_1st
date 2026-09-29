"""Root orchestration for the current Core Programme student module.

This is intentionally framework-neutral until LangGraph is added as a project
dependency. Its state transitions map directly to the planned graph nodes.

Every method logs its stage to `state.audit` (a QueryAudit correlated by
`state.trace_id`), so a single request can be replayed stage-by-stage from
the JSON-lines log regardless of which entry point called it (script, test,
or the debug HTTP server).
"""

from __future__ import annotations

import time
from typing import Any

from app.graph.state import QueryState
from app.database.executor import SafeQueryExecutor
from app.intelligence.entity_resolver import ConceptExtractor, IntentClassifier
from app.intelligence.query_analyzer import QueryAnalyzer
from app.intelligence.schema_retriever import SchemaRetriever
from app.metadata.registry import MetadataRegistry
from app.observability.query_audit import QueryAudit
from app.response.answer_validator import AnswerFaithfulnessValidator, AnswerValidationResult
from app.response.formatter import AnswerGenerator
from app.response.presenter import build_presentation
from app.sql.generator import SQLGenerator, build_sql_generation_context
from app.sql.validator import SQLValidationResult, SQLValidator


class StudentQueryOrchestrator:
    def __init__(
        self,
        registry: MetadataRegistry | None = None,
        concept_extractor: ConceptExtractor | None = None,
        intent_classifier: IntentClassifier | None = None,
    ):
        self.registry = registry or MetadataRegistry()
        self.registry.load()
        # Both None by default = today's fully deterministic, offline
        # behavior (unchanged for every existing caller/test). A caller
        # that wants the LLM fallbacks (e.g. the debug server) passes them
        # in here once, and every prepare() call reuses them.
        self.concept_extractor = concept_extractor
        self.intent_classifier = intent_classifier

    def prepare(
        self, user_query: str, trace_id: str | None = None, audit: QueryAudit | None = None
    ) -> QueryState:
        state = QueryState(user_query=user_query)
        state.audit = audit or QueryAudit(trace_id=trace_id)
        state.trace_id = state.audit.trace_id
        state.audit.record("received_query", input=user_query)

        state.analysis = QueryAnalyzer(
            self.registry.student_truth, self.registry.schema, self.registry.column_semantics,
            concept_extractor=self.concept_extractor, intent_classifier=self.intent_classifier,
        ).analyze(user_query)
        state.audit.record("query_analysis", input=user_query, output=state.analysis.as_dict())

        if not state.analysis.is_ready:
            state.clarification = state.analysis.clarification
            state.audit.record("clarification", output=state.clarification)
            return state

        state.schema_selection = SchemaRetriever(
            self.registry.schema, self.registry.student_truth, self.registry.column_semantics
        ).retrieve(state.analysis)
        state.audit.record(
            "schema_selection",
            input={"intent": state.analysis.intent},
            output=state.schema_selection.as_llm_context(),
        )
        state.llm_context = build_sql_generation_context(
            user_query, state.analysis, state.schema_selection
        )
        return state

    def generate_and_validate(
        self, state: QueryState, generator: SQLGenerator, feedback: dict[str, Any] | None = None
    ) -> QueryState:
        if state.clarification:
            return state
        if not state.analysis or not state.schema_selection:
            raise ValueError("Prepare the query before SQL generation.")

        def _log_llm_call(context: dict[str, Any], response: Any) -> None:
            state.audit.record("sql_generation", input=context, output=response)

        try:
            generate_kwargs: dict[str, Any] = {"logger": _log_llm_call}
            if feedback:
                generate_kwargs["feedback"] = feedback
            state.generated_sql = generator.generate_sql(
                state.user_query, state.analysis, state.schema_selection, **generate_kwargs
            )
        except Exception as error:
            # Any failure calling the LLM (network/HTTP error, malformed
            # response shape) degrades to a controlled validation failure
            # instead of an unhandled exception reaching the caller.
            state.generated_sql = None
            state.validation = SQLValidationResult(valid=False, errors=[str(error)])
            state.audit.record("sql_generation_error", output=str(error))
            return state

        state.validation = SQLValidator().validate(
            state.generated_sql["sql"], state.schema_selection,
            result_scope=state.analysis.result_scope if state.analysis else None,
        )
        state.audit.record(
            "sql_validation",
            input=state.generated_sql["sql"],
            output={"valid": state.validation.valid, "errors": state.validation.errors},
        )
        return state

    def execute_validated(self, state: QueryState, executor: SafeQueryExecutor | None = None) -> QueryState:
        if not state.validation or not state.validation.valid:
            raise ValueError("Only successfully validated SQL may be executed.")
        sql = state.generated_sql["sql"]
        parameters = state.generated_sql["parameters"]
        started = time.perf_counter()
        try:
            state.query_result = (executor or SafeQueryExecutor()).execute(sql, parameters)
        except Exception as error:
            state.audit.record(
                "db_execution",
                input={"sql": sql, "parameters": parameters},
                output={"error": str(error)},
                elapsed_ms=round((time.perf_counter() - started) * 1000, 1),
            )
            raise
        _ROWS_LOGGED_LIMIT = 50
        state.audit.record(
            "db_execution",
            input={"sql": sql, "parameters": parameters},
            output={
                "row_count": len(state.query_result.rows),
                "columns": state.query_result.columns,
                "truncated": state.query_result.truncated,
                "rows": state.query_result.rows[:_ROWS_LOGGED_LIMIT],
                "rows_logged": min(len(state.query_result.rows), _ROWS_LOGGED_LIMIT),
            },
            elapsed_ms=round((time.perf_counter() - started) * 1000, 1),
        )
        return state

    def synthesize_answer(self, state: QueryState, answer_generator: AnswerGenerator) -> QueryState:
        if not state.query_result:
            raise ValueError("Execute the query before synthesizing an answer.")

        # A recognised result shape (subject-wise marks, per-semester results)
        # is laid out directly from the rows - a mark sheet, not a sentence.
        # No answer-LLM call: every value is copied from a row, so there is
        # nothing to hallucinate or to fact-check.
        scalar_filters = {
            parameter.column: parameter.value
            for parameter in (state.analysis.parameters if state.analysis else [])
            if not parameter.values
        }
        presentation = build_presentation(
            state.query_result.columns, state.query_result.rows, state.query_result.truncated,
            filters=scalar_filters,
            scope=state.analysis.result_scope if state.analysis else None,
        )
        if presentation is not None:
            state.presentation = presentation
            state.answer = {
                "answer": presentation.text,
                "reasoning": f"Laid out directly from the result rows (presenter: {presentation.kind}); no language-model call.",
            }
            state.answer_validation = AnswerValidationResult(valid=True, reasons=[])
            state.audit.record("presentation", input={"presenter": presentation.kind}, output=presentation.as_dict())
            state.audit.record(
                "answer_validation",
                input=state.answer["answer"],
                output={"valid": True, "reasons": [], "note": "values copied verbatim from the rows"},
            )
            return state

        def _log_llm_call(context: dict[str, Any], response: Any) -> None:
            state.audit.record("answer_generation", input=context, output=response)

        state.answer = answer_generator.generate_answer(
            state.user_query, state.generated_sql["sql"], state.query_result, logger=_log_llm_call
        )
        filter_values: list[Any] = []
        for value in state.generated_sql["parameters"].values():
            if isinstance(value, list):
                filter_values.extend(value)
            else:
                filter_values.append(value)
        state.answer_validation = AnswerFaithfulnessValidator().validate(
            state.query_result, state.answer["answer"], filter_values=filter_values,
            question_text=state.user_query,
        )
        state.audit.record(
            "answer_validation",
            input=state.answer["answer"],
            output={"valid": state.answer_validation.valid, "reasons": state.answer_validation.reasons},
        )
        return state

    def answer(
        self,
        user_query: str,
        generator: SQLGenerator,
        answer_generator: AnswerGenerator,
        executor: SafeQueryExecutor | None = None,
        trace_id: str | None = None,
        audit: QueryAudit | None = None,
        max_sql_attempts: int = 2,
    ) -> QueryState:
        """Closes the full loop: NL query -> SQL -> DB -> NL answer ->
        faithfulness check. Retries SQL generation once on a validation
        failure (a real, observed failure mode - see docs) before giving up
        with a clear, logged clarification rather than executing anything
        unsafe or crashing."""
        state = self.prepare(user_query, trace_id=trace_id, audit=audit)
        if state.clarification:
            state.audit.record("pipeline_complete", output={"outcome": "clarification"})
            return state

        errors: list[str] = []
        feedback: dict[str, Any] | None = None
        for attempt in range(1, max_sql_attempts + 1):
            state.audit.record("sql_generation_attempt", input={"attempt": attempt, "max_attempts": max_sql_attempts})
            state = self.generate_and_validate(state, generator, feedback=feedback)
            if state.validation and state.validation.valid:
                break
            errors = state.validation.errors if state.validation else ["unknown error"]
            feedback = {
                "rejected_sql": state.generated_sql["sql"] if state.generated_sql else None,
                "errors": errors,
            }

        if not state.validation or not state.validation.valid:
            state.clarification = (
                f"Could not produce a safe SQL query for this question after {max_sql_attempts} "
                f"attempt(s): {errors}"
            )
            state.audit.record("pipeline_complete", output={"outcome": "sql_generation_failed", "errors": errors})
            return state

        try:
            state = self.execute_validated(state, executor)
        except Exception as error:
            state.clarification = f"The generated query failed to execute: {error}"
            state.audit.record("pipeline_complete", output={"outcome": "db_execution_failed", "error": str(error)})
            return state

        try:
            state = self.synthesize_answer(state, answer_generator)
        except Exception as error:
            state.clarification = f"Could not generate a natural-language answer: {error}"
            state.audit.record("pipeline_complete", output={"outcome": "answer_generation_failed", "error": str(error)})
            return state

        state.audit.record("pipeline_complete", output={
            "outcome": "success",
            "answer_valid": state.answer_validation.valid,
        })
        return state
