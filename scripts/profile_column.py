"""Profile a column (and optionally a join key) BEFORE exposing it to the chatbot.

Read-only. Answers the questions that have repeatedly turned out to matter:
is the column actually populated, what values does it really hold (and in
which spellings), is a join key unique, and how often does a join match?

    python scripts/profile_column.py core_exam_results.result_status
    python scripts/profile_column.py core_exam_results.core_student_name
    python scripts/profile_column.py student_referrals.code --join student_referrals.code=employees.employee_code

Table and column names are checked against the extracted schema
(metadata/generated/schema.json), so nothing user-typed is ever spliced into
SQL unvalidated.
"""

from __future__ import annotations

import argparse
import sys
from pathlib import Path

PROJECT_ROOT = Path(__file__).resolve().parents[1]
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from sqlalchemy import text

from app.database.connection import engine
from app.metadata.registry import MetadataRegistry


def _split(reference: str, registry: MetadataRegistry) -> tuple[str, str]:
    table, _, column = reference.partition(".")
    if not column or not registry.has_table(table):
        raise SystemExit(f"'{reference}' must be table.column with a table that exists in the schema.")
    if column not in {c.name for c in registry.get_table(table).columns}:
        raise SystemExit(f"Column '{column}' does not exist on table '{table}'.")
    return table, column


def profile_column(table: str, column: str, top: int) -> None:
    with engine.connect() as connection:
        total, empty, distinct = connection.execute(text(
            f"SELECT COUNT(*), SUM(`{column}` IS NULL OR TRIM(CAST(`{column}` AS CHAR)) = ''), "
            f"COUNT(DISTINCT `{column}`) FROM `{table}`"
        )).one()
        print(f"{table}.{column}: {total} rows, {empty or 0} NULL/empty "
              f"({100 * (empty or 0) / max(total, 1):.1f}%), {distinct} distinct values")
        if (empty or 0) == total:
            print("  !! EVERY row is empty - do not expose this column (find where the data really lives).")
        rows = connection.execute(text(
            f"SELECT `{column}`, COUNT(*) n FROM `{table}` GROUP BY `{column}` ORDER BY n DESC LIMIT {top}"
        )).fetchall()
        print(f"  top {len(rows)} values:")
        for value, count in rows:
            print(f"    {value!r:60} {count}")
        if 0 < distinct <= 60:
            print("  -> few distinct values: consider `allowed_values` (not a real ENUM) or `value_catalog: true`.")


def profile_join(left: tuple[str, str], right: tuple[str, str]) -> None:
    (lt, lc), (rt, rc) = left, right
    with engine.connect() as connection:
        dup_left = connection.execute(text(
            f"SELECT COUNT(*) FROM (SELECT `{lc}` FROM `{lt}` WHERE `{lc}` IS NOT NULL AND `{lc}` <> '' "
            f"GROUP BY `{lc}` HAVING COUNT(*) > 1) t"
        )).scalar()
        dup_right = connection.execute(text(
            f"SELECT COUNT(*) FROM (SELECT `{rc}` FROM `{rt}` WHERE `{rc}` IS NOT NULL AND `{rc}` <> '' "
            f"GROUP BY `{rc}` HAVING COUNT(*) > 1) t"
        )).scalar()
        total, matched = connection.execute(text(
            f"SELECT COUNT(*), SUM(r.`{rc}` IS NOT NULL) FROM `{lt}` l LEFT JOIN "
            f"(SELECT DISTINCT `{rc}` FROM `{rt}`) r ON r.`{rc}` = l.`{lc}` "
            f"WHERE l.`{lc}` IS NOT NULL AND l.`{lc}` <> ''"
        )).one()
    print(f"join {lt}.{lc} = {rt}.{rc}")
    print(f"  {matched or 0} of {total} non-empty {lt}.{lc} values find a match "
          f"({100 * (matched or 0) / max(total, 1):.1f}%)")
    print(f"  duplicated values: {dup_left} on {lt}.{lc}, {dup_right} on {rt}.{rc}"
          f"  (duplicates on the target side mean a join can fan rows out)")


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("column", help="table.column to profile")
    parser.add_argument("--join", help="left.col=right.col - also measure how well this join matches")
    parser.add_argument("--top", type=int, default=15, help="how many most-common values to show")
    args = parser.parse_args()

    registry = MetadataRegistry()
    registry.load()
    table, column = _split(args.column, registry)
    profile_column(table, column, args.top)
    if args.join:
        left, _, right = args.join.partition("=")
        profile_join(_split(left, registry), _split(right, registry))


if __name__ == "__main__":
    main()
