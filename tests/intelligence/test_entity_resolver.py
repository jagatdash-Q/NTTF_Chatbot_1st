from app.intelligence.entity_resolver import (
    ExtractedConcept,
    LLMConceptExtractor,
    RegexConceptExtractor,
)
from app.metadata.registry import MetadataRegistry


def _extractor() -> RegexConceptExtractor:
    registry = MetadataRegistry()
    registry.load()
    return RegexConceptExtractor(registry.student_truth)


def test_generic_connector_extraction_works_for_a_concept_with_no_curated_entry():
    extractor = _extractor()

    concepts = extractor.extract("List records with grade as A", hint_vocabulary=[])

    assert concepts == [ExtractedConcept(name="grade", value="A")]


def test_stopwords_are_never_treated_as_concepts():
    extractor = _extractor()

    concepts = extractor.extract("List students who is active", hint_vocabulary=[])

    assert all(concept.name != "who" for concept in concepts)


def test_hint_vocabulary_enables_bare_juxtaposition_without_a_connector():
    extractor = _extractor()

    concepts = extractor.extract("List students in semester 3", hint_vocabulary=["semester"])
    assert [(c.name, c.value) for c in concepts] == [("semester", "3")]

    # Without the hint, a bare word next to a number is not assumed to be a
    # filter - avoids false positives on ordinary sentence structure.
    concepts_without_hint = extractor.extract("List students in semester 3", hint_vocabulary=[])
    assert concepts_without_hint == []


def test_hint_vocabulary_juxtaposition_does_not_swallow_a_leading_filler_word():
    """Live-repro'd bug: with hint_vocabulary=["cgpa", "semester"], "...cgpa
    in all semester who has..." previously extracted cgpa="in all" and
    semester="who has" - a bare-juxtaposition match starting on a filler
    word ("in", "who") with no real value ever mentioned. The primary
    value's first token must be guarded against generic filler words even
    when there's no explicit connector to anchor the match."""
    extractor = _extractor()

    concepts = extractor.extract(
        "cgpa in all semester who has student code = BNTC1520001",
        hint_vocabulary=["cgpa", "semester"],
    )

    names_values = {c.name: c.value for c in concepts}
    assert "cgpa" not in names_values
    assert "semester" not in names_values
    assert names_values["core_student_code"] == "BNTC1520001"


def test_student_with_code_phrasing_is_recognized_as_the_student_code():
    """Live-repro'd bug: "student with code X" (and "having"/"whose code")
    has "student" and "code" separated by a connecting word the shared
    <phrase> [connector] <value> grammar doesn't span, so with only "student
    code" as an alias this extracted nothing at all - the SQL generator then
    invented a :core_student_code parameter that was never supplied, and the
    validator correctly rejected it as unsupplied every retry."""
    extractor = _extractor()

    for phrasing in ("with code", "having code", "whose code"):
        concepts = extractor.extract(
            f"is this student {phrasing} TTC0825020 present today", hint_vocabulary=[],
        )
        names_values = {c.name: c.value for c in concepts}
        assert names_values.get("core_student_code") == "TTC0825020", phrasing

    # A bare "code" next to a punching-code-SHAPED value (pure digits) must
    # stay unresolved as core_student_code - the word "code" alone is
    # ambiguous, and nothing here disambiguates it in this direction.
    concepts = extractor.extract("what is the punching code for 1108867", hint_vocabulary=[])
    assert "core_student_code" not in {c.name for c in concepts}

    # A bare "code" next to a value SHAPED like a real student code (letters
    # then digits, e.g. TTC0825020 - never how a punching_code looks, see
    # test_a_bare_student_code_shape_is_recognized_without_any_anchor_word)
    # is unambiguous by shape even though the word "code" alone is not -
    # this is intentionally now recognized, not a regression.
    concepts = extractor.extract("what is the punching code for TTC0825020", hint_vocabulary=[])
    assert {c.name: c.value for c in concepts}.get("core_student_code") == "TTC0825020"


def test_a_bare_student_code_shape_is_recognized_without_any_anchor_word():
    """Live-repro'd bug: "is this student present today NEC0525039" gives
    the code with NO anchor word anywhere ("code", "student code", ...) -
    just typed at the end of the sentence - so nothing extracted it, the
    SQL generator invented an unsupplied :core_student_code placeholder,
    and the validator correctly rejected it every retry. A real
    core_student_code's own shape (letters then digits, no separator) is
    unambiguous in this domain - verified to collide with none of
    batch_name/enquiry_number/ticket_number/punching_code/section_name/
    center_name/program_name/stream_name/core_year/status/residency_type
    across every stored value of each."""
    extractor = _extractor()

    concepts = extractor.extract("is this student present today NEC0525039", hint_vocabulary=[])
    assert {c.name: c.value for c in concepts}.get("core_student_code") == "NEC0525039"

    # A pure-digit value (how a punching_code actually looks) must NOT be
    # guessed as a core_student_code by this shape.
    concepts = extractor.extract("is this student present today 1108867", hint_vocabulary=[])
    assert "core_student_code" not in {c.name for c in concepts}


