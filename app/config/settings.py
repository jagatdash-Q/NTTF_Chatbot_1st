from pydantic import SecretStr
from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    DB_CONNECTION: str = "mysql"
    DB_HOST: str
    DB_PORT: int = 3306
    DB_DATABASE: str
    DB_USERNAME: str
    DB_PASSWORD: str

    LLM_PROVIDER: str = "gemini"
    SARVAM_API_KEY: SecretStr | None = None
    SARVAM_MODEL: str = "sarvam-105b"
    SARVAM_API_URL: str = "https://api.sarvam.ai/v1/chat/completions"
    GEMINI_API_KEY: SecretStr | None = None
    GEMINI_MODEL: str = "gemini-3.1-pro-preview"
    GEMINI_API_URL: str = "https://generativelanguage.googleapis.com/v1beta/models"
    QUERY_MAX_ROWS: int = 100
    QUERY_AUDIT_LOG: str = "logs/query_audit.jsonl"

    model_config = SettingsConfigDict(
        env_file=".env",
        env_file_encoding="utf-8",
        case_sensitive=True,
        extra="ignore",
    )


settings = Settings()
