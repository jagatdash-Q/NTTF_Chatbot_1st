"""Result shapes we recognise are laid out deterministically, not summarised."""

from app.database.executor import QueryExecutionResult
from app.graph.root import StudentQueryOrchestrator
from app.intelligence.result_scope import ResultScope
from app.response.formatter import AnswerGenerator
from app.response.presenter import build_presentation

COLUMNS = [
    "applicant_name", "core_student_code", "semester_id",
    "subject_name", "subject_code", "midterm_marks", "endterm_marks", "total_marks",
]


def _subject_rows(semester=1):
    return [
        {"applicant_name": "AANCHAL KUMARI", "core_student_code": "BNTC1520001", "semester_id": semester,
         "subject_name": "English I", "subject_code": "CP15101T", "midterm_marks": "43", "endterm_marks": "36", "total_marks": "79"},
        {"applicant_name": "AANCHAL KUMARI", "core_student_code": "BNTC1520001", "semester_id": semester,
         "subject_name": "Mathematics I", "subject_code": "CP15102T", "midterm_marks": "44", "endterm_marks": "41", "total_marks": "85"},
    ]


def _details(presentation):
    """The student-details section (facts only) that opens every layout."""
    return presentation.sections[0]


def _blocks(presentation):
    return presentation.sections[1:]


# --- basic mark sheet -------------------------------------------------------------------

def test_subject_marks_are_a_mark_sheet_with_student_details_first():
    presentation = build_presentation(COLUMNS, _subject_rows())

    assert presentation.kind == "subject_marks"
    assert _details(presentation).facts == [("Student name", "AANCHAL KUMARI"), ("Student code", "BNTC1520001")]
    assert _details(presentation).table_columns == []
    block = _blocks(presentation)[0]
    assert block.title == "Semester 1"
    assert block.table_columns == ["Subject", "Subject code", "Midterm", "Endterm", "Total"]
    assert block.table_rows[0] == ["English I", "CP15101T", "43", "36", "79"]
    assert block.summary == []          # no semester-level figure was asked for
    assert "| Subject | Subject code | Midterm | Endterm | Total |" in presentation.text


def test_grade_and_gp_are_shown_only_when_asked_and_subject_result_comes_from_the_grade():
    columns = COLUMNS + ["grade", "grade_point"]
    rows = [dict(_subject_rows()[0], grade="B", grade_point="8.5"), dict(_subject_rows()[1], grade="F", grade_point="0")]

    asked = build_presentation(columns, rows, scope=ResultScope(show_grade=True))
    not_asked = build_presentation(columns, rows, scope=ResultScope(show_grade=False))

    assert _blocks(asked)[0].table_columns == ["Subject", "Subject code", "Midterm", "Endterm", "Total", "Grade", "GP", "Result"]
    assert [row[-1] for row in _blocks(asked)[0].table_rows] == ["Pass", "Fail"]
    assert _blocks(not_asked)[0].table_columns == ["Subject", "Subject code", "Midterm", "Endterm", "Total", "Result"]


def test_semester_totals_are_listed_point_by_point():
    columns = COLUMNS + ["grand_total", "sgpa", "cgpa", "result_status"]
    rows = [dict(row, grand_total=906, sgpa="8.82", cgpa="8.82", result_status="P") for row in _subject_rows()]

    block = _blocks(build_presentation(columns, rows))[0]

    assert block.summary_title == "Semester result"
    assert block.summary == [("Grand total", "906"), ("SGPA", "8.82"), ("CGPA", "8.82"), ("Result", "Pass")]


def test_several_semesters_get_one_block_each():
    presentation = build_presentation(COLUMNS, _subject_rows(1) + _subject_rows(2))

    assert [section.title for section in _blocks(presentation)] == ["Semester 1", "Semester 2"]


def test_a_cohort_of_many_students_becomes_one_flat_table():
    rows = []
    for n in range(12):
        rows += [dict(row, core_student_code=f"S{n}") for row in _subject_rows()]

    presentation = build_presentation(COLUMNS, rows)

    assert len(presentation.sections) == 1
    assert presentation.sections[0].table_columns[0] == "Student name"


# --- repeated details are points, only results are tables ------------------------------------

