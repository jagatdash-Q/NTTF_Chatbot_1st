"""Tests for the metadata-driven parameter resolution injection pipeline.

Covers: connector handling generalized beyond a single parameter, dynamic
column discovery from schema metadata (no manual mapping), ambiguity
handling, unknown-parameter safety, and minimal-schema/context guarantees.
"""

from app.intelligence.entity_resolver import ExtractedConcept
from app.intelligence.parameter_resolution import ParameterResolver
from app.intelligence.query_analyzer import QueryAnalyzer
from app.intelligence.schema_retriever import SchemaRetriever
from app.graph.root import StudentQueryOrchestrator
from app.metadata.models import ColumnMetadata, TableMetadata
from app.metadata.registry import MetadataRegistry
from app.sql.generator import build_sql_generation_context


def _registry() -> MetadataRegistry:
    registry = MetadataRegistry()
    registry.load()
    return registry


def _analyze(query: str):
    registry = _registry()
    return QueryAnalyzer(registry.student_truth, registry.schema, registry.column_semantics).analyze(query)


def _param_map(analysis) -> dict[str, str]:
    return {parameter.name: parameter.value for parameter in analysis.parameters}


def test_single_parameter_batch_resolves_via_dynamic_schema_discovery():
    analysis = _analyze("List students in batch 2024")

    assert _param_map(analysis) == {"batch": "2024"}
    resolved = {(p.name): (p.table, p.column) for p in analysis.parameters}
    assert resolved["batch"] == ("student_admissions", "batch_name")


def test_multiple_parameters_batch_and_status():
    analysis = _analyze("List students in batch 2024 with status as active")

    assert _param_map(analysis) == {"batch": "2024", "status": "active"}
    assert analysis.clarification is None


def test_status_connector_forms_all_resolve_to_the_same_value():
    forms = [
        "List students with status active",
        "List students with status is active",
        "List students with status as active",
        "List students with status = active",
        "List students with status: active",
    ]
    for query in forms:
        analysis = _analyze(query)
        assert _param_map(analysis).get("status") == "active", query
        assert _param_map(analysis).get("status") != "as", query


def test_multiple_filters_batch_status_and_program():
    analysis = _analyze("List students in batch 2024 with status active in program Fitter")

    assert _param_map(analysis) == {"batch": "2024", "status": "active", "program": "Fitter"}


def test_ambiguous_concept_across_candidate_tables_requests_clarification():
    truth = _registry().student_truth
    tables = [
        TableMetadata(table="table_a", columns=[ColumnMetadata(name="grade", type="VARCHAR(255)")]),
        TableMetadata(table="table_b", columns=[ColumnMetadata(name="grade", type="VARCHAR(255)")]),
    ]

    concepts = [ExtractedConcept(name="grade", value="A")]
    outcome = ParameterResolver(truth).resolve(concepts, tables)

    assert outcome.resolved == []
    assert len(outcome.ambiguous) == 1
    ambiguous = outcome.ambiguous[0]
    assert ambiguous.concept == "grade"
    assert ambiguous.value == "A"
    assert {(c.table, c.column) for c in ambiguous.candidates} == {
        ("table_a", "grade"), ("table_b", "grade"),
    }


def test_unknown_parameter_is_ignored_rather_than_guessed():
    analysis = _analyze("List students in batch 2024 with hobby painting")

    assert _param_map(analysis) == {"batch": "2024"}
    assert "hobby" not in _param_map(analysis)


def test_dynamic_column_discovery_does_not_require_manual_semantic_entry():
    truth = _registry().student_truth
    assert "batch" not in truth.semantic["parameters"]

    tables = [
        TableMetadata(table="student_admissions", columns=[
            ColumnMetadata(name="batch_name", type="VARCHAR(255)"),
            ColumnMetadata(name="batch_id", type="INTEGER"),
        ]),
    ]
    concepts = [ExtractedConcept(name="batch", value="2024")]
    outcome = ParameterResolver(truth).resolve(concepts, tables)

    assert len(outcome.resolved) == 1
    assert outcome.resolved[0].table == "student_admissions"
    assert outcome.resolved[0].column == "batch_name"
    assert outcome.resolved[0].source == "dynamic"


def test_irrelevant_tables_are_not_included_in_selection():
    registry = _registry()
    analysis = QueryAnalyzer(registry.student_truth, registry.schema).analyze(
        "List students in batch 2024 with status as active"
    )
    from app.intelligence.schema_retriever import SchemaRetriever

    selection = SchemaRetriever(registry.schema, registry.student_truth).retrieve(analysis)

    assert set(selection.tables) == {"student_leads", "student_admissions"}
    assert "core_exam_results" not in selection.tables
    assert selection.total_available_tables == 387


