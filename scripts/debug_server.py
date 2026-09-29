"""Minimal dev-only debug UI for the NL -> SQL -> DB -> NL answer pipeline.

Single self-contained page, stdlib-only (no new dependencies) - once
things are stable this is expected to be replaced by a real FastAPI/Flask
app, per project decision.

Usage:
    python scripts/debug_server.py [--port 8000] [--provider sarvam|gemini]

Defaults to the sarvam provider regardless of .env's LLM_PROVIDER, since
gemini is currently rate-limited on this account (see docs) - pass
--provider gemini to override once that's resolved.

Then open http://127.0.0.1:8000/ in a browser. Every submitted query runs
the real pipeline (real DB, real configured LLM provider) via
StudentQueryOrchestrator.answer(), and the page is a pure viewer over the
same JSON-lines trace log every other entry point writes to - it does not
reimplement any pipeline logic.
"""

from __future__ import annotations

import argparse
import hashlib
import json
import subprocess
import time
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path
from urllib.parse import urlparse
import sys
import uuid

PROJECT_ROOT = Path(__file__).resolve().parents[1]
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from app.config.settings import Settings
from app.database.executor import SafeQueryExecutor
from app.graph.root import StudentQueryOrchestrator
from app.intelligence.entity_resolver import build_configured_intent_classifier
from app.observability.query_audit import QueryAudit
from app.response.formatter import build_configured_answer_generator
from app.sql.generator import build_configured_sql_generator

