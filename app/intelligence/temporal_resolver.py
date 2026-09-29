# """Deterministic temporal resolution for date and time expressions in user queries.

# Anchors relative terms (e.g., "next week", "tomorrow", "upcoming", "coming next")
# and explicit calendar dates (e.g., "25 September", "between 25 September and 30 September")
# to a concrete reference date without requiring LLM calls.
# """

# from __future__ import annotations

# import calendar
# import re
# from dataclasses import dataclass
# from datetime import date, datetime, timedelta
# from typing import Any

# _MONTH_MAP = {
#     "jan": 1, "january": 1,
#     "feb": 2, "february": 2,
#     "mar": 3, "march": 3,
#     "apr": 4, "april": 4,
#     "may": 5,
#     "jun": 6, "june": 6,
#     "jul": 7, "july": 7,
#     "aug": 8, "august": 8,
#     "sep": 9, "sept": 9, "september": 9,
#     "oct": 10, "october": 10,
#     "nov": 11, "november": 11,
#     "dec": 12, "december": 12,
# }

# _MONTH_PATTERN_STR = "|".join(re.escape(m) for m in sorted(_MONTH_MAP.keys(), key=len, reverse=True))

# # "between 25 September and 30 September", "from 25 Sep to 30 Sep", "25 Sep to 30 Sep"
# _DATE_RANGE_PATTERN = re.compile(
#     rf"\b(?:between|from)?\s*(?P<d1>\d{{1,2}})(?:st|nd|rd|th)?\s+(?P<m1>{_MONTH_PATTERN_STR})(?:\s+(?P<y1>\d{{4}}))?"
#     rf"\s*(?:and|to|-)\s*"
#     rf"(?P<d2>\d{{1,2}})(?:st|nd|rd|th)?\s+(?P<m2>{_MONTH_PATTERN_STR})?(?:\s+(?P<y2>\d{{4}}))?\b",
#     re.IGNORECASE,
# )

# # "on 25 September", "25th September 2026", "September 25"
# _EXPLICIT_DATE_DMY_PATTERN = re.compile(
#     rf"\b(?:on\s+|for\s+)?(?P<day>\d{{1,2}})(?:st|nd|rd|th)?\s+(?P<month>{_MONTH_PATTERN_STR})(?:\s+(?P<year>\d{{4}}))?\b",
#     re.IGNORECASE,
# )

# _EXPLICIT_DATE_MDY_PATTERN = re.compile(
#     rf"\b(?:on\s+|for\s+)?(?P<month>{_MONTH_PATTERN_STR})\s+(?P<day>\d{{1,2}})(?:st|nd|rd|th)?(?:\s+(?P<year>\d{{4}}))?\b",
#     re.IGNORECASE,
# )

# _ISO_DATE_PATTERN = re.compile(
#     r"\b(?P<year>\d{4})-(?P<month>\d{1,2})-(?P<day>\d{1,2})\b"
# )


# @dataclass(frozen=True)
# class TemporalResolution:
#     raw_expression: str
#     kind: str  # "exact_date" | "date_range" | "upcoming" | "next_one"
#     exact_date: str | None = None  # "YYYY-MM-DD"
#     start_date: str | None = None  # "YYYY-MM-DD"
#     end_date: str | None = None  # "YYYY-MM-DD"
#     is_next: bool = False
#     is_upcoming: bool = False

#     def as_dict(self) -> dict[str, Any]:
#         return {
#             "raw_expression": self.raw_expression,
#             "kind": self.kind,
#             "exact_date": self.exact_date,
#             "start_date": self.start_date,
#             "end_date": self.end_date,
#             "is_next": self.is_next,
#             "is_upcoming": self.is_upcoming,
#         }


# def _last_day_of_month(year: int, month: int) -> int:
#     return calendar.monthrange(year, month)[1]


# def resolve_temporal_expression(
#     query: str, reference_date: date | None = None
# ) -> TemporalResolution | None:
#     """Parse relative or explicit temporal references from a query.

#     Args:
#         query: Natural language query string.
#         reference_date: Anchor date for relative terms (defaults to today).

#     Returns:
#         TemporalResolution object or None if no temporal expression matched.
#     """
#     if reference_date is None:
#         reference_date = datetime.now().date()

#     q_lower = " ".join(query.lower().split())

#     # 1. Date Range: "between 25 September and 30 September", "from 25 Sep to 30 Sep"
#     range_match = _DATE_RANGE_PATTERN.search(q_lower)
#     if range_match:
#         d1 = int(range_match.group("d1"))
#         m1_str = range_match.group("m1").lower()
#         m1 = _MONTH_MAP[m1_str]
#         y1 = int(range_match.group("y1")) if range_match.group("y1") else reference_date.year

#         d2 = int(range_match.group("d2"))
#         m2_str = range_match.group("m2").lower() if range_match.group("m2") else m1_str
#         m2 = _MONTH_MAP[m2_str]
#         y2 = int(range_match.group("y2")) if range_match.group("y2") else y1

#         try:
#             start = date(y1, m1, d1)
#             end = date(y2, m2, d2)
#             return TemporalResolution(
#                 raw_expression=range_match.group(0),
#                 kind="date_range",
#                 start_date=start.isoformat(),
#                 end_date=end.isoformat(),
#             )
#         except ValueError:
#             pass

