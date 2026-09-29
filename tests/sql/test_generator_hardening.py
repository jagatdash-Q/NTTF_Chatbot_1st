import pytest

from app.intelligence.query_analyzer import QueryAnalyzer
from app.intelligence.schema_retriever import SchemaRetriever
from app.metadata.registry import MetadataRegistry
from app.sql.generator import SQLGenerator


def _selection():
    registry = MetadataRegistry()
    registry.load()
    analysis = QueryAnalyzer(
        registry.student_truth, registry.schema, registry.column_semantics
    ).analyze("List students in batch 2024")
    selection = SchemaRetriever(registry.schema, registry.student_truth).retrieve(analysis)
    return analysis, selection


def test_parameters_returned_as_a_list_is_normalized_not_rejected():
    """Live-repro'd, recurring Sarvam quirk: `parameters` returned as
    `[{"name": ..., "value": ...}]` instead of a flat object. This shape is
    unambiguous, so it's normalized rather than wasting a retry attempt -
    values are overwritten by the deterministic extractor regardless."""
    analysis, selection = _selection()

    def list_shaped_client(context):
        return {
            "sql": "SELECT sa.batch_name FROM student_admissions sa WHERE sa.batch_name = :batch",
            "parameters": [{"name": "batch", "value": "2024"}],
            "tables_used": ["student_admissions"],
            "explanation": "test",
        }

    response = SQLGenerator(list_shaped_client).generate_sql(
        "List students in batch 2024", analysis, selection
    )
    assert response["parameters"] == selection.parameters


def test_parameters_with_an_unexpected_name_still_raises_after_normalization():
    analysis, selection = _selection()

    def bad_client(context):
        return {
            "sql": "SELECT 1",
            "parameters": [{"name": "not_a_real_parameter", "value": "x"}],
            "tables_used": ["student_admissions"],
            "explanation": "test",
        }

    with pytest.raises(ValueError, match="unexpected parameters"):
        SQLGenerator(bad_client).generate_sql("List students in batch 2024", analysis, selection)


def test_genuinely_malformed_parameters_still_raises_a_clean_error():
    analysis, selection = _selection()

    def bad_client(context):
        return {
            "sql": "SELECT 1",
            "parameters": "batch=2024",
            "tables_used": ["student_admissions"],
            "explanation": "test",
        }

    with pytest.raises(ValueError, match="JSON object"):
        SQLGenerator(bad_client).generate_sql("List students in batch 2024", analysis, selection)


def test_non_dict_response_raises_a_clean_error():
    analysis, selection = _selection()

    with pytest.raises(ValueError):
        SQLGenerator(lambda context: "not a dict").generate_sql(
            "List students in batch 2024", analysis, selection
        )


def test_logger_hook_receives_the_exact_prompt_and_raw_response():
    analysis, selection = _selection()
    captured = {}

    def client(context):
        return {
            "sql": "SELECT sa.batch_name FROM student_admissions sa WHERE sa.batch_name = :batch",
            "parameters": {"batch": "2024"},
            "tables_used": ["student_admissions"],
            "explanation": "test",
        }

    def logger(context, response):
        captured["context"] = context
        captured["response"] = response

    SQLGenerator(client).generate_sql("List students in batch 2024", analysis, selection, logger=logger)

    assert captured["context"]["user_question"] == "List students in batch 2024"
    assert captured["response"]["sql"].startswith("SELECT")
