"""Live-repro'd bug: "academic year 2026 JUN and 2025 JUN" needs a single
IN (:param) clause bound to a list, which plain SQLAlchemy text() execution
rejects unless the placeholder is explicitly marked expanding=True. Hits
the real (read-only) database, same precedent as test_connection.py.
"""

from app.database.executor import SafeQueryExecutor


def test_list_valued_parameter_executes_as_an_in_clause():
    executor = SafeQueryExecutor()

    result = executor.execute(
        "SELECT core_year, COUNT(*) AS total FROM student_admissions "
        "WHERE core_year IN (:academic_year) GROUP BY core_year",
        {"academic_year": ["2026 JUN", "2025 JUN"]},
    )

    assert result.columns == ["core_year", "total"]
    years = {row["core_year"]: row["total"] for row in result.rows}
    assert set(years) == {"2026 JUN", "2025 JUN"}
    assert all(count > 0 for count in years.values())


def test_list_valued_parameter_without_literal_parens_also_works():
    """The generator is instructed to write `IN :param` (no parens, since
    the expanding bind supplies them) - confirms that correct form isn't
    broken by the defensive paren-stripping for the `IN (:param)` form."""
    executor = SafeQueryExecutor()

    result = executor.execute(
        "SELECT core_year, COUNT(*) AS total FROM student_admissions "
        "WHERE core_year IN :academic_year GROUP BY core_year",
        {"academic_year": ["2026 JUN", "2025 JUN"]},
    )

    assert {row["core_year"] for row in result.rows} == {"2026 JUN", "2025 JUN"}


def test_scalar_and_list_parameters_can_be_mixed():
    executor = SafeQueryExecutor()

    result = executor.execute(
        "SELECT COUNT(*) AS total FROM student_admissions "
        "WHERE status = :status AND core_year IN (:academic_year)",
        {"status": "active", "academic_year": ["2026 JUN", "2025 JUN"]},
    )

    assert result.rows[0]["total"] >= 0
