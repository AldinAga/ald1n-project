#!/usr/bin/env python3
"""B844: complete unassessed WSTG v4.2 + ASVS v5.0.0 L1/L2 coverage census.

This is an INVENTORY, not a scanner, penetration test, or security PASS.
Require upstream fixed Git blob identities before producing any output.
"""
from __future__ import annotations

import argparse
import csv
import hashlib
import io
import json
import re
from pathlib import Path

WSTG_SOURCE = "https://github.com/OWASP/wstg/blob/6664009ef074ee08f349b9da1909acc4340c0e18/checklists/checklist.md"
WSTG_BLOB = "7fe5f31651ccbdf4fd7f67fe72b1b4f866c98931"
ASVS_SOURCE = "https://github.com/OWASP/ASVS/blob/v5.0.0/5.0/docs_en/OWASP_Application_Security_Verification_Standard_5.0.0_en.csv"
ASVS_BLOB = "184cf3b04a8fe1809dbed8b6e73be7a6fbaf8689"
WSTG_EXPECTED = 115
ASVS_L1_EXPECTED = 70
ASVS_L2_EXPECTED = 183
ASVS_L3_EXPECTED = 92


def git_blob_sha(payload: bytes) -> str:
    data = b"blob " + str(len(payload)).encode("ascii") + b"\0" + payload
    return hashlib.sha1(data, usedforsecurity=False).hexdigest()


def verify_source(payload: bytes, expected: str, label: str) -> None:
    actual = git_blob_sha(payload)
    if actual != expected:
        raise ValueError(f"HOLD_{label}_UPSTREAM_BLOB_DRIFT expected={expected} actual={actual}")


def wstg_items(text: str) -> list[dict[str, object]]:
    items = []
    for line in text.splitlines():
        match = re.match(r"^\|\s*(WSTG-[A-Z]{4}-\d{2})\s*\|\s*([^|]+)\|", line)
        if not match:
            continue
        identifier, title = match.group(1), match.group(2).strip()
        items.append({
            "framework": "OWASP_WSTG",
            "version": "4.2",
            "id": identifier,
            "section": identifier.split("-")[1],
            "title": title,
            "requirement_text": title,
            "source": WSTG_SOURCE,
        })
    return items


def asvs_l2_items(text: str) -> tuple[list[dict[str, object]], dict[int, int]]:
    items = []
    level_counts = {1: 0, 2: 0, 3: 0}
    for row in csv.DictReader(io.StringIO(text)):
        level_text = row.get("L", "").strip()
        if level_text not in ("1", "2", "3"):
            raise ValueError("HOLD_ASVS_UNEXPECTED_LEVEL")
        level = int(level_text)
        level_counts[level] += 1
        if level == 3:
            continue  # L3 is outside the initial Level 2 census.
        raw_id = row.get("req_id", "").strip()
        if not re.fullmatch(r"V\d+\.\d+\.\d+", raw_id):
            raise ValueError("HOLD_ASVS_MALFORMED_ID")
        # The initial B844 register stored only the ASVS chapter as "title".
        # An assessor cannot test 253 distinct requirements without the exact
        # req_description. Preserve canonical text and chapter/section provenance.
        chapter_id = row.get("chapter_id", "").strip()
        chapter_name = row.get("chapter_name", "").strip()
        section_id = row.get("section_id", "").strip()
        section_name = row.get("section_name", "").strip()
        requirement_text = row.get("req_description", "").strip()
        if (not re.fullmatch(r"V\d+", chapter_id)
                or not re.fullmatch(r"V\d+\.\d+", section_id)
                or not raw_id.startswith(section_id + ".")
                or not section_id.startswith(chapter_id + ".")
                or not chapter_name or not section_name
                or len(requirement_text) < 30
                or any(ord(ch) < 32 and ch not in "\n\t" for ch in requirement_text)):
            raise ValueError("HOLD_ASVS_MISSING_OR_MISMATCHED_REQUIREMENT_TEXT")
        items.append({
            "framework": "OWASP_ASVS",
            "version": "5.0.0",
            "id": "v5.0.0-" + raw_id[1:],
            "chapter_id": chapter_id,
            "chapter_name": chapter_name,
            "section_id": section_id,
            "section_name": section_name,
            "section": section_name,
            "title": requirement_text,
            "requirement_text": requirement_text,
            "level": level,
            "source": ASVS_SOURCE,
        })
    return items, level_counts


def generate(wstg_bytes: bytes, asvs_bytes: bytes, target_commit: str) -> dict[str, object]:
    verify_source(wstg_bytes, WSTG_BLOB, "WSTG42")
    verify_source(asvs_bytes, ASVS_BLOB, "ASVS500")
    if not re.fullmatch(r"[a-f0-9]{40}", target_commit):
        raise ValueError("HOLD_INVALID_TARGET_COMMIT")

    items_w = wstg_items(wstg_bytes.decode("utf-8-sig"))
    items_a, counts = asvs_l2_items(asvs_bytes.decode("utf-8-sig"))
    if len(items_w) != WSTG_EXPECTED or counts != {1: ASVS_L1_EXPECTED, 2: ASVS_L2_EXPECTED, 3: ASVS_L3_EXPECTED}:
        raise ValueError("HOLD_UPSTREAM_CHECKLIST_CENSUS_MISMATCH")
    if len(items_a) != ASVS_L1_EXPECTED + ASVS_L2_EXPECTED:
        raise ValueError("HOLD_ASVS_L2_COUNT_MISMATCH")
    items = items_w + items_a
    identifiers = [str(item["id"]) for item in items]
    if len(identifiers) != len(set(identifiers)):
        raise ValueError("HOLD_DUPLICATE_SECURITY_REQUIREMENT_ID")

    return {
        "title": "Ald1n B844 WSTG4.2 + ASVS5.0.0 L2 item-level initial census",
        "target_commit": target_commit,
        "disclaimer": "Requirements preserved verbatim from pinned source. Mapping only; all applicability and full tests UNASSESSED/NOT_RUN. No security PASS.",
        "license": "OWASP original sources CC BY-SA 4.0; this register cites their pinned identifiers and category names.",
        "source_git_blob_ids": {"wstg": WSTG_BLOB, "asvs": ASVS_BLOB},
        "counts": {
            "wstg_v4_2": len(items_w),
            "asvs_l1": counts[1],
            "asvs_l2_additional": counts[2],
            "asvs_l1_plus_l2": len(items_a),
            "total": len(items),
            "unassessed": len(items),
            "verified_security_pass": 0,
        },
        "items": [
            item | {
                "applicability": "UNASSESSED",
                "status": "NOT_RUN",
                "scope_route_role": "",
                "test_method": "",
                "expected_result": "",
                "evidence_ref": "",
                "applicability_rationale": "",
                "reviewed_by": "",
            }
            for item in items
        ],
    }


def main() -> None:
    ap = argparse.ArgumentParser()
    ap.add_argument("--wstg", type=Path, required=True)
    ap.add_argument("--asvs", type=Path, required=True)
    ap.add_argument("--target-commit", required=True)
    ap.add_argument("--output", type=Path, required=True)
    args = ap.parse_args()
    data = generate(args.wstg.read_bytes(), args.asvs.read_bytes(), args.target_commit)
    args.output.parent.mkdir(parents=True, exist_ok=True)
    args.output.write_text(json.dumps(data, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
    print("B844_INITIAL_CENSUS_ONLY_NOT_SECURITY_PASS=" + str(data["counts"]["total"]))
    print("B844_WSTG=" + str(data["counts"]["wstg_v4_2"]))
    print("B844_ASVS_LEVEL1_PLUS_2=" + str(data["counts"]["asvs_l1_plus_l2"]))
    print("B844_SECURITY_PASS=0_UNASSESSED=" + str(data["counts"]["unassessed"]))


if __name__ == "__main__":
    main()
