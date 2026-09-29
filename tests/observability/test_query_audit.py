from app.config.settings import Settings
from app.observability.query_audit import QueryAudit


def _settings(tmp_path):
    return Settings(QUERY_AUDIT_LOG=str(tmp_path / "audit.jsonl"))


def test_events_are_correlated_by_trace_id_and_read_back_in_order(tmp_path):
    settings = _settings(tmp_path)
    audit_a = QueryAudit(settings, trace_id="trace-a")
    audit_b = QueryAudit(settings, trace_id="trace-b")

    audit_a.record("stage_1", input="q1")
    audit_b.record("stage_1", input="q2")
    audit_a.record("stage_2", output="done")

    events_a = QueryAudit.read_events("trace-a", settings)
    events_b = QueryAudit.read_events("trace-b", settings)

    assert [e["stage"] for e in events_a] == ["stage_1", "stage_2"]
    assert all(e["run_id"] == "trace-a" for e in events_a)
    assert [e["stage"] for e in events_b] == ["stage_1"]


def test_sensitive_fields_are_redacted(tmp_path):
    settings = _settings(tmp_path)
    audit = QueryAudit(settings, trace_id="trace-c")

    audit.record("stage", input={"mobile": "9999999999", "applicant_name": "Ravi Kumar"})

    event = QueryAudit.read_events("trace-c", settings)[0]
    assert event["input"]["mobile"] == "[REDACTED]"
    assert event["input"]["applicant_name"] == "Ravi Kumar"


def test_read_events_for_unknown_trace_id_returns_empty_list(tmp_path):
    settings = _settings(tmp_path)
    assert QueryAudit.read_events("does-not-exist", settings) == []
