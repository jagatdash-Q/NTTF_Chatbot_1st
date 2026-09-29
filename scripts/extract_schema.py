import json
from pathlib import Path

from app.database.schema_extractor import SchemaExtractor


def main():
    extractor = SchemaExtractor()

    schema = extractor.extract_schema()

    output_path = Path("metadata/generated/schema.json")
    output_path.parent.mkdir(parents=True, exist_ok=True)

    output_path.write_text(
        json.dumps(schema, indent=2, default=str),
        encoding="utf-8",
    )

    print(f"Schema extracted successfully.")
    print(f"Tables found: {len(schema['tables'])}")
    print(f"Saved to: {output_path}")


if __name__ == "__main__":
    main()