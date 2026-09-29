"""Subject-wise results (by student OR by cohort filters) and referrer details.

Covers the deterministic layers only (analysis -> resolution -> schema
selection); live SQL/answer behavior is exercised through the debug server.
"""

import pytest

from app.intelligence.parameter_resolution import _snap_to_known_values
from app.intelligence.query_analyzer import QueryAnalyzer
from app.intelligence.schema_retriever import SchemaRetriever
from app.metadata.registry import MetadataRegistry


def _registry() -> MetadataRegistry:
    registry = MetadataRegistry()
    registry.load()
    return registry


def _analyze_and_select(query: str):
    registry = _registry()
    analysis = QueryAnalyzer(registry.student_truth, registry.schema, registry.column_semantics).analyze(query)
    selection = None
    if analysis.is_ready:
        selection = SchemaRetriever(
            registry.schema, registry.student_truth, registry.column_semantics
        ).retrieve(analysis)
    return analysis, selection


def _params(analysis):
    return {p.name: p for p in analysis.parameters}


# --- value-catalog snapping -------------------------------------------------

CENTERS = ["BTC - PEENYA", "NEC - ELECTRONIC CITY", "RNTC - JAMSHEDPUR", "BNTC - JAMSHEDPUR"]


def test_partial_wording_snaps_to_the_single_matching_stored_value():
    assert _snap_to_known_values("Peenya", CENTERS) == ["BTC - PEENYA"]
    assert _snap_to_known_values("electronic city", CENTERS) == ["NEC - ELECTRONIC CITY"]


def test_wording_matching_several_values_expands_to_all_of_them():
    assert _snap_to_known_values("Jamshedpur", CENTERS) == ["RNTC - JAMSHEDPUR", "BNTC - JAMSHEDPUR"]


def test_exact_match_beats_a_longer_value_that_merely_contains_it():
    programs = ["CERTIFICATE", "DIPLOMA", "POST DIPLOMA"]
    assert _snap_to_known_values("Diploma", programs) == ["DIPLOMA"]


def test_unknown_or_too_generic_wording_is_left_alone():
    assert _snap_to_known_values("Fitter", CENTERS) == []
    many = [f"2024 M{i}" for i in range(11)]
    assert _snap_to_known_values("2024", many) == []


# --- results by cohort filters ------------------------------------------------

def test_marks_question_without_the_word_student_passes_the_module_gate():
    analysis, _ = _analyze_and_select("Show subject wise marks for semester 3 in center Peenya")

    assert "student_result" in analysis.intents
    assert analysis.clarification is None


def test_results_filters_resolve_to_the_results_table_and_real_values():
    analysis, selection = _analyze_and_select(
        "Show subject wise marks for center Peenya program Diploma semester three academic year 2023 JUN"
    )

    params = _params(analysis)
    assert analysis.clarification is None
    assert (params["center"].table, params["center"].column, params["center"].value) == (
        "core_exam_results", "center_name", "BTC - PEENYA")
    assert (params["program"].table, params["program"].column, params["program"].value) == (
        "core_exam_results", "program_name", "DIPLOMA")
    assert (params["semester"].table, params["semester"].column, params["semester"].value) == (
        "core_exam_results", "semester_id", "3")
    assert (params["academic_year"].table, params["academic_year"].column, params["academic_year"].value) == (
        "core_exam_results", "academic_year", "2023 JUN")
    result_columns = {c["name"] for c in selection.tables["core_exam_results"]}
    assert {"midterm_marks", "endterm_marks", "total_marks", "grade", "grade_point",
            "grand_total", "result_status", "sgpa", "cgpa"} <= result_columns


def test_a_single_filter_is_enough_for_a_results_question():
    analysis, _ = _analyze_and_select("Show subject wise marks for semester 2")

    assert analysis.clarification is None
    assert set(_params(analysis)) == {"semester"}


def test_results_question_with_neither_identity_nor_filter_asks_for_one():
    analysis, _ = _analyze_and_select("Show me the marks")

    assert analysis.clarification is not None
    assert "filter" in analysis.clarification


def test_student_code_still_works_as_the_other_way_to_search():
    analysis, _ = _analyze_and_select("Show subject wise marks for semester 1 of student code = BNTC1520001")

    params = _params(analysis)
    assert analysis.clarification is None
    assert params["core_student_code"].value == "BNTC1520001"
    assert params["semester"].table == "core_exam_results"


def test_fail_result_status_vocabulary_reaches_the_llm_schema():
    """Fail is stored as both 'Fail' and 'F' - the schema handed to the LLM
    must say so, or "failed students" silently misses one spelling."""
    _, selection = _analyze_and_select("Show grand total of failed students in semester 3 for center Peenya")

    status = next(c for c in selection.tables["core_exam_results"] if c["name"] == "result_status")
    assert {"Fail", "F", "Pass", "P"} <= set(status["allowed_values"])
    assert "'Fail' OR 'F'" in status["description"]


# --- referrer details -----------------------------------------------------------

