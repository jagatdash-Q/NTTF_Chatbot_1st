from sqlalchemy import create_engine, text
from sqlalchemy.engine import Engine

from app.config.settings import settings


def build_database_url() -> str:
    return (
        f"mysql+pymysql://"
        f"{settings.DB_USERNAME}:{settings.DB_PASSWORD}"
        f"@{settings.DB_HOST}:{settings.DB_PORT}/"
        f"{settings.DB_DATABASE}"
    )


engine: Engine = create_engine(
    build_database_url(),
    pool_pre_ping=True,
    pool_recycle=3600,
)


def test_connection() -> bool:
    with engine.connect() as connection:
        connection.execute(text("SELECT 1"))

    return True