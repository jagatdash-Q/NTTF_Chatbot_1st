# Developer playbook: adding a new table or a new kind of question

Goal: a developer who has never touched this repo can add a new question type
(e.g. "hostel allotment", "fee defaulters", "placement offers") without
reading the whole codebase. **Almost everything is metadata (YAML), not
code.** Read [architecture.md](architecture.md) §1–3 first (10 minutes) so the
node names below (N3, N5, N7 …) mean something.

```
YAML you edit                       what it controls                         node
---------------------------------   --------------------------------------   ----
core_student.yaml  relationships    whicch joins are allowed                  N7 N9
core_student.yaml  query_catalog    the new question type                    N3 N7
core_student.yaml  semantic.*       scope words, curated filters             N2 N4 N5
column_semantics.yaml               column meaning, aliases, value lists     N4 N5 N7
```

---

## Step 0 — Find out how the data really works (do not skip)

Most bugs in this project were **wrong assumptions about data**, not code.
Spend the time here.

1. **Find the Laravel model.** The original app's PHP models are vendored in
   [`app/Models/`](../app/Models). Search for the table/class
   (`grep -rn "class StudentReferral" app/Models`). Relationship methods
   (`hasOne`, `hasMany`, `belongsTo`) tell you the *real* join keys:

   ```php
   // Studentlead.php  →  student_leads.id = student_referrals.student_lead_id
   public function getReferral(): HasOne { return $this->hasOne(StudentReferral::class, 'student_lead_id', 'id'); }
   // Laravel signature: hasOne(Related, foreignKeyOnRelated, localKey)
   ```
2. **Confirm the table/columns exist** in
   [`metadata/generated/schema.json`](../metadata/generated/schema.json) (if the
   DB changed: `python scripts/extract_schema.py`).
3. **Profile every column you plan to expose** with
   [`scripts/profile_column.py`](../scripts/profile_column.py):

   ```powershell
   .\.venv\Scripts\python.exe scripts\profile_column.py core_exam_results.result_status
   .\.venv\Scripts\python.exe scripts\profile_column.py student_referrals.code --join student_referrals.code=employees.employee_code
   ```
   Look for the traps below.
4. **Run the join by hand** for one real record and compare with what the ERP
   screen shows.

### Traps that have already cost us a debugging session

| Trap | Real example | What to do |
|---|---|---|
| **Dead column** (empty on every row) | `core_exam_results.core_student_name` is 100 % empty | Never expose it; take the value from the table that has it (`student_leads.applicant_name`) |
| **Look-alike column** | `family_details.relationship` (correct) vs `relationship_status` (a real ENUM but `'Member'` on 82 % of rows) | Leave the wrong one out of `output_columns`; describe the right one in `column_semantics` |
| **VARCHAR that behaves like an ENUM** | `student_payments.payment_type`, `family_details.relationship`, `student_referrals.status` | `allowed_values` in `column_semantics.yaml` (real DB ENUMs are picked up automatically) |
| **Two spellings of one meaning** | fail = `'Fail'` **or** `'F'`; pass = `'Pass'` **or** `'P'` | Say so in the column `description` ("match both with IN") |
| **Similar-but-different values in two columns** | status `temporary_enrolled` vs payment_type `temporary_enroll` | Give each column its own `allowed_values`; never assume shared vocabulary |
| **Same word, different table** | "semester": admission's current semester vs the exam record's semester | `preferred_filter_tables` / `alternates` (Step 5) |
| **Denormalised repeats** | results: one row per *subject*, but SGPA/CGPA/grand total repeat on every row | `sql_hints`: "use SELECT DISTINCT for per-semester values" |
| **Join that isn't in the Laravel model** | referral `code` ↔ `employees.employee_code` | Only via live-data verification: `source: data_verified`, and record the match % in the description |
| **Duplicate keys on the target side** | 9 duplicated employee codes | Say so in the relationship description; joins may fan out |
| **Sensitive columns** | employee PAN/Aadhar/passport, user passwords/tokens | Do not list them in `output_columns` — the model can only select what the minimal schema contains |

---

## Step 1 — Add the relationship(s)

In [`core_student.yaml`](../metadata/manual/core_student.yaml) → `relationships:`

