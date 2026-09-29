from app.database.connection import test_connection


def test_database_connection():
    assert test_connection() is True