def test_values_that_repeat_on_every_row_are_points_not_table_columns():
    """Live-repro'd: father name / status / academic year were table columns."""
    columns = COLUMNS + ["core_year", "status", "father_name"]
    rows = [dict(row, core_year="2025 JUN", status="active", father_name="ANIL KUMAR V") for row in _subject_rows()]

    presentation = build_presentation(columns, rows)

    assert ("Academic year", "2025 JUN") in _details(presentation).facts
    assert ("Current status", "active") in _details(presentation).facts
    assert ("Father name", "ANIL KUMAR V") in _details(presentation).facts
    assert _blocks(presentation)[0].table_columns == ["Subject", "Subject code", "Midterm", "Endterm", "Total"]


def test_a_column_that_differs_between_subjects_stays_in_the_table():
    columns = COLUMNS + ["faculty"]
    rows = [dict(_subject_rows()[0], faculty="A"), dict(_subject_rows()[1], faculty="B")]

    presentation = build_presentation(columns, rows)

    assert _blocks(presentation)[0].table_columns[-1] == "Faculty"
    assert ("Faculty", "A") not in _details(presentation).facts


# --- exam attempts -----------------------------------------------------------------------------

ATTEMPT_COLUMNS = COLUMNS + [
    "grade", "final_exam_type", "suppl_no", "grand_total", "sgpa", "cgpa", "result_status",
]


def _attempt_rows():
    regular = [
        dict(_subject_rows()[0], grade="C", final_exam_type="semester", suppl_no=None, grand_total=448, sgpa="3.25", cgpa=None, result_status="F"),
        dict(_subject_rows()[1], grade="F", total_marks="30", final_exam_type=None, suppl_no=None, grand_total=448, sgpa="3.25", cgpa=None, result_status="F"),
    ]
    supplementary = [
        dict(_subject_rows()[1], grade="E", total_marks="43", final_exam_type="supplementary", suppl_no=1, grand_total=543, sgpa="6.32", cgpa="6.32", result_status="Pass"),
    ]
    return regular + supplementary


def test_each_attempt_gets_its_own_table_and_totals_regular_first():
    presentation = build_presentation(ATTEMPT_COLUMNS, _attempt_rows(), scope=ResultScope(show_grade=True))

    titles = [section.title for section in _blocks(presentation)]
    assert titles == ["Semester 1 - Regular exam", "Semester 1 - Supplementary attempt 1"]
    regular, supplementary = _blocks(presentation)
    assert regular.summary_title == "Regular exam result"
    assert regular.summary == [("Grand total", "448"), ("SGPA", "3.25"), ("CGPA", "-"), ("Result", "Fail")]
    assert supplementary.summary == [("Grand total", "543"), ("SGPA", "6.32"), ("CGPA", "6.32"), ("Result", "Pass")]
    assert [row[-1] for row in regular.table_rows] == ["Pass", "Fail"]
    assert "Exam type" not in regular.table_columns and "Supplementary no." not in regular.table_columns


def test_overall_result_is_fail_when_any_subject_failed_even_if_the_recorded_status_says_pass():
    rows = _attempt_rows()[:2]
    for row in rows:
        row["result_status"] = "Pass"

    regular = _blocks(build_presentation(ATTEMPT_COLUMNS, rows))[0]

    assert regular.summary[-1] == ("Result", "Fail")


def test_a_supplementary_attempt_keeps_a_recorded_fail_for_subjects_it_did_not_resit():
    rows = _attempt_rows()
    rows[2]["result_status"] = "F"      # every re-sat subject passed, student still failing overall

    supplementary = _blocks(build_presentation(ATTEMPT_COLUMNS, rows))[1]

    assert supplementary.summary[-1] == ("Result", "Fail")


def test_asking_for_the_regular_exam_shows_only_the_regular_attempt():
    presentation = build_presentation(ATTEMPT_COLUMNS, _attempt_rows(), scope=ResultScope(exam_type="regular"))

    assert [section.title for section in _blocks(presentation)] == ["Semester 1 - Regular exam"]


def test_asking_for_one_supplementary_attempt_shows_only_that_attempt():
    rows = _attempt_rows() + [dict(_attempt_rows()[2], suppl_no=2, grand_total=560)]

    presentation = build_presentation(ATTEMPT_COLUMNS, rows, scope=ResultScope(exam_type="supplementary", attempt=2))

    assert [section.title for section in _blocks(presentation)] == ["Semester 1 - Supplementary attempt 2"]


# --- consolidated ---------------------------------------------------------------------------------

