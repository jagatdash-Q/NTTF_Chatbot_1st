
import json

with open("metadata/generated/schema.json", encoding="utf-8") as file:
    schema = json.load(file)

for table in schema["tables"]:
    for fk in table["foreign_keys"]:
        for column, referred_column in zip(
            fk["columns"],
            fk["referred_columns"],
        ):
            print(
                f"{table['table']}.{column} "
                f"-> "
                f"{fk['referred_table']}.{referred_column}"
            )
