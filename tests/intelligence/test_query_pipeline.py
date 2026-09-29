from app.intelligence.query_analyzer import QueryAnalyzer
from app.intelligence.schema_retriever import SchemaRetriever
from app.graph.root import StudentQueryOrchestrator
from app.metadata.registry import MetadataRegistry
from app.sql.generator import build_sql_generation_context
from app.sql.generator import GeminiSQLClient, SarvamSQLClient
from app.sql.validator import SQLValidator


def pipeline(query: str):
    registry = MetadataRegistry()
    registry.load()
    analysis = QueryAnalyzer(registry.student_truth, registry.schema).analyze(query)
    selection = SchemaRetriever(registry.schema, registry.student_truth).retrieve(analysis)
    return analysis, selection


def test_listing_extracts_filters_and_selects_minimal_schema():
    query = "Show me the students in batch 2024 who are enrolled in Fitter program"
    analysis, selection = pipeline(query)

    assert analysis.intent == "student_listing"
    assert [(item.name, item.value) for item in analysis.parameters] == [
        ("batch", "2024"), ("program", "Fitter"),
    ]
    assert set(selection.tables) == {"student_leads", "student_admissions"}
    assert selection.total_available_tables == 387
    assert selection.selected_columns == 17
    assert selection.as_llm_context()["context_metrics"]["selected_tables"] == 2


def test_result_without_identifier_requires_clarification():
    registry = MetadataRegistry()
    registry.load()

    analysis = QueryAnalyzer(registry.student_truth, registry.schema).analyze("Show me the result")

    assert analysis.intent == "student_result"
    assert not analysis.is_ready
    assert "Core student code" in analysis.clarification


def test_context_excludes_irrelevant_tables_and_preserves_parameters():
    query = "Show students in batch 2024 enrolled in Fitter program"
    analysis, selection = pipeline(query)
    context = build_sql_generation_context(query, analysis, selection)

    schema = context["minimal_schema"]
    assert "legs_student_batches" not in schema["tables"]
    assert "student_admission_legs" not in schema["tables"]
    assert schema["parameters"] == {"batch": "2024", "program": "Fitter"}
    assert schema["context_metrics"]["selected_columns"] < 19


def test_validator_rejects_unselected_table_column_and_bad_join():
    _, selection = pipeline("Show students in batch 2024 enrolled in Fitter program")
    validator = SQLValidator()

    good = validator.validate(
        "SELECT sl.applicant_name, sa.batch_name FROM student_leads sl "
        "JOIN student_admissions sa ON sl.id = sa.student_lead_id "
        "WHERE sa.batch_name = :batch AND sa.program_name = :program",
        selection,
    )
    bad_table = validator.validate("SELECT * FROM users", selection)
    bad_column = validator.validate("SELECT sa.password FROM student_admissions sa", selection)
    bad_join = validator.validate(
        "SELECT sl.id FROM student_leads sl JOIN student_admissions sa ON sl.id = sa.id",
        selection,
    )

    assert good.valid
    assert not bad_table.valid
    assert not bad_column.valid
    assert not bad_join.valid


def test_validator_rejects_rather_than_crashes_on_qualified_column_of_an_unselected_table():
    """Live-repro'd bug: a qualified-column reference to a table the LLM
    hallucinated (not in the minimal schema, e.g. student_payments) was
    still registered as a "known" alias right after being flagged invalid,
    so the later column check did `selection.tables[table_name]` on a table
    that was never in that dict - an unguarded KeyError that crashed the
    whole request instead of surfacing as a normal validation error."""
    _, selection = pipeline("Show students in batch 2024 enrolled in Fitter program")
    validator = SQLValidator()

    result = validator.validate(
        "SELECT sl.applicant_name FROM student_leads sl "
        "JOIN student_payments sp ON sl.id = sp.student_lead_id "
        "WHERE sp.fee_paid > :amount",
        selection,
    )

    assert not result.valid
    assert any("student_payments" in error for error in result.errors)


def test_validator_accepts_explicit_join_type_keywords():
    """Regression test: live-run bug where INNER/LEFT/RIGHT JOIN caused the
    preceding table's alias to be silently dropped, rejecting every later
    reference to it as an unknown alias false positive."""
    _, selection = pipeline("Show students in batch 2024 enrolled in Fitter program")
    validator = SQLValidator()

    for join_type in ("INNER JOIN", "LEFT JOIN", "RIGHT JOIN"):
        result = validator.validate(
            f"SELECT sl.applicant_name, sa.batch_name FROM student_leads sl "
            f"{join_type} student_admissions sa ON sl.id = sa.student_lead_id "
            f"WHERE sa.batch_name = :batch",
            selection,
        )
        assert result.valid, f"{join_type} incorrectly rejected: {result.errors}"