#     # 2. ISO Date: "2026-09-25"
#     iso_match = _ISO_DATE_PATTERN.search(q_lower)
#     if iso_match:
#         try:
#             dt = date(int(iso_match.group("year")), int(iso_match.group("month")), int(iso_match.group("day")))
#             return TemporalResolution(
#                 raw_expression=iso_match.group(0),
#                 kind="exact_date",
#                 exact_date=dt.isoformat(),
#             )
#         except ValueError:
#             pass

#     # 3. Explicit Calendar Date: "25 September", "September 25"
#     dmy_match = _EXPLICIT_DATE_DMY_PATTERN.search(q_lower)
#     if dmy_match:
#         day = int(dmy_match.group("day"))
#         month = _MONTH_MAP[dmy_match.group("month").lower()]
#         year = int(dmy_match.group("year")) if dmy_match.group("year") else reference_date.year
#         try:
#             dt = date(year, month, day)
#             return TemporalResolution(
#                 raw_expression=dmy_match.group(0),
#                 kind="exact_date",
#                 exact_date=dt.isoformat(),
#             )
#         except ValueError:
#             pass

#     mdy_match = _EXPLICIT_DATE_MDY_PATTERN.search(q_lower)
#     if mdy_match:
#         day = int(mdy_match.group("day"))
#         month = _MONTH_MAP[mdy_match.group("month").lower()]
#         year = int(mdy_match.group("year")) if mdy_match.group("year") else reference_date.year
#         try:
#             dt = date(year, month, day)
#             return TemporalResolution(
#                 raw_expression=mdy_match.group(0),
#                 kind="exact_date",
#                 exact_date=dt.isoformat(),
#             )
#         except ValueError:
#             pass

#     # 4. Relative: "tomorrow"
#     if re.search(r"\btomorrow\b", q_lower):
#         target = reference_date + timedelta(days=1)
#         return TemporalResolution(
#             raw_expression="tomorrow",
#             kind="exact_date",
#             exact_date=target.isoformat(),
#         )

#     # 5. Relative: "today"
#     if re.search(r"\btoday\b", q_lower):
#         return TemporalResolution(
#             raw_expression="today",
#             kind="exact_date",
#             exact_date=reference_date.isoformat(),
#         )

#     # 6. Relative: "yesterday"
#     if re.search(r"\byesterday\b", q_lower):
#         target = reference_date - timedelta(days=1)
#         return TemporalResolution(
#             raw_expression="yesterday",
#             kind="exact_date",
#             exact_date=target.isoformat(),
#         )

#     # 7. Relative: "next week" (Next calendar week: Monday to Sunday)
#     if re.search(r"\bnext\s+week\b", q_lower):
#         days_ahead = 7 - reference_date.weekday()  # Next Monday
#         next_monday = reference_date + timedelta(days=days_ahead)
#         next_sunday = next_monday + timedelta(days=6)
#         return TemporalResolution(
#             raw_expression="next week",
#             kind="date_range",
#             start_date=next_monday.isoformat(),
#             end_date=next_sunday.isoformat(),
#         )

#     # 8. Relative: "this week" (Current calendar week: Monday to Sunday)
#     if re.search(r"\bthis\s+week\b", q_lower):
#         start_of_week = reference_date - timedelta(days=reference_date.weekday())
#         end_of_week = start_of_week + timedelta(days=6)
#         return TemporalResolution(
#             raw_expression="this week",
#             kind="date_range",
#             start_date=start_of_week.isoformat(),
#             end_date=end_of_week.isoformat(),
#         )

#     # 9. Relative: "next month"
#     if re.search(r"\bnext\s+month\b", q_lower):
#         if reference_date.month == 12:
#             next_m = 1
#             next_y = reference_date.year + 1
#         else:
#             next_m = reference_date.month + 1
#             next_y = reference_date.year
#         start_date = date(next_y, next_m, 1)
#         end_date = date(next_y, next_m, _last_day_of_month(next_y, next_m))
#         return TemporalResolution(
#             raw_expression="next month",
#             kind="date_range",
#             start_date=start_date.isoformat(),
#             end_date=end_date.isoformat(),
#         )

#     # 10. Relative: "this month"
#     if re.search(r"\bthis\s+month\b", q_lower):
#         start_date = date(reference_date.year, reference_date.month, 1)
#         end_date = date(reference_date.year, reference_date.month, _last_day_of_month(reference_date.year, reference_date.month))
#         return TemporalResolution(
#             raw_expression="this month",
#             kind="date_range",
#             start_date=start_date.isoformat(),
#             end_date=end_date.isoformat(),
#         )

#     # 11. "coming next", "next event", "coming up next", "which event is next"
#     if re.search(r"\b(?:coming\s+next|next\s+(?:placement\s+|core\s+)?event|coming\s+up\s+next|event\s+is\s+next)\b", q_lower):
#         return TemporalResolution(
#             raw_expression="coming next",
#             kind="next_one",
#             start_date=reference_date.isoformat(),
#             is_next=True,
#             is_upcoming=True,
#         )

#     # 12. "upcoming events", "upcoming", "coming events", "future events"
#     if re.search(r"\b(?:upcoming|coming\s+events?|future\s+events?)\b", q_lower):
#         return TemporalResolution(
#             raw_expression="upcoming",
#             kind="upcoming",
#             start_date=reference_date.isoformat(),
#             is_upcoming=True,
#         )

#     return None
