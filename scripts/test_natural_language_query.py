"""Manually test natural-language-to-SQL generation without DB execution.

Examples:
    python scripts/test_natural_language_query.py "List students in batch 2024"
    python scripts/test_natural_language_query.py --interactive --provider gemini
    python scripts/test_natural_language_query.py --dry-run "Show me the result"
"""

from __future__ import annotations

import argparse
import json
from pathlib import Path
import sys

PROJECT_ROOT = Path(__file__).resolve().parents[1]
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from app.config.settings import Settings
from app.graph.root import StudentQueryOrchestrator
from app.sql.generator import build_configured_sql_generator


def run_query(
    question: str,
    provider: str | None,
    show_context: bool,
    dry_run: bool,
) -> None:
    orchestrator = StudentQueryOrchestrator()
    state = orchestrator.prepare(question)

    print("\nUser query:", question)
    print("Analysis:", json.dumps(state.analysis.as_dict(), indent=2))
    if state.clarification:
        print("\nClarification required:", state.clarification)
        return

    if show_context:
        print("\nLLM request context (API key excluded):")
        print(json.dumps(state.llm_context, indent=2))
    else:
        metrics = state.llm_context["minimal_schema"]["context_metrics"]
        print("\nSelected tables:", ", ".join(state.schema_selection.tables))
        print("Context metrics:", metrics)

    if dry_run:
        print("\nDry run complete: no LLM request and no database execution.")
        return

    settings = Settings(LLM_PROVIDER=provider) if provider else Settings()
    state = orchestrator.generate_and_validate(
        state, build_configured_sql_generator(settings)
    )
    print("\nLLM structured response:")
    print(json.dumps(state.generated_sql, indent=2))
    print("\nSQL validation:", "passed" if state.validation.valid else state.validation.errors)
    print("Database execution: skipped by this test script.")


def main() -> None:
    parser = argparse.ArgumentParser(
        description="Test Core-student natural-language query generation safely."
    )
    parser.add_argument("query", nargs="?", help="Natural-language user query")
    parser.add_argument("--provider", choices=["sarvam", "gemini"])
    parser.add_argument("--show-context", action="store_true")
    parser.add_argument("--dry-run", action="store_true")
    parser.add_argument("--interactive", action="store_true")
    args = parser.parse_args()

    if not args.query and not args.interactive:
        parser.error("Provide a query or use --interactive.")
    if args.query:
        run_query(args.query, args.provider, args.show_context, args.dry_run)
        return

    print("Interactive mode. Type 'exit' to stop.")
    while True:
        try:
            question = input("\nQuery> ").strip()
        except (EOFError, KeyboardInterrupt):
            print()
            return
        if question.lower() in {"exit", "quit"}:
            return
        if question:
            run_query(question, args.provider, args.show_context, args.dry_run)


if __name__ == "__main__":
    main()
