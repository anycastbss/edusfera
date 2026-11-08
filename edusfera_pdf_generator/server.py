from __future__ import annotations

import json
import mimetypes
import shutil
import threading
import uuid
import zipfile
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path
from urllib.parse import unquote

from generator import ROOT, generate_pdfs, example_data

WEB = ROOT / "web"
OUTPUT = ROOT / "output" / "generated"
OUTPUT.mkdir(parents=True, exist_ok=True)
MAX_BODY = 24 * 1024 * 1024


class Handler(BaseHTTPRequestHandler):
    server_version = "EDUSFERA-PDF-Generator/1.0"

    def _send(self, code: int, content: bytes, content_type: str, filename: str | None = None):
        self.send_response(code)
        self.send_header("Content-Type", content_type)
        self.send_header("Content-Length", str(len(content)))
        self.send_header("Cache-Control", "no-store")
        if filename:
            self.send_header("Content-Disposition", f'attachment; filename="{filename}"')
        self.end_headers()
        self.wfile.write(content)

    def _json(self, code: int, obj: dict):
        self._send(code, json.dumps(obj, ensure_ascii=False).encode("utf-8"), "application/json; charset=utf-8")

    def do_GET(self):
        path = unquote(self.path.split("?", 1)[0])
        static = {
            "/": (WEB / "index.html", "text/html; charset=utf-8"),
            "/style.css": (WEB / "style.css", "text/css; charset=utf-8"),
            "/app.js": (WEB / "app.js", "application/javascript; charset=utf-8"),
        }
        if path in static:
            file, content_type = static[path]
            return self._send(200, file.read_bytes(), content_type)
        if path == "/api/example":
            return self._json(200, example_data(ROOT / "assets"))
        if path.startswith("/assets/"):
            file = (ROOT / path.lstrip("/")).resolve()
            if ROOT not in file.parents or not file.exists() or not file.is_file():
                return self._json(404, {"error": "Файл не найден"})
            return self._send(200, file.read_bytes(), mimetypes.guess_type(file.name)[0] or "application/octet-stream")
        if path.startswith("/download/"):
            parts = path.strip("/").split("/")
            if len(parts) != 3:
                return self._json(404, {"error": "Файл не найден"})
            token, name = parts[1], parts[2]
            if name not in {"edusfera_test_student.pdf", "edusfera_test_teacher_key.pdf", "edusfera_test_bundle.zip"}:
                return self._json(404, {"error": "Файл не найден"})
            file = (OUTPUT / token / name).resolve()
            if OUTPUT.resolve() not in file.parents or not file.exists():
                return self._json(404, {"error": "Ссылка устарела. Создайте PDF ещё раз."})
            return self._send(200, file.read_bytes(), "application/pdf" if name.endswith(".pdf") else "application/zip", name)
        return self._json(404, {"error": "Маршрут не найден"})

    def do_POST(self):
        if self.path != "/api/generate":
            return self._json(404, {"error": "Маршрут не найден"})
        try:
            length = int(self.headers.get("Content-Length", "0"))
            if length <= 0 or length > MAX_BODY:
                return self._json(413, {"error": "Размер запроса превышает допустимый лимит 24 МБ."})
            data = json.loads(self.rfile.read(length).decode("utf-8"))
            token = uuid.uuid4().hex[:12]
            out_dir = OUTPUT / token
            out_dir.mkdir(parents=True, exist_ok=True)
            student, key = generate_pdfs(data, out_dir)
            # Keep the source data next to the PDFs for editing/reuse.
            (out_dir / "source_test.json").write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8")
            bundle = out_dir / "edusfera_test_bundle.zip"
            with zipfile.ZipFile(bundle, "w", compression=zipfile.ZIP_DEFLATED) as zf:
                zf.write(student, student.name)
                zf.write(key, key.name)
                zf.write(out_dir / "source_test.json", "source_test.json")
                for question in data.get("questions", []):
                    diagram = question.get("diagram_path")
                    if diagram:
                        diagram_file = Path(diagram)
                        if not diagram_file.is_absolute():
                            diagram_file = (ROOT / diagram_file).resolve()
                        if diagram_file.exists() and diagram_file.is_file():
                            zf.write(diagram_file, f"assets/{diagram_file.name}")
            return self._json(200, {
                "ok": True,
                "student": f"/download/{token}/{student.name}",
                "key": f"/download/{token}/{key.name}",
                "bundle": f"/download/{token}/{bundle.name}",
                "count": len(data.get("questions", [])),
            })
        except Exception as exc:
            return self._json(400, {"error": str(exc)})

    def log_message(self, format, *args):
        print("[EDUSFERA] " + format % args)


def main():
    host, port = "127.0.0.1", 8765
    print("\nEDUSFERA PDF Generator запущен")
    print(f"Откройте в браузере: http://{host}:{port}")
    print("Файлы обрабатываются локально на этом компьютере. Для остановки нажмите Ctrl+C.\n")
    server = ThreadingHTTPServer((host, port), Handler)
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        print("\nСервер остановлен.")
    finally:
        server.server_close()


if __name__ == "__main__":
    main()
