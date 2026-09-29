# Standard operating procedure

Day-to-day "how do I..." tasks. For how the pipeline works internally, see
[architecture.md](architecture.md); to add a table or a new kind of question,
follow [ADDING_A_NEW_QUERY.md](ADDING_A_NEW_QUERY.md).

**The debug server restarts itself.** It loads code and metadata once, so it
now runs under a small supervisor that restarts it whenever a file under `app/`,
`metadata/`, `.env` or `scripts/debug_server.py` changes on disk — including a
**revert**. (Before this, a server started before an edit or revert kept
serving the old behaviour, e.g. still splitting a question after the splitting
code had been removed.) Just reload the page after saving. `--no-reload` turns it
off. If you ever suspect an old process, make sure only one is listening:
`Get-NetTCPConnection -LocalPort 8124 -State Listen` should show a single row.

## Run the debug UI

```powershell
.\.venv\Scripts\python.exe scripts\debug_server.py --port 8124
```

Open `http://127.0.0.1:8124/` in a browser. Type a question, click Ask. It
calls the real pipeline (real DB, real configured LLM) and shows every
logged stage plus the final answer and its faithfulness-validation badge.

**Run it in your own terminal, not just via Claude Code**, if you want it to
survive between Claude Code sessions. It has already died silently twice
when a Claude Code session/harness restarted (it's an ordinary background
process with no supervisor) — if you start it yourself in a terminal you
keep open, it stays up regardless of what happens on the Claude Code side.

To stop a stale server that still holds the port (PowerShell):

```powershell
Get-NetTCPConnection -LocalPort 8124 -State Listen | ForEach-Object { Stop-Process -Id $_.OwningProcess -Force }
```

**"Running..." with no result ever appearing** almost always means the
server process died, not that the query is slow. To tell the difference:

```powershell
curl -s -o NUL -w "status: %{http_code}`n" http://127.0.0.1:8124/
```

- `status: 200` → server is up; the query is just still running (LLM +
  DB calls can legitimately take 20-60+ seconds, especially with an SQL
  retry). Wait.
- Connection refused / no response → the server process is gone. Restart it.

## Switch the LLM provider

Everything is `.env`-driven — no code change needed:

```
LLM_PROVIDER=sarvam        # or: gemini
SARVAM_API_KEY=...
GEMINI_API_KEY=...
```

`app/config/settings.py` reads `.env` via pydantic-settings. Note:
`scripts/debug_server.py` currently *defaults its own `--provider` flag to
`sarvam`* regardless of `.env`'s `LLM_PROVIDER`, because Gemini is
presently rate-limited on this account — pass `--provider gemini` to the
debug server explicitly if you want to test against Gemini once that's
resolved. Every other entry point (tests, `scripts/test_natural_language_query.py`)
follows `.env` as normal.

## Run the test suite

```powershell
.\.venv\Scripts\python.exe -m pytest -q
```

Some tests (`tests/database/`, parts of `tests/intelligence/`) hit the real
read-only `nttf_prod` database directly — same precedent as
`tests/database/test_connection.py`. There is no mocked-DB test mode today.

## Refresh schema metadata after a DB change

```powershell
.\.venv\Scripts\python.exe scripts\extract_schema.py
.\.venv\Scripts\python.exe -m pytest tests\metadata -q
```

This only regenerates `metadata/generated/schema.json` (the raw column/type
catalog, including real DB ENUM values). It never touches
`metadata/manual/core_student.yaml` — the business truth layer
(relationships, aliases, query_catalog) is always hand-curated on top, per
README.

## Refresh the value catalogs (centers, programs, streams, academic years)

Columns flagged `value_catalog: true` in `metadata/manual/column_semantics.yaml`
have their real distinct values snapshotted so partial wording ("Peenya",
"2024") maps to what is stored. Re-run when the data gains a new
center/stream/academic year:

```powershell
.\.venv\Scripts\python.exe scripts\extract_column_values.py
```

(Writes `metadata/generated/column_values.json`; read-only against the DB.)

## Profile a column or join before exposing it

```powershell
.\.venv\Scripts\python.exe scripts\profile_column.py core_exam_results.result_status
.\.venv\Scripts\python.exe scripts\profile_column.py student_referrals.code --join student_referrals.code=employees.employee_code
```

Shows NULL/empty rate, distinct values and their spellings, and how often a
join matches / fans out — see the "traps" table in
[ADDING_A_NEW_QUERY.md](ADDING_A_NEW_QUERY.md).

## Add a new filterable parameter

Usually **nothing to do** — if the concept name maps to a real column on a
candidate table (e.g. "batch", "gender", "semester"), `ParameterResolver`
finds it dynamically from schema metadata. Only add a curated entry to
`metadata/manual/core_student.yaml` → `semantic.parameters` when:

- it's a business identifier whose wording doesn't match any column name
  (e.g. "student code" → `core_student_code`), or
- the value precedes the concept word in natural phrasing (needs a custom
  `pattern`, e.g. `program`).

## Add a new query intent / expose new output columns

Full step-by-step (investigation checklist, annotated YAML templates, tests,
how to read the trace): **[ADDING_A_NEW_QUERY.md](ADDING_A_NEW_QUERY.md)**.

Short version: add `relationships` (verified only) → add a `query_catalog`
entry (`terms`, `tables`, `output_columns` incl. join keys,
`required_relationships`) → describe traps in `column_semantics.yaml` → add
`sql_hints` for anything non-obvious → tests → try it in the debug UI.

A question matching several catalog entries gets the **union** of all their
tables/columns/relationships/`sql_hints`. If a needed field isn't in any
entry's `output_columns` yet, add it — not a code change.

## Add a new module beyond Core Programme students

Currently **only Core Programme student data is in scope**
(`metadata/manual/core_student.yaml`, `semantic.out_of_scope_terms: [legs,
placement, deployment]`). To bring in a new module (LEGS, placement,
industry-programme, etc.):

1. Verify the identity bridge from that module's records to a Core student
   (a real FK or a Laravel model relationship) — do not assume shared
   column names imply a join.
2. Add a new manual truth-layer YAML (mirroring `core_student.yaml`'s
   structure: `relationships`, `semantic.parameters`, `query_catalog`) and
   wire it into `MetadataRegistry` (`app/metadata/registry.py`).
3. Remove the relevant term from `out_of_scope_terms` only once step 1 is
   actually verified, not before.

## Debugging a "stuck"/failed query

1. Confirm the server is actually up (see curl check above).
2. If it responded but returned a `clarification` string instead of an
   answer, that string states the actual reason (ambiguous intent, no safe
   SQL after retries, DB execution error, etc.) — it is not a generic
   failure message.
3. Pull the full trace for the exact stage that failed:
   `GET /api/trace/<trace_id>` (the debug page renders this automatically
   under "Generated DB query" / "SQL validation" / etc.), or read
   `logs/query_audit.jsonl` directly and filter by `trace_id`.
4. Use the stage-by-stage table in
   [ADDING_A_NEW_QUERY.md](ADDING_A_NEW_QUERY.md) ("Try it live and read the
   trace") to find which node went wrong, and check `docs/architecture.md`
   §6 "Known limitations" — some failure modes are known gaps, not new bugs.
5. A failed **answer badge** (red "OUTPUT VALIDATION: FAIL") is not a crash: it
   means a number/name in the sentence isn't in the rows. The result table
   shown under the answer is the ground truth.

## Manually test a query without the debug UI

```powershell
# Dry run: deterministic analysis + schema selection only, no LLM call
.\.venv\Scripts\python.exe scripts\test_natural_language_query.py --dry-run "List students in batch 2024"

# Full run against the configured provider
.\.venv\Scripts\python.exe scripts\test_natural_language_query.py "Show students in batch 2024 enrolled in Fitter program"

# Interactive loop
.\.venv\Scripts\python.exe scripts\test_natural_language_query.py --interactive --show-context
```

`--show-context` prints the exact redacted LLM payload (never the API key).
The curated regression catalog for known-good/known-tricky queries lives in
`tests/natural_language_queries.md`, run via
`scripts/run_nl_query_testcases.py` (deterministic layer only) or
`scripts/run_nl_query_testcases_live.py` (full pipeline, real LLM + DB).