def test_minimal_schema_injection_only_includes_selected_columns():
    registry = _registry()
    query = "List students in batch 2024 with status as active"
    analysis = QueryAnalyzer(registry.student_truth, registry.schema).analyze(query)
    from app.intelligence.schema_retriever import SchemaRetriever

    selection = SchemaRetriever(registry.schema, registry.student_truth).retrieve(analysis)
    context = build_sql_generation_context(query, analysis, selection)

    metrics = context["minimal_schema"]["context_metrics"]
    assert metrics["selected_tables"] == 2
    assert metrics["candidate_tables"] == 2
    assert metrics["total_available_tables"] == 387
    assert metrics["selected_columns"] < 19
    assert context["minimal_schema"]["parameters"] == {"batch": "2024", "status": "active"}


def test_parameter_values_survive_all_pipeline_stages_unchanged():
    orchestrator = StudentQueryOrchestrator()
    state = orchestrator.prepare("List students in batch 2024 with status as active")

    assert state.clarification is None
    assert state.llm_context["minimal_schema"]["parameters"] == {"batch": "2024", "status": "active"}
    assert state.llm_context["interpreted_request"]["parameters"] == [
        {"name": "batch", "value": "2024", "table": "student_admissions", "column": "batch_name",
         "operator": "=", "values": None},
        {"name": "status", "value": "active", "table": "student_admissions", "column": "status",
         "operator": "=", "values": None},
    ]


def test_status_as_active_does_not_resolve_to_the_connector_word():
    analysis = _analyze("List students in batch 2024 with status as active")

    status_values = [p.value for p in analysis.parameters if p.name == "status"]
    assert status_values == ["active"]
    assert "as" not in status_values


def test_academic_year_resolves_to_core_year_via_curated_column_alias():
    """End-to-end test 2: a column whose name isn't self-descriptive
    (core_year) is discovered through metadata/manual/column_semantics.yaml
    aliases - no `academic_year -> core_year` entry exists in
    semantic.parameters (the forbidden manually maintained mapping)."""
    registry = _registry()
    assert "academic_year" not in registry.student_truth.semantic["parameters"]

    analysis = _analyze("Show students from academic year 2024 in the Fitter program who are active")

    # "2024" alone is a partial value: the column stores "2024 JAN/JUN/SEP"
    # (value catalog), so it expands to every 2024 session instead of
    # filtering on a literal "2024" that matches nothing.
    year = {p.name: p for p in analysis.parameters}["academic_year"]
    assert year.operator == "in"
    assert year.values == ["2024 JAN", "2024 JUN", "2024 SEP"]
    resolved = {p.name: (p.table, p.column) for p in analysis.parameters}
    assert resolved["academic_year"] == ("student_admissions", "core_year")
    assert resolved["program"] == ("student_admissions", "program_name")


def test_relative_academic_year_requests_clarification_instead_of_guessing():
    """End-to-end test 3: no verified business rule resolves "previous
    academic year" to a concrete value, so the system must ask rather than
    guess."""
    analysis = _analyze("Show students from the previous academic year")

    assert analysis.clarification is not None
    assert "previous academic year" in analysis.clarification
    assert _param_map(analysis) == {}


def test_llm_concept_extractor_is_pluggable_and_shares_the_same_deterministic_resolver():
    """End-to-end test 2 (LLM path): "who are active" implies status=active
    without the word "status" appearing - beyond what regex can infer, but
    the LLM's extracted concept still resolves through the identical
    deterministic schema-search-and-score resolver, and the complete schema
    is never part of what the extractor receives."""
    from app.intelligence.entity_resolver import LLMConceptExtractor

    captured_contexts = []

    def fake_llm_client(context):
        captured_contexts.append(context)
        return {
            "parameters": [
                {"name": "academic_year", "value": "2024", "operator": "="},
                {"name": "program", "value": "Fitter", "operator": "="},
                {"name": "status", "value": "active", "operator": "="},
            ],
        }

    registry = _registry()
    analysis = QueryAnalyzer(
        registry.student_truth, registry.schema, registry.column_semantics,
        concept_extractor=LLMConceptExtractor(fake_llm_client),
    ).analyze("Show students from academic year 2024 in the Fitter program who are active")

    assert analysis.clarification is None
    resolved = {p.name: (p.table, p.column, p.value) for p in analysis.parameters}
    assert resolved["academic_year"] == (
        "student_admissions", "core_year", "2024 JAN and 2024 JUN and 2024 SEP",
    )
    assert resolved["program"] == ("student_admissions", "program_name", "Fitter")
    assert resolved["status"] == ("student_admissions", "status", "active")
    assert "tables" not in captured_contexts[0]
    assert "columns" not in captured_contexts[0]