def test_a_status_word_right_before_student_is_recognized_without_the_word_status():
    """Live-repro'd bug: "What is the total active student strength" names
    no "status" word at all - the SQL generator produced an unfiltered
    COUNT(*) over every status (26,337) instead of just active students,
    because nothing extracted status="active". Scoped to the enum's own
    words immediately before student/trainee/learner, not a bare "active"
    anywhere, so ordinary unrelated uses of these words aren't affected."""
    extractor = _extractor()

    concepts = extractor.extract("What is the total active student strength", hint_vocabulary=[])
    names_values = {c.name: c.value for c in concepts}
    assert names_values.get("status") == "active"

    # "status active" (the word "status" present) must keep working exactly
    # as before - this is additive, not a replacement of that grammar.
    concepts = extractor.extract("students with status active", hint_vocabulary=[])
    assert {c.name: c.value for c in concepts}.get("status") == "active"

    # A word from the bare list used away from "student"/"trainee" must not
    # be guessed as a status filter.
    concepts = extractor.extract("the office is closed today", hint_vocabulary=[])
    assert "status" not in {c.name for c in concepts}


def test_a_status_word_right_before_a_grouping_dimension_is_also_recognized():
    """Live-repro'd bug: "which ACTIVE center has the lowest attendance
    today" - "active" modifies "center" (the grouping dimension), not
    "student" - extracted nothing, so the query ran with no status filter
    at all and could return a center with zero ACTIVE students as if it
    legitimately had the lowest attendance."""
    extractor = _extractor()

    for phrase in (
        "which active center has the lowest attendance today",
        "active program wise strength",
        "active stream wise count",
        "active batch",
        "active section",
        "active group",
    ):
        concepts = extractor.extract(phrase, hint_vocabulary=[])
        assert {c.name: c.value for c in concepts}.get("status") == "active", phrase


def test_a_bare_academic_year_shape_is_recognized_without_the_words_academic_year():
    """Live-repro'd bug: "...in 2026 jun and live in hostel" names no
    "academic year" concept word at all, so the value was silently dropped
    and the SQL generator invented a bare year literal ('2026', missing the
    month) that matches no stored core_year value - a real "students in
    hostel in 2026 JUN" question came back "no matching records"."""
    extractor = _extractor()

    concepts = extractor.extract(
        "how many boys and girls in 2026 jun and live in hostel", hint_vocabulary=[],
    )
    names_values = {c.name: c.value for c in concepts}
    # Underscore, not a space - ":academic year" is not a legal SQL bound
    # parameter name (live-repro'd: a first version of this fix used the
    # spaced name and every generated query was then rejected as using an
    # unsupplied ":academic_year" placeholder, since no valid SQL could ever
    # spell out the real ":academic year" parameter name it was given).
    assert names_values.get("academic_year") == "2026 JUN"

    # "academic year 2026 JUN" (the phrase present) must not be extracted
    # twice - once by the existing hint-vocabulary path and once more by
    # this new bare-shape step.
    concepts = extractor.extract(
        "strength in academic year 2026 JUN", hint_vocabulary=["academic year"],
    )
    assert sum(1 for c in concepts if c.value == "2026 JUN") == 1

    # A bare 4-digit year with no month must stay unresolved by this new
    # step (that's a batch year, not an academic year/month value).
    concepts = extractor.extract("show students in batch 2024", hint_vocabulary=["batch"])
    assert "academic_year" not in {c.name for c in concepts}


def test_relative_temporal_expression_requires_resolution_instead_of_guessing():
    extractor = _extractor()

    concepts = extractor.extract("Show students from the previous academic year", hint_vocabulary=[])

    assert len(concepts) == 1
    concept = concepts[0]
    assert concept.name == "academic_year"
    assert concept.requires_resolution is True
    assert concept.normalized_value is None
    assert "previous" in concept.value


def test_llm_extractor_returns_the_same_contract_as_the_regex_extractor():
    def fake_client(context: dict) -> dict:
        assert "known_domain_concepts" in context
        assert "user_question" in context
        return {"parameters": [{"name": "status", "value": "active", "operator": "="}]}

    extractor = LLMConceptExtractor(fake_client)

    concepts = extractor.extract("students who are active", hint_vocabulary=["status"])

    assert concepts == [ExtractedConcept(name="status", value="active", source="llm")]


def test_llm_extractor_never_receives_the_generated_schema():
    captured = {}

    def fake_client(context: dict) -> dict:
        captured.update(context)
        return {"parameters": []}

    LLMConceptExtractor(fake_client).extract("query", hint_vocabulary=["batch", "status"])

    assert set(captured) == {"user_question", "known_domain_concepts", "instructions"}
    assert captured["known_domain_concepts"] == ["batch", "status"]
