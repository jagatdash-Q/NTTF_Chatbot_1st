"""Snapshot the real distinct values of columns flagged `value_catalog: true`
in metadata/manual/column_semantics.yaml into
metadata/generated/column_values.json.

Read-only (SELECT DISTINCT only). Re-run after the underlying data changes
(e.g. a new center/stream/academic year is added):

    python scripts/extract_column_values.py

A column with more than MAX_VALUES distinct values is skipped (and reported):
it is not a closed vocabulary, so snapping to it would be unreliable.
"""

from __future__ import annotations

import json
import sys
from pathlib import Path

PROJECT_ROOT = Path(__file__).resolve().parents[1]
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from sqlalchemy import text

from app.database.connection import engine
from app.metadata.registry import MetadataRegistry

MAX_VALUES = 500
OUTPUT_PATH = PROJECT_ROOT / "metadata" / "generated" / "column_values.json"


def main() -> None:
    registry = MetadataRegistry()
    registry.load()

    snapshot: dict[str, dict[str, list[str]]] = {}
    with engine.connect() as connection:
        for table, column in registry.column_semantics.value_catalog_columns():
            # Table/column names come from our own validated YAML (the
            # registry rejects unknown ones), never from user input.
            rows = connection.execute(text(
                f"SELECT DISTINCT `{column}` FROM `{table}` "
                f"WHERE `{column}` IS NOT NULL AND TRIM(`{column}`) <> '' "
                f"LIMIT {MAX_VALUES + 1}"
            )).fetchall()
            values = sorted(str(row[0]).strip() for row in rows)
            if len(values) > MAX_VALUES:
                print(f"SKIPPED {table}.{column}: more than {MAX_VALUES} distinct values")
                continue
            snapshot.setdefault(table, {})[column] = values
            print(f"{table}.{column}: {len(values)} values")

    OUTPUT_PATH.parent.mkdir(parents=True, exist_ok=True)
    OUTPUT_PATH.write_text(json.dumps(snapshot, indent=2, ensure_ascii=False), encoding="utf-8")
    print(f"Saved to: {OUTPUT_PATH}")


if __name__ == "__main__":
    main()
