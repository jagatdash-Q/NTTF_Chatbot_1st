# Natural-language query catalog (manual, hand-editable)

This file is a **consolidated view** of every natural-language query
exercised anywhere in this repo's test suite and manual scripts. It does
**not** replace the automated tests under `tests/intelligence/`,
`tests/database/`, and `tests/metadata/` — those still run in CI/locally via
`pytest` and are the source of truth for pass/fail gating. This file exists
so a human can read, tweak, and re-run one query at a time without writing
Python.

Each entry is a fenced `yaml` block. Edit the `natural_language_input` (or
add a new block anywhere in this file, in the same shape) and re-run it —
see **How to run** below.

## How to run

**Option A — fast, deterministic, no LLM/DB (recommended for editing this file):**

```powershell
.\.venv\Scripts\python.exe scripts\run_nl_query_testcases.py
```

This parses every `yaml` block below, runs it through the real
`QueryAnalyzer` + `SchemaRetriever` against the real generated schema and
Truth Layer, and reports PASS/FAIL against each entry's `expected:` block.
No API key, no live database connection, and no `--dry-run` flag needed —
concept extraction, parameter resolution, and schema selection are all
deterministic and offline.

Run a single entry by id:

```powershell
.\.venv\Scripts\python.exe scripts\run_nl_query_testcases.py --id NLQ-01
```

Run only entries in one category:

```powershell
.\.venv\Scripts\python.exe scripts\run_nl_query_testcases.py --category temporal
```

**Option B — full pipeline including the LLM SQL generator (needs `.env` with
`SARVAM_API_KEY` or `GEMINI_API_KEY`):**

```powershell
.\.venv\Scripts\python.exe scripts\test_natural_language_query.py "List students in batch 2024 with status as active"
```

Add `--dry-run` to stop after schema selection (no LLM call, no DB call).
Add `--show-context` to print the exact payload sent to the SQL-generation
LLM.

**Option C — full loop including DB execution and the answer-generation LLM
call:** not implemented yet as of Phase 2 of this work (see Phase 3's
"where the pipeline stops short" finding below). Will be added in Phase 4
and this section updated with the actual command.

## Editing an entry

Each block's fields:

| Field | Meaning |
|---|---|
| `id` | Stable identifier, referenced by `--id` above and by other docs |
| `category` | Groups related entries for `--category` filtering |
| `natural_language_input` | The exact question to run. Edit this to try variations. |
| `variants` | Optional list of alternate phrasings expected to behave identically (e.g. connector forms) |
| `expected.clarification` | `null` if the query should resolve without asking anything, or a substring that must appear in the clarification text |
| `expected.intent` | The `query_catalog` intent name expected to be detected |
| `expected.parameters` | List of `{name, value, table, column}` the resolver should produce, in any order |
| `expected.tables_selected` | Table names expected in the minimal schema selection |
| `expected_query` | Always `null` here — SQL generation is an LLM call and is not deterministic; use Option B/C to inspect the actual generated SQL for a given run |
| `skip` / `skip_reason` | Present only on entries the fast runner can't execute (e.g. needs a synthetic schema, or a mocked LLM) — see `skip_reason` for where the behavior *is* actually tested |
| `notes` | Edge case explanation |
| `source_tests` | Automated pytest test(s) this entry was consolidated from |

---

```yaml
id: NLQ-01
category: single_parameter
natural_language_input: "List students in batch 2024"
expected:
  clarification: null
  intent: student_listing
  parameters:
    - {name: batch, value: "2024", table: student_admissions, column: batch_name}
  tables_selected: [student_leads, student_admissions]
expected_query: null
notes: >
  Baseline dynamic-schema-discovery case. `batch` has no entry in
  semantic.parameters - it is resolved purely from the schema
  (student_admissions.batch_name), proving a new filterable column never
  needs a manual parameter mapping.
source_tests:
  - tests/intelligence/test_parameter_resolution.py::test_single_parameter_batch_resolves_via_dynamic_schema_discovery
```

```yaml
id: NLQ-02
category: multiple_parameters
natural_language_input: "List students in batch 2024 with status as active"
expected:
  clarification: null
  intent: student_listing
  parameters:
    - {name: batch, value: "2024", table: student_admissions, column: batch_name}
    - {name: status, value: "active", table: student_admissions, column: status}
  tables_selected: [student_leads, student_admissions]
expected_query: null
notes: >
  The original bug report case: "status as active" must resolve to
  status=active, never status=as.
source_tests:
  - tests/intelligence/test_parameter_resolution.py::test_multiple_parameters_batch_and_status
  - tests/intelligence/test_parameter_resolution.py::test_status_as_active_does_not_resolve_to_the_connector_word
  - tests/intelligence/test_parameter_resolution.py::test_parameter_values_survive_all_pipeline_stages_unchanged
```

