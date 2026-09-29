from app.database.executor import QueryExecutionResult
from app.response.answer_validator import AnswerFaithfulnessValidator


def _result(rows, truncated=False):
    columns = list(rows[0].keys()) if rows else []
    return QueryExecutionResult(columns=columns, rows=rows, truncated=truncated)


def test_empty_result_with_no_results_language_passes():
    result = _result([])
    outcome = AnswerFaithfulnessValidator().validate(result, "No students matched that batch.")
    assert outcome.valid


def test_empty_result_with_a_fabricated_answer_fails():
    result = _result([])
    outcome = AnswerFaithfulnessValidator().validate(
        result, "There are 12 students in batch 2024 with active status."
    )
    assert not outcome.valid
    assert "empty result" in outcome.reasons[0]


def test_answer_grounded_in_a_real_row_value_passes():
    result = _result([{"applicant_name": "Ravi Kumar", "batch_name": "2024"}])
    outcome = AnswerFaithfulnessValidator().validate(
        result, "Ravi Kumar is a student in batch 2024."
    )
    assert outcome.valid


def test_generic_answer_with_no_row_values_fails():
    result = _result([{"applicant_name": "Ravi Kumar", "batch_name": "2024"}])
    outcome = AnswerFaithfulnessValidator().validate(
        result, "Here are the students matching your search criteria."
    )
    assert not outcome.valid
    assert any("does not reference" in reason for reason in outcome.reasons)


def test_mismatched_stated_count_fails_when_not_truncated():
    result = _result([{"applicant_name": "Ravi Kumar"}, {"applicant_name": "Anita Singh"}])
    outcome = AnswerFaithfulnessValidator().validate(
        result, "There are 5 students: Ravi Kumar and Anita Singh."
    )
    assert not outcome.valid
    assert any("row_count" in reason for reason in outcome.reasons)


def test_stated_count_matching_row_count_passes():
    result = _result([{"applicant_name": "Ravi Kumar"}, {"applicant_name": "Anita Singh"}])
    outcome = AnswerFaithfulnessValidator().validate(
        result, "There are 2 students: Ravi Kumar and Anita Singh."
    )
    assert outcome.valid


def test_comma_formatted_count_is_recognized_as_grounded():
    """Live-repro'd bug: the answer LLM wrote "5,556" (thousands separator)
    for an aggregate row value of 5556 - exact substring/int matching must
    not treat that as a fabricated, ungrounded number. The answer also
    restates its own filter ("2026 JUN"), which is legitimate even though
    that value isn't itself a selected column - passed as filter_values,
    exactly as app.graph.root.synthesize_answer does."""
    result = _result([{"total_students": 5556}])
    outcome = AnswerFaithfulnessValidator().validate(
        result, "There are 5,556 total students in academic year 2026 JUN.",
        filter_values=["2026 JUN"],
    )
    assert outcome.valid


def test_answer_restating_its_own_filter_value_is_not_flagged():
    result = _result([{"total_students": 0}])
    outcome = AnswerFaithfulnessValidator().validate(
        result, "No students were found in batch 2024.", filter_values=["2024"],
    )
    assert outcome.valid


def test_comma_in_a_name_is_not_treated_as_a_grouped_number():
    result = _result([{"applicant_name": "Kumar, Ravi"}])
    outcome = AnswerFaithfulnessValidator().validate(result, "Kumar, Ravi is a student.")
    assert outcome.valid


def test_decimal_value_is_not_split_into_separate_fabricated_numbers():
    """Live-repro'd bug: the answer wrote "8.60" for a stored cgpa of "8.6"
    (the LLM zero-padded it) - `\\b\\d+\\b` treated the "." as a boundary
    and read "8.60" as two separate numbers {8, 60}, and 60 matched nothing
    in the row data, flagging a false fabrication. A decimal must be
    compared as one float value, and differing trailing-zero formatting of
    the same value must compare equal."""
    result = _result([{"semester_id": 5, "cgpa": "8.6"}])
    outcome = AnswerFaithfulnessValidator().validate(
        result, "In semester 5, the CGPA was 8.60."
    )
    assert outcome.valid


