# Query capabilities — what to test, and what "just works"

A reference for testing coverage: every kind of question the chatbot answers
today, the filter/grouping dimensions each accepts, and questions nobody
wrote a specific rule for but the system answers anyway because of how the
pipeline composes (see [architecture.md](architecture.md) for how). Verified
live against `nttf_prod` on 2026-09-22 unless noted.

For how to add a new intent/table, see [ADDING_A_NEW_QUERY.md](ADDING_A_NEW_QUERY.md).

---

## 1. The 8 intents

| Intent | What it answers | Needs |
|---|---|---|
| `student_listing` | student lists/details, **strength** (count) total or broken down by any dimension, hostel/gender breakdowns | nothing |
| `student_result` | subject marks, SGPA/CGPA, grand total, pass/fail — one student or a cohort | student code **or** ≥1 filter |
| `student_certificate` | certificates | identifier |
| `student_family` | father/mother/guardian details | identifier |
| `student_payment` | payments made / not made | nothing |
| `student_referral` | who referred the student, referral type | identifier |
| `student_referrer_details` | the referrer's own details (employee or student) | identifier |
| `student_attendance` | **new** — present/absent, late arrivals, continuous absence, lowest/highest attendance by dimension | nothing |

A question can trigger more than one intent at once (e.g. "student detail +
father name + SGPA" = `student_listing` + `student_family` + `student_result`)
and both answers are produced.

---

## 2. Filter/grouping dimensions (compose freely, not hand-coded per combo)

These columns are all on `student_admissions` and resolve generically — any
of them can be a **filter** ("...in batch 2024") or, for a strength/attendance
question, a **grouping** ("...batch wise"), and several can combine in one
question ("center and program wise", "batch 2024 and status active"):

| Dimension | Column | Say it as |
|---|---|---|
| Center | `center_name` | "center Peenya", "centre wise" |
| Program | `program_name` | "Fitter program", "program wise" |
| Stream | `stream_name` | "stream Diploma in ..." |
| Batch | `batch_name` | "batch 2024", "batch wise" |
| Section | `section_name` | "section CP15...", "section wise" |
| Group | `group_name` | "group wise" |
| Academic year | `core_year` | "academic year 2026 JUN" |
| Status | `status` | "status active", "status wise" |
| Gender | `gender` | "male"/"female", "boys"/"girls" (rewritten to gender), "gender wise" |
| Hostel/residency | `residency_type` | "hostel", "hostellers", "day scholars", "hostel wise" |

`gender`/`residency_type` support this exact question from the request that
started this work:

> "How many trainees are in hostel (boys & girls)?"
> → filters `residency_type = 'Hosteller'`, groups by `gender`
> → verified live: **female 345, male 1,771 (total 2,116 hostellers)**

> "How many students are day scholars center wise?"
> → verified live: 14 centers, **total 6,054 day scholars**

---

## 3. Attendance (`student_attendance`) — new this session

Source: `biometric_info` (device punch log), joined to `student_admissions`
via `punching_code` (see relationship `admission_to_biometric` in
[core_student.yaml](../metadata/manual/core_student.yaml) — not a declared
foreign key; ~7,600 of ~20,000 admissions with a `punching_code` have a
matching punch). A student with no `punching_code` at all is excluded from
presence/absence answers (untracked, not "absent").

**7 question types**, each combinable with any of the 10 filter/grouping
dimensions above (center, program, stream, batch, section, group, academic
year, status, gender, hostel) — over 70 testable combinations:

| # | Question type | Example | Verified live (2026-09-01, since "today" has no device data yet — see §6) |
|---|---|---|---|
| 1 | Present/absent count, one date | "What is the attendance on 2026-09-01?" | present 5,722 / absent 14,293 |
| 2 | Present count only | "How many students were present on 2026-09-01?" | 5,722 |
| 3 | Is one student present/absent | "Is student BNTC1525114 present on 2026-09-01?" | Yes (Karan Kumar Singh) |
| 4 | Lowest/highest attendance by dimension | "Which section had the lowest attendance on 2026-09-01?" | returns the section(s) with 0 present that day |
| 5 | Late arrivals, one date | "How many trainees arrived late on 2026-09-01?" | 757 (first punch after the assumed 09:30 cutoff) |
| 6 | Average late arrivals over a week | "What is the average number of late arrivals over the last week?" | correct query shape; returns NULL in this snapshot (no punches in the trailing week — see §6) |
| 7 | Continuous absence for N days | "How many trainees are absent continuously for 5 days?" | correct query shape; returns the whole tracked cohort in this snapshot (same reason) |

Filtered/grouped examples worth testing:
- "Which center had the lowest attendance on 2026-09-01?"
- "How many female students were present on 2026-09-01?"
- "Which batch has the most late arrivals?"
- "Is enquiry number E... present today?" (identity-based, like other intents)
- "How many hostellers were absent on 2026-09-01?" (residency_type + attendance combined)

---

## 4. New questions answered without a dedicated rule

Nobody wrote a specific `sql_hint` for these; they work because filters,
groupings and intents documented above compose freely — proof the design is
additive, not per-query hardcoding:

- **Any dimension pair/triple, attendance included**: "which program and
  stream had the lowest attendance on 2026-09-01" — the same GROUP BY rule
  used for "center and program wise" strength applies here too.
- **Gender/hostel crossed with any other filter**: "how many female
  hostellers are in batch 2024", "male day scholars program wise" — `gender`
  and `residency_type` are ordinary filter/grouping columns like `center` or
  `status`, so they combine with everything those do.
- **A specific student's attendance by any identifier**: enquiry number or
  hall ticket number work for "is this student present" exactly as they do
  for results/certificates/family questions — the identity mechanism is
  shared, not attendance-specific.
- **"boy"/"boys"/"girl"/"girls"/"hostel"/"boarder"/"dayscholar" in any
  sentence position**: these are rewritten to the real column words
  (`male`/`female`/`Hosteller`/`Day Scholar`) before matching, the same
  mechanism that already turns "trainee" into "student" — so "boys in
  hostel", "hostel boys", "how many boys are hostellers" all resolve
  identically.
- **A cohort of absentees/late-comers as a LIST, not just a count**: "who
  arrived late on 2026-09-01", "list absent students in batch 2024" — the
  same `sql_hints` bullet that says how to select student columns "under
  their own names" for a listing question applies to attendance's student
  rows too.

---

## 5. Regression coverage

Broad regression-testing across existing (non-attendance) query types
alongside the new ones surfaced several general extraction/generation gaps,
all fixed with tests, none specific to attendance:

- `tests/response/test_answer_validator.py` — faithfulness-check edge cases
  (a restated calendar date, a number named in the question but not
  extracted as a parameter, a NULL single-aggregate, a zero-count aggregate
  worded "zero"/"no") — general fixes; also correct any other date- or
  number-restating answer.
- `tests/intelligence/test_entity_resolver.py` — "student **with** code X"
  (a connecting word between "student" and "code") extracted nothing; a
  closed-enum value with no concept word nearby ("total **active** student
  strength", not "status active") was silently dropped; a bare academic
  year ("in **2026 jun** and live in hostel", not "academic year 2026 jun")
  was silently dropped, both producing an unfiltered or wrong-filtered
  answer instead of a clarification.
- One live SQL-generation bug: a "is `<student>` present" query that also
  selects other student-detail columns was rejected by MySQL's
  `sql_mode=only_full_group_by` — fixed by grouping on each joined table's
  primary key instead of display columns (`student_attendance.sql_hints`).

Full suite: 168 passing.

---

## 6. Known caveats when testing

1. **The device attendance data (`biometric_info`) stops on 2026-09-03** in
   this snapshot, while "today" is later than that — so any "today"/"this
   week"/"last N days" attendance question correctly returns 0 present /
   the whole cohort absent, which is the right answer for this data, not a
   bug. Test with an explicit historical date (e.g. `2026-09-01`) to see
   non-trivial numbers.
2. **"Late" uses an assumed 09:30 cutoff** — there is no configured
   reporting/session-start time in the schema yet. Tell the system the real
   cutoff (or per-center/batch class start time from `core_timetable_attendance.session_start_time`,
   not yet wired in) and the `student_attendance.sql_hints` cutoff in
   core_student.yaml can be updated.
3. **Continuous-absence windows don't know about holidays/weekends** — a
   center closed for a few of the N days will inflate the "absent" count;
   the sql_hint says so and asks the answer to flag a surprisingly high
   count.
4. **Only ~38% of admissions have a matching punch** (7,597 of 20,015 with
   a `punching_code`) — expected for a live device rollout; those without a
   `punching_code` are correctly excluded rather than counted absent.
5. **Two distinct attendance questions asked in one sentence can still fail**
   ("...arrived late today **or** average over a week?") - live-tested: the
   SQL generator tried to answer both in one query and mis-scoped a table
   alias, so the query failed to execute (the pipeline reported this as a
   clarification, not a silent wrong answer). Each half asked separately
   works correctly (verified). This is the same "one message, several
   questions" limitation the rest of the system has - deferred until chat
   history exists (see §1 "One request, start to finish" in
   [architecture.md](architecture.md)) rather than patched with a
   combined-query special case.
