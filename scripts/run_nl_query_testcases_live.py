"""Phase 3: run the natural-language query catalog through the REAL, full
pipeline - real Sarvam calls, real (read-only) DB execution against
selected database, real answer synthesis, real faithfulness check.

This is deliberately separate from scripts/run_nl_query_testcases.py (which
stays fast/offline/deterministic for everyday editing) - this one costs
real API calls and DB round-trips, so it's a manual verification pass, not
something to run on every edit.

Usage:
    python scripts/run_nl_query_testcases_live.py
    python scripts/run_nl_query_testcases_live.py --id NLQ-02
"""

from __future__ import annotations

import argparse
import json
import re
from pathlib import Path
import sys

import yaml

PROJECT_ROOT = Path(__file__).resolve().parents[1]
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from app.config.settings import Settings
from app.database.executor import SafeQueryExecutor
from app.graph.root import StudentQueryOrchestrator
from app.intelligence.entity_resolver import build_configured_intent_classifier
from app.observability.query_audit import QueryAudit
from app.response.formatter import build_configured_answer_generator
from app.sql.generator import build_configured_sql_generator

CATALOG_PATH = PROJECT_ROOT / "tests" / "natural_language_queries.md"
_YAML_BLOCK = re.compile(r"```yaml\n(.*?)\n```", re.DOTALL)


def load_entries(path: Path) -> list[dict]:
    text = path.read_text(encoding="utf-8")
    return [yaml.safe_load(match) for match in _YAML_BLOCK.findall(text)]


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--id", help="Run only the entry with this id")
    parser.add_argument("--provider", choices=["sarvam", "gemini"], default="sarvam")
    args = parser.parse_args()

    entries = load_entries(CATALOG_PATH)
    if args.id:
        entries = [e for e in entries if e["id"] == args.id]

    settings = Settings(LLM_PROVIDER=args.provider)
    orchestrator = StudentQueryOrchestrator(intent_classifier=build_configured_intent_classifier(settings))
    generator = build_configured_sql_generator(settings)
    answer_generator = build_configured_answer_generator(settings)
    executor = SafeQueryExecutor(settings)

    results = []
    for entry in entries:
        if entry.get("skip"):
            print(f"SKIP  {entry['id']}")
            continue

        queries = entry.get("variants") or [entry["natural_language_input"]]
        query = queries[0]
        print(f"\n{'='*70}\n{entry['id']}: {query!r}\n{'='*70}")

        audit = QueryAudit(settings)
        state = orchestrator.answer(query, generator, answer_generator, executor, audit=audit)

        record = {"id": entry["id"], "query": query, "trace_id": audit.trace_id}
        if state.clarification:
            print(f"CLARIFICATION / STOPPED: {state.clarification}")
            record["outcome"] = "clarification"
            record["clarification"] = state.clarification
        else:
            sql = state.generated_sql["sql"]
            row_count = len(state.query_result.rows)
            print(f"Generated SQL: {sql}")
            print(f"Parameters:    {state.generated_sql['parameters']}")
            print(f"DB execution:  {row_count} row(s), truncated={state.query_result.truncated}")
            if row_count:
                print(f"Sample row:    {state.query_result.rows[0]}")
            print(f"Answer:        {state.answer['answer']}")
            print(f"Faithfulness:  {'PASS' if state.answer_validation.valid else 'FAIL'} "
                  f"{state.answer_validation.reasons}")
            record.update({
                "outcome": "success",
                "sql": sql,
                "row_count": row_count,
                "answer": state.answer["answer"],
                "faithfulness_valid": state.answer_validation.valid,
                "faithfulness_reasons": state.answer_validation.reasons,
            })

        # Compare against the catalog's deterministic expectations (these
        # were already verified offline by run_nl_query_testcases.py; here
        # we only check the parts that depend on this being a live run).
        expected = entry.get("expected", {})
        if expected.get("clarification") is None and state.clarification is not None:
            print(f"NOTE: catalog expected no clarification, but the live pipeline stopped: {state.clarification}")

        results.append(record)

    print(f"\n\n{len(results)} case(s) run live.")


if __name__ == "__main__":
    main()