PAGE = """<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>NL query pipeline - debug trace viewer</title>
<style>
  body { font-family: -apple-system, Segoe UI, sans-serif; max-width: 960px; margin: 2rem auto; padding: 0 1rem; color: #1a1a1a; }
  h1 { font-size: 1.3rem; }
  #query { width: 100%; padding: 0.5rem; font-size: 1rem; box-sizing: border-box; }
  button { padding: 0.5rem 1.2rem; font-size: 1rem; margin-top: 0.5rem; cursor: pointer; }
  #status { margin: 0.75rem 0; font-style: italic; color: #555; }
  .badge { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 4px; font-size: 0.8rem; font-weight: bold; }
  .pass { background: #d4edda; color: #155724; }
  .fail { background: #f8d7da; color: #721c24; }
  #answer-box { border: 2px solid #444; border-radius: 6px; padding: 1rem; margin: 1rem 0; background: #fafafa; }
  details { border: 1px solid #ccc; border-radius: 6px; margin-bottom: 0.6rem; }
  summary { padding: 0.6rem; cursor: pointer; font-weight: 600; background: #f0f0f0; border-radius: 6px; }
  pre { white-space: pre-wrap; word-break: break-word; padding: 0.75rem; margin: 0; font-size: 0.85rem; }
  table { border-collapse: collapse; width: 100%; margin: 0.5rem 0; }
  th, td { border: 1px solid #ccc; padding: 4px 8px; font-size: 0.85rem; text-align: left; }
  th { background: #eee; }
</style>
</head>
<body>
<h1>NL query pipeline - debug trace viewer</h1>
<p>Runs the real pipeline (real DB, real configured LLM provider) for one query and shows every logged stage, in order, pulled from the same trace log used everywhere else.</p>
<input id="query" placeholder="e.g. List students in batch 2024 with status as active" />
<button id="submit">Ask</button>
<div id="status"></div>
<div id="answer-box" style="display:none"></div>
<div id="trace"></div>

<script>
const UI_VERSION = "__UI_VERSION__";
const STAGE_LABELS = {
  received_query: "1. Original NL query",
  query_analysis: "Deterministic query analysis",
  schema_selection: "Minimal schema selection",
  sql_generation_attempt: "SQL generation attempt",
  sql_generation: "2. Generated DB query (LLM prompt + raw response)",
  sql_generation_error: "SQL generation error",
  sql_validation: "SQL validation",
  db_execution: "3. DB execution result",
  answer_generation: "4/5. Final NL answer (LLM prompt + raw response)",
  presentation: "4/5. Final answer laid out from the rows (no LLM call)",
  answer_validation: "6. Output-validation (faithfulness check)",
  clarification: "Clarification requested",
  pipeline_complete: "Pipeline complete",
};

function el(tag, props, children) {
  const node = document.createElement(tag);
  Object.assign(node, props || {});
  (children || []).forEach(c => node.appendChild(c));
  return node;
}

function rowsTable(rows) {
  if (!rows || !rows.length) return el('p', {textContent: '(no rows)'});
  const columns = Object.keys(rows[0]);
  const thead = el('tr', {}, columns.map(c => el('th', {textContent: c})));
  const trs = rows.map(r => el('tr', {}, columns.map(c => el('td', {textContent: String(r[c])}))));
  return el('table', {}, [el('thead', {}, [thead]), el('tbody', {}, trs)]);
}

function renderEvent(event) {
  const label = STAGE_LABELS[event.stage] || event.stage;
  const details = el('details', {open: ['db_execution', 'answer_generation', 'answer_validation'].includes(event.stage)});
  details.appendChild(el('summary', {textContent: `${label}  —  ${event.timestamp}`}));

  if (event.stage === 'db_execution' && event.output && event.output.rows) {
    const wrap = el('div');
    wrap.appendChild(el('p', {textContent: `row_count=${event.output.row_count}, truncated=${event.output.truncated}, elapsed_ms=${event.elapsed_ms}`}));
    wrap.appendChild(rowsTable(event.output.rows));
    details.appendChild(wrap);
  } else {
    const body = { input: event.input, output: event.output };
    Object.keys(event).forEach(k => { if (!['stage','timestamp','run_id','input','output'].includes(k)) body[k] = event[k]; });
    details.appendChild(el('pre', {textContent: JSON.stringify(body, null, 2)}));
  }
  return details;
}

function matrixTable(columns, rows) {
  const head = el('tr', {}, columns.map(c => el('th', {textContent: c})));
  const body = rows.map(r => el('tr', {}, r.map(v => el('td', {textContent: v}))));
  return el('table', {}, [el('thead', {}, [head]), el('tbody', {}, body)]);
}

function renderPresentation(presentation) {
  const wrap = el('div');
  presentation.sections.forEach(section => {
    if (section.title) wrap.appendChild(el('h3', {textContent: section.title}));
    if (section.facts.length) {
      const list = el('ul', {});
      section.facts.forEach(([label, value]) => {
        list.appendChild(el('li', {}, [el('b', {textContent: label + ': '}), document.createTextNode(value)]));
      });
      wrap.appendChild(list);
    }
    if (section.table) wrap.appendChild(matrixTable(section.table.columns, section.table.rows));
    if (section.summary.length) {
      if (section.summary_title) wrap.appendChild(el('h4', {textContent: section.summary_title}));
      const list = el('ul', {});
      section.summary.forEach(([label, value]) => list.appendChild(el('li', {textContent: label + ': ' + value})));
      wrap.appendChild(list);
    }
  });
  presentation.notes.forEach(note => wrap.appendChild(el('p', {textContent: note, style: 'font-style:italic'})));
  return wrap;
}

async function submitQuery() {
  const query = document.getElementById('query').value.trim();
  if (!query) return;
  const status = document.getElementById('status');
  const answerBox = document.getElementById('answer-box');
  const traceDiv = document.getElementById('trace');
  answerBox.style.display = 'none';
  traceDiv.innerHTML = '';
  status.textContent = 'Running... (this calls the real LLM and DB, can take several seconds)';

  const res = await fetch('/api/query', {
    method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({query}),
  });
  const data = await res.json();
  if (data.ui_version && data.ui_version !== UI_VERSION) {
    // This tab was loaded from an older server: its JavaScript no longer
    // matches the API (e.g. it would show a mark sheet as raw markdown).
    status.textContent = 'The server was updated - reloading this page...';
    setTimeout(() => location.reload(), 800);
    return;
  }
  status.textContent = `Done — trace_id: ${data.trace_id}`;

  answerBox.style.display = 'block';
  answerBox.innerHTML = '';
  if (data.clarification) {
    answerBox.appendChild(el('p', {}, [el('b', {textContent: 'Clarification / stopped early: '}), document.createTextNode(data.clarification)]));
  } else {
    const badge = el('span', {
      className: 'badge ' + (data.answer_valid ? 'pass' : 'fail'),
      textContent: data.answer_valid ? 'OUTPUT VALIDATION: PASS' : 'OUTPUT VALIDATION: FAIL',
    });
    answerBox.appendChild(el('p', {}, [el('b', {textContent: '5. Final answer shown to user:'})]));
    if (data.presentation) {
      answerBox.appendChild(renderPresentation(data.presentation));
    } else {
      answerBox.appendChild(el('p', {textContent: data.answer}));
    }
    answerBox.appendChild(badge);
    if (data.answer_validation_reasons && data.answer_validation_reasons.length) {
      answerBox.appendChild(el('pre', {textContent: 'Validation reasons:\\n' + data.answer_validation_reasons.join('\\n')}));
    }
    if (!data.presentation && data.result && data.result.rows.length) {
      answerBox.appendChild(el('p', {textContent: `Result table - ${data.result.rows.length} of ${data.result.row_count} row(s) shown` + (data.result.truncated ? ' (cut off by the row limit)' : '')}));
      answerBox.appendChild(rowsTable(data.result.rows));
    }
  }

  const traceRes = await fetch('/api/trace/' + data.trace_id);
  const events = await traceRes.json();
  events.forEach(event => traceDiv.appendChild(renderEvent(event)));
}

document.getElementById('submit').addEventListener('click', submitQuery);
document.getElementById('query').addEventListener('keydown', e => { if (e.key === 'Enter') submitQuery(); });
</script>
</body>
</html>
"""


