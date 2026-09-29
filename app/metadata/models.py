from typing import Any

from pydantic import BaseModel, ConfigDict, Field


class ColumnMetadata(BaseModel):
    model_config = ConfigDict(extra="allow")

    name: str
    type: str
    nullable: bool | None = None
    default: Any = None
    enum_values: list[str] | None = None


class ForeignKeyMetadata(BaseModel):
    model_config = ConfigDict(extra="allow")

    columns: list[str] = Field(default_factory=list)
    referred_table: str
    referred_columns: list[str] = Field(default_factory=list)


class TableMetadata(BaseModel):
    model_config = ConfigDict(extra="allow")

    table: str
    columns: list[ColumnMetadata] = Field(default_factory=list)
    primary_keys: list[str] = Field(default_factory=list)
    foreign_keys: list[ForeignKeyMetadata] = Field(default_factory=list)


class SchemaMetadata(BaseModel):
    model_config = ConfigDict(extra="allow")

    tables: list[TableMetadata] = Field(default_factory=list)


class EntityReference(BaseModel):
    model_config = ConfigDict(extra="allow")

    table: str
    primary_key: list[str] = Field(default_factory=list)
    role: str | None = None
    description: str | None = None


class RelationshipEndpoint(BaseModel):
    table: str
    column: str


class RelationshipMetadata(BaseModel):
    model_config = ConfigDict(extra="allow")

    name: str
    from_: RelationshipEndpoint = Field(alias="from")
    to: RelationshipEndpoint
    cardinality: str | None = None
    source: str
    verified: bool = False
    description: str | None = None


class IdentifierMetadata(BaseModel):
    model_config = ConfigDict(extra="allow")

    user_terms: list[str] = Field(default_factory=list)
    table: str
    column: str
    resolves_to: str | None = None
    resolves_via: dict[str, Any] | None = None


class ColumnSemanticAnnotation(BaseModel):
    model_config = ConfigDict(extra="allow")

    description: str | None = None
    aliases: list[str] = Field(default_factory=list)
    # For a column that behaves like an enum by convention (a small, fixed
    # vocabulary of values) but isn't a real DB-level ENUM type, so schema
    # extraction can't discover its values automatically (e.g. VARCHAR
    # student_payments.payment_type) - curated here from an actual `SELECT
    # DISTINCT` so the SQL-generation LLM can ground a literal against the
    # real values instead of guessing/copying a wrong one from elsewhere in
    # the prompt.
    allowed_values: list[str] | None = None
    # Opt-in: this column holds a modest, closed vocabulary of real values
    # (center/program/stream/academic-year names, ...) that scripts/
    # extract_column_values.py should snapshot from the live DB into
    # metadata/generated/column_values.json, so a user's partial or
    # differently-cased wording can be snapped to the stored value(s).
    value_catalog: bool = False


class ColumnSemanticsMetadata(BaseModel):
    """Curated descriptions/aliases that enrich - never replace - the
    generated schema. Optional: a table/column absent here is still fully
    resolvable from its own name via app.intelligence.parameter_resolution.
    """

    model_config = ConfigDict(extra="allow")

    tables: dict[str, dict[str, ColumnSemanticAnnotation]] = Field(default_factory=dict)
    # {table: {column: [distinct real values]}} - generated (not hand-written)
    # by scripts/extract_column_values.py for columns flagged `value_catalog`,
    # attached here by MetadataLoader.load_column_semantics().
    known_values: dict[str, dict[str, list[str]]] = Field(default_factory=dict)

    def value_catalog_columns(self) -> list[tuple[str, str]]:
        return [
            (table, column)
            for table, columns in self.tables.items()
            for column, annotation in columns.items()
            if annotation.value_catalog
        ]

    def known_values_for(self, table: str, column: str) -> list[str] | None:
        return self.known_values.get(table, {}).get(column) or None

    def aliases_for(self, table: str, column: str) -> list[str]:
        annotation = self.tables.get(table, {}).get(column)
        return annotation.aliases if annotation else []

    def description_for(self, table: str, column: str) -> str | None:
        annotation = self.tables.get(table, {}).get(column)
        return annotation.description if annotation else None

    def allowed_values_for(self, table: str, column: str) -> list[str] | None:
        annotation = self.tables.get(table, {}).get(column)
        return annotation.allowed_values if annotation else None


class StudentTruthLayer(BaseModel):
    model_config = ConfigDict(extra="allow")

    module: str
    domain: str
    version: int

    entities: dict[str, EntityReference] = Field(default_factory=dict)

    identity: dict[str, Any] = Field(default_factory=dict)

    relationships: dict[str, RelationshipMetadata] = Field(
        default_factory=dict
    )

    identity_resolution: dict[str, Any] = Field(default_factory=dict)

    student_status: dict[str, Any] = Field(default_factory=dict)

    downstream_resolution: dict[str, Any] = Field(default_factory=dict)

    query_rules: dict[str, Any] = Field(default_factory=dict)