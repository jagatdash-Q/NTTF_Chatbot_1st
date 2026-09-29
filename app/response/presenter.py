"""Deterministic, user-friendly layouts for result shapes we recognise.

A sentence is the wrong output for a mark sheet. When the returned columns
look like subject-wise marks or per-semester results, the answer is laid out
directly from the rows - student details as points, then one table per exam
attempt with the semester totals as points under it - with no language-model
call. Every value is copied from a row, so there is nothing to hallucinate and
nothing for the faithfulness check to flag. Any other shape returns None and
falls back to the LLM sentence (see app.graph.root.synthesize_answer).

Layout rules
------------
* A value that repeats on every row (father name, current status, academic
  year, program ...) is a *point* under the student's name; only data that
  differs per subject goes in a table.
* Exam attempts (core_exam_results.final_exam_type / suppl_no): NULL or
  'semester' = the regular exam; 'supplementary' + suppl_no 1..10 = attempts.
  Each attempt is its own table with its own SGPA / CGPA / grand total /
  overall result.
* Subject result is derived from the grade (F = Fail, AB = Absent, A-E =
  Pass). The overall result of an attempt is Fail if the recorded status is
  Fail OR any subject in that attempt failed.
* "Consolidated" shows the latest attempt of each subject (what the student
  has actually achieved to date).
* Grade / GP columns appear only if the question mentioned them
  (ResultScope.show_grade).
* A grouped count ("strength center wise / program wise / ...") is a table of
  the group columns plus the count, the question's own filters as points above
  it, and the total as a point below.

To add a layout for another result shape: write a `_present_*` function that
takes (columns, rows, truncated, filters, scope) and returns a Presentation or
None, then list it in `_PRESENTERS`.
"""

from __future__ import annotations

from dataclasses import dataclass, field
from datetime import timedelta
from typing import Any, Callable

from app.intelligence.result_scope import ResultScope

# Column -> label shown to the user.
_LABELS = {
    "applicant_name": "Student name",
    "core_student_name": "Student name",
    "core_student_code": "Student code",
    "semester_id": "Semester",
    "program_name": "Program",
    "stream_name": "Stream",
    "academic_year": "Academic year",
    "core_year": "Academic year",
    "center_name": "Center",
    "status": "Current status",
    "batch_name": "Batch",
    "father_name": "Father name",
    "mother_name": "Mother name",
    "guardian_name": "Guardian name",
    "subject_name": "Subject",
    "subject_code": "Subject code",
    "midterm_marks": "Midterm",
    "endterm_marks": "Endterm",
    "total_marks": "Total",
    "grade": "Grade",
    "grade_point": "GP",
    "grand_total": "Grand total",
    "sgpa": "SGPA",
    "cgpa": "CGPA",
    "result_status": "Result",
    "final_exam_type": "Exam type",
    "suppl_no": "Supplementary no.",
    # Event schedule labels
    "event_name": "Event name",
    "event_type": "Event type",
    "company_name": "Company",
    "plant_name": "Plant",
    "event_date": "Date",
    "event_time": "Time",
    "selection_venue": "Venue",
    "hiring_location": "Job location",
    "position": "Role",
    "no_of_opening": "Openings",
    "company_spoc": "Company contact",
}

# Fixed display order of the well-known student details; any other column that
# is constant for the student is appended after them.
_FACT_COLUMNS = (
    "applicant_name", "core_student_name", "core_student_code", "semester_id",
    "program_name", "stream_name", "academic_year", "core_year", "center_name",
)
_SUBJECT_COLUMNS = ("subject_name", "subject_code", "midterm_marks", "endterm_marks", "total_marks")
_GRADE_COLUMNS = ("grade", "grade_point")
_ATTEMPT_COLUMNS = ("final_exam_type", "suppl_no")
_SUMMARY_COLUMNS = ("grand_total", "sgpa", "cgpa", "result_status")
_KNOWN = set(_FACT_COLUMNS) | set(_SUBJECT_COLUMNS) | set(_GRADE_COLUMNS) | set(_ATTEMPT_COLUMNS) | set(_SUMMARY_COLUMNS)

# Recorded result vocabulary (core_exam_results.result_status).
_RESULT_WORDS = {"p": "Pass", "pass": "Pass", "f": "Fail", "fail": "Fail", "ab": "Absent"}

# A cohort of more students than this reads better as one flat table.
_MAX_STUDENTS = 3
_MAX_BLOCKS = 24


@dataclass
class Section:
    title: str | None = None
    facts: list[tuple[str, str]] = field(default_factory=list)
    table_columns: list[str] = field(default_factory=list)
    table_rows: list[list[str]] = field(default_factory=list)
    summary_title: str | None = None
    summary: list[tuple[str, str]] = field(default_factory=list)


