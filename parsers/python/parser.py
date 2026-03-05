#!/usr/bin/env python3
"""
Простий парсер тестів на Python.

Для адаптації під інший сайт змініть CSS-селектори у словнику CONFIG.
Результат зберігається у JSON-файл.
"""

from __future__ import annotations

import json
import sys
from pathlib import Path
from urllib.parse import urlparse

import requests
from bs4 import BeautifulSoup

CONFIG = {
    "question_selector": "div.question",
    "option_selector": "li.option",
    "correct_attr": "data-correct",
}


def load_source(source: str) -> str:
    parsed = urlparse(source)
    if parsed.scheme in {"http", "https"}:
        response = requests.get(source, timeout=20)
        response.raise_for_status()
        return response.text
    return Path(source).read_text(encoding="utf-8")


def main() -> int:
    if len(sys.argv) < 2:
        print("Використання: python parser.py <url_or_html_file> [output_json]", file=sys.stderr)
        return 1

    source = sys.argv[1]
    output = Path(sys.argv[2]) if len(sys.argv) > 2 else Path(__file__).resolve().parents[2] / "data" / "python_parsed_questions.json"

    html = load_source(source)
    soup = BeautifulSoup(html, "html.parser")

    payload = {
        "class": "7",
        "subject": "Невизначено",
        "topic": "Імпортовано парсером Python",
        "test_title": "Імпорт із сайту (Python parser)",
        "questions": [],
    }

    for q in soup.select(CONFIG["question_selector"]):
        options = q.select(CONFIG["option_selector"])
        if len(options) != 4:
            continue

        normalized_options = [x.get_text(" ", strip=True) for x in options]
        correct_idx = 0
        for idx, opt in enumerate(options):
            if opt.get(CONFIG["correct_attr"]) == "1":
                correct_idx = idx
                break

        payload["questions"].append(
            {
                "text": q.get_text(" ", strip=True),
                "options": normalized_options,
                "correct_index": correct_idx,
            }
        )

    output.parent.mkdir(parents=True, exist_ok=True)
    output.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"Збережено у {output}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
