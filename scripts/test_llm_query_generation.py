"""Run one safe, non-executing end-to-end LLM SQL-generation check."""

from pathlib import Path
import sys
import argparse
import json

PROJECT_ROOT = Path(__file__).resolve().parents[1]
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from app.graph.root import StudentQueryOrchestrator
from app.config.settings import Settings
from app.sql.generator import build_configured_sql_generator


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--provider", choices=["sarvam", "gemini"])
    parser.add_argument("--show-context", action="store_true")
    args = parser.parse_args()

    question = "Show me the students in batch 2024 who are enrolled in Fitter program"
    orchestrator = StudentQueryOrchestrator()
    state = orchestrator.prepare(question)
    if state.clarification:
        print(f"Clarification: {state.clarification}")
        return

    settings = Settings(LLM_PROVIDER=args.provider) if args.provider else Settings()
    if args.show_context:
        print("LLM request context (API key excluded):")
        print(json.dumps(state.llm_context, indent=2))

    state = orchestrator.generate_and_validate(state, build_configured_sql_generator(settings))
    print("Selected tables:", ", ".join(state.schema_selection.tables))
    print("Schema metrics:", state.llm_context["minimal_schema"]["context_metrics"])
    print("LLM structured response:", json.dumps(state.generated_sql, indent=2))
    print("Validation:", "passed" if state.validation.valid else state.validation.errors)


if __name__ == "__main__":
    main()