def test_multiple_values_for_the_same_concept_resolve_to_an_in_list():
    """Live-repro'd bug: "academic year 2026 JUN and 2025 JUN" only ever
    extracted the first year, silently dropping the second."""
    analysis = _analyze("What is the total students count in academic year 2026 JUN and 2025 JUN")

    assert len(analysis.parameters) == 1
    parameter = analysis.parameters[0]
    assert parameter.name == "academic_year"
    assert parameter.operator == "in"
    assert parameter.values == ["2026 JUN", "2025 JUN"]
    assert parameter.table == "student_admissions"
    assert parameter.column == "core_year"


def test_multivalue_parameter_is_exposed_as_a_json_array_in_llm_context():
    registry = _registry()
    query = "What is the total students count in academic year 2026 JUN and 2025 JUN"
    analysis = QueryAnalyzer(registry.student_truth, registry.schema, registry.column_semantics).analyze(query)
    from app.intelligence.schema_retriever import SchemaRetriever

    selection = SchemaRetriever(registry.schema, registry.student_truth).retrieve(analysis)
    context = build_sql_generation_context(query, analysis, selection)

    assert context["minimal_schema"]["parameters"] == {"academic_year": ["2026 JUN", "2025 JUN"]}


def test_and_between_different_filters_is_not_merged_into_one_multivalue_parameter():
    """"batch 2024 and status active" must stay two separate filters, not
    one batch filter with value ["2024", "status", "active"] - "status" is
    itself a recognized concept name for this candidate table set, so it
    guards against being swallowed as a second list item."""
    analysis = _analyze("List students in batch 2024 and status active")

    params = {p.name: p for p in analysis.parameters}
    assert set(params) == {"batch", "status"}
    assert params["batch"].value == "2024"
    assert params["batch"].values is None
    assert params["status"].value == "active"


def test_status_ambiguity_uses_module_context_not_a_guess():
    """End-to-end test 4: a bare 'status' concept could match more than one
    candidate table's status column; the curated Truth Layer entry supplies
    the disambiguating business context (admission status is canonical for
    this module) so the system resolves confidently instead of guessing."""
    analysis = _analyze("Show students with status active")

    assert analysis.clarification is None
    resolved = {p.name: (p.table, p.column) for p in analysis.parameters}
    assert resolved["status"] == ("student_admissions", "status")


def test_semester_resolves_to_exam_results_when_asking_about_results():
    """Live-repro'd bug: "...cgpa...semester one...student code = X" resolved
    "semester" to student_admissions.semester_no (the student's current
    overall semester) instead of core_exam_results.semester_id (which
    semester's exam record) - silently filtering the wrong table, and with
    the wrong value shape ("one" instead of "1"). core_exam_results.
    semester_id is now a candidate too (curated alias in column_semantics.
    yaml), which outranks the admissions column whenever both are in play."""
    analysis = _analyze(
        "Give me the cgpa for semester one for student code = BNTC1520001"
    )

    resolved = {p.name: (p.table, p.column, p.value) for p in analysis.parameters}
    assert resolved["semester"] == ("core_exam_results", "semester_id", "1")


def test_semester_resolves_to_admissions_when_no_results_are_requested():
    """The same bare "semester" concept must still resolve to
    student_admissions.semester_no for a pure listing/count query that never
    brings core_exam_results into the candidate table set."""
    analysis = _analyze("List students in semester 2")

    resolved = {p.name: (p.table, p.column, p.value) for p in analysis.parameters}
    assert resolved["semester"] == ("student_admissions", "semester_no", "2")


def test_enum_value_is_snapped_to_its_full_stored_token():
    """Live-repro'd bug: the DB stores student_admissions.status as a single
    snake_case ENUM token ("temporary_enrolled"), but the user types it as
    ordinary words ("temporary enrolled status"). Extraction only ever
    captures a prefix of what the user typed (it stops early, never starts
    late), so "temporary" - or even the full "temporary enrolled" with a
    space instead of an underscore - must snap to the one real enum value
    it uniquely identifies, not filter on a value that can never match a
    real row."""
    analysis = _analyze("Show students with status temporary")

    resolved = {p.name: (p.table, p.column, p.value) for p in analysis.parameters}
    assert resolved["status"] == ("student_admissions", "status", "temporary_enrolled")