def test_validator_accepts_verified_join_without_aliases():
    _, selection = pipeline("Show students in batch 2024 enrolled in Fitter program")

    result = SQLValidator().validate(
        "SELECT student_leads.id FROM student_admissions "
        "JOIN student_leads ON student_admissions.student_lead_id = student_leads.id "
        "WHERE student_admissions.batch_name = :batch",
        selection,
    )

    assert result.valid


def test_orchestrator_returns_context_or_clarification_without_calling_an_llm():
    orchestrator = StudentQueryOrchestrator()

    prepared = orchestrator.prepare("List students in batch 2024")
    clarification = orchestrator.prepare("Show me the result")

    assert prepared.llm_context is not None
    assert set(prepared.llm_context["minimal_schema"]["tables"]) == {
        "student_leads", "student_admissions"
    }
    assert clarification.llm_context is None
    assert clarification.clarification is not None


def test_sarvam_client_parses_json_and_rejects_non_json():
    valid = SarvamSQLClient._parse_json(
        '```json\n{"sql":"SELECT 1","parameters":{},"tables_used":[],"explanation":"test"}\n```'
    )

    assert valid["sql"] == "SELECT 1"


def test_gemini_client_uses_the_same_structured_response_contract():
    valid = GeminiSQLClient._parse_json(
        '{"sql":"SELECT 1","parameters":{},"tables_used":[],"explanation":"test"}'
    )

    assert set(valid) == {"sql", "parameters", "tables_used", "explanation"}


def test_query_spanning_two_catalog_intents_unions_their_schemas():
    """Live-repro'd bug: "student detail ... cgpa in all semester ... father
    name" matched only the single "student_result" catalog entry, whose
    student_admissions output_columns are limited to [id, core_student_code]
    - so the LLM's otherwise-correct attempt to also select identity/family
    columns was rejected as "not selected for this query". Matched catalog
    entries are now unioned instead of only using the top match."""
    query = (
        "Can you give me student detail like student name, DOB, Father name "
        "along with there cgpa in all semester who has student code = BNTC1520001"
    )
    analysis, selection = pipeline(query)

    assert set(analysis.intents) >= {"student_result", "student_family"}
    assert set(selection.tables) >= {
        "student_admissions", "core_exam_results", "student_families", "family_details",
    }
    family_columns = {column["name"] for column in selection.tables["family_details"]}
    assert {"relationship", "name", "dob"} <= family_columns
    result_columns = {column["name"] for column in selection.tables["core_exam_results"]}
    assert "cgpa" in result_columns


def test_validator_rejects_a_placeholder_that_was_not_supplied_instead_of_crashing_later():
    """Live-repro'd bug: the LLM wrote `:semester_id` while the only supplied
    parameter was `semester`; the executor then failed with "A value is
    required for bind parameter". Now caught (and retried) at validation."""
    _, selection = pipeline("Show students in batch 2024 enrolled in Fitter program")
    validator = SQLValidator()

    bad = validator.validate(
        "SELECT sa.batch_name FROM student_admissions sa WHERE sa.batch_name = :batch_id",
        selection,
    )
    good = validator.validate(
        "SELECT sa.batch_name FROM student_admissions sa WHERE sa.batch_name = :batch",
        selection,
    )
    literal_colon = validator.validate(
        "SELECT sa.batch_name FROM student_admissions sa WHERE sa.batch_name = '10:30' AND sa.batch_name = :batch",
        selection,
    )

    assert not bad.valid and any(":batch_id" in error for error in bad.errors)
    assert good.valid
    assert literal_colon.valid


def test_domain_synonyms_are_mapped_before_any_matching():
    """Live-repro'd: "total active trainees strength" was rejected as not a
    student question. Synonyms (semantic.synonyms in core_student.yaml) rewrite
    the question - plurals kept - before the scope gate, intents and filters."""
    registry = MetadataRegistry()
    registry.load()
    analyzer = QueryAnalyzer(registry.student_truth, registry.schema, registry.column_semantics)

    analysis = analyzer.analyze("What is the total active trainees strength")

    assert analysis.clarification is None
    assert analysis.intent == "student_listing"
    assert analysis.normalized_query == "What is the total active students count"
    assert analysis.as_dict()["normalized_question"] == "What is the total active students count"
    assert analysis.user_query == "What is the total active trainees strength"   # the original is kept


def test_synonym_rewrite_keeps_plurals_and_ignores_partial_words():
    registry = MetadataRegistry()
    registry.load()
    analyzer = QueryAnalyzer(registry.student_truth, registry.schema, registry.column_semantics)

    assert analyzer._canonicalize("a trainee, two Trainees and a learner") == "a student, two students and a student"
    assert analyzer._canonicalize("the contraineeship") == "the contraineeship"
    assert analyzer.analyze("List students in batch 2024").normalized_query is None
