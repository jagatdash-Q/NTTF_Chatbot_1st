"""Which exam attempts a results question means (regular / supplementary n / consolidated)."""

import pytest

from app.intelligence.query_analyzer import QueryAnalyzer
from app.intelligence.result_scope import detect_result_scope
from app.metadata.registry import MetadataRegistry


@pytest.mark.parametrize("query, exam_type, attempt", [
    ("semester 1 result for student code = X", None, None),
    ("semester one result regular and supplimentery", None, None),
    ("show the regular result of semester 1", "regular", None),
    ("supplementary result of semester 1", "supplementary", None),
    ("supplimentery 2 result for semester 1", "supplementary", 2),
    ("suppl no = 3 for semester 1", "supplementary", 3),
    ("second supplementary attempt of semester 2", "supplementary", 2),
    ("attempt 4 marks of semester 1", "supplementary", 4),
    ("consolidated result of semester 1", None, None),
])
def test_exam_type_and_attempt_are_read_from_the_wording(query, exam_type, attempt):
    scope = detect_result_scope(query)

    assert scope.exam_type == exam_type
    assert scope.attempt == attempt


def test_consolidated_and_all_semesters_and_grade_flags():
    assert detect_result_scope("consolidated result for semester 1").consolidated
    assert not detect_result_scope("result for semester 1").consolidated
    assert detect_result_scope("consolidated result for all semesters").all_semesters
    assert detect_result_scope("semester wise marks").all_semesters
    assert not detect_result_scope("marks for semester 2").all_semesters
    assert detect_result_scope("marks with gp and gr").show_grade
    assert detect_result_scope("marks and grade point").show_grade
    assert not detect_result_scope("marks and sgpa").show_grade


def _analyze(query):
    registry = MetadataRegistry()
    registry.load()
    return QueryAnalyzer(registry.student_truth, registry.schema, registry.column_semantics).analyze(query)


def test_consolidated_without_a_semester_asks_which_one():
    analysis = _analyze("Give me the consolidated result of student code = TTC0825020")

    assert "semester number" in analysis.clarification
    assert "all semesters" in analysis.clarification


def test_consolidated_is_accepted_with_a_semester_or_for_all_semesters():
    one = _analyze("Give me the consolidated result of semester 1 of student code = TTC0825020")
    every = _analyze("Give me the consolidated result for all semesters of student code = TTC0825020")

    assert one.clarification is None and one.result_scope.consolidated
    assert every.clarification is None and every.result_scope.all_semesters


def test_the_scope_reaches_the_sql_model_in_the_interpreted_request():
    analysis = _analyze("Show supplementary 2 result of semester 1 of student code = TTC0825020")

    assert analysis.as_dict()["result_scope"] == {
        "exam_type": "supplementary", "attempt": 2, "consolidated": False, "all_semesters": False,
    }


def test_questions_that_are_not_about_results_have_no_scope():
    assert _analyze("List students in batch 2024").as_dict()["result_scope"] is None


def _validate(sql, scope):
    from app.intelligence.schema_retriever import SchemaRetriever
    from app.sql.validator import SQLValidator

    registry = MetadataRegistry()
    registry.load()
    query = "Show subject wise marks for semester 1 of student code = TTC0825020"
    analysis = QueryAnalyzer(registry.student_truth, registry.schema, registry.column_semantics).analyze(query)
    selection = SchemaRetriever(registry.schema, registry.student_truth, registry.column_semantics).retrieve(analysis)
    return SQLValidator().validate(sql, selection, result_scope=scope)


_BASE = (
    "SELECT cer.subject_name, cer.final_exam_type, cer.suppl_no FROM core_exam_results cer "
    "JOIN student_admissions sa ON cer.core_student_id = sa.id "
    "WHERE sa.core_student_code = :core_student_code AND cer.semester_id = :semester"
)


def test_sql_that_drops_attempts_is_rejected_when_the_question_covers_every_attempt():
    """Live-repro'd: "regular and supplementary" produced a query keeping only
    the regular exam, so the supplementary rows could never be shown."""
    every_attempt = detect_result_scope("semester one result regular and supplimentery")

    bad = _validate(_BASE + " AND (cer.final_exam_type IS NULL OR cer.final_exam_type = 'semester')", every_attempt)
    also_bad = _validate(_BASE + " AND cer.suppl_no = 1", every_attempt)
    good = _validate(_BASE + " ORDER BY cer.final_exam_type, cer.suppl_no", every_attempt)

    assert not bad.valid and any("every exam attempt" in e for e in bad.errors)
    assert not also_bad.valid
    assert good.valid, good.errors


def test_an_attempt_filter_is_fine_when_the_question_names_one():
    regular = detect_result_scope("regular result of semester 1")

    result = _validate(_BASE + " AND (cer.final_exam_type IS NULL OR cer.final_exam_type = 'semester')", regular)

    assert result.valid, result.errors


def test_a_consolidated_request_must_return_subject_rows():
    consolidated = detect_result_scope("consolidated result of semester 1")

    summary_only = _validate(
        "SELECT DISTINCT cer.semester_id, cer.sgpa, cer.cgpa FROM core_exam_results cer "
        "JOIN student_admissions sa ON cer.core_student_id = sa.id "
        "WHERE sa.core_student_code = :core_student_code AND cer.semester_id = :semester",
        consolidated,
    )
    with_subjects = _validate(_BASE, consolidated)

    assert not summary_only.valid and any("subject rows" in e for e in summary_only.errors)
    assert with_subjects.valid, with_subjects.errors