def test_consolidated_shows_the_latest_attempt_of_each_subject():
    presentation = build_presentation(
        ATTEMPT_COLUMNS, _attempt_rows(), scope=ResultScope(consolidated=True, show_grade=True),
    )

    block = _blocks(presentation)[0]
    assert block.title == "Semester 1 - Consolidated"
    assert block.table_columns[-2:] == ["Result", "Attempt"]
    by_subject = {row[0]: row for row in block.table_rows}
    assert by_subject["English I"][-1] == "Regular exam"                 # never re-sat
    assert by_subject["Mathematics I"][-2:] == ["Pass", "Supplementary attempt 1"]   # F -> E on the re-sit
    assert by_subject["Mathematics I"][4] == "43"
    assert block.summary == [
        ("Latest attempt on record", "Supplementary attempt 1"),
        ("Grand total (latest attempt)", "543"),
        ("SGPA (latest attempt)", "6.32"),
        ("CGPA (latest attempt)", "6.32"),
        ("Overall result", "Pass"),
    ]
    assert "Consolidated" in presentation.notes[0]


# --- per-semester results -----------------------------------------------------------------------------

def test_per_semester_results_for_one_student_are_a_semester_table():
    columns = ["applicant_name", "core_student_code", "semester_id", "grand_total", "sgpa", "cgpa", "result_status"]
    rows = [
        {"applicant_name": "AANCHAL KUMARI", "core_student_code": "BNTC1520001", "semester_id": 1,
         "grand_total": 906, "sgpa": "8.82", "cgpa": "8.82", "result_status": "P"},
        {"applicant_name": "AANCHAL KUMARI", "core_student_code": "BNTC1520001", "semester_id": 2,
         "grand_total": 938, "sgpa": "8.85", "cgpa": "8.84", "result_status": "F"},
    ]

    presentation = build_presentation(columns, rows)

    assert presentation.kind == "semester_results"
    section = presentation.sections[0]
    assert section.facts == [("Student name", "AANCHAL KUMARI"), ("Student code", "BNTC1520001")]
    assert section.table_columns == ["Semester", "Grand total", "SGPA", "CGPA", "Result"]
    assert section.table_rows == [["1", "906", "8.82", "8.82", "Pass"], ["2", "938", "8.85", "8.84", "Fail"]]


def test_semester_results_show_one_row_per_attempt():
    columns = ["applicant_name", "core_student_code", "semester_id", "final_exam_type", "suppl_no", "grand_total", "sgpa", "cgpa", "result_status"]
    base = {"applicant_name": "A", "core_student_code": "X1", "semester_id": 1}
    rows = [
        dict(base, final_exam_type="supplementary", suppl_no=1, grand_total=543, sgpa="6.32", cgpa="6.32", result_status="Pass"),
        dict(base, final_exam_type="semester", suppl_no=None, grand_total=448, sgpa="3.25", cgpa=None, result_status="F"),
    ]

    section = build_presentation(columns, rows).sections[0]

    assert section.table_columns == ["Semester", "Attempt", "Grand total", "SGPA", "CGPA", "Result"]
    assert [row[1] for row in section.table_rows] == ["Regular exam", "Supplementary attempt 1"]


def test_a_cohort_of_students_is_one_flat_table_with_student_columns_first():
    columns = ["core_student_code", "applicant_name", "semester_id", "grand_total", "sgpa", "result_status"]
    rows = [
        {"core_student_code": "A1", "applicant_name": "ONE", "semester_id": 3, "grand_total": 609, "sgpa": "6.99", "result_status": "F"},
        {"core_student_code": "B2", "applicant_name": "TWO", "semester_id": 3, "grand_total": 585, "sgpa": "6.37", "result_status": "Fail"},
    ]

    presentation = build_presentation(columns, rows)

    assert presentation.sections[0].table_columns[:2] == ["Student name", "Student code"]
    assert presentation.sections[0].table_rows[1][-1] == "Fail"


# --- misc -----------------------------------------------------------------------------------------------

def test_other_shapes_and_empty_results_are_not_presented():
    assert build_presentation(["total_students"], [{"total_students": 5556}]) is None
    assert build_presentation(COLUMNS, []) is None
    assert build_presentation(["applicant_name", "batch_name"], [{"applicant_name": "X", "batch_name": "Y"}]) is None


def test_missing_values_and_pipes_are_rendered_safely_and_duplicates_dropped():
    rows = _subject_rows() + _subject_rows()
    rows[0]["subject_name"] = "Maths | Lab"
    rows[2]["subject_name"] = "Maths | Lab"
    rows[1]["midterm_marks"] = None
    rows[3]["midterm_marks"] = None

    presentation = build_presentation(COLUMNS, rows, truncated=True)

    block = _blocks(presentation)[0]
    assert len(block.table_rows) == 2
    assert block.table_rows[1][2] == "-"
    assert "Maths / Lab" in presentation.text
    assert "row limit" in presentation.text


