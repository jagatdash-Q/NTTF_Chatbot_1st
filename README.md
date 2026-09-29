# NTTF ERP chatbot

- [docs/architecture.md](docs/architecture.md) - how the NL-to-SQL pipeline
  works end to end: flow chart, and for every node the file to open.
- [docs/ADDING_A_NEW_QUERY.md](docs/ADDING_A_NEW_QUERY.md) - developer
  playbook: adding a table or a new kind of question (mostly YAML).
- [docs/SOP.md](docs/SOP.md) - day-to-day tasks: running the debug UI,
  switching LLM providers, refreshing metadata, troubleshooting.

This project builds a safe, read-only chatbot layer over the NTTF ERP database.
The database schema is extracted into `metadata/generated/schema.json`; business
relationships that are absent from database foreign keys live in
`metadata/manual/`.

## Current scope

The implemented metadata scope is **Core Programme student data only**. LEGS,
placement, deployment, and industry-programme data are intentionally excluded
until their identity bridge to Core students is verified.

The Core student truth layer supports deterministic resolution by enquiry
number, hall-ticket number, or Core student code. Resolution produces an
identity context rather than one assumed universal key:

- `student_lead_id` for lead, enrollment, and lead-scoped finance data
- `student_admission_id` for results and certificates
- `student_enrollment_id` for enrollment documents and enrollment-specific
  payments

Only relationships marked `verified: true` in
`metadata/manual/core_student.yaml` may be used to construct joins.

## Refresh and validate metadata

```powershell
.\.venv\Scripts\python.exe scripts\extract_schema.py
.\.venv\Scripts\python.exe -m pytest tests\metadata -q
```

Do not add a relationship based only on matching column names. Verify it from
the Laravel model and/or an actual database foreign key, then add it to the
manual truth layer with its source and cardinality.

## Manual natural-language query test

This test invokes deterministic analysis, minimal-schema retrieval, the
configured LLM, and SQL validation. It never executes generated SQL.

```powershell
# Inspect analysis and selected schema without an external LLM call
.\.venv\Scripts\python.exe scripts\test_natural_language_query.py --dry-run "List students in batch 2024"

# Generate and validate with the configured provider
.\.venv\Scripts\python.exe scripts\test_natural_language_query.py "Show students in batch 2024 enrolled in Fitter program"

# Test multiple questions manually; use Ctrl+C or `exit` to stop
.\.venv\Scripts\python.exe scripts\test_natural_language_query.py --interactive --show-context
```

Use `--provider gemini` or `--provider sarvam` to override `LLM_PROVIDER` for
one run. `--show-context` prints the exact redacted LLM payload and never
prints the API key.
