# NTTF Model Audit (Phase 1)

**Scope of this audit.** This repository contains the *original NTTF Laravel
application's* Eloquent model source under `app/Models/` (365 `.php` files) —
this is not a separate reference project; it is the ground truth the rest of
this Python pipeline (`metadata/generated/schema.json`,
`metadata/manual/core_student.yaml`, `metadata/manual/column_semantics.yaml`)
is derived from and curated against. "Cross-referencing against the original
NTTF project" below means comparing these Laravel models against the
generated schema and the Truth Layer.

**Method.** All 365 model files were parsed programmatically (not read
individually) for: class name, declared `$table`, `$fillable`, and
relationship methods (`belongsTo`/`hasOne`/`hasMany`/`belongsToMany`). Where
a model declares no `$table`, Laravel's default naming convention
(`snake(plural(ClassName))`) was approximated to guess the table it maps to.
This approximation is **not** a full re-implementation of Laravel's
inflector — it gets irregular plurals wrong (`Syllabus`→`Syllabi`,
`Quiz`→`Quizzes`, `PI`-acronym splitting) — so its "no matching table" output
below is a starting point for follow-up, not a confirmed defect list. The
9 models actually used by the current chatbot's Truth Layer were read in
full and checked line-by-line against `metadata/manual/core_student.yaml`
and `metadata/generated/schema.json`.

---

## 1. Inventory by directory

| Directory | File count | Character |
|---|---|---|
| `app/Models/` (flat, no subdirectory) | 252 | Mixed: HR/payroll (Stipend*, Staff*), fee/demand/refund logic (Core*Refund*, Core*Demand*), convocation, principal reporting, LEGS result/attendance tracking, misc admin (Notification, User, LoginActivity) |
| `CommonModels/` | 36 | Shared reference/lookup data used across Core and LEGS: `Centres`, `Address`, `Bank`, `StudentPayment`, `ContactDetails`, `FamilyDetails`, zones/states/cities |
| `LegsProgrammeModels/` | 31 | LEGS programme domain (out of the current chatbot's scope per the Truth Layer's `excluded_domains`) |
| `AcademicSectionModels/` | 15 | Programme/stream/subject/semester catalog shared by Core and LEGS |
| `IndustryPartnerModels/` | 14 | Industry partner / placement domain (out of scope) |
| `CoreProgrammeModels/` | 6 | **The Core Student domain the chatbot currently serves**: `Studentlead`, `StudentAdmission`, `StudentEnrollment`, `StudentPaymentTransactions`, `StudentCentreCoursePreferences`, `Invoices` |
| `QuizModels/` | 6 | Quiz/exam question bank (out of scope) |
| `AwardingBody/` | 5 | External awarding-body reporting (out of scope) |
| **Total** | **365** | |

**Auto-generated vs. hand-written.** All 365 files are hand-written
Eloquent models (no scaffolding markers, no `@generated` headers). Richness
varies a lot and correlates with business importance:
- 27 models have 5+ relationship methods and heavy custom logic
  (`StudentAdmission`: 27 relationships and 122 fillable fields;
  `Studentlead`: 13 relationships; `StudentEnrollment`: 10;
  `StudentPayment`: 9) — these are the actively-maintained transactional
  core.
- 12 models declare neither `$fillable` nor any relationship — thin
  lookup/reference stubs (e.g. simple master-data tables).
- Naming is inconsistent project-wide: PascalCase directories
  (`CommonModels`) sit next to 252 ungrouped files directly under
  `app/Models/`, and one class (`Core_student_semester_logs`) uses
  snake_case where every sibling uses PascalCase — a sign of organic,
  multi-author growth rather than a generated/enforced layout.

---

## 2. Deep audit: Core Student domain (what the chatbot uses today)

This is the only part of the schema the Truth Layer (`core_student.yaml`)
currently curates, so it's the part audited field-by-field.