```yaml
  referral_to_employee:                 # name: <from>_to_<to>
    name: referral_to_employee
    from: {table: student_referrals, column: code}
    to:   {table: employees,         column: employee_code}
    cardinality: many_to_one            # one_to_one | one_to_many | many_to_one
    source: data_verified               # laravel_model | laravel_model_and_schema_fk | data_verified
    verified: true                      # false = kept for documentation, NEVER used to join
    description: >
      Where it comes from and the evidence (Laravel method, or match rate
      from profile_column.py + date). When it only applies to some rows,
      say when (here: only when student_referrals.status = '2').
```

Rules: never add a join because two columns have similar names. If the same
table is joined twice in one question (referrer student vs referred student),
the relationship stays table-level; the LLM uses different aliases (a
`sql_hints` template, Step 6, shows how).

The registry validates that both tables/columns exist when the app starts.

## Step 2 — Add the query_catalog entry

`core_student.yaml` → `query_catalog:`. Full annotated template:

```yaml
  student_referral:                           # intent name (snake_case)
    terms: [referral, referred, referrer, who referred, reference, lead source]
    #   words/phrases that trigger this intent (substring match on the lower-cased
    #   question). Several intents may match one question - they are UNIONED.
    entities: [student]
    tables: [student_leads, student_admissions, student_referrals, lead_master]
    #   every table the question may use
    filter_tables: [student_leads, student_admissions]        # optional
    #   only these tables' columns may act as FILTERS. Leave lookup / output-only
    #   tables out, or their column names ("employee", "name") turn into bogus filters.
    preferred_filter_tables: [core_exam_results]              # optional
    #   when a filter name exists on several tables, resolve it to these first.
    output_columns:
      student_leads:      [id, applicant_name, enquiry_number, ticket_number]
      student_admissions: [id, student_lead_id, core_student_code, lead_source_id]
      student_referrals:  [id, student_lead_id, name, code, centre, status]
      lead_master:        [id, lead_name]
    #   per table: the columns the LLM may select AND join on.
    #   !! join key columns MUST be listed, or the validator rejects the join.
    required_relationships: [lead_to_admission, lead_to_referral, admission_to_lead_source]
    requires_identity: true                   # needs enquiry no. / hall ticket / student code
    identity_optional_with_filters: true      # optional: ...or any one filter instead (results)
    sql_hints:                                # optional, see Step 6
      - >
        Rule or SQL pattern the model must follow for THIS kind of question.
```

Choosing `terms`: use the words a user would type. Avoid very common words
that would trigger it accidentally. Add a plural/variant only when substring
matching doesn't already cover it (`refer` covers `referral`, `referred`).

## Step 3 — Scope gate (only if needed)

If the question won't contain a Core-student word ("student", "admission",
"result", "certificate", …) add a distinctive word to `semantic.module_terms`
(that is how "subject wise marks for semester 2" and "who gave the reference"
get in). Never add anything that opens LEGS/placement scope.

### Users say a different word for something ("trainee" for "student")

Add it to `semantic.synonyms` in `core_student.yaml` — do **not** add the variant
to every `terms` list:

```yaml
semantic:
  synonyms:
    student: [trainee, learner]     # canonical word: [variants users type]
    count: [strength, headcount]
```

The question is rewritten (plurals kept: *trainees* → *students*) before the scope
gate, intent terms and filter extraction, and the SQL model sees the rewritten
question as `interpreted_request.normalized_question`. Whole words only.

## Step 4 — Describe the columns (`column_semantics.yaml`)

```yaml
tables:
  core_exam_results:
    result_status:
      description: >          # sent to the LLM with the column - explain traps here
        Overall semester result. FAIL is stored as 'Fail' OR 'F'; match both.
      allowed_values: [Pass, P, Fail, F]     # only for non-ENUM columns with a closed vocabulary
    center_name:
      aliases: [center, centre]              # other words users say; also extraction vocabulary
      value_catalog: true                    # snapshot real values (Step 5)
```

Add a `description` whenever the physical name isn't self-explanatory
(`core_year` = academic year) or a neighbouring column is a trap.

## Step 5 — Filters: values and where they resolve

**Value catalog** — lets "Peenya" mean `BTC - PEENYA`, "2024" mean every 2024
session, "three" mean 3:

1. flag the column `value_catalog: true` (Step 4) — closed vocabularies only
   (< 500 distinct values),
