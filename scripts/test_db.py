from sqlalchemy import text

from app.database.connection import engine


def main():
    with engine.connect() as connection:
        result = connection.execute(text("SELECT DATABASE(), VERSION()"))

        database, version = result.fetchone()

        print(f"Database : {database}")
        print(f"MySQL    : {version}")
        print("Connection successful.")


if __name__ == "__main__":
    main()