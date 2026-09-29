from sqlalchemy import inspect

from app.database.connection import engine


class SchemaExtractor:

    def __init__(self):
        self.inspector = inspect(engine)

    def get_tables(self) -> list[str]:
        return self.inspector.get_table_names()

    def get_columns(self, table_name: str) -> list[dict]:
        columns = self.inspector.get_columns(table_name)

        return [
            {
                "name": column["name"],
                "type": str(column["type"]),
                "nullable": column.get("nullable"),
                "default": column.get("default"),
                # ENUM columns expose their allowed values via .enums (MySQL
                # dialect); str(type) alone only ever returns "ENUM" with no
                # values. Knowing the real values lets a user's natural-
                # language value (any wording/word count) be matched to the
                # exact stored token (e.g. "temporary_enrolled").
                "enum_values": list(getattr(column["type"], "enums", None) or []) or None,
            }
            for column in columns
        ]

    def get_primary_keys(self, table_name: str) -> list[str]:
        pk = self.inspector.get_pk_constraint(table_name)

        return pk.get("constrained_columns", [])

    def get_foreign_keys(self, table_name: str) -> list[dict]:
        foreign_keys = self.inspector.get_foreign_keys(table_name)

        return [
            {
                "columns": fk.get("constrained_columns", []),
                "referred_table": fk.get("referred_table"),
                "referred_columns": fk.get("referred_columns", []),
            }
            for fk in foreign_keys
        ]

    def extract_table(self, table_name: str) -> dict:
        return {
            "table": table_name,
            "columns": self.get_columns(table_name),
            "primary_keys": self.get_primary_keys(table_name),
            "foreign_keys": self.get_foreign_keys(table_name),
        }

    def extract_schema(self) -> dict:
        tables = self.get_tables()

        return {
            "tables": [
                self.extract_table(table)
                for table in tables
            ]
        }