2. `python scripts/extract_column_values.py` (writes `metadata/generated/column_values.json`;
   rerun when the data gains new centers/years),
3. behaviour: exact match wins (`Diploma` → `DIPLOMA`, not `POST DIPLOMA`);
   otherwise every stored value containing the words (≤ 10) becomes an `IN` list;
   no match → the wording is used as typed.

**Same filter word on several tables** — pick one:

- dynamic concepts (`center`, `stream`, `semester`, `academic year`): put the
  right table in the intent's `preferred_filter_tables`,
- curated concepts (`program`): add `alternates: {core_exam_results: program_name}`
  under `semantic.parameters.program`.

**A brand-new curated concept** is rarely needed (any column whose name or
alias the user says is found automatically). Add one to `semantic.parameters`
only for identifiers or irregular word orders; copy `core_student_code` /
`program` in [core_student.yaml](../metadata/manual/core_student.yaml).

## Step 6 — `sql_hints`: teach the model this question's SQL

Global instructions live in [`generator.py`](../app/sql/generator.py#L33) and are
**generic** (named parameters, IN lists, "select every requested field",
anti-join, OR between identifiers). Anything specific to one kind of question
goes in that intent's `sql_hints` — it is only sent when the intent matched.

Good hints (they worked):

- *say what the data is*: "core_exam_results has one row per SUBJECT per semester;
  sgpa/cgpa/grand_total repeat on every row";
- *say which reading of an ambiguous phrase is right*: "'grand total of failed
  students' = a LIST with the grand_total column; COUNT only if asked 'how many'";
- **give a concrete SQL pattern** for anything with self-joins or unusual joins
  (the referrer-student template with `referrer_sa` / `referrer_sl` aliases fixed a
  case where the model silently answered about the wrong student).

Keep each hint short and testable.

## Step 7 — Tests

1. **Deterministic (no LLM, no DB writes)** — copy
   [`tests/intelligence/test_results_and_referrer_queries.py`](../tests/intelligence/test_results_and_referrer_queries.py):

   ```python
   def test_my_new_question_selects_the_right_schema():
       analysis, selection = _analyze_and_select("<a real question a user would type>")
       assert "my_intent" in analysis.intents
       assert analysis.clarification is None
       assert {"my_table"} <= set(selection.tables)
       assert {p.name: p for p in analysis.parameters}["my_filter"].table == "my_table"
   ```
   Also add a negative test (missing identifier → clarification) and one for any
   trap you found in Step 0 (e.g. "dead column not exposed").
2. **NL catalog** — add an entry to [`tests/natural_language_queries.md`](../tests/natural_language_queries.md)
   (id `NLQ-nn`) and run `python scripts/run_nl_query_testcases.py`.
3. **Whole suite** — `python -m pytest -q`. Counts of exposed columns are
   asserted in a couple of tests (`selected_columns == 12`); update them when you
   intentionally add columns.

## Step 8 — Try it live and read the trace

```powershell
.\.venv\Scripts\python.exe scripts\debug_server.py --port 8124     # then open http://127.0.0.1:8124/
```

Restart the server after every YAML/code change (it loads metadata once).
Ask the question; open the stages in order and stop at the first wrong one:

| Look at | If it is wrong, the cause is usually | Fix in |
|---|---|---|
| **Clarification** shown | scope word missing (N2), no `terms` match (N3), identity/filter gate (N6), ambiguous column | Step 3 / 2 / `identity_optional_with_filters` / Step 5 |
| `query_analysis` → `intents` | wrong or missing intent | `terms` (Step 2) |
| `query_analysis` → `parameters` | a filter missing, a bogus filter, wrong table, wrong value | stop words / `filter_tables` (N4); `preferred_filter_tables`, `alternates`, value catalog, aliases (N5) |
| `schema_selection` | column or table the SQL needs isn't there | `output_columns`, `tables`, `required_relationships` |
| `sql_generation` → SQL | model ignored/misread the data | `description`, `allowed_values`, `sql_hints` (Step 6) |
| `sql_validation` errors | unknown table/column/join/`:param` | add the column/relationship — or the model hallucinated: sharpen `sql_hints` (the errors are fed back on retry) |
| `db_execution` rows | the SQL is right but data is odd | profile the column again (Step 0) — dead column? duplicates? |
| final answer / badge | answer LLM slip, or numbers not in rows | usually leave as is: the badge is *meant* to flag it; the result table under the answer is the truth |

Programmatic check without a browser: `POST /api/query {"query": "..."}` then
`GET /api/trace/<trace_id>` (the same trace is in `logs/query_audit.jsonl`).

## Step 9 — Document

Add the intent to the table in [architecture.md §4](architecture.md) and, if you
introduced a new metadata key or script, to §3. Note any limitation you found in §6.

---

## When is a *code* change needed?

| Situation | Where |
|---|---|
| A new kind of filter grammar (ranges, "between", negation) | [`entity_resolver.py`](../app/intelligence/entity_resolver.py) (N4) — add a test that also proves the old grammar still passes |
| A word that keeps being swallowed as a filter value | `_STOPWORDS` in `entity_resolver.py`; or narrow the intent's `filter_tables` |
| A new validator rule (e.g. forbid a construct) | [`validator.py`](../app/sql/validator.py) (N9) |
| Rule that applies to *every* question | generic instruction list in [`generator.py`](../app/sql/generator.py#L33) |
| A new **answer layout** (mark sheet, statement, roster …) instead of an LLM sentence | [`presenter.py`](../app/response/presenter.py) (N11): add a `_present_*(columns, rows, truncated, filters)` function that recognises the column set, returns a `Presentation` (or `None`), and list it in `_PRESENTERS`; then make the intent's `sql_hints` select exactly those columns (grade/GP only when asked, totals only when asked). Add a test in [`tests/response/test_presenter.py`](../tests/response/test_presenter.py) |
| A second LLM provider | `SQLClient`/`AnswerClient` classes + `build_configured_*` in `generator.py` / `formatter.py`; provider switch is `.env` `LLM_PROVIDER` |
| A new module outside Core students | a new `metadata/manual/<module>.yaml` + wiring in [`registry.py`](../app/metadata/registry.py)/`loader.py`; get the identity bridge verified first (README "Current scope") |

## Worked examples (what was actually added, for reference)

**Mark sheet output** — subject-wise results are shown by `presenter.py` as the student's
repeated details (points), then one table per exam attempt (regular, supplementary 1…10;
midterm/endterm/total, grade & GP only when asked, subject Pass/Fail) with each attempt's
grand total / SGPA / CGPA / overall result as points. What the question means by
"regular", "supplementary 2" or "consolidated" is read deterministically by
[`result_scope.py`](../app/intelligence/result_scope.py), handed to the SQL model in
`interpreted_request.result_scope`, checked by the validator and enforced again by the
presenter. A consolidated request without a semester is answered with a clarification.
This is the pattern for any question whose wording selects *which rows* (attempts,
periods…): detect it in code, pass it as scope, enforce it in validator + presenter.

**Results by student OR by cohort filters** — `student_result` entry:
`identity_optional_with_filters: true`, `preferred_filter_tables: [core_exam_results]`,
`program` `alternates`, value catalogs on center/program/stream/academic year,
`result_status` description (`'Fail'` or `'F'`), `student_leads` added *only to name
the student* (the results table's name column is dead), 5 `sql_hints`.

**Referrer details (employee / student / user)** — relationships
`referral_to_employee`, `referral_to_referrer_admission` (`data_verified`),
`employee_to_user` (Laravel); `student_referrer_details` entry with
`filter_tables` excluding `employees`/`users`, two SQL-pattern hints; column
description that decodes `student_referrals.status` (`'1'` student, `'2'` staff,
`'3'` alumni, `'4'` other).

## Definition of done

- [ ] Laravel model / data profile checked; traps noted (Step 0)
- [ ] relationships `verified: true` with evidence in `description`
- [ ] `query_catalog` entry: `terms`, `tables`, `output_columns` (incl. join keys), `required_relationships`
- [ ] no sensitive or dead columns exposed
- [ ] `column_semantics` descriptions / `allowed_values` / `value_catalog` (+ `extract_column_values.py` run)
- [ ] `sql_hints` for anything non-obvious
- [ ] deterministic tests + NLQ entry; `python -m pytest -q` green
- [ ] tried live, trace read stage by stage, wrong-answer cases understood
- [ ] docs updated (architecture §3/§4/§6)
