from app.metadata.registry import MetadataRegistry


def test_core_student_truth_layer_loads_against_generated_schema():
    registry = MetadataRegistry()

    registry.load()

    truth = registry.student_truth
    assert truth.version == 2
    assert "placement" in truth.scope["included_domains"]
    assert truth.scope["excluded_domains"] == [
        "legs_programme",
        "deployment",
    ]


def test_core_student_identity_context_contains_verified_core_keys():
    registry = MetadataRegistry()
    registry.load()

    canonical_keys = registry.student_truth.identity["canonical_keys"]
    assert set(canonical_keys) == {
        "student_lead_id",
        "student_admission_id",
        "student_enrollment_id",
    }

    relationships = registry.student_truth.relationships
    assert relationships["admission_to_exam_result"].verified
    assert relationships["admission_to_exam_result"].to.column == "core_student_id"
    assert relationships["admission_to_certificate"].to.column == "student_admission_id"