def test_referrer_details_bring_in_employees_and_users_without_bogus_filters():
    analysis, selection = _analyze_and_select(
        "Give me the employee details who has given the reference for the student with enquiry number E25-000006"
    )

    assert {"student_referral", "student_referrer_details"} <= set(analysis.intents)
    assert {"student_referrals", "employees", "users"} <= set(selection.tables)
    # Output-only tables must not donate column names as filter vocabulary:
    # "employee" must not become a bogus employee_code filter.
    assert set(_params(analysis)) == {"enquiry_number"}
    employee_columns = {c["name"] for c in selection.tables["employees"]}
    assert {"employee_code", "first_name", "designation", "email", "mobile"} <= employee_columns
    # No credentials/government IDs are exposed to the LLM.
    assert not employee_columns & {"pan_number", "aadhar_number", "passport_number"}
    assert not {c["name"] for c in selection.tables["users"]} & {"password", "remember_token"}


def test_referrer_joins_are_verified_relationships():
    _, selection = _analyze_and_select(
        "Give me the employee details who has given the reference for the student with enquiry number E25-000006"
    )

    names = {relationship.name for relationship in selection.relationships}
    assert {"lead_to_referral", "referral_to_employee", "employee_to_user",
            "referral_to_referrer_admission"} <= names


def test_referral_status_category_codes_are_described_for_the_llm():
    _, selection = _analyze_and_select("Who gave the referral for enquiry number E25-000003")

    status = next(c for c in selection.tables["student_referrals"] if c["name"] == "status")
    assert "staff/employee referral" in status["description"]
    assert set(status["allowed_values"]) == {"1", "2", "3", "4"}


def test_grade_point_is_not_read_as_grade_with_the_value_point():
    """Live-repro'd bug: "... grade and grade point" made the shorter phrase
    "grade" match inside "grade point" and take "point" as its value."""
    analysis, _ = _analyze_and_select(
        "Show subject wise marks (midterm marks, total marks, grade and grade point) "
        "for semester 2 of student code = BNTC1520001"
    )

    assert set(_params(analysis)) == {"semester", "core_student_code"}


def test_identifier_followed_by_prose_is_not_read_as_a_two_item_list():
    """Live-repro'd bug: "enquiry number E25-000006 and show me the
    referrer's details" became the list ['E25-000006', 'show me'], which the
    database rejected ("Operand should contain 1 column(s)")."""
    analysis, _ = _analyze_and_select(
        "Who has given the reference for enquiry number E25-000006 and show me the referrer's details"
    )

    enquiry = _params(analysis)["enquiry_number"]
    assert enquiry.value == "E25-000006"
    assert enquiry.values is None


def test_genuine_multi_value_lists_still_work():
    analysis, _ = _analyze_and_select("What is the total students count in academic year 2026 JUN and 2025 JUN")

    assert _params(analysis)["academic_year"].values == ["2026 JUN", "2025 JUN"]


def test_student_referrer_wording_brings_in_the_referrer_student_records():
    analysis, selection = _analyze_and_select(
        "Give me the details of the student who has given the reference for enquiry number E25-000004"
    )

    assert "student_referrer_details" in analysis.intents
    assert "referral_to_referrer_admission" in {r.name for r in selection.relationships}


def test_intent_specific_sql_rules_reach_only_the_questions_they_apply_to():
    from app.sql.generator import build_sql_generation_context

    registry = _registry()

    def instructions(query):
        analysis = QueryAnalyzer(registry.student_truth, registry.schema, registry.column_semantics).analyze(query)
        selection = SchemaRetriever(registry.schema, registry.student_truth, registry.column_semantics).retrieve(analysis)
        return " ".join(build_sql_generation_context(query, analysis, selection)["instructions"])

    results = instructions("Show subject wise marks for semester 2")
    referrer = instructions("Give me the employee details who has given the reference for enquiry number E25-000006")
    listing = instructions("List students in batch 2024")

    assert "one row per SUBJECT per semester" in results
    assert "one row per SUBJECT per semester" not in referrer
    assert "referrer_sa" in referrer and "referrer_sa" not in results
    assert "referrer_sa" not in listing and "SUBJECT per semester" not in listing


def test_strength_center_wise_extracts_only_the_real_filters():
    """Live-repro'd: "strength center wise with status withdrawn in academic
    year 2026 jun" - "wise" must not be a value, and the lower-case month must
    snap to the stored "2026 JUN"."""
    analysis, selection = _analyze_and_select(
        "What is the total trainees strength center wise with status withdrawn in academic year 2026 jun"
    )

    params = _params(analysis)
    assert set(params) == {"status", "academic_year"}
    assert params["status"].value == "withdrawn"
    assert params["academic_year"].value == "2026 JUN"
    assert "center_name" in {c["name"] for c in selection.tables["student_admissions"]}
    assert any("wise" in hint for hint in selection.sql_hints)


@pytest.mark.parametrize("phrase", ["program wise", "stream wise", "academic year wise", "status wise"])
def test_other_wise_breakdowns_do_not_create_bogus_filters(phrase):
    analysis, _ = _analyze_and_select(f"Show the total student strength {phrase}")

    assert analysis.clarification is None
    assert analysis.parameters == []