@dataclass
class Presentation:
    kind: str
    sections: list[Section]
    notes: list[str] = field(default_factory=list)

    def as_dict(self) -> dict[str, Any]:
        return {
            "kind": self.kind,
            "sections": [
                {
                    "title": s.title,
                    "facts": [list(pair) for pair in s.facts],
                    "table": {"columns": s.table_columns, "rows": s.table_rows} if s.table_columns else None,
                    "summary_title": s.summary_title,
                    "summary": [list(pair) for pair in s.summary],
                }
                for s in self.sections
            ],
            "notes": self.notes,
        }

    @property
    def text(self) -> str:
        """Markdown rendering - what API/script callers get as the answer."""
        lines: list[str] = []
        for section in self.sections:
            if section.title:
                lines += [f"### {section.title}", ""]
            lines += [f"- **{label}:** {value}" for label, value in section.facts]
            if section.facts:
                lines.append("")
            if section.table_columns:
                lines.append("| " + " | ".join(section.table_columns) + " |")
                lines.append("|" + "---|" * len(section.table_columns))
                for row in section.table_rows:
                    lines.append("| " + " | ".join(cell.replace("|", "/") for cell in row) + " |")
                lines.append("")
            if section.summary:
                if section.summary_title:
                    lines += [f"**{section.summary_title}**", ""]
                lines += [f"- {label}: {value}" for label, value in section.summary]
                lines.append("")
        lines += [f"_{note}_" for note in self.notes]
        return "\n".join(lines).strip()


# --- small value helpers --------------------------------------------------------

def _label(column: str) -> str:
    # "center_wise" (an alias the SQL gave a group column) reads as "Center".
    column = column[: -len("_wise")] if column.endswith("_wise") and len(column) > len("_wise") else column
    return _LABELS.get(column) or _LABELS.get(f"{column}_name") or column.replace("_", " ").capitalize()


def _cell(column: str, value: Any) -> str:
    if value is None or str(value).strip() == "":
        return "-"
    if isinstance(value, timedelta):
        total_seconds = int(value.total_seconds())
        hours = total_seconds // 3600
        minutes = (total_seconds % 3600) // 60
        period = "AM" if hours < 12 else "PM"
        display_hours = hours % 12
        if display_hours == 0:
            display_hours = 12
        return f"{display_hours:02d}:{minutes:02d} {period}"
    text = str(value).strip()
    if column == "result_status":
        return _RESULT_WORDS.get(text.lower(), text)
    return text


def _dedupe(rows: list[list[str]]) -> list[list[str]]:
    seen: set[tuple[str, ...]] = set()
    unique = []
    for row in rows:
        if tuple(row) not in seen:
            seen.add(tuple(row))
            unique.append(row)
    return unique


def _notes(truncated: bool) -> list[str]:
    return ["The result was cut off at the row limit - narrow the question (e.g. add a filter) to see the rest."] if truncated else []


def _flat_table(kind: str, columns: list[str], rows: list[dict[str, Any]], truncated: bool) -> Presentation:
    section = Section(
        table_columns=[_label(c) for c in columns],
        table_rows=_dedupe([[_cell(c, row.get(c)) for c in columns] for row in rows]),
    )
    return Presentation(kind=kind, sections=[section], notes=_notes(truncated))


# --- exam attempts and results --------------------------------------------------

def _attempt(row: dict[str, Any]) -> int:
    """0 = the regular exam (final_exam_type NULL or 'semester'); n >= 1 = supplementary attempt n."""
    if str(row.get("final_exam_type") or "").strip().lower() == "supplementary":
        try:
            return max(int(row.get("suppl_no") or 0), 1)
        except (TypeError, ValueError):
            return 1
    return 0


def _attempt_label(order: int) -> str:
    return "Regular exam" if order == 0 else f"Supplementary attempt {order}"


def _subject_result(row: dict[str, Any]) -> str:
    grade = str(row.get("grade") or "").strip().upper()
    if grade == "F":
        return "Fail"
    if grade == "AB":
        return "Absent"
    return "Pass" if grade in {"A", "B", "C", "D", "E"} else "-"


