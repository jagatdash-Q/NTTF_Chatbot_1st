"""Live-repro'd bug: "Can you give me student detail who has student code =
BNTC1520001" matched none of student_listing's literal configured terms (it
says "student", not "students"), so deterministic intent detection returned
None and the query never reached SQL generation at all. student_listing's
terms now also cover "student detail(s)" phrasing directly (see
metadata/manual/core_student.yaml), so that exact query is deterministic
again - these tests instead use a phrasing that genuinely matches no
catalog term ("admission ... student code"), to keep covering the LLM
fallback and its validation against the known catalog for whatever future
phrasing the term list still doesn't cover.
"""

from app.intelligence.entity_resolver import LLMIntentClassifier
from app.intelligence.query_analyzer import QueryAnalyzer
from app.metadata.registry import MetadataRegistry

_UNMATCHED_PHRASING = "Can you fetch the admission whose student code = BNTC1520001"


def _registry() -> MetadataRegistry:
    registry = MetadataRegistry()
    registry.load()
    return registry


def test_without_a_classifier_ambiguous_phrasing_still_asks_for_clarification():
    """Default behavior (no classifier passed) is unchanged."""
    registry = _registry()
    analysis = QueryAnalyzer(registry.student_truth, registry.schema, registry.column_semantics).analyze(
        _UNMATCHED_PHRASING
    )

    assert analysis.intent is None
    assert analysis.clarification is not None


def test_llm_classifier_resolves_phrasing_the_term_list_does_not_cover():
    registry = _registry()

    def fake_client(context):
        assert "candidate_intents" in context
        assert set(context["candidate_intents"]) == set(registry.student_truth.query_catalog.keys())
        return {"intent": "student_listing"}

    classifier = LLMIntentClassifier(fake_client)
    analysis = QueryAnalyzer(
        registry.student_truth, registry.schema, registry.column_semantics,
        intent_classifier=classifier,
    ).analyze(_UNMATCHED_PHRASING)

    assert analysis.intent == "student_listing"
    resolved = {p.name: (p.table, p.column, p.value) for p in analysis.parameters}
    assert resolved["core_student_code"] == ("student_admissions", "core_student_code", "BNTC1520001")


def test_llm_classifier_response_is_validated_against_the_known_catalog():
    """The classifier must never be trusted to invent an intent - only an
    exact match against the real query_catalog keys is accepted."""
    registry = _registry()

    def fake_client(context):
        return {"intent": "some_intent_the_model_made_up"}

    classifier = LLMIntentClassifier(fake_client)
    analysis = QueryAnalyzer(
        registry.student_truth, registry.schema, registry.column_semantics,
        intent_classifier=classifier,
    ).analyze(_UNMATCHED_PHRASING)

    assert analysis.intent is None
    assert analysis.clarification is not None


def test_classifier_is_not_consulted_when_deterministic_matching_already_succeeds():
    """No LLM call on the normal/unambiguous path, even with a classifier
    configured - it's a fallback, not a replacement."""
    registry = _registry()
    calls = []

    def spy_client(context):
        calls.append(context)
        return {"intent": "student_result"}

    classifier = LLMIntentClassifier(spy_client)
    analysis = QueryAnalyzer(
        registry.student_truth, registry.schema, registry.column_semantics,
        intent_classifier=classifier,
    ).analyze("List students in batch 2024")

    assert analysis.intent == "student_listing"
    assert calls == []
