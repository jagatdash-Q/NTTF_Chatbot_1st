"""Full-loop test: NL query -> SQL -> DB -> NL answer -> faithfulness check.

Every external boundary (LLM clients, DB executor) is mocked, so this runs
offline and fast while still exercising the real orchestration, logging,
and validation code.
"""

from app.database.executor import QueryExecutionResult
from app.graph.root import StudentQueryOrchestrator
from app.observability.query_audit import QueryAudit
from app.response.formatter import AnswerGenerator
from app.sql.generator import SQLGenerator
from app.sql.validator import SQLValidationResult


GOOD_SQL_RESPONSE = {
    "sql": (
        "SELECT sl.applicant_name, sa.batch_name FROM student_leads sl "
        "JOIN student_admissions sa ON sl.id = sa.student_lead_id "
        "WHERE sa.batch_name = :batch"
    ),
    "parameters": {},
    "tables_used": ["student_leads", "student_admissions"],
    "explanation": "Joins leads to admissions and filters by batch.",
}


class FakeExecutor:
    def __init__(self, rows, columns=("applicant_name", "batch_name")):
        self.rows = rows
        self.columns = list(columns)
        self.calls = []

    def execute(self, sql, parameters):
        self.calls.append((sql, parameters))
        return QueryExecutionResult(columns=self.columns, rows=self.rows, truncated=False)


def test_full_loop_success(tmp_path):
    from app.config.settings import Settings

    settings = Settings(QUERY_AUDIT_LOG=str(tmp_path / "audit.jsonl"))
    audit = QueryAudit(settings, trace_id="test-trace-1")

    orchestrator = StudentQueryOrchestrator()
    generator = SQLGenerator(lambda context: dict(GOOD_SQL_RESPONSE))
    answer_generator = AnswerGenerator(
        lambda context: {
            "answer": "Ravi Kumar is a student in batch 2024.",
            "reasoning": "Derived from applicant_name and batch_name.",
        }
    )
    # A shape with no fixed layout (not a mark sheet / count table) goes
    # through the answer LLM and the faithfulness check.
    executor = FakeExecutor(
        [{"batch_name": "2024", "enrolled_by": "Ravi Kumar"}], columns=("batch_name", "enrolled_by"),
    )

    state = orchestrator.answer(
        "List students in batch 2024", generator, answer_generator, executor, audit=audit
    )

    assert state.clarification is None
    assert state.validation.valid
    assert executor.calls, "the DB executor was never called"
    assert state.answer["answer"] == "Ravi Kumar is a student in batch 2024."
    assert state.answer_validation.valid

    events = QueryAudit.read_events("test-trace-1", settings)
    stages = [e["stage"] for e in events]
    assert stages == [
        "received_query", "query_analysis", "schema_selection",
        "sql_generation_attempt", "sql_generation", "sql_validation",
        "db_execution", "answer_generation", "answer_validation", "pipeline_complete",
    ]
    assert events[-1]["output"]["outcome"] == "success"


def _audit(tmp_path, trace_id):
    from app.config.settings import Settings

    return QueryAudit(Settings(QUERY_AUDIT_LOG=str(tmp_path / "audit.jsonl")), trace_id=trace_id)


def test_full_loop_retries_once_then_gives_up_on_persistent_invalid_sql(tmp_path):
    orchestrator = StudentQueryOrchestrator()
    attempts = []

    def always_bad_sql(context):
        attempts.append(1)
        return {
            "sql": "SELECT * FROM users",
            "parameters": {},
            "tables_used": ["users"],
            "explanation": "bad",
        }

    generator = SQLGenerator(always_bad_sql)
    answer_generator = AnswerGenerator(lambda context: {"answer": "should never be called"})

    state = orchestrator.answer(
        "List students in batch 2024", generator, answer_generator,
        audit=_audit(tmp_path, "test-trace-2"), max_sql_attempts=2,
    )

    assert len(attempts) == 2
    assert state.clarification is not None
    assert "safe SQL" in state.clarification
    assert state.query_result is None
    assert state.answer is None


def test_db_execution_failure_produces_a_controlled_clarification_not_a_crash(tmp_path):
    orchestrator = StudentQueryOrchestrator()
    generator = SQLGenerator(lambda context: dict(GOOD_SQL_RESPONSE))
    answer_generator = AnswerGenerator(lambda context: {"answer": "unused"})

    class FailingExecutor:
        def execute(self, sql, parameters):
            raise RuntimeError("connection reset")

    state = orchestrator.answer(
        "List students in batch 2024", generator, answer_generator, FailingExecutor(),
        audit=_audit(tmp_path, "test-trace-3"),
    )

    assert state.clarification is not None
    assert "failed to execute" in state.clarification
    assert state.answer is None


def test_retry_after_a_rejected_statement_sends_the_errors_back_to_the_generator():
    """The second SQL attempt must see what the first one got wrong."""
    orchestrator = StudentQueryOrchestrator()
    seen_feedback = []

    class FlakyGenerator:
        def generate_sql(self, user_query, analysis, selection, logger=None, feedback=None):
            seen_feedback.append(feedback)
            sql = (
                "SELECT sa.batch_name FROM student_admissions sa WHERE sa.batch_name = :wrong_name"
                if feedback is None else
                "SELECT sa.batch_name FROM student_admissions sa WHERE sa.batch_name = :batch"
            )
            return {"sql": sql, "parameters": selection.parameters, "tables_used": ["student_admissions"], "explanation": ""}

    state = orchestrator.prepare("List students in batch 2024")
    state = orchestrator.generate_and_validate(state, FlakyGenerator())
    assert not state.validation.valid
    state = orchestrator.generate_and_validate(
        state, FlakyGenerator(),
        feedback={"rejected_sql": state.generated_sql["sql"], "errors": state.validation.errors},
    )

    assert state.validation.valid
    assert seen_feedback[0] is None
    assert ":wrong_name" in seen_feedback[1]["errors"][0]
