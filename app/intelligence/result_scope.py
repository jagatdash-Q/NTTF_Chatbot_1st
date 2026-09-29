"""Which exam attempts a results question is about, read from its wording.

core_exam_results keeps every attempt of a semester: the regular exam
(`final_exam_type` NULL or 'semester', `suppl_no` NULL) and each
supplementary attempt (`final_exam_type` = 'supplementary', `suppl_no` 1..10).
"Semester 1 result" therefore means *all* attempts, "regular result" only the
first, "supplementary 2" only that attempt, and "consolidated" the latest
attempt of every subject.

This is deterministic (regex, no LLM). The scope is handed to the SQL-writing
model as `interpreted_request.result_scope` (see the `student_result`
`sql_hints`) and is enforced again by the presenter, so a mis-filtered query
can never show the wrong attempts.
"""

from __future__ import annotations

import re
from dataclasses import dataclass

_WORDS = {
    "one": 1, "two": 2, "three": 3, "four": 4, "five": 5,
    "six": 6, "seven": 7, "eight": 8, "nine": 9, "ten": 10,
    "first": 1, "second": 2, "third": 3, "fourth": 4, "fifth": 5,
    "sixth": 6, "seventh": 7, "eighth": 8, "ninth": 9, "tenth": 10,
}
_NUMBER = r"(\d{1,2}|" + "|".join(_WORDS) + r")"

# "supplementary", "supplimentery", "suppl", "suppli", "supply", "supp"
_SUPPLEMENTARY = re.compile(r"\b(?:sup+l[a-z]*|supp)\b")
_REGULAR = re.compile(r"\bregular\b")
_CONSOLIDATED = re.compile(r"\bconsolidat[a-z]*\b")
_ALL_SEMESTERS = re.compile(
    r"\b(?:all|every|each)\s+(?:the\s+)?(?:sem|semesters?)\b|\bsemester[\s-]*wise\b|\bacross\s+(?:all\s+)?semesters\b"
)
_GRADE = re.compile(r"\bgrades?\b|\bgr\b|\bgp\b|\bgrade\s*points?\b")
_ATTEMPT_PATTERNS = (
    re.compile(rf"\battempts?\s*(?:no\.?|number|#)?\s*[=:]?\s*{_NUMBER}\b"),
    re.compile(rf"\b{_NUMBER}(?:st|nd|rd|th)?\s+(?:sup+l[a-z]*\s+)?attempt\b"),
    re.compile(rf"\b(?:sup+l[a-z]*|supp)\s*(?:attempt|no\.?|number|#)?\s*[=:]?\s*{_NUMBER}\b"),
)


@dataclass(frozen=True)
class ResultScope:
    exam_type: str | None = None      # "regular" | "supplementary" | None = every attempt
    attempt: int | None = None        # supplementary attempt number, if one was named
    consolidated: bool = False        # latest attempt of every subject
    all_semesters: bool = False       # user explicitly asked for every semester
    show_grade: bool = False          # grade / GP were mentioned

    def as_dict(self) -> dict:
        return {
            "exam_type": self.exam_type,
            "attempt": self.attempt,
            "consolidated": self.consolidated,
            "all_semesters": self.all_semesters,
        }


def _to_int(token: str) -> int | None:
    number = int(token) if token.isdigit() else _WORDS.get(token)
    return number if number and number > 0 else None


def detect_result_scope(query: str) -> ResultScope:
    text = " ".join(query.lower().split())

    attempt = None
    for pattern in _ATTEMPT_PATTERNS:
        match = pattern.search(text)
        if match and _to_int(match.group(1)):
            attempt = _to_int(match.group(1))
            break

    wants_regular = bool(_REGULAR.search(text))
    wants_supplementary = bool(_SUPPLEMENTARY.search(text)) or attempt is not None
    consolidated = bool(_CONSOLIDATED.search(text))

    if consolidated or (wants_regular and wants_supplementary) or not (wants_regular or wants_supplementary):
        exam_type = None
    else:
        exam_type = "supplementary" if wants_supplementary else "regular"
    return ResultScope(
        exam_type=exam_type,
        attempt=attempt if exam_type == "supplementary" else None,
        consolidated=consolidated,
        all_semesters=bool(_ALL_SEMESTERS.search(text)),
        show_grade=bool(_GRADE.search(text)),
    )
