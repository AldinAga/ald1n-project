#!/usr/bin/env python3
"""B846: admit bounded synthetic JUnit evidence, never OWASP or release PASS."""
import argparse
import hashlib
import json
import re
import xml.etree.ElementTree as ET
from pathlib import Path

REQUIRED = {
"test_same_nonce_cannot_change_shipping_address_on_replay",
"test_missing_receipt_ciphertext_cannot_create_fresh_access",
"test_mismatched_receipt_token_hash_cannot_be_replayed",
"test_reassigned_order_cannot_expose_private_receipt_to_original_buyer",
"test_blocked_buyer_cannot_replay_previous_checkout",
"test_emergency_storefront_off_cancels_replay_before_data_access",
}
def parse(path):
    raw=Path(path).read_bytes()
    root=ET.fromstring(raw)
    cases=[]
    for case in root.iter("testcase"):
        name=case.get("name","")
        if not name.startswith("test_") or not (case.get("class") or case.get("classname")):
            raise ValueError("HOLD_UNNAMED_CASE")
        if any(item.tag in ("failure","error","skipped") for item in case.iter() if item is not case):
            raise ValueError("HOLD_FAILED_ERROR_OR_SKIPPED")
        cases.append(name)
    if not cases or len(set(cases))!=len(cases):
        raise ValueError("HOLD_EMPTY_OR_DUPLICATE")
    return cases,hashlib.sha256(raw).hexdigest()
def build(replay,boundary,sha):
    if re.fullmatch(r"[a-f0-9]{40}",sha) is None:
        raise ValueError("HOLD_INVALID_SOURCE")
    r,rhash=parse(replay)
    b,bhash=parse(boundary)
    if len(r)!=11 or len(b)!=15 or set(r)&set(b) or not REQUIRED.issubset(r):
        raise ValueError("HOLD_SCOPE_MISMATCH")
    return {"source_sha":sha,"result":"SCOPED_SYNTHETIC_ONLY","release":"HOLD",
      "tests":{"replay":len(r),"boundary":len(b),"combined":len(r)+len(b)},
      "required_negative_cases":sorted(REQUIRED),
      "junit_sha256":{"replay":rhash,"boundary":bhash},
      "owasp_status":"UNASSESSED_NOT_RUN","verified_security_pass":0,
      "independent_review":"NOT_RUN","production_deployed":False}
def main():
    p=argparse.ArgumentParser()
    for name in ("replay","boundary","source-sha","output"):
        p.add_argument("--"+name,required=True)
    a=p.parse_args()
    result=build(a.replay,a.boundary,a.source_sha)
    Path(a.output).write_text(json.dumps(result,indent=2)+"\n",encoding="utf-8")
    print("B846_SCOPE="+str(result["tests"]["combined"])+"_TESTS_RELEASE_HOLD")
if __name__=="__main__":
    main()