```yaml
id: NLQ-03
category: connector_forms
natural_language_input: "List students with status active"
variants:
  - "List students with status active"
  - "List students with status is active"
  - "List students with status as active"
  - "List students with status = active"
  - "List students with status: active"
expected:
  clarification: null
  intent: student_listing
  parameters:
    - {name: status, value: "active", table: student_admissions, column: status}
  tables_selected: [student_leads, student_admissions]
expected_query: null
notes: >
  All five connector forms must resolve identically. "status: active"
  (colon touching the word, no space) was the concrete regression this
  suite guards against.
source_tests:
  - tests/intelligence/test_parameter_resolution.py::test_status_connector_forms_all_resolve_to_the_same_value
```

```yaml
id: NLQ-04
category: multiple_filters
natural_language_input: "List students in batch 2024 with status active in program Fitter"
expected:
  clarification: null
  intent: student_listing
  parameters:
    - {name: batch, value: "2024", table: student_admissions, column: batch_name}
    - {name: status, value: "active", table: student_admissions, column: status}
    - {name: program, value: "Fitter", table: student_admissions, column: program_name}
  tables_selected: [student_leads, student_admissions]
expected_query: null
notes: >
  Three filters in one sentence, mixing a curated irregular-order concept
  (program) with two schema-discovered/curated concept-first ones.
source_tests:
  - tests/intelligence/test_parameter_resolution.py::test_multiple_filters_batch_status_and_program
```

```yaml
id: NLQ-05
category: legacy_word_order
natural_language_input: "Show me the students in batch 2024 who are enrolled in Fitter program"
expected:
  clarification: null
  intent: student_listing
  parameters:
    - {name: batch, value: "2024", table: student_admissions, column: batch_name}
    - {name: program, value: "Fitter", table: student_admissions, column: program_name}
  tables_selected: [student_leads, student_admissions]
expected_query: null
notes: >
  The original idiom "Fitter program" (value before the concept word) -
  a curated pattern, kept for backward compatibility alongside NLQ-04's
  "program Fitter" order.
source_tests:
  - tests/intelligence/test_query_pipeline.py::test_listing_extracts_filters_and_selects_minimal_schema
  - tests/intelligence/test_query_pipeline.py::test_context_excludes_irrelevant_tables_and_preserves_parameters
  - scripts/test_llm_query_generation.py (full pipeline including SQL generation and validation)
```

```yaml
id: NLQ-06
category: unknown_parameter_safety
natural_language_input: "List students in batch 2024 with hobby painting"
expected:
  clarification: null
  intent: student_listing
  parameters:
    - {name: batch, value: "2024", table: student_admissions, column: batch_name}
  tables_selected: [student_leads, student_admissions]
expected_query: null
notes: >
  "hobby" matches no column in the candidate tables and must be silently
  dropped, not guessed or crashed on.
source_tests:
  - tests/intelligence/test_parameter_resolution.py::test_unknown_parameter_is_ignored_rather_than_guessed
```

```yaml
id: NLQ-07
category: out_of_scope
natural_language_input: "Show me LEGS placement students"
expected:
  clarification: "not in the current scope"
  intent: null
  parameters: []
  tables_selected: []
expected_query: null
notes: >
  LEGS/placement/deployment are explicitly out of scope until the identity
  bridge to Core students is verified (metadata/manual/core_student.yaml
  scope.excluded_domains).
source_tests:
  - app/intelligence/query_analyzer.py (out_of_scope_terms gate) - add a
    dedicated pytest case here if this behavior needs stronger regression
    coverage.
```

```yaml
id: NLQ-08
category: missing_identity
natural_language_input: "Show me the result"
expected:
  clarification: "Core student code"
  intent: student_result
  parameters: []
  tables_selected: []
expected_query: null
notes: >
  student_result requires an identifier (enquiry number, hall-ticket
  number, or Core student code); none was given, so the system must ask
  rather than return every student's results.
source_tests:
  - tests/intelligence/test_query_pipeline.py::test_result_without_identifier_requires_clarification
  - tests/intelligence/test_query_pipeline.py::test_orchestrator_returns_context_or_clarification_without_calling_an_llm
```