def test_allowed_values_reach_the_llm_schema_for_true_and_curated_enums():
    """Live-repro'd bug: the LLM reused "temporary_enrolled" (student_
    admissions.status's real ENUM value) for student_payments.payment_type
    too, whose real value is the differently-spelled "temporary_enroll" -
    payment_type is a VARCHAR, not a DB ENUM, so schema extraction can't
    discover its values on its own. Both a true ENUM (status) and a
    VARCHAR with curated allowed_values (payment_type) must show up in the
    minimal schema so the LLM can ground a literal instead of guessing."""
    registry = _registry()
    query = "Show students with temporary_enrolled status and a temporary_enroll payment"
    analysis = QueryAnalyzer(registry.student_truth, registry.schema, registry.column_semantics).analyze(query)
    selection = SchemaRetriever(registry.schema, registry.student_truth, registry.column_semantics).retrieve(analysis)

    status_column = next(c for c in selection.tables["student_admissions"] if c["name"] == "status")
    assert "temporary_enrolled" in status_column["allowed_values"]

    payment_column = next(c for c in selection.tables["student_payments"] if c["name"] == "payment_type")
    assert "temporary_enroll" in payment_column["allowed_values"]
    assert "temporary_enrolled" not in payment_column["allowed_values"]


def test_student_detail_exposes_stream_and_academic_year_not_just_batch_and_status():
    """Live-repro'd bug: asking for "program, stream, academic year" in a
    student-detail query had no stream_name/core_year in student_listing's
    output_columns at all, so the LLM improvised - showing batch_name as
    "stream" and status ("passout") as "academic year". Both real columns
    must be in scope so the LLM never has to substitute a different column
    for a field it wasn't given."""
    registry = _registry()
    analysis = QueryAnalyzer(registry.student_truth, registry.schema, registry.column_semantics).analyze(
        "Can you give me basic student detail like name, program, stream, academic year, "
        "father name who has student code = BNTC1520001"
    )
    selection = SchemaRetriever(registry.schema, registry.student_truth, registry.column_semantics).retrieve(analysis)

    admission_columns = {c["name"] for c in selection.tables["student_admissions"]}
    assert {"stream_name", "core_year", "batch_name", "status"} <= admission_columns


def test_academic_year_column_carries_its_curated_description_to_the_llm():
    """The curated description for core_year (which says it means "academic
    year") must actually reach the LLM's schema context, not just live in
    the YAML - otherwise a self-explanatory-looking but wrongly-named
    column like core_year has no way to be understood correctly."""
    registry = _registry()
    analysis = QueryAnalyzer(registry.student_truth, registry.schema, registry.column_semantics).analyze(
        "Show students from academic year 2024"
    )
    selection = SchemaRetriever(registry.schema, registry.student_truth, registry.column_semantics).retrieve(analysis)

    core_year_column = next(c for c in selection.tables["student_admissions"] if c["name"] == "core_year")
    assert "academic year" in core_year_column["description"].lower()


def test_referral_query_brings_in_student_referrals_and_lead_master():
    """Live-repro'd bug: "who has given referral for this student..." had no
    catalog entry at all touching student_referrals/lead_master, so the
    schema never exposed the referrer's name or the lead-source type."""
    registry = _registry()
    analysis = QueryAnalyzer(registry.student_truth, registry.schema, registry.column_semantics).analyze(
        "Who has given referral for this student with enquiry number E25-000003"
    )
    selection = SchemaRetriever(registry.schema, registry.student_truth, registry.column_semantics).retrieve(analysis)

    assert {"student_referrals", "lead_master"} <= set(selection.tables)
    referral_columns = {c["name"] for c in selection.tables["student_referrals"]}
    assert "name" in referral_columns
    lead_master_columns = {c["name"] for c in selection.tables["lead_master"]}
    assert "lead_name" in lead_master_columns


def test_ambiguous_enum_prefix_is_left_unchanged_rather_than_guessed():
    """'not' is a prefix of both not_interested and not_eligible - with no
    unique match, the raw value must be left as-is rather than guessing
    which one the user meant."""
    from app.intelligence.parameter_resolution import _snap_to_enum_value

    enum_values = ["not_interested", "not_eligible", "active"]
    assert _snap_to_enum_value("not", enum_values) == "not"
    assert _snap_to_enum_value("active", enum_values) == "active"
    assert _snap_to_enum_value("not_interested", enum_values) == "not_interested"
