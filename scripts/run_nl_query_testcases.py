"""Run the hand-editable natural-language query catalog deterministically.

Parses every ```yaml block in tests/natural_language_queries.md and runs it
through the real QueryAnalyzer + SchemaRetriever (no LLM call, no database
connection - everything up to and including parameter resolution and
minimal-schema selection is deterministic and offline). Reports PASS/FAIL
per entry against its `expected:` block.

Examples:
    python scripts/run_nl_query_testcases.py
    python scripts/run_nl_query_testcases.py --id NLQ-02
    python scripts/run_nl_query_testcases.py --category temporal_expression
"""

from __future__ import annotations

import argparse
import re
from pathlib import Path
import sys

import yaml

PROJECT_ROOT = Path(__file__).resolve().parents[1]
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from app.intelligence.query_analyzer import QueryAnalyzer
from app.intelligence.schema_retriever import SchemaRetriever
from app.metadata.registry import MetadataRegistry

CATALOG_PATH = PROJECT_ROOT / "tests" / "natural_language_queries.md"
_YAML_BLOCK = re.compile(r"```yaml\n(.*?)\n```", re.DOTALL)


def load_entries(path: Path) -> list[dict]:
    text = path.read_text(encoding="utf-8")
    return [yaml.safe_load(match) for match in _YAML_BLOCK.findall(text)]


def run_query(registry: MetadataRegistry, query: str) -> dict:
    analysis = QueryAnalyzer(
        registry.student_truth, registry.schema, registry.column_semantics
    ).analyze(query)

    tables_selected: list[str] = []
    if analysis.is_ready:
        selection = SchemaRetriever(registry.schema, registry.student_truth).retrieve(analysis)
        tables_selected = sorted(selection.tables)

    return {
        "clarification": analysis.clarification,
        "intent": analysis.intent,
        "parameters": sorted(
            (p.name, p.value, p.table, p.column) for p in analysis.parameters
        ),
        "tables_selected": sorted(tables_selected),
    }


def compare(expected: dict, actual: dict) -> list[str]:
    problems = []

    expected_clarification = expected.get("clarification")
    if expected_clarification is None:
        if actual["clarification"] is not None:
            problems.append(f"expected no clarification, got: {actual['clarification']!r}")
    else:
        if not actual["clarification"] or expected_clarification.lower() not in actual["clarification"].lower():
            problems.append(
                f"expected clarification to contain {expected_clarification!r}, got: {actual['clarification']!r}"
            )

    if expected.get("intent") != actual["intent"]:
        problems.append(f"expected intent={expected.get('intent')!r}, got {actual['intent']!r}")

    expected_params = sorted(
        (p["name"], p["value"], p["table"], p["column"]) for p in expected.get("parameters", [])
    )
    if expected_params != actual["parameters"]:
        problems.append(f"expected parameters={expected_params}, got {actual['parameters']}")

    expected_tables = sorted(expected.get("tables_selected", []))
    if expected_tables and expected_tables != actual["tables_selected"]:
        problems.append(f"expected tables_selected={expected_tables}, got {actual['tables_selected']}")

    return problems


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--id", help="Run only the entry with this id")
    parser.add_argument("--category", help="Run only entries in this category")
    args = parser.parse_args()

    entries = load_entries(CATALOG_PATH)
    if args.id:
        entries = [e for e in entries if e["id"] == args.id]
        if not entries:
            parser.error(f"No entry with id {args.id!r} in {CATALOG_PATH}")
    if args.category:
        entries = [e for e in entries if e.get("category") == args.category]

    registry = MetadataRegistry()
    registry.load()

    passed = failed = skipped = 0
    for entry in entries:
        entry_id = entry["id"]
        if entry.get("skip"):
            skipped += 1
            print(f"SKIP  {entry_id}: {entry.get('skip_reason', '').strip().splitlines()[0]}")
            continue

        queries = entry.get("variants") or [entry["natural_language_input"]]
        entry_ok = True
        for query in queries:
            actual = run_query(registry, query)
            problems = compare(entry.get("expected", {}), actual)
            if problems:
                entry_ok = False
                print(f"FAIL  {entry_id}  ({query!r})")
                for problem in problems:
                    print(f"      - {problem}")

        if entry_ok:
            passed += 1
            label = entry["natural_language_input"] if len(queries) == 1 else f"{len(queries)} variants"
            print(f"PASS  {entry_id}  ({label})")
        else:
            failed += 1

    print()
    print(f"{passed} passed, {failed} failed, {skipped} skipped, {len(entries)} total")
    sys.exit(1 if failed else 0)


if __name__ == "__main__":
    main()