```yaml
id: NLQ-09
category: identity_provided
natural_language_input: "Show me the result for core student code NTTF-2024-001"
expected:
  clarification: null
  intent: student_result
  parameters:
    - {name: core_student_code, value: "NTTF-2024-001", table: student_admissions, column: core_student_code}
  tables_selected: [student_leads, student_admissions, core_exam_results]
expected_query: null
notes: >
  student_leads is part of the results schema only to name the student
  (core_exam_results.core_student_name is empty on every row).
source_tests: []
```

```yaml
id: NLQ-10
category: alias_resolution
natural_language_input: "Show students from academic year 2024 in the Fitter program who are active"
expected:
  clarification: null
  intent: student_listing
  parameters:
    - {name: academic_year, value: "2024 JAN and 2024 JUN and 2024 SEP", table: student_admissions, column: core_year}
    - {name: program, value: "Fitter", table: student_admissions, column: program_name}
  tables_selected: [student_leads, student_admissions]
expected_query: null
notes: >
  "2024" alone is a partial value: core_year stores "2024 JAN/JUN/SEP" (its
  value catalog, metadata/generated/column_values.json), so it expands to
  every 2024 session as an IN list. "academic year" has no entry in semantic.parameters at all - it resolves
  to the oddly-named student_admissions.core_year column purely through
  metadata/manual/column_semantics.yaml aliases. The deterministic
  extractor does NOT also find status=active here, because "who are
  active" never says the word "status" - see NLQ-11 for the LLM-extractor
  variant that does catch it.
source_tests:
  - tests/intelligence/test_parameter_resolution.py::test_academic_year_resolves_to_core_year_via_curated_column_alias
```

```yaml
id: NLQ-11
category: llm_extraction_demo
natural_language_input: "Show students from academic year 2024 in the Fitter program who are active"
skip: true
skip_reason: >
  Requires an LLMConceptExtractor (real provider or a mock client) plugged
  into QueryAnalyzer instead of the default RegexConceptExtractor - the
  fast deterministic runner does not swap extractors. Run this exact
  scenario via:
    tests/intelligence/test_parameter_resolution.py::test_llm_concept_extractor_is_pluggable_and_shares_the_same_deterministic_resolver
  (mocked LLM, runs in pytest with no network) or via
    scripts/test_natural_language_query.py --provider gemini "..."
  (real LLM call, needs GEMINI_API_KEY/SARVAM_API_KEY).
expected:
  clarification: null
  intent: student_listing
  parameters:
    - {name: academic_year, value: "2024", table: student_admissions, column: core_year}
    - {name: program, value: "Fitter", table: student_admissions, column: program_name}
    - {name: status, value: "active", table: student_admissions, column: status}
  tables_selected: [student_leads, student_admissions]
expected_query: null
notes: >
  Demonstrates why an LLM extractor is offered as an opt-in: "who are
  active" implies status=active without ever saying the word "status" -
  beyond what the default regex extractor can infer, but it resolves
  through the identical deterministic schema-search-and-score resolver
  either way.
source_tests:
  - tests/intelligence/test_parameter_resolution.py::test_llm_concept_extractor_is_pluggable_and_shares_the_same_deterministic_resolver
```

```yaml
id: NLQ-12
category: temporal_expression
natural_language_input: "Show students from the previous academic year"
expected:
  clarification: "previous academic year"
  intent: student_listing
  parameters: []
  tables_selected: []
expected_query: null
notes: >
  Relative temporal expressions are never guessed. No verified business
  rule resolves "current/previous academic year" today, so this must
  always ask for an exact value rather than silently picking one.
source_tests:
  - tests/intelligence/test_parameter_resolution.py::test_relative_academic_year_requests_clarification_instead_of_guessing
  - tests/intelligence/test_entity_resolver.py::test_relative_temporal_expression_requires_resolution_instead_of_guessing
```

```yaml
id: NLQ-13
category: ambiguity
natural_language_input: "List records with grade as A"
skip: true
skip_reason: >
  This exact ambiguity (two candidate tables both having a same-named,
  uncurated column) does not occur anywhere in the current real schema/
  Truth Layer scope - `status` is the one concept that could collide this
  way (student_leads.status vs student_admissions.status) and it is
  deliberately curated to avoid exactly this ambiguity (see NLQ-11's
  status resolution). The "do not guess" behavior is verified with a
  synthetic two-table fixture instead:
    tests/intelligence/test_parameter_resolution.py::test_ambiguous_concept_across_candidate_tables_requests_clarification
expected:
  clarification: "clarify which column"
  intent: null
  parameters: []
  tables_selected: []
expected_query: null
notes: >
  If you extend the Truth Layer to a new module/table set and a genuine
  ambiguous column appears, turn this into a real, runnable entry.
source_tests:
  - tests/intelligence/test_parameter_resolution.py::test_ambiguous_concept_across_candidate_tables_requests_clarification
```

