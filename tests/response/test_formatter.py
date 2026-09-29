import pytest

from app.database.executor import QueryExecutionResult
from app.response.formatter import (
    AnswerGenerator,
    SarvamAnswerClient,
    build_answer_generation_context,
)


def _result():
    return QueryExecutionResult(
        columns=["applicant_name", "batch_name"],
        rows=[{"applicant_name": "Ravi Kumar", "batch_name": "2024"}],
        truncated=False,
    )


def test_build_answer_generation_context_never_includes_the_full_schema():
    context = build_answer_generation_context("List students in batch 2024", "SELECT 1", _result())

    assert context["row_count"] == 1
    assert context["columns"] == ["applicant_name", "batch_name"]
    assert context["rows"] == [["Ravi Kumar", "2024"]]
    assert "tables" not in context
    assert "columns_metadata" not in context


def test_answer_generator_returns_the_client_response():
    def client(context):
        return {"answer": "Ravi Kumar is in batch 2024.", "reasoning": "From applicant_name and batch_name."}

    response = AnswerGenerator(client).generate_answer("List students in batch 2024", "SELECT 1", _result())

    assert response["answer"] == "Ravi Kumar is in batch 2024."


def test_answer_generator_rejects_a_response_with_no_answer_field():
    with pytest.raises(ValueError):
        AnswerGenerator(lambda context: {"reasoning": "..."}).generate_answer(
            "List students in batch 2024", "SELECT 1", _result()
        )


def test_logger_hook_captures_prompt_and_raw_response():
    captured = {}

    def client(context):
        return {"answer": "Ravi Kumar is in batch 2024."}

    def logger(context, response):
        captured["context"] = context
        captured["response"] = response

    AnswerGenerator(client).generate_answer(
        "List students in batch 2024", "SELECT 1", _result(), logger=logger
    )

    assert captured["context"]["user_question"] == "List students in batch 2024"
    assert captured["response"]["answer"] == "Ravi Kumar is in batch 2024."


def test_sarvam_client_parses_fenced_json():
    parsed = SarvamAnswerClient._parse_json(
        '```json\n{"answer": "Ravi Kumar is in batch 2024.", "reasoning": "test"}\n```'
    )
    assert parsed["answer"] == "Ravi Kumar is in batch 2024."


def test_up_to_query_max_rows_reach_the_answer_prompt_not_just_thirty():
    """Live-repro'd bug: with a 30-row cap a 57-row subject-wise result was
    cut off after semester 3 and the answer claimed "three semesters"."""
    rows = [{"semester_id": n // 10 + 1, "subject": f"S{n}"} for n in range(60)]
    result = QueryExecutionResult(columns=["semester_id", "subject"], rows=rows, truncated=False)

    context = build_answer_generation_context("marks for all semesters", "SELECT 1", result)

    assert context["row_count"] == 60
    assert context["rows_shown"] == 60
    assert len(context["rows"]) == 60
    assert context["rows"][-1] == [6, "S59"]