def _overall_result(recorded_rows: list[dict[str, Any]], subject_rows: list[dict[str, Any]] | None = None) -> str:
    """Fail if the recorded status (taken from `recorded_rows`) says Fail OR any
    of `subject_rows` failed. A supplementary attempt's recorded status also
    carries the student's standing in subjects that attempt did not re-sit,
    which is why it counts even when every re-sat subject passed."""
    subject_rows = recorded_rows if subject_rows is None else subject_rows
    recorded = {
        _RESULT_WORDS.get(str(row.get("result_status")).strip().lower(), str(row.get("result_status")).strip())
        for row in recorded_rows if row.get("result_status") not in (None, "")
    }
    if "Fail" in recorded or any(_subject_result(row) == "Fail" for row in subject_rows):
        return "Fail"
    if "Pass" in recorded or any(_subject_result(row) == "Pass" for row in subject_rows):
        return "Pass"
    return " / ".join(sorted(recorded)) or "-"


def _apply_scope(rows: list[dict[str, Any]], columns: list[str], scope: ResultScope | None) -> list[dict[str, Any]]:
    """The question's exam-type / attempt filter, enforced regardless of how
    the SQL was written."""
    if scope is None or scope.consolidated or scope.exam_type is None or "final_exam_type" not in columns:
        return rows
    if scope.exam_type == "regular":
        return [row for row in rows if _attempt(row) == 0]
    return [row for row in rows if _attempt(row) >= 1 and (scope.attempt is None or _attempt(row) == scope.attempt)]


# --- grouping ---------------------------------------------------------------------

def _student_key(row: dict[str, Any], columns: list[str]) -> Any:
    if "core_student_code" in columns:
        return row.get("core_student_code")
    return row.get("applicant_name") if "applicant_name" in columns else ""


def _group(rows: list[dict[str, Any]], key: Callable[[dict[str, Any]], Any]) -> list[list[dict[str, Any]]]:
    grouped: dict[Any, list[dict[str, Any]]] = {}
    for row in rows:
        grouped.setdefault(key(row), []).append(row)
    return list(grouped.values())


def _semester_sort(value: Any) -> tuple[int, Any]:
    try:
        return (0, int(value))
    except (TypeError, ValueError):
        return (1, str(value))


def _constant(column: str, rows: list[dict[str, Any]]) -> bool:
    return len({str(row.get(column)) for row in rows}) == 1


def _student_facts(
    rows: list[dict[str, Any]], columns: list[str], filters: dict[str, str], varying_extras: list[str],
) -> list[tuple[str, str]]:
    """The student's repeated details, as points: known ones in a fixed order,
    then every other column that is the same on all of the student's rows."""
    first = rows[0]
    known = [c for c in _FACT_COLUMNS if c != "semester_id" and (c in columns or c in filters)]
    extras = [c for c in columns if c not in _KNOWN and c not in varying_extras]
    return [
        (_label(c), _cell(c, first.get(c) if c in columns else filters.get(c)))
        for c in known + extras
    ]


# --- subject-wise marks -------------------------------------------------------------

def _present_subject_marks(
    columns: list[str], rows: list[dict[str, Any]], truncated: bool,
    filters: dict[str, str], scope: ResultScope | None,
) -> Presentation | None:
    has_subject = "subject_name" in columns or "subject_code" in columns
    has_marks = any(c in columns for c in ("midterm_marks", "endterm_marks", "total_marks", "grade", "grade_point"))
    if not (has_subject and has_marks) or not rows:
        return None
    rows = _apply_scope(rows, columns, scope)
    if not rows:
        return None

    students = _group(rows, lambda row: _student_key(row, columns))
    blocks = sum(len({(row.get("semester_id"), _attempt(row)) for row in student}) for student in students)
    if len(students) > _MAX_STUDENTS or blocks > _MAX_BLOCKS:
        return _flat_table("subject_marks", columns, rows, truncated)

    consolidated = bool(scope and scope.consolidated)
    show_grade = scope is None or scope.show_grade
    has_grade = "grade" in columns
    has_attempts = "final_exam_type" in columns
    summary_columns = [c for c in _SUMMARY_COLUMNS if c in columns]

    sections: list[Section] = []
    for student in students:
        varying = [c for c in columns if c not in _KNOWN and not _constant(c, student)]
        table_columns = (
            [c for c in _SUBJECT_COLUMNS if c in columns]
            + ([c for c in _GRADE_COLUMNS if c in columns] if show_grade else [])
            + varying
        )
        headers = (
            [_label(c) for c in table_columns]
            + (["Result"] if has_grade else [])
            + (["Attempt"] if consolidated else [])
        )

        def subject_cells(row: dict[str, Any], with_attempt: bool) -> list[str]:
            cells = [_cell(c, row.get(c)) for c in table_columns]
            if has_grade:
                cells.append(_subject_result(row))
            if with_attempt:
                cells.append(_attempt_label(_attempt(row)))
            return cells

        sections.append(Section(facts=_student_facts(student, columns, filters, varying)))
        semesters = list(dict.fromkeys(row.get("semester_id") for row in student))
        if "semester_id" not in columns and filters.get("semester_id"):
            semesters = [filters["semester_id"]]
        for semester in sorted(semesters, key=_semester_sort):
            semester_rows = [row for row in student if row.get("semester_id") == semester] if "semester_id" in columns else student
            semester_text = _cell("semester_id", semester) if semester is not None else None
            heading = f"Semester {semester_text}" if semester_text and semester_text != "-" else None

            if consolidated:
                sections.append(_consolidated_section(
                    heading, semester_rows, headers, subject_cells, summary_columns, has_grade,
                ))
                continue

            for order in sorted({_attempt(row) for row in semester_rows}):
                attempt_rows = [row for row in semester_rows if _attempt(row) == order]
                title_parts = [heading] if heading else []
                if has_attempts:
                    title_parts.append(_attempt_label(order))
                first = attempt_rows[0]
                summary = [(_label(c), _cell(c, first.get(c))) for c in summary_columns if c != "result_status"]
                if summary_columns:
                    summary.append(("Result", _overall_result(attempt_rows)))
                sections.append(Section(
                    title=" - ".join(title_parts) or None,
                    table_columns=headers,
                    table_rows=_dedupe([subject_cells(row, False) for row in attempt_rows]),
                    summary_title=(f"{_attempt_label(order)} result" if has_attempts else "Semester result") if summary else None,
                    summary=summary,
                ))
    notes = _notes(truncated)
    if consolidated:
        notes.insert(0, "Consolidated: the most recent attempt of each subject.")
    return Presentation(kind="subject_marks", sections=sections, notes=notes)