```yaml
id: NLQ-14
category: sql_validator
natural_language_input: "N/A - this exercises SQLValidator directly, not the NL pipeline"
skip: true
skip_reason: >
  These are hand-written SQL strings, not natural-language input - kept
  here only so this file is a complete index of everything the automated
  suite covers. See the test file directly to edit/run these.
expected:
  clarification: null
  intent: null
  parameters: []
  tables_selected: []
expected_query: null
notes: >
  Covers: unselected table rejected, unselected column rejected, unverified
  join rejected, verified join without aliases accepted.
source_tests:
  - tests/intelligence/test_query_pipeline.py::test_validator_rejects_unselected_table_column_and_bad_join
  - tests/intelligence/test_query_pipeline.py::test_validator_accepts_verified_join_without_aliases
```


```yaml
id: NLQ-15
category: results_by_filters
natural_language_input: "Show subject wise marks for center Peenya program Diploma semester three academic year 2023 JUN"
expected:
  clarification: null
  intent: student_result
  parameters:
    - {name: center, value: "BTC - PEENYA", table: core_exam_results, column: center_name}
    - {name: program, value: "DIPLOMA", table: core_exam_results, column: program_name}
    - {name: semester, value: "3", table: core_exam_results, column: semester_id}
    - {name: academic_year, value: "2023 JUN", table: core_exam_results, column: academic_year}
  tables_selected: [student_leads, student_admissions, core_exam_results]
expected_query: null
notes: >
  Second way to search results: no student code, just cohort filters (any
  one is enough). Filters resolve to core_exam_results itself
  (preferred_filter_tables), values are snapped to the stored vocabulary
  (value catalog: "Peenya" -> "BTC - PEENYA", "three" -> 3).
source_tests:
  - tests/intelligence/test_results_and_referrer_queries.py::test_results_filters_resolve_to_the_results_table_and_real_values
```

```yaml
id: NLQ-16
category: results_by_student
natural_language_input: "Show subject wise marks for semester 1 of student code = BNTC1520001"
expected:
  clarification: null
  intent: student_result
  parameters:
    - {name: semester, value: "1", table: core_exam_results, column: semester_id}
    - {name: core_student_code, value: "BNTC1520001", table: student_admissions, column: core_student_code}
  tables_selected: [student_leads, student_admissions, core_exam_results]
expected_query: null
notes: >
  First way to search results: one specific student by code, optionally
  narrowed to a semester (omit the semester for all semesters).
source_tests:
  - tests/intelligence/test_results_and_referrer_queries.py::test_student_code_still_works_as_the_other_way_to_search
```

```yaml
id: NLQ-17
category: results_missing_scope
natural_language_input: "Show me the marks"
expected:
  clarification: "filter"
  intent: student_result
  parameters: []
  tables_selected: []
expected_query: null
notes: >
  Neither an identifier nor a single filter: asks for one (either works).
source_tests:
  - tests/intelligence/test_results_and_referrer_queries.py::test_results_question_with_neither_identity_nor_filter_asks_for_one
```

```yaml
id: NLQ-18
category: referrer_details
natural_language_input: "Give me the employee details who has given the reference for the student with enquiry number E25-000006"
expected:
  clarification: null
  intent: student_referral
  parameters:
    - {name: enquiry_number, value: "E25-000006", table: student_leads, column: enquiry_number}
  tables_selected: [student_leads, student_admissions, student_referrals, lead_master, employees, users]
expected_query: null
notes: >
  Referrer's employee/user record. employees and users are output-only
  (filter_tables excludes them), so "employee" never becomes a bogus
  employee_code filter. A student referrer is answered from a second
  student_admissions/student_leads pair (sql_hints in core_student.yaml).
source_tests:
  - tests/intelligence/test_results_and_referrer_queries.py::test_referrer_details_bring_in_employees_and_users_without_bogus_filters
```


```yaml
id: NLQ-19
category: results_consolidated_needs_semester
natural_language_input: "Give me the consolidated result of student code = TTC0825020"
expected:
  clarification: "semester number"
  intent: student_result
  parameters:
    - {name: core_student_code, value: "TTC0825020", table: student_admissions, column: core_student_code}
  tables_selected: []
expected_query: null
notes: >
  A consolidated result (latest attempt of every subject) needs a semester,
  or an explicit "for all semesters" - otherwise the user is asked.
source_tests:
  - tests/intelligence/test_result_scope.py::test_consolidated_without_a_semester_asks_which_one
```
