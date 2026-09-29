from app.metadata.loader import MetadataLoader
from app.metadata.models import (
    ColumnSemanticsMetadata,
    SchemaMetadata,
    StudentTruthLayer,
    TableMetadata,
)


class MetadataRegistry:

    def __init__(self, loader: MetadataLoader | None = None):
        self.loader = loader or MetadataLoader()

        self._schema: SchemaMetadata | None = None
        self._student_truth: StudentTruthLayer | None = None
        self._column_semantics: ColumnSemanticsMetadata | None = None

    def load(self) -> None:
        self._schema = self.loader.load_schema()
        self._student_truth = self.loader.load_student_truth()
        self._column_semantics = self.loader.load_column_semantics()

        self._validate_student_truth()
        self._validate_column_semantics()

    @property
    def schema(self) -> SchemaMetadata:
        if self._schema is None:
            raise RuntimeError(
                "MetadataRegistry has not been loaded."
            )

        return self._schema

    @property
    def student_truth(self) -> StudentTruthLayer:
        if self._student_truth is None:
            raise RuntimeError(
                "MetadataRegistry has not been loaded."
            )

        return self._student_truth

    @property
    def column_semantics(self) -> ColumnSemanticsMetadata:
        if self._column_semantics is None:
            raise RuntimeError(
                "MetadataRegistry has not been loaded."
            )

        return self._column_semantics

    def get_table(self, table_name: str) -> TableMetadata:
        for table in self.schema.tables:
            if table.table == table_name:
                return table

        raise KeyError(
            f"Table not found in generated schema: {table_name}"
        )

    def has_table(self, table_name: str) -> bool:
        return any(
            table.table == table_name
            for table in self.schema.tables
        )

    def _validate_student_truth(self) -> None:
        truth = self.student_truth

        for entity_name, entity in truth.entities.items():
            if not self.has_table(entity.table):
                raise ValueError(
                    f"Student Truth Layer entity '{entity_name}' "
                    f"references unknown table '{entity.table}'."
                )

            table = self.get_table(entity.table)

            for primary_key in entity.primary_key:
                columns = {
                    column.name for column in table.columns
                }

                if primary_key not in columns:
                    raise ValueError(
                        f"Entity '{entity_name}' references column "
                        f"'{entity.table}.{primary_key}', "
                        f"but the column does not exist."
                    )

        for name, relationship in truth.relationships.items():
            if not self.has_table(relationship.from_.table):
                raise ValueError(
                    f"Relationship '{name}' references unknown "
                    f"source table '{relationship.from_.table}'."
                )

            if not self.has_table(relationship.to.table):
                raise ValueError(
                    f"Relationship '{name}' references unknown "
                    f"target table '{relationship.to.table}'."
                )

            from_table = self.get_table(relationship.from_.table)
            to_table = self.get_table(relationship.to.table)

            from_columns = {
                column.name for column in from_table.columns
            }

            to_columns = {
                column.name for column in to_table.columns
            }

            if relationship.from_.column not in from_columns:
                raise ValueError(
                    f"Relationship '{name}' references unknown "
                    f"column '{relationship.from_.table}."
                    f"{relationship.from_.column}'."
                )

            if relationship.to.column not in to_columns:
                raise ValueError(
                    f"Relationship '{name}' references unknown "
                    f"column '{relationship.to.table}."
                    f"{relationship.to.column}'."
                )

        self._validate_identity_references()

    def _validate_column_semantics(self) -> None:
        for table_name, columns in self.column_semantics.tables.items():
            for column_name in columns:
                self._validate_table_column(
                    table_name, column_name,
                    f"Column semantics entry '{table_name}.{column_name}'",
                )

    def _validate_identity_references(self) -> None:
        """Reject manual identity metadata that cannot be executed safely."""
        truth = self.student_truth

        canonical_keys = truth.identity.get("canonical_keys", {})
        for key_name, reference in canonical_keys.items():
            self._validate_table_column(
                reference.get("table"),
                reference.get("column"),
                f"Canonical key '{key_name}'",
            )

        for identifier_name, identifier in truth.identity.get(
            "identifiers", {}
        ).items():
            self._validate_table_column(
                identifier.get("table"),
                identifier.get("column"),
                f"Identifier '{identifier_name}'",
            )

        resolution_rules = truth.identity_resolution.get(
            "resolution_rules", {}
        )
        for identifier_name, rule in resolution_rules.items():
            lookup = rule.get("lookup", {})
            self._validate_table_column(
                lookup.get("table"),
                lookup.get("column"),
                f"Resolution rule '{identifier_name}' lookup",
            )

            for context_key, reference in rule.get("return", {}).items():
                table_name, separator, column_name = reference.partition(".")
                if not separator:
                    raise ValueError(
                        f"Resolution rule '{identifier_name}' return value for "
                        f"'{context_key}' must be 'table.column'."
                    )
                self._validate_table_column(
                    table_name,
                    column_name,
                    f"Resolution rule '{identifier_name}' return value",
                )

    def _validate_table_column(
        self,
        table_name: str | None,
        column_name: str | None,
        context: str,
    ) -> None:
        if not table_name or not column_name:
            raise ValueError(
                f"{context} must define both a table and a column."
            )
        if not self.has_table(table_name):
            raise ValueError(
                f"{context} references unknown table '{table_name}'."
            )
        columns = {column.name for column in self.get_table(table_name).columns}
        if column_name not in columns:
            raise ValueError(
                f"{context} references unknown column "
                f"'{table_name}.{column_name}'."
            )