def _consolidated_section(
    heading: str | None,
    semester_rows: list[dict[str, Any]],
    headers: list[str],
    subject_cells: Callable[[dict[str, Any], bool], list[str]],
    summary_columns: list[str],
    has_grade: bool,
) -> Section:
    """Latest attempt of every subject of one semester."""
    latest: dict[Any, dict[str, Any]] = {}
    for row in sorted(semester_rows, key=_attempt):
        latest[row.get("subject_code") or row.get("subject_name")] = row
    chosen = list(latest.values())
    last_order = max(_attempt(row) for row in semester_rows)
    last_rows = [row for row in semester_rows if _attempt(row) == last_order]

    summary: list[tuple[str, str]] = [("Latest attempt on record", _attempt_label(last_order))]
    for column in summary_columns:
        if column != "result_status":
            summary.append((f"{_label(column)} (latest attempt)", _cell(column, last_rows[0].get(column))))
    # Fail if any subject's latest attempt failed, or the latest attempt's
    # recorded status is Fail (e.g. a subject that attempt did not re-sit).
    summary.append(("Overall result", _overall_result(last_rows, chosen if has_grade else last_rows)))
    return Section(
        title=f"{heading} - Consolidated" if heading else "Consolidated",
        table_columns=headers,
        table_rows=_dedupe([subject_cells(row, True) for row in chosen]),
        summary_title="Consolidated result",
        summary=summary,
    )


# --- per-semester results (no subject rows) -----------------------------------------

def _present_semester_results(
    columns: list[str], rows: list[dict[str, Any]], truncated: bool,
    filters: dict[str, str], scope: ResultScope | None,
) -> Presentation | None:
    has_subject = "subject_name" in columns or "subject_code" in columns
    if has_subject or "semester_id" not in columns or not rows:
        return None
    summary_columns = [c for c in _SUMMARY_COLUMNS if c in columns]
    if not summary_columns:
        return None
    rows = _apply_scope(rows, columns, scope)
    if not rows:
        return None

    students = _group(rows, lambda row: _student_key(row, columns))
    if len(students) != 1:
        # A cohort (e.g. every failed student): one flat table, student columns first.
        ordered = [c for c in _FACT_COLUMNS if c in columns] + summary_columns
        ordered += [c for c in columns if c not in ordered]
        return _flat_table("semester_results", ordered, rows, truncated)

    student = students[0]
    has_attempts = "final_exam_type" in columns
    varying = [c for c in columns if c not in _KNOWN and not _constant(c, student)]
    table_columns = ["semester_id"] + (["attempt"] if has_attempts else []) + summary_columns + varying

    def cell(column: str, row: dict[str, Any]) -> str:
        return _attempt_label(_attempt(row)) if column == "attempt" else _cell(column, row.get(column))

    ordered_rows = sorted(student, key=lambda row: (_semester_sort(row.get("semester_id")), _attempt(row)))
    section = Section(
        facts=_student_facts(student, columns, filters, varying),
        table_columns=["Attempt" if c == "attempt" else _label(c) for c in table_columns],
        table_rows=_dedupe([[cell(c, row) for c in table_columns] for row in ordered_rows]),
    )
    return Presentation(kind="semester_results", sections=[section], notes=_notes(truncated))


