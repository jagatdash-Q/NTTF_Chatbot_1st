"""A verified-relationship graph with multi-hop join-path lookup.

Two sources feed the graph, both already truthful without needing to parse
the Laravel application's source (which is not part of this repository):

* explicit database foreign keys in the generated schema - authoritative
  wherever the legacy database actually declares them;
* Truth Layer relationships marked ``verified: true`` - the project's
  existing, human-curated record of what a Laravel model relationship
  (``belongsTo``/``hasOne``/``hasMany``) resolves to in the database, kept
  in ``metadata/manual/core_student.yaml`` with explicit provenance
  (``source: laravel_model`` etc.).

Unverified relationships (see ``enrollment_to_payment`` in the Core Student
Truth Layer) are deliberately excluded from the graph, so a join can never
be silently invented from a curated-but-unconfirmed guess.
"""

from __future__ import annotations

from dataclasses import dataclass

from app.metadata.models import SchemaMetadata, StudentTruthLayer


@dataclass(frozen=True)
class DiscoveredRelationship:
    from_table: str
    from_column: str
    to_table: str
    to_column: str
    source: str  # e.g. "database_fk", "laravel_model", "laravel_model_and_schema_fk"
    verified: bool


def discover_fk_relationships(schema: SchemaMetadata) -> list[DiscoveredRelationship]:
    """Explicit MySQL foreign keys already captured by the schema extractor."""
    relationships = []
    for table in schema.tables:
        for foreign_key in table.foreign_keys:
            for column, referred_column in zip(foreign_key.columns, foreign_key.referred_columns):
                relationships.append(DiscoveredRelationship(
                    from_table=table.table,
                    from_column=column,
                    to_table=foreign_key.referred_table,
                    to_column=referred_column,
                    source="database_fk",
                    verified=True,
                ))
    return relationships


class RelationshipGraph:
    """Finds join paths using only explicit FKs and verified relationships."""

    def __init__(self, relationships: list[DiscoveredRelationship]):
        self.relationships = relationships
        self._edges: dict[str, list[DiscoveredRelationship]] = {}
        for relationship in relationships:
            self._edges.setdefault(relationship.from_table, []).append(relationship)
            self._edges.setdefault(relationship.to_table, []).append(relationship)

    @classmethod
    def from_metadata(cls, schema: SchemaMetadata, truth: StudentTruthLayer) -> "RelationshipGraph":
        relationships = discover_fk_relationships(schema)
        for relationship in truth.relationships.values():
            if not relationship.verified:
                continue
            relationships.append(DiscoveredRelationship(
                from_table=relationship.from_.table,
                from_column=relationship.from_.column,
                to_table=relationship.to.table,
                to_column=relationship.to.column,
                source=relationship.source,
                verified=True,
            ))
        return cls(relationships)

    def find_path(self, start: str, goal: str, max_hops: int = 4) -> list[DiscoveredRelationship] | None:
        """Breadth-first search over verified edges only.

        Returns the shortest verified join path, or None if the tables are
        unreachable through any verified relationship within ``max_hops``.
        """
        if start == goal:
            return []
        visited = {start}
        frontier: list[tuple[str, list[DiscoveredRelationship]]] = [(start, [])]
        for _ in range(max_hops):
            next_frontier: list[tuple[str, list[DiscoveredRelationship]]] = []
            for table, path in frontier:
                for relationship in self._edges.get(table, []):
                    other = relationship.to_table if relationship.from_table == table else relationship.from_table
                    if other in visited:
                        continue
                    new_path = path + [relationship]
                    if other == goal:
                        return new_path
                    visited.add(other)
                    next_frontier.append((other, new_path))
            frontier = next_frontier
            if not frontier:
                break
        return None

    def path_exists(self, start: str, goal: str, max_hops: int = 4) -> bool:
        return self.find_path(start, goal, max_hops) is not None