### `Studentlead` (`app/Models/CoreProgrammeModels/Studentlead.php`)
- **Table:** `public $table = "student_leads";` — explicit, matches schema. ✅
- **Fillable (15):** `uuid, program_type, ticket_number, applicant_name, dob, center_core_program_id, college_academic_program_stream_branch_id, refer_code, mobile, lateral_entry, created_by, updated_by, status, enquiry_number, created_with`
- **Relationships (13, selected):** `getCompleteAdmissionDetails`/`getFullAdmission`/`getStudentAdmission` → `StudentAdmission` (hasOne, three differently-named accessors for the *same* relationship — redundant but harmless); `getStudentEnrollmentDetails` → `StudentEnrollment`; `getPaymentTransactions` → `StudentPaymentTransactions`; `getStudentAdmissionStatusChangeLog` → `CoreStudentAdmissionStatusChangeLog` (hasMany); `getPreference` → `StudentCentreCoursePreferences` (hasMany).
- **Truth Layer cross-check:** ✅ Matches. Uses `SoftDeletes` + Spatie `LogsActivity` (soft-delete-aware; the schema extractor's `deleted_at` column on this table is consistent with that).

### `StudentAdmission` (`app/Models/CoreProgrammeModels/StudentAdmission.php`)
- **Table:** not declared; Laravel default `student_admissions` matches schema. ✅
- **Fillable:** 122 fields (too many to enumerate; matches the ~130-column table observed in `schema.json`).
- **Relationships (27):** `getStudentLead` → `Studentlead` (hasOne); `results` → `CoreExamResults` (hasMany); `supplementaryResults` → `CoreExamResultSupplymentary`; `getStudentEnrollmentDetails` → `StudentEnrollment`; `totalAdvancePayments` → `StudentPayment` (hasMany, **not** `StudentPaymentTransactions** — see §3); plus 20+ lookup relationships (program, stream, center, quota, fee plan, batch, etc.) not currently in the Truth Layer's scope.
- **Truth Layer cross-check:** ✅ `lead_to_admission` (via `Studentlead::getStudentAdmission`) confirmed.

### `StudentEnrollment` (`app/Models/CoreProgrammeModels/StudentEnrollment.php`)
- **Table:** `student_enrollments` (explicit). ✅
- **Fillable (32):** includes `student_lead_id`, `exam_result_id`, `centre_id`, `course_id`, `fee_quota_id`, `academic_year_id`, `core_fee_plan_id`.
- **Relationships:** `getStudentLead` → `Studentlead`; `getPaymentDetails` → `StudentPayment` (hasMany); `getDocument` → `StudentEnrollmentDocument` (hasMany).
- **Truth Layer cross-check:** ✅ `lead_to_enrollment` and `enrollment_to_document` confirmed exactly.

### `CoreExamResults` (`app/Models/CoreExamResults.php`)
- **Table:** `core_exam_results` (explicit). ✅
- **`student()`:** `belongsTo(StudentAdmission::class, 'core_student_id')` — **confirms Truth Layer's `admission_to_exam_result`** (`student_admissions.id = core_exam_results.core_student_id`) exactly. ✅

### `StudentCertificate` (`app/Models/StudentCertificate.php`)
- **Table:** `student_certificates` (explicit). ✅
- **`studentAdmission()`:** `belongsTo(StudentAdmission::class, 'student_admission_id')` — **confirms Truth Layer's `admission_to_certificate`** exactly. ✅
- Also has un-curated relationships to `Centres`, `CollegeAcademicProgrammes`, `CollegeAcademicProgrammeStreams` (center/program/stream) — candidates for future Truth Layer expansion if certificate queries ever need to filter by center/program.

### `StudentPayment` (`app/Models/CommonModels/StudentPayment.php`) — **the table the Truth Layer's `student_payment` entity actually means**
- **Table:** `student_payments` (explicit). ✅
- **Fillable (30):** includes both `student_lead_id` **and** `student_enrollment_id`.
- **Relationships found (this audit read the full file, not just the regex pass):**
  - `getStudentLeadDetails()`: `hasOne(Studentlead::class, 'id', 'student_lead_id')` → **confirms Truth Layer's `lead_to_payment`** exactly. ✅
  - `getAdmissionDetails()`: `hasOne(StudentAdmission::class, 'student_lead_id', 'student_lead_id')` — a relationship **not yet in the Truth Layer** (payment→admission via the shared `student_lead_id`, not `student_admissions.id`). Candidate for a future curated relationship if payment-filtered-by-admission-status queries are needed.
  - `getCurrentEnrollmentDetails()`: `hasOne(StudentEnrollment::class, 'student_lead_id', 'student_lead_id')` — **this independently confirms** the Truth Layer's own caution on `enrollment_to_payment`: the YAML already says "the column [`student_enrollment_id`] exists, but the Laravel models currently navigate payments by `student_lead_id` rather than this key" — the actual model code proves that note correct. The `student_enrollment_id` column is present in `$fillable` but genuinely unused for this navigation path, exactly as the Truth Layer states. ✅ No change needed; the existing `verified: false` flag is correct and now independently evidenced.

### `StudentPaymentTransactions` (`app/Models/CoreProgrammeModels/StudentPaymentTransactions.php`) — **a different table from `StudentPayment`, not currently modeled in the Truth Layer at all**
- **Table:** not declared; Laravel default → `student_payment_transactions`, which **is a separate real table** from `student_payments` in `schema.json`. Confirmed distinct via direct inspection.
- **Fillable (26):** payment-gateway fields (`txnid`, `hash`, `key`, `service_provider`, `udf1`..`udf10`) — this is clearly the online payment-gateway transaction log, not the authoritative payment ledger.
- **`getStudent()`:** `hasOne(Studentlead::class, 'ticket_number', 'student_id')` — joins on `student_leads.ticket_number` (a string business key), **not** `student_leads.id`. This is a materially different (weaker, string-keyed) join style than every other curated relationship in the Truth Layer.
- **Not currently referenced anywhere in `core_student.yaml`.** Flag: if a future query needs online-payment-gateway status (as opposed to the recorded fee ledger in `student_payments`), this table/relationship would need its own curated entry — do not assume `StudentPayment` and `StudentPaymentTransactions` are interchangeable; they are genuinely different tables with different keys.

### `StudentCentreCoursePreferences` / `Invoices`
- Table names inferred correctly by Laravel's default convention (`student_centre_course_preferences`, `invoices`), both present in schema. Not currently referenced by the Truth Layer's `query_catalog`; low-relationship-count, lower priority.

---

## 3. Divergences and flags

1. **`student_payments` vs. `student_payment_transactions` are two different tables with two different models** (`StudentPayment` vs. `StudentPaymentTransactions`) and two different join keys (`id`/`student_lead_id` vs. `ticket_number`/`student_id`). Easy to confuse by name alone — the Truth Layer currently (correctly) only curates `student_payments`.
2. **`StudentAdmission::totalAdvancePayments()` and `StudentEnrollment::getPaymentDetails()` both point at `StudentPayment`, not `StudentPaymentTransactions`** — consistent with (1); the payment ledger, not the gateway log, is the one connected from both admission and enrollment.
3. **`OjtFeedback` model likely can't resolve its own table.** It declares no `$table` and no override; Laravel's default convention would produce `ojt_feedbacks`, but the real table in `schema.json` is singular: `ojt_feedback`. This looks like a latent bug in the *original* Laravel codebase (out of scope for us to fix), not something introduced by this project — flagged for awareness only.
4. **`IndustryPartnerContract`** declares `protected $table = 'IndustryPartnerContract';` (mixed-case, not snake_case) — works only because MySQL table names are case-insensitive on the app's configured collation; stylistically inconsistent with every other model.
5. **Redundant accessor methods**: `Studentlead` exposes three separately-named relationships (`getCompleteAdmissionDetails`, `getFullAdmission`, `getStudentAdmission`) that all resolve to the same `StudentAdmission` hasOne — harmless, but a sign of organic growth/copy-paste rather than a clean single source of truth. Not a concern for the Truth Layer (which already picks one canonical relationship name, `lead_to_admission`).
6. **~44 tables in `schema.json` have no model file whose name (explicit or default-Laravel-inferred) matches them.** After removing framework/package tables that legitimately have no Eloquent model (Laravel's own `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `migrations`, `sessions`, `password_reset_tokens`, `personal_access_tokens`; Spatie permissions' `permissions`, `roles`, `model_has_permissions`, `model_has_roles`, `role_has_permissions`) and irregular-pluralization false positives from this audit's approximate matching (`core_syllabi`/`latest_core_syllabi` ↔ `CoreSyllabus`/`LatestCoreSyllabus`, `legs_quizzes`/`quizzes`/`quizzes_bkp` ↔ `LegsQuiz`/`Quiz`, `legs_award_of_classes` ↔ `LegsAwardOfClass`, `legs_p_i_visit*` / `management_review_with_p_i*` ↔ the `PI`-acronym classes, `stipend_attendance_bonuses` ↔ `StipendAttendanceBonus`, `legs_student_media` ↔ `LegsStudentMedia`), a genuine residual list remains with no obviously-named model at all:
   `student_qualifications`, `student_placement_career_status`, `student_red_status_logs`, `quota_master_config`, `semester_result_locks`, `report_core_year_master_settings`, `principal_report_core_year_master_settings`, `placement_year_create`, `placement_year_master_settings`, `center_daily_admission_snapshots`, `lead_management_daily_snapshots`, `centre_program_zone_centre_academic_program_stream_type`, `biometric_unmatched_logs`.
   These may be tables the original app queries via raw SQL/reporting code rather than Eloquent — not confirmed as gaps, just flagged for follow-up if any of them ever becomes relevant to a query the chatbot needs to answer.

---

## 4. ER summary (text form) — Core Student domain

```
student_leads (Studentlead)                      [registration/enquiry root]
  │  id ─────────────────────────────────────────────────┐
  │                                                        │  student_lead_id (1:1)
  ├── student_admissions.student_lead_id  (StudentAdmission)
  ├── student_enrollments.student_lead_id (StudentEnrollment, 1:1)
  ├── student_payments.student_lead_id    (StudentPayment, 1:many, verified)
  └── student_payment_transactions        (via ticket_number = student_id,
                                            NOT curated in the Truth Layer)

student_admissions (StudentAdmission)             [admission root]
  │  id
  ├── core_exam_results.core_student_id       (1:many, verified)
  ├── student_certificates.student_admission_id (1:many, verified)
  └── student_payments.student_lead_id         (payment→admission via shared
                                                 lead id - NOT via admission.id;
                                                 not yet curated)

student_enrollments (StudentEnrollment)           [enrollment record]
  │  id
  ├── student_enrollment_documents.student_enrollment_id (1:many, verified)
  └── student_payments.student_enrollment_id  (column exists but UNUSED by
                                                the actual Laravel navigation
                                                path - correctly marked
                                                verified:false in the Truth
                                                Layer; independently confirmed
                                                by this audit)
```

---

## Summary

- All 4 relationships the Truth Layer currently marks `verified: true` for
  the Core Student domain were independently confirmed against the actual
  Laravel model source, exactly as curated.
- The one relationship marked `verified: false` (`enrollment_to_payment`)
  was also independently confirmed to be correctly distrusted — the model
  code proves payments are navigated by `student_lead_id`, not
  `student_enrollment_id`, even though that column exists.
- One new, not-yet-curated relationship was found
  (`StudentPayment::getAdmissionDetails` → `student_admissions` via
  `student_lead_id`) — a candidate for future Truth Layer expansion, not
  acted on here.
- `StudentPayment` (`student_payments`) and `StudentPaymentTransactions`
  (`student_payment_transactions`) are confirmed to be two different tables
  with two different join keys — a real naming trap for anyone extending
  the Truth Layer later.
- No corrections to `metadata/manual/core_student.yaml` were needed as a
  result of this audit — everything currently curated for the chatbot's
  active scope checked out.
