"""Append-only, redacted audit events for query preparation and execution."""

from __future__ import annotations

import json
from datetime import datetime, timezone
from pathlib import Path
from typing import Any
from uuid import uuid4

from app.config.settings import Settings


class QueryAudit:
    """One instance per request. `run_id` is the trace_id every stage's log
    line is correlated by - pass one in explicitly so a caller (e.g. the
    debug HTTP server) can hand it back to the client before logging even
    starts."""

    _SENSITIVE = {"password", "email", "mobile", "aadhar", "token", "bank", "api_key"}

    def __init__(self, settings: Settings | None = None, trace_id: str | None = None):
        configured = settings or Settings()
        self.path = Path(configured.QUERY_AUDIT_LOG)
        self.run_id = trace_id or str(uuid4())

    @property
    def trace_id(self) -> str:
        return self.run_id

    def record(self, stage: str, input: Any = None, output: Any = None, **extra: Any) -> None:
        self.path.parent.mkdir(parents=True, exist_ok=True)
        event = {
            "timestamp": datetime.now(timezone.utc).isoformat(),
            "run_id": self.run_id,
            "stage": stage,
            "input": self._redact(input),
            "output": self._redact(output),
            **{name: self._redact(value) for name, value in extra.items()},
        }
        with self.path.open("a", encoding="utf-8") as file:
            file.write(json.dumps(event, default=str) + "\n")

    @classmethod
    def read_events(cls, trace_id: str, settings: Settings | None = None) -> list[dict[str, Any]]:
        """All logged stages for one request, in the order they were recorded."""
        path = Path((settings or Settings()).QUERY_AUDIT_LOG)
        if not path.exists():
            return []
        events = []
        with path.open("r", encoding="utf-8") as file:
            for line in file:
                line = line.strip()
                if not line:
                    continue
                event = json.loads(line)
                if event.get("run_id") == trace_id:
                    events.append(event)
        return events

    def _redact(self, value: Any, key: str = "") -> Any:
        if any(sensitive in key.lower() for sensitive in self._SENSITIVE):
            return "[REDACTED]"
        if isinstance(value, dict):
            return {name: self._redact(item, name) for name, item in value.items()}
        if isinstance(value, list):
            return [self._redact(item, key) for item in value]
        return value