def test_a_restated_calendar_date_is_not_read_as_fabricated_numbers():
    """Live-repro'd bug: an attendance answer restating the question's own
    date ("...arrived late on 2026-09-01.") had its hyphen-separated pieces
    (2026, 09, 01) each read as a separate invented number by \\b\\d+\\b -
    none of which is the row's own aggregate value, so a genuinely correct
    answer was flagged as unfaithful. A date is not a fabricated count."""
    result = _result([{"strength": 757}])
    outcome = AnswerFaithfulnessValidator().validate(
        result, "757 trainees arrived late on 2026-09-01."
    )
    assert outcome.valid


def test_a_number_the_user_named_in_the_question_is_not_treated_as_fabricated():
    """Live-repro'd bug: "How many trainees are absent continuously for 5
    days?" is answered with "...absent continuously for 5 days" - the
    attendance query catalog writes N as an inline SQL literal (there is no
    extracted "days" parameter to put in filter_values), so "5" only ever
    appears in the user's own question, never in filter_values."""
    result = _result([{"strength": 20015}])
    outcome = AnswerFaithfulnessValidator().validate(
        result, "There are 20,015 trainees who are absent continuously for 5 days.",
        question_text="How many trainees are absent continuously for 5 days?",
    )
    assert outcome.valid


def test_a_null_single_aggregate_is_not_treated_as_ungrounded():
    """Live-repro'd bug: "average late arrivals over the last week" run over
    a week with no late arrivals at all returns AVG() of an empty group -
    SQL NULL, stringified as "None" - which the answer honestly reports as
    "null"/"zero", never matching the stored "none" text."""
    result = _result([{"avg_late_per_day": None}])
    outcome = AnswerFaithfulnessValidator().validate(
        result, "The average number of late arrivals over the last week is null."
    )
    assert outcome.valid


def test_a_null_single_aggregate_without_no_value_language_fails():
    result = _result([{"avg_late_per_day": None}])
    outcome = AnswerFaithfulnessValidator().validate(
        result, "The average number of late arrivals over the last week is 4.2."
    )
    assert not outcome.valid


def test_a_zero_aggregate_worded_as_zero_is_grounded():
    """Live-repro'd bug: "Zero trainees arrived late today." for a single
    aggregate row {"strength": 0} was flagged as ungrounded - the digit "0"
    never appears in the answer text, only the word "zero"."""
    result = _result([{"strength": 0}])
    outcome = AnswerFaithfulnessValidator().validate(result, "Zero trainees arrived late today.")
    assert outcome.valid


def test_a_zero_aggregate_worded_as_a_flat_negation_is_grounded():
    """Live-repro'd bug: "No trainees arrived late today." (no number word
    at all, just a negation) for {"strength": 0} was flagged as ungrounded."""
    result = _result([{"strength": 0}])
    outcome = AnswerFaithfulnessValidator().validate(result, "No trainees arrived late today.")
    assert outcome.valid


def test_a_nonzero_answer_for_a_zero_aggregate_still_fails():
    result = _result([{"strength": 0}])
    outcome = AnswerFaithfulnessValidator().validate(result, "5 trainees arrived late today.")
    assert not outcome.valid


def test_truncated_result_does_not_penalize_a_count_below_total():
    result = _result([{"applicant_name": "Ravi Kumar"}], truncated=True)
    outcome = AnswerFaithfulnessValidator().validate(
        result, "Ravi Kumar is among the students shown (more results were truncated)."
    )
    assert outcome.valid


def test_short_aggregate_count_is_grounded_in_a_single_value_result():
    """Live-repro'd bug: "There are 28 distinct failed students..." for a
    result of [{'failed': 28}] was flagged as not grounded because a
    two-character value is below the generic minimum grounding length."""
    result = _result([{"failed_students": 28}])

    good = AnswerFaithfulnessValidator().validate(
        result, "There are 28 students who failed semester 3.", filter_values=["3"])
    wrong = AnswerFaithfulnessValidator().validate(
        result, "There are 8 students who failed semester 3.", filter_values=["3"])

    assert good.valid
    assert not wrong.valid
