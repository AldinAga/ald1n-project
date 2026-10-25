#!/usr/bin/env python3
"""B859: exact-head 574-case dependency-only full-suite comparison.

This does not hide inherited red suites, rewrite security tests, or declare
production release. It only answers whether the verified Composer lock update
added a new PHPUnit failure/error/warning to the unchanged CMS source.
"""
import argparse
import hashlib
import json
import re
import xml.etree.ElementTree as ET
from pathlib import Path

PREPATCH="6af1e49df4f96d8a7fb284b3f93cc7c90075c264"
EXPECTED_CASES=574
SHA=re.compile(r"[0-9a-f]{40}\Z")

def load_junit(raw):
    root=ET.fromstring(raw)
    if root.tag not in ("testsuite","testsuites"):
        raise ValueError("HOLD_JUNIT_ROOT")
    rows={}
    for case in root.iter("testcase"):
        name=case.get("name","")
        klass=case.get("class") or case.get("classname") or ""
        if not name or not klass:
            raise ValueError("HOLD_EMPTY_CASE_IDENTITY")
        identity=klass+"::"+name
        if identity in rows:
            raise ValueError("HOLD_DUPLICATE_CASE_IDENTITY")
        flags={child.tag for child in case.iter() if child is not case}
        rows[identity]={
            "failure":bool({"failure","error"} & flags),
            "warning":"warning" in flags,
            "skipped":"skipped" in flags,
        }
    if len(rows)!=EXPECTED_CASES:
        raise ValueError("HOLD_EXPECTED_574_CASES_"+str(len(rows)))
    return rows

def compare(before_raw,after_raw,before_sha,after_sha,lock_before,lock_after):
    if SHA.fullmatch(before_sha) is None or SHA.fullmatch(after_sha) is None:
        raise ValueError("HOLD_BAD_SOURCE_SHA")
    if before_sha!=PREPATCH or after_sha==before_sha:
        raise ValueError("HOLD_SOURCE_PROVENANCE_MISMATCH")
    if len(lock_before)!=64 or len(lock_after)!=64 or not all(
        re.fullmatch("[0-9a-f]{64}", x) for x in (lock_before,lock_after)
    ):
        raise ValueError("HOLD_BAD_LOCK_HASH")
    if lock_before==lock_after:
        raise ValueError("HOLD_UNCHANGED_LOCKFILE")
    a=load_junit(before_raw);b=load_junit(after_raw)
    if set(a)!=set(b):
        missing=sorted(set(a)-set(b)); added=sorted(set(b)-set(a))
        raise ValueError("HOLD_CASE_IDENTITY_DRIFT:"+str(len(missing))+":"+str(len(added)))
    if any(x["skipped"] for x in list(a.values())+list(b.values())):
        raise ValueError("HOLD_SKIPPED_PHPUNIT_CASE")
    old_bad={k for k,v in a.items() if v["failure"]}
    new_bad={k for k,v in b.items() if v["failure"]}
    new_failures=sorted(new_bad-old_bad)
    fixed_failures=sorted(old_bad-new_bad)
    old_warnings={k for k,v in a.items() if v["warning"]}
    new_warnings=sorted(k for k,v in b.items() if v["warning"] and k not in old_warnings)
    receipt={
      "before_source":before_sha,"after_source":after_sha,
      "before_lock_sha256":lock_before,"after_lock_sha256":lock_after,
      "before_junit_sha256":hashlib.sha256(before_raw).hexdigest(),
      "after_junit_sha256":hashlib.sha256(after_raw).hexdigest(),
      "cases_before":len(a),"cases_after":len(b),
      "failures_before":len(old_bad),"failures_after":len(new_bad),
      "new_failure_count":len(new_failures),"new_failures":new_failures,
      "fixed_count":len(fixed_failures),"fixed":fixed_failures,
      "new_warning_count":len(new_warnings),"new_warnings":new_warnings,
      "result":"HOLD_NEW_DEPENDENCY_REGRESSION" if new_failures or new_warnings else "PASS_DEPENDENCY_ONLY_NO_NEW_FAILURE_IDENTITIES",
      "global_suite_green":not bool(new_bad),
      "global_release":"HOLD",
      "known_B803_56_new_failures":"OPEN_UNWAIVED",
      "owasp_368_status":"UNASSESSED_NOT_RUN",
      "source_mutation":"NONE_CI_ONLY",
      "production_deployed":False,
    }
    # Do not emit a global release PASS even if the new package combination
    # happens to produce a green suite: merchant, device and pen-test gates remain.
    return receipt

def main():
    p=argparse.ArgumentParser()
    for flag in ("before-junit","after-junit","before-sha","after-sha",
                 "before-lock-sha256","after-lock-sha256","output"):
        p.add_argument("--"+flag,required=True)
    a=p.parse_args()
    receipt=compare(Path(a.before_junit).read_bytes(),Path(a.after_junit).read_bytes(),
                    a.before_sha,a.after_sha,a.before_lock_sha256,a.after_lock_sha256)
    Path(a.output).write_text(json.dumps(receipt,indent=2,sort_keys=True)+"\n",encoding="utf-8")
    print("B859_DEPENDENCY_DELTA="+receipt["result"])
    print("B859_FULL_SUITE_STATUS=GLOBAL_RELEASE_HOLD")
    if receipt["result"]!="PASS_DEPENDENCY_ONLY_NO_NEW_FAILURE_IDENTITIES":
        raise SystemExit(2)
if __name__=="__main__":
    main()