# --- grouped counts ("strength center wise") ----------------------------------------

_COUNT_NAME_HINTS = ("count", "strength", "total", "number", "num", "students", "trainees")
_MAX_GROUP_COLUMNS = 4
_FILTER_LABELS = {"status": "Status"}


def _is_whole_number(value: Any) -> bool:
    if isinstance(value, bool):
        return False
    try:
        number = float(str(value).strip())
    except (TypeError, ValueError):
        return False
    return number == int(number)


def _present_grouped_counts(
    columns: list[str], rows: list[dict[str, Any]], truncated: bool,
    filters: dict[str, str], scope: ResultScope | None,
) -> Presentation | None:
    result_columns = set(_SUBJECT_COLUMNS) | set(_GRADE_COLUMNS) | set(_ATTEMPT_COLUMNS) | set(_SUMMARY_COLUMNS)
    if len(columns) < 2 or not rows or len(columns) - 1 > _MAX_GROUP_COLUMNS or result_columns & set(columns):
        return None
    count_column = columns[-1]
    if not any(hint in count_column.lower() for hint in _COUNT_NAME_HINTS):
        return None
    if not all(_is_whole_number(row.get(count_column)) for row in rows):
        return None

    group_columns = columns[:-1]
    facts = [
        (_FILTER_LABELS.get(column, _label(column)), value)
        for column, value in filters.items() if column not in group_columns and column in _LABELS
    ]
    count_label = _label(count_column)
    summary = []
    if len(rows) > 1 and not truncated:
        total = sum(int(float(str(row.get(count_column)))) for row in rows)
        name = count_label if count_label.lower().startswith("total") else f"Total {count_label.lower()}"
        summary = [(name, f"{total:,}")]
    section = Section(
        facts=facts,
        table_columns=[_label(c) for c in group_columns] + [count_label],
        table_rows=_dedupe([
            [("(not set)" if c in group_columns and _cell(c, row.get(c)) == "-" else _cell(c, row.get(c))) for c in columns]
            for row in rows
        ]),
        summary=summary,
    )
    return Presentation(kind="grouped_counts", sections=[section], notes=_notes(truncated))


_EVENT_COLUMNS = {
    "event_name", "event_type", "company_name", "plant_name", "event_date",
    "event_time", "selection_venue", "hiring_location", "position", "no_of_opening",
}


def _present_event_schedule(
    columns: list[str], rows: list[dict[str, Any]], truncated: bool,
    filters: dict[str, str], scope: ResultScope | None,
) -> Presentation | None:
    event_matched = set(columns) & _EVENT_COLUMNS
    if len(event_matched) < 2 or not ("event_name" in columns or "event_date" in columns or "company_name" in columns):
        return None

    display_columns = [
        c for c in columns
        if c not in ("id", "company_id", "center_id", "hiring_id", "created_at", "updated_at", "created_by", "updated_by")
    ]
    if not display_columns:
        return None

    if not rows:
        return Presentation(
            kind="event_schedule",
            sections=[Section(title="Event Schedule", facts=[("Notice", "No events found matching the criteria.")])],
            notes=_notes(truncated),
        )

    table_rows = [
        [_cell(c, row.get(c)) for c in display_columns]
        for row in rows
    ]

    title = f"Scheduled Events ({len(rows)})" if len(rows) > 1 else "Scheduled Event"
    section = Section(
        title=title,
        table_columns=[_label(c) for c in display_columns],
        table_rows=_dedupe(table_rows),
    )
    return Presentation(kind="event_schedule", sections=[section], notes=_notes(truncated))


_PRESENTERS: tuple[
    Callable[[list[str], list[dict[str, Any]], bool, dict[str, str], ResultScope | None], Presentation | None], ...
] = (
    _present_subject_marks,
    _present_semester_results,
    _present_event_schedule,
    _present_grouped_counts,
)


def build_presentation(
    columns: list[str], rows: list[dict[str, Any]], truncated: bool = False,
    filters: dict[str, str] | None = None, scope: ResultScope | None = None,
) -> Presentation | None:
    """`filters` maps a column name to the single value the question filtered
    it by (e.g. {"semester_id": "1"}); used only to fill student details the
    query did not select. `scope` says which exam attempts were asked for."""
    for present in _PRESENTERS:
        presentation = present(columns, rows, truncated, filters or {}, scope)
        if presentation is not None:
            return presentation
    return None