# Changes whenever the page's HTML/JS changes; lets an already-open tab notice
# that the server was updated and reload itself.
UI_VERSION = hashlib.sha1(PAGE.encode("utf-8")).hexdigest()[:10]


class _State:
    orchestrator: StudentQueryOrchestrator
    settings: Settings


_state = _State()


class Handler(BaseHTTPRequestHandler):
    def _send_json(self, status: int, payload: dict) -> None:
        body = json.dumps(payload, default=str).encode("utf-8")
        self.send_response(status)
        self.send_header("Content-Type", "application/json")
        self.send_header("Content-Length", str(len(body)))
        self.end_headers()
        self.wfile.write(body)

    def do_GET(self) -> None:  # noqa: N802 (stdlib method name)
        path = urlparse(self.path).path
        if path == "/":
            body = PAGE.replace("__UI_VERSION__", UI_VERSION).encode("utf-8")
            self.send_response(200)
            self.send_header("Content-Type", "text/html; charset=utf-8")
            self.send_header("Cache-Control", "no-store")
            self.send_header("Content-Length", str(len(body)))
            self.end_headers()
            self.wfile.write(body)
            return
        if path.startswith("/api/trace/"):
            trace_id = path.removeprefix("/api/trace/")
            events = QueryAudit.read_events(trace_id, _state.settings)
            self._send_json(200, events)
            return
        self._send_json(404, {"error": "not found"})

    def do_POST(self) -> None:  # noqa: N802
        if urlparse(self.path).path != "/api/query":
            self._send_json(404, {"error": "not found"})
            return
        length = int(self.headers.get("Content-Length", 0))
        try:
            body = json.loads(self.rfile.read(length) or b"{}")
            query = body["query"]
        except (json.JSONDecodeError, KeyError):
            self._send_json(400, {"error": "expected JSON body {\"query\": \"...\"}"})
            return

        trace_id = str(uuid.uuid4())
        audit = QueryAudit(_state.settings, trace_id=trace_id)
        try:
            state = _state.orchestrator.answer(
                query,
                build_configured_sql_generator(_state.settings),
                build_configured_answer_generator(_state.settings),
                SafeQueryExecutor(_state.settings),
                audit=audit,
            )
        except Exception as error:
            # Defense in depth: the pipeline itself degrades every known
            # failure mode into state.clarification, but this guarantees a
            # crash anywhere never takes the whole debug server down.
            audit.record("pipeline_complete", output={"outcome": "unhandled_error", "error": str(error)})
            self._send_json(200, {"trace_id": trace_id, "ui_version": UI_VERSION, "clarification": f"Unexpected error: {error}"})
            return
        self._send_json(200, {
            "trace_id": trace_id,
            "ui_version": UI_VERSION,
            "clarification": state.clarification,
            "answer": state.answer["answer"] if state.answer else None,
            "answer_valid": state.answer_validation.valid if state.answer_validation else None,
            "answer_validation_reasons": state.answer_validation.reasons if state.answer_validation else [],
            "presentation": state.presentation.as_dict() if state.presentation else None,
            # The data itself, so a table-shaped answer (subject-wise marks,
            # a list of students) is shown in full, not only summarised.
            "result": {
                "columns": state.query_result.columns,
                "rows": state.query_result.rows[:200],
                "row_count": len(state.query_result.rows),
                "truncated": state.query_result.truncated,
            } if state.query_result else None,
        })

    def log_message(self, format: str, *args) -> None:  # quieter default logging
        print(f"[debug_server] {self.address_string()} - {format % args}")


