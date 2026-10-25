#!/usr/bin/env python3
"""B857: inspect real composer-audit lockfile output; advisory findings are HOLD.

This is SCA for a pinned lockfile, not an authorized live attack, remediation
or independent assessment of the full 368-item OWASP census.
"""
import argparse
import hashlib
import json
import re
from pathlib import Path

def classify(raw: bytes, source_sha: str, lockfile: bytes, audit_rc: int) -> dict:
    if re.fullmatch(r"[0-9a-f]{40}",source_sha) is None:
        raise ValueError("HOLD_SOURCE_HEAD")
    if audit_rc not in (0,1,2,3):
        raise ValueError("HOLD_AUDIT_TOOL_EXIT")
    lock=json.loads(lockfile)
    if not isinstance(lock,dict) or "packages" not in lock or not isinstance(lock["packages"],list):
        raise ValueError("HOLD_INVALID_LOCKFILE")
    result=json.loads(raw)
    if not isinstance(result,dict):
        raise ValueError("HOLD_BAD_AUDIT_JSON")
    # Composer 2.x renders empty advisory/abandoned collections as [] rather than {}.
    # Accept only an EMPTY array as zero, never discard nonempty unknown records.
    advisory_data=result.get("advisories")
    if advisory_data==[]:
        advisory_data={}
    if not isinstance(advisory_data,dict):
        raise ValueError("HOLD_BAD_AUDIT_JSON")
    abandoned=result.get("abandoned",{})
    if abandoned==[]:
        abandoned={}
    if not isinstance(abandoned,dict):
        raise ValueError("HOLD_BAD_ABANDONMENT_FORMAT")
    findings=[]
    for package,items in advisory_data.items():
        if not isinstance(package,str) or not isinstance(items,list):
            raise ValueError("HOLD_BAD_ADVISORY")
        for item in items:
            if not isinstance(item,dict):
                raise ValueError("HOLD_BAD_ADVISORY_RECORD")
            ident=item.get("advisoryId") or item.get("cve") or item.get("remoteId")
            if not isinstance(ident,(str,int)):
                raise ValueError("HOLD_MISSING_ADVISORY_ID")
            findings.append({
                "package":package,
                "advisory":str(ident),
                "severity":str(item.get("severity","unknown")),
                "title":str(item.get("title",""))[:180],
            })
    abandoned_count=len(abandoned)
    finding_count=len(findings)
    if finding_count==0 and abandoned_count==0 and audit_rc!=0:
        raise ValueError("HOLD_NONZERO_WITHOUT_EXPLAINED_FINDING")
    if finding_count>0 and audit_rc==0:
        raise ValueError("HOLD_ZERO_EXIT_BUT_ADVISORIES")
    decision="PASS_LOCKED_COMPOSER_AUDIT" if audit_rc==0 and finding_count==0 and abandoned_count==0 else "HOLD_ADVISORIES_OR_ABANDONED"
    return {
        "source_sha":source_sha,
        "composer_lock_sha256":hashlib.sha256(lockfile).hexdigest(),
        "audit_json_sha256":hashlib.sha256(raw).hexdigest(),
        "composer_audit_exit_code":audit_rc,
        "advisory_count":finding_count,
        "abandoned_count":abandoned_count,
        "advisories":sorted(findings,key=lambda v:(v["package"],v["advisory"])),
        "abandoned":abandoned,
        "result":decision,
        "release":"HOLD",
        "owasp_items_verified_pass":0,
        "coverage":"COMPOSER_LOCK_SCA_ONLY_NOT_FULL_PENETRATION",
        "production_mutated":False,
    }

def main():
    p=argparse.ArgumentParser()
    p.add_argument("--audit-json",required=True,type=Path)
    p.add_argument("--lockfile",required=True,type=Path)
    p.add_argument("--source-sha",required=True)
    p.add_argument("--exit-code",required=True,type=int)
    p.add_argument("--output",required=True,type=Path)
    a=p.parse_args()
    result=classify(a.audit_json.read_bytes(),a.source_sha,a.lockfile.read_bytes(),a.exit_code)
    a.output.write_text(json.dumps(result,indent=2,sort_keys=True,ensure_ascii=False)+"\n",encoding="utf-8")
    print("B857_COMPOSER_ADVISORIES="+str(result["advisory_count"]))
    print("B857_COMPOSER_ABANDONED="+str(result["abandoned_count"]))
    print("B857_COMPOSER_RESULT="+result["result"]+"_GLOBAL_RELEASE_HOLD")
    if result["result"]!="PASS_LOCKED_COMPOSER_AUDIT":
        raise SystemExit(2)

if __name__=="__main__":
    main()