def test_a_semester_filter_titles_the_sheet_even_when_the_query_did_not_select_the_semester():
    """Live-repro'd: the model left semester_id out of the SELECT, so the
    mark sheet had no "Semester 1" heading although the question said so."""
    columns = [c for c in COLUMNS if c != "semester_id"]
    rows = [{k: v for k, v in row.items() if k != "semester_id"} for row in _subject_rows()]

    presentation = build_presentation(columns, rows, filters={"semester_id": "1"})

    assert _blocks(presentation)[0].title == "Semester 1"
    assert ("Student code", "BNTC1520001") in _details(presentation).facts
    assert _blocks(build_presentation(columns, rows))[0].title is None


def test_orchestrator_uses_the_presenter_and_makes_no_answer_llm_call():
    orchestrator = StudentQueryOrchestrator()
    state = orchestrator.prepare("Show subject wise marks for semester 1 of student code = BNTC1520001")
    state.generated_sql = {"sql": "SELECT 1", "parameters": {"core_student_code": "BNTC1520001", "semester": "1"}}
    state.query_result = QueryExecutionResult(columns=COLUMNS, rows=_subject_rows(), truncated=False)

    def must_not_be_called(context):
        raise AssertionError("the answer LLM must not be called for a presentable result")

    state = orchestrator.synthesize_answer(state, AnswerGenerator(must_not_be_called))

    assert state.presentation.kind == "subject_marks"
    assert "English I" in state.answer["answer"]
    assert state.answer_validation.valid


# --- grouped counts ("strength center wise") -------------------------------------------------------

def test_a_grouped_strength_is_a_table_with_the_filters_above_and_the_total_below():
    columns = ["center_name", "strength"]
    rows = [{"center_name": "BTC - PEENYA", "strength": 120}, {"center_name": "NEC - ELECTRONIC CITY", "strength": 3000}]

    presentation = build_presentation(columns, rows, filters={"status": "withdrawn", "core_year": "2026 JUN"})

    assert presentation.kind == "grouped_counts"
    section = presentation.sections[0]
    assert section.facts == [("Status", "withdrawn"), ("Academic year", "2026 JUN")]
    assert section.table_columns == ["Center", "Strength"]
    assert section.table_rows == [["BTC - PEENYA", "120"], ["NEC - ELECTRONIC CITY", "3000"]]
    assert section.summary == [("Total strength", "3,120")]


def test_a_filter_that_is_also_a_group_column_is_not_repeated_as_a_point():
    columns = ["center_name", "program_name", "student_count"]
    rows = [
        {"center_name": "A", "program_name": "DIPLOMA", "student_count": 5},
        {"center_name": "A", "program_name": "CERTIFICATE", "student_count": 2},
    ]

    section = build_presentation(columns, rows, filters={"center_name": "A", "status": "active"}).sections[0]

    assert section.facts == [("Status", "active")]
    assert section.table_columns == ["Center", "Program", "Student count"]


def test_a_truncated_grouped_count_shows_no_total():
    rows = [{"center_name": f"C{n}", "strength": n} for n in range(1, 4)]

    presentation = build_presentation(["center_name", "strength"], rows, truncated=True)

    assert presentation.sections[0].summary == []
    assert "row limit" in presentation.text


def test_single_totals_and_plain_listings_are_still_sentences():
    assert build_presentation(["strength"], [{"strength": 11202}]) is None
    assert build_presentation(["applicant_name", "batch_name"], [{"applicant_name": "X", "batch_name": "Y"}]) is None
    assert build_presentation(["applicant_name", "total_fee"], [{"applicant_name": "X", "total_fee": "Y"}]) is None


def test_group_columns_are_labelled_by_what_they_group_and_missing_values_are_visible():
    columns = ["center_wise", "program_name", "strength"]
    rows = [
        {"center_wise": "BTC - PEENYA", "program_name": None, "strength": 4},
        {"center_wise": "BTC - PEENYA", "program_name": "DIPLOMA", "strength": 9},
    ]

    section = build_presentation(columns, rows).sections[0]

    assert section.table_columns == ["Center", "Program", "Strength"]
    assert section.table_rows[0] == ["BTC - PEENYA", "(not set)", "4"]