def _source_fingerprint() -> tuple[int, int]:
    """(file count, newest mtime) of everything the running server loaded once
    at start-up: the app code, this script, the metadata and .env."""
    watched = [
        *PROJECT_ROOT.joinpath("app").rglob("*.py"),
        *PROJECT_ROOT.joinpath("metadata").rglob("*.yaml"),
        *PROJECT_ROOT.joinpath("metadata", "generated").glob("*.json"),
        Path(__file__).resolve(),
        PROJECT_ROOT / ".env",
    ]
    stamps = [
        path.stat().st_mtime_ns for path in watched
        if path.exists() and "Models" not in path.parts and "__pycache__" not in path.parts
    ]
    return len(stamps), max(stamps, default=0)


def _supervise(argv: list[str]) -> int:
    """Run the real server as a child process and restart it whenever the
    source or metadata changes on disk.

    The server loads code and metadata once; without this, editing or
    reverting a file left the *old* behaviour running until someone remembered
    to restart it - answers kept following code that no longer existed.
    Ctrl+C here stops everything; `--no-reload` runs the server directly.
    """
    child = None
    try:
        while True:
            seen = _source_fingerprint()
            child = subprocess.Popen([sys.executable, str(Path(__file__).resolve()), *argv, "--child"])
            while child.poll() is None:
                time.sleep(1)
                if _source_fingerprint() != seen:
                    print("[debug_server] code/metadata changed on disk - restarting with the new version", flush=True)
                    child.terminate()
                    child.wait(timeout=15)
                    break
            else:
                return child.returncode or 0
    except KeyboardInterrupt:
        if child is not None and child.poll() is None:
            child.terminate()
        return 0


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--port", type=int, default=8000)
    parser.add_argument("--provider", choices=["sarvam", "gemini"], default="sarvam")
    parser.add_argument("--no-reload", action="store_true", help="do not restart when files change")
    parser.add_argument("--child", action="store_true", help=argparse.SUPPRESS)
    args = parser.parse_args()

    if not args.no_reload and not args.child:
        forwarded = ["--port", str(args.port), "--provider", args.provider]
        print(f"Auto-reload on: the server restarts itself when code/metadata changes (use --no-reload to disable).", flush=True)
        raise SystemExit(_supervise(forwarded))

    _state.settings = Settings(LLM_PROVIDER=args.provider)
    # Deterministic intent-matching is tried first and is free; this only
    # runs as a fallback when that's ambiguous or empty (e.g. "student
    # detail" instead of "students") - see LLMIntentClassifier.
    _state.orchestrator = StudentQueryOrchestrator(
        intent_classifier=build_configured_intent_classifier(_state.settings)
    )
    print(f"Using LLM provider: {args.provider}")

    server = ThreadingHTTPServer(("127.0.0.1", args.port), Handler)
    print(f"Debug trace viewer running at http://127.0.0.1:{args.port}/  (Ctrl+C to stop)")
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        pass


if __name__ == "__main__":
    main()
