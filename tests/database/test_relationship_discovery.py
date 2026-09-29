from app.database.relationship_discovery import RelationshipGraph, discover_fk_relationships
from app.metadata.registry import MetadataRegistry


def _graph() -> RelationshipGraph:
    registry = MetadataRegistry()
    registry.load()
    return RelationshipGraph.from_metadata(registry.schema, registry.student_truth)


def test_explicit_database_fk_relationship_is_discovered():
    registry = MetadataRegistry()
    registry.load()

    relationships = discover_fk_relationships(registry.schema)

    assert any(
        r.from_table == "student_certificates"
        and r.from_column == "student_admission_id"
        and r.to_table == "student_admissions"
        and r.source == "database_fk"
        for r in relationships
    )


def test_curated_truth_layer_relationship_is_included_as_verified_evidence():
    graph = _graph()

    path = graph.find_path("student_leads", "student_admissions")

    assert path is not None
    assert path[0].source == "laravel_model"
    assert path[0].verified


def test_multi_hop_path_is_found_through_intermediate_table():
    graph = _graph()

    path = graph.find_path("student_certificates", "student_leads")

    assert path is not None
    assert len(path) == 2
    tables_touched = {path[0].from_table, path[0].to_table, path[1].from_table, path[1].to_table}
    assert {"student_certificates", "student_admissions", "student_leads"} <= tables_touched


def test_unreachable_tables_return_no_path():
    graph = _graph()

    assert graph.find_path("student_leads", "permissions") is None
    assert not graph.path_exists("student_leads", "permissions")


def test_unverified_relationship_is_excluded_but_a_verified_alternate_path_still_works():
    graph = _graph()

    # enrollment_to_payment is curated but verified: false in the Truth
    # Layer - it must never appear as an edge, even though a verified
    # alternate path (via student_leads) does exist and should still work.
    path = graph.find_path("student_enrollments", "student_payments")

    assert path is not None
    assert all(edge.source != "schema_column" or edge.verified for edge in path)
    touched_tables = {edge.from_table for edge in path} | {edge.to_table for edge in path}
    assert "student_leads" in touched_tables
