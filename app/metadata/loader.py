from pathlib import Path
from typing import Any

import json
import yaml

from app.metadata.models import ColumnSemanticsMetadata, SchemaMetadata, StudentTruthLayer


class MetadataLoader:

    def __init__(
        self,
        generated_dir: str | Path = "metadata/generated",
        manual_dir: str | Path = "metadata/manual",
    ):
        self.generated_dir = Path(generated_dir)
        self.manual_dir = Path(manual_dir)

    def load_schema(self) -> SchemaMetadata:
        path = self.generated_dir / "schema.json"

        if not path.exists():
            raise FileNotFoundError(
                f"Generated schema not found: {path}"
            )

        with path.open("r", encoding="utf-8") as file:
            data = json.load(file)

        return SchemaMetadata.model_validate(data)

    def load_yaml(self, path: str | Path) -> dict[str, Any]:
        path = Path(path)

        if not path.exists():
            raise FileNotFoundError(
                f"Metadata file not found: {path}"
            )

        with path.open("r", encoding="utf-8") as file:
            data = yaml.safe_load(file)

        if not isinstance(data, dict):
            raise ValueError(
                f"Metadata file must contain a YAML object: {path}"
            )

        return data

    def load_student_truth(self) -> StudentTruthLayer:
        path = self.manual_dir / "core_student.yaml"

        data = self.load_yaml(path)

        return StudentTruthLayer.model_validate(data)

    def load_column_semantics(self) -> ColumnSemanticsMetadata:
        """Optional enrichment. Missing file = no curated aliases, and
        columns remain fully resolvable from their own names.
        """
        path = self.manual_dir / "column_semantics.yaml"

        if not path.exists():
            semantics = ColumnSemanticsMetadata()
        else:
            semantics = ColumnSemanticsMetadata.model_validate(self.load_yaml(path))

        # Optional generated snapshot of real values for `value_catalog`
        # columns (scripts/extract_column_values.py). Absent = no snapping.
        values_path = self.generated_dir / "column_values.json"
        if values_path.exists():
            with values_path.open("r", encoding="utf-8") as file:
                semantics.known_values = json.load(file)

        return semantics