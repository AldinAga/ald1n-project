#!/usr/bin/env python3
"""B862-B864: item-level 368 OWASP review queue; NO security test is marked PASS.

Retains verbatim OWASP requirement descriptions from B844's two Git-pinned
source blobs. Maps each item to an *unreviewed candidate* SEC01-SEC28
workstream, never asserting applicability, route, owner, method or evidence.
No network, active HTTP probes, live credentials, production or CI bypass.
"""
from __future__ import annotations

import argparse
import csv
import hashlib
import io
import json
import re
from collections import Counter
from pathlib import Path

WSTG_STREAM = {
    "INFO": "SEC01", "CONF": "SEC03", "IDNT": "SEC04",
    "ATHN": "SEC07", "ATHZ": "SEC05", "SESS": "SEC08",
    "INJT": "SEC11", "ERRH": "SEC24", "CRYP": "SEC03",
    "BUSL": "SEC26", "CLNT": "SEC23", "APIT": "SEC04",
    "WASM": "SEC23",
}
ASVS_STREAM = {
    "V1": "SEC10", "V2": "SEC17", "V3": "SEC23",
    "V4": "SEC04", "V5": "SEC13", "V6": "SEC07",
    "V7": "SEC08", "V8": "SEC05", "V9": "SEC06",
    "V10": "SEC07", "V11": "SEC03", "V12": "SEC03",
    "V13": "SEC28", "V14": "SEC22", "V15": "SEC25",
    "V16": "SEC24", "V17": "SEC23",
}
EXPECTED_SOURCE = {
    "wstg": "7fe5f31651ccbdf4fd7f67fe72b1b4f866c98931",
    "asvs": "184cf3b04a8fe1809dbed8b6e73be7a6fbaf8689",
}
FIELDS = (
    "framework", "id", "version", "level", "chapter", "section",
    "candidate_workstream", "requirement_text", "applicability", "status",
    "scope_route_role", "test_method", "expected_result",
    "evidence_ref", "reviewed_by", "review_notes",
)
SHA = re.compile(r"[a-f0-9]{40}\Z")
ASVS_ID = re.compile(r"v5\.0\.0-\d+\.\d+\.\d+\Z")
WSTG_ID = re.compile(r"WSTG-[A-Z]{4}-\d{2}\Z")

def build(register: dict, expected_source_sha: str, register_raw: bytes) -> tuple[dict, str]:
    if SHA.fullmatch(expected_source_sha) is None:
        raise ValueError("HOLD_BAD_TARGET_COMMIT")
    if not isinstance(register, dict) or register.get("target_commit") != expected_source_sha:
        raise ValueError("HOLD_SOURCE_COMMIT_MISMATCH")
    if register.get("source_git_blob_ids") != EXPECTED_SOURCE:
        raise ValueError("HOLD_OWASP_SOURCE_BLOBS_MISMATCH")
    totals=register.get("counts")
    if not isinstance(totals, dict) or any(totals.get(k)!=v for k,v in {
        "wstg_v4_2":115, "asvs_l1":70, "asvs_l2_additional":183,
        "asvs_l1_plus_l2":253, "total":368, "unassessed":368,
        "verified_security_pass":0,
    }.items()):
        raise ValueError("HOLD_WRONG_368_CONTROL_COUNTS")
    items=register.get("items")
    if not isinstance(items,list) or len(items)!=368:
        raise ValueError("HOLD_INCOMPLETE_CONTROL_ROWS")
    seen=set()
    rows=[]
    bystream=Counter()
    framework_counts=Counter()
    level_counts=Counter()
    for item in items:
        if not isinstance(item,dict):
            raise ValueError("HOLD_INVALID_CONTROL")
        identifier=item.get("id")
        if not isinstance(identifier,str) or identifier in seen:
            raise ValueError("HOLD_DUPLICATE_OR_INVALID_CONTROL_ID")
        seen.add(identifier)
        if item.get("status")!="NOT_RUN" or item.get("applicability")!="UNASSESSED":
            raise ValueError("HOLD_UNAUTHORIZED_ASSESSMENT_OR_PASS")
        if any(item.get(field,"") not in ("",None) for field in
               ("scope_route_role","test_method","expected_result","evidence_ref",
                "applicability_rationale","reviewed_by")):
            raise ValueError("HOLD_PREEXISTING_UNATTESTED_EVIDENCE")
        requirement=item.get("requirement_text")
        if not isinstance(requirement,str) or not requirement.strip():
            raise ValueError("HOLD_MISSING_ACTUAL_REQUIREMENT_TEXT")
        if item.get("framework")=="OWASP_WSTG":
            if WSTG_ID.fullmatch(identifier) is None or item.get("version")!="4.2":
                raise ValueError("HOLD_BAD_WSTG_ID_OR_VERSION")
            section=identifier.split("-")[1]
            if item.get("section")!=section:
                raise ValueError("HOLD_WSTG_SECTION_DRIFT")
            if requirement!=item.get("title"):
                raise ValueError("HOLD_WSTG_TITLE_DRIFT")
            stream=WSTG_STREAM.get(section)
            chapter="WSTG-"+section
            level=""
            framework_counts["WSTG"]+=1
        elif item.get("framework")=="OWASP_ASVS":
            if ASVS_ID.fullmatch(identifier) is None or item.get("version")!="5.0.0":
                raise ValueError("HOLD_BAD_ASVS_ID_OR_VERSION")
            chapter=item.get("chapter_id")
            section=item.get("section_id")
            if not isinstance(chapter,str) or not isinstance(section,str):
                raise ValueError("HOLD_MISSING_ASVS_CHAPTER")
            if not section.startswith(chapter+".") or not identifier[7:].startswith(section[1:]+"."):
                raise ValueError("HOLD_ASVS_SECTION_ID_MISMATCH")
            if (item.get("level") not in (1,2)
                or len(requirement.strip())<30
                or requirement!=item.get("title")
                or not item.get("chapter_name")
                or not item.get("section_name")):
                raise ValueError("HOLD_ASVS_REQUIREMENT_LOST_OR_TRUNCATED")
            stream=ASVS_STREAM.get(chapter)
            level=item["level"]
            framework_counts["ASVS"]+=1
            level_counts[level]+=1
        else:
            raise ValueError("HOLD_UNKNOWN_FRAMEWORK")
        if stream is None:
            raise ValueError("HOLD_UNMAPPED_CHAPTER_MANUAL_SCOPE_REVIEW")
        if any(ord(c)<32 and c not in "\n\t" for c in requirement):
            raise ValueError("HOLD_UNSAFE_REQUIREMENT_CONTROL_CHAR")
        bystream[stream]+=1
        rows.append({
            "framework":item["framework"],
            "id":identifier,
            "version":item["version"],
            "level":level,
            "chapter":chapter,
            "section":section,
            "candidate_workstream":stream,
            "requirement_text":requirement,
            "applicability":"UNASSESSED",
            "status":"NOT_RUN",
            "scope_route_role":"",
            "test_method":"",
            "expected_result":"",
            "evidence_ref":"",
            "reviewed_by":"",
            "review_notes":"",
        })
    if framework_counts!={"WSTG":115,"ASVS":253} or level_counts!={1:70,2:183}:
        raise ValueError("HOLD_FRAMEWORK_LEVEL_COUNTS")
    ordered=sorted(rows,key=lambda x:(x["framework"],x["id"]))
    doc={
        "target_commit":expected_source_sha,
        "input_census_sha256":hashlib.sha256(register_raw).hexdigest(),
        "official_source_blobs":EXPECTED_SOURCE,
        "frameworks":{"WSTG_4_2":115,"ASVS_5_0_0_L1":70,"ASVS_5_0_0_L2_ADD":183},
        "total":368,
        "unassessed":368,
        "not_run":368,
        "applicability_reviewed":0,
        "verified_security_pass":0,
        "not_applicable":0,
        "status":"PRELIMINARY_SCOPE_QUEUE_ONLY_NOT_PENETRATION_ASSESSMENT",
        "mapping_authority":"CANDIDATE_WORKSTREAM_ONLY_NO_APPLICABILITY_OR_COVERAGE_CLAIMS",
        "workstream_candidate_counts":dict(sorted(bystream.items())),
        "methodology":"Only pinned OWASP requirement wording, source ID/level, and a coarse chapter-to-SEC routing hint. All applicability, route/role scope, method, expected result, evidence and reviewer fields MUST be completed and independently assessed before any control PASS.",
        "production_mutated":False,
        "release":"HOLD",
        "items":ordered,
    }
    out=io.StringIO(newline="")
    writer=csv.DictWriter(out,fieldnames=FIELDS,lineterminator="\n")
    writer.writeheader()
    writer.writerows(ordered)
    return doc,out.getvalue()

def main():
    p=argparse.ArgumentParser()
    p.add_argument("--census",type=Path,required=True)
    p.add_argument("--source-sha",required=True)
    p.add_argument("--json",type=Path,required=True)
    p.add_argument("--csv",type=Path,required=True)
    a=p.parse_args()
    raw=a.census.read_bytes()
    register=json.loads(raw)
    document,csv_text=build(register,a.source_sha,raw)
    a.json.write_text(json.dumps(document,ensure_ascii=False,sort_keys=True,indent=2)+"\n",encoding="utf-8")
    a.csv.write_text(csv_text,encoding="utf-8")
    print("B862_OWASP_FULL_REQUIREMENTS=368_VERBATIM")
    print("B863_CANDIDATE_SCOPE_ONLY=368_UNASSESSED_NOT_RUN")
    print("B864_VERIFIED_SECURITY_PASS=0_RELEASE_HOLD")
    print("B862_QUEUE_CSV_SHA256="+hashlib.sha256(a.csv.read_bytes()).hexdigest())
if __name__=="__main__":
    main()
