import pytest
from datetime import date, timedelta

from app.database.executor import SafeQueryExecutor
from app.graph.root import StudentQueryOrchestrator
from app.intelligence.query_analyzer import QueryAnalyzer
from app.intelligence.schema_retriever import SchemaRetriever
from app.intelligence.temporal_resolver import resolve_temporal_expression
from app.metadata.registry import MetadataRegistry
from app.response.presenter import build_presentation
from app.sql.validator import SQLValidator


@pytest.fixture(scope="module")
def registry():
    reg = MetadataRegistry()
    reg.load()
    return reg


@pytest.fixture(scope="module")
def analyzer(registry):
    return QueryAnalyzer(registry.student_truth, registry.schema, registry.column_semantics)


@pytest.fixture(scope="module")
def retriever(registry):
    return SchemaRetriever(registry.schema, registry.student_truth, registry.column_semantics)


def test_temporal_resolver_all_target_phrases():
    ref = date(2026, 9, 24)

    # 1. next week
    r1 = resolve_temporal_expression("What placement events are happening next week?", reference_date=ref)
    assert r1 is not None and r1.kind == "date_range"
    assert r1.start_date == "2026-09-28" and r1.end_date == "2026-10-04"

    # 2. coming next
    r2 = resolve_temporal_expression("Which placement event is coming next?", reference_date=ref)
    assert r2 is not None and r2.is_next and r2.is_upcoming

    # 3. scheduled on 25 September
    r3 = resolve_temporal_expression("What events are scheduled on 25 September?", reference_date=ref)
    assert r3 is not None and r3.kind == "exact_date" and r3.exact_date == "2026-09-25"

    # 4. between 25 September and 30 September
    r4 = resolve_temporal_expression("Show me the placement events between 25 September and 30 September.", reference_date=ref)
    assert r4 is not None and r4.kind == "date_range"
    assert r4.start_date == "2026-09-25" and r4.end_date == "2026-09-30"

    # 5. tomorrow
    r5 = resolve_temporal_expression("Are there any core or placement events happening tomorrow?", reference_date=ref)
    assert r5 is not None and r5.kind == "exact_date" and r5.exact_date == "2026-09-25"

    # 6. upcoming events
    r6 = resolve_temporal_expression("Show the upcoming events with event name, date, time, venue, and other available details.", reference_date=ref)
    assert r6 is not None and r6.is_upcoming


@pytest.mark.parametrize("query,expected_params", [
    (
        "What placement events are happening next week?",
        ["event_date_start", "event_date_end"],
    ),
    (
        "What core events are happening next week?",
        ["event_date_start", "event_date_end"],
    ),
    (
        "Which placement event is coming next?",
        ["event_date_start"],
    ),
    (
        "What events are scheduled on 25 September?",
        ["event_date"],
    ),
    (
        "Show me the placement events between 25 September and 30 September.",
        ["event_date_start", "event_date_end"],
    ),
    (
        "What is the date and time of the next placement event?",
        ["event_date_start"],
    ),
    (
        "Are there any core or placement events happening tomorrow?",
        ["event_date"],
    ),
    (
        "Show the upcoming events with event name, date, time, venue, and other available details.",
        ["event_date_start"],
    ),
])
def test_all_8_target_queries_analyze_successfully(analyzer, retriever, query, expected_params):
    analysis = analyzer.analyze(query)
    assert analysis.is_ready is True
    assert analysis.intent == "event_schedule"
    assert analysis.clarification is None

    param_names = [p.name for p in analysis.parameters]
    for expected in expected_params:
        assert expected in param_names

    selection = retriever.retrieve(analysis)
    assert "core_events" in selection.tables
    assert len(selection.tables) == 1
    assert any("event_date" in col["name"] for col in selection.tables["core_events"])


def test_sql_validator_accepts_event_sql(analyzer, retriever):
    analysis = analyzer.analyze("What events are scheduled on 25 September?")
    selection = retriever.retrieve(analysis)

    sql = (
        "SELECT core_events.event_name, core_events.company_name, core_events.event_type, "
        "core_events.event_date, core_events.event_time, core_events.selection_venue, "
        "core_events.hiring_location, core_events.position "
        "FROM core_events "
        "WHERE core_events.event_date = :event_date "
        "ORDER BY core_events.event_date ASC, core_events.event_time ASC"
    )

    val = SQLValidator().validate(sql, selection)
    assert val.valid is True
    assert len(val.errors) == 0


def test_event_schedule_presentation():
    sample_rows = [
        {
            "event_name": "TATA ADVANCE-Direct Hiring",
            "event_type": "Campus Drive",
            "company_name": "TATA ADVANCE",
            "event_date": "2026-09-25",
            "event_time": timedelta(seconds=36000),
            "selection_venue": "NEC",
            "hiring_location": "Hyderabad",
            "position": "Graduate Engineer Trainee",
        }
    ]
    p = build_presentation(list(sample_rows[0].keys()), sample_rows)
    assert p is not None
    assert p.kind == "event_schedule"
    assert "Scheduled Event" in p.text
    assert "10:00 AM" in p.text
    assert "TATA ADVANCE" in p.text


def test_live_database_execution():
    executor = SafeQueryExecutor()
    sql = (
        "SELECT core_events.event_name, core_events.company_name, core_events.event_type, "
        "core_events.event_date, core_events.event_time, core_events.selection_venue "
        "FROM core_events "
        "LIMIT 3"
    )
    result = executor.execute(sql, {})
    assert len(result.rows) == 3
    assert "event_name" in result.columns
    assert result.truncated is False
