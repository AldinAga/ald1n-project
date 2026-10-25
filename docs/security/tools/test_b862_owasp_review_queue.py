#!/usr/bin/env python3
"""B863/B864 synthetic-only fail-closed review-queue tests, no live targets."""
import copy
import csv
import io
import json
import unittest
from b862_owasp_review_queue import EXPECTED_SOURCE,build

SHA="a"*40
def fixture():
    rows=[]
    for section,count in (("INFO",60),("CONF",55)):
        for i in range(1,count+1):
            ident=f"WSTG-{section}-{i:02d}"
            title=f"Review synthetic {ident} security requirement"
            rows.append({
                "framework":"OWASP_WSTG","version":"4.2",
                "id":ident,"section":section,"title":title,
                "requirement_text":title,"applicability":"UNASSESSED","status":"NOT_RUN",
            })
    for i in range(1,254):
        desc=f"Verify that synthetic security requirement number {i} is preserved as exact unique wording."
        rows.append({
            "framework":"OWASP_ASVS","version":"5.0.0",
            "id":f"v5.0.0-1.1.{i}","chapter_id":"V1",
            "chapter_name":"Encoding and Sanitization",
            "section_id":"V1.1","section_name":"Encoding",
            "section":"Encoding","title":desc,
            "requirement_text":desc,"level":1 if i<=70 else 2,
            "applicability":"UNASSESSED","status":"NOT_RUN",
        })
    return {
        "target_commit":SHA,"source_git_blob_ids":dict(EXPECTED_SOURCE),
        "counts":{"wstg_v4_2":115,"asvs_l1":70,"asvs_l2_additional":183,
                  "asvs_l1_plus_l2":253,"total":368,"unassessed":368,
                  "verified_security_pass":0},
        "items":rows,
    }
def execute(register=None,head=SHA):
    record=fixture() if register is None else register
    raw=json.dumps(record,sort_keys=True).encode()
    return build(record,head,raw)

class ReviewQueueTest(unittest.TestCase):
    def test_positive_complete_no_pass_no_auto_na(self):
        data,text=execute()
        self.assertEqual(data["total"],368)
        self.assertEqual(data["verified_security_pass"],0)
        self.assertEqual(data["applicability_reviewed"],0)
        self.assertEqual(data["not_applicable"],0)
        self.assertEqual(data["not_run"],368)
        self.assertEqual(len(data["items"]),368)
        self.assertTrue(all(x["status"]=="NOT_RUN" and
                            x["applicability"]=="UNASSESSED" for x in data["items"]))
        self.assertIn("CANDIDATE",data["mapping_authority"])
        self.assertEqual(len(list(csv.DictReader(io.StringIO(text)))),368)
        self.assertIn("requirement_text",text.splitlines()[0])
        self.assertIn("unique wording",text)
    def test_source_commit_mismatch_is_hold(self):
        with self.assertRaisesRegex(ValueError,"HOLD_SOURCE_COMMIT_MISMATCH"):
            execute(head="b"*40)
        with self.assertRaisesRegex(ValueError,"HOLD_BAD_TARGET_COMMIT"):
            execute(head="main")
    def test_upstream_source_blob_mismatch_is_hold(self):
        f=fixture()
        f["source_git_blob_ids"]["asvs"]="0"*40
        with self.assertRaisesRegex(ValueError,"HOLD_OWASP_SOURCE_BLOBS"):
            execute(f)
    def test_missing_case_is_hold(self):
        f=fixture()
        f["items"].pop()
        with self.assertRaisesRegex(ValueError,"HOLD_INCOMPLETE_CONTROL_ROWS"):
            execute(f)
    def test_duplicate_case_is_hold(self):
        f=fixture()
        f["items"][1]["id"]=f["items"][0]["id"]
        with self.assertRaisesRegex(ValueError,"HOLD_DUPLICATE_OR_INVALID"):
            execute(f)
    def test_false_pass_is_hold(self):
        f=fixture()
        f["items"][0]["status"]="PASS"
        with self.assertRaisesRegex(ValueError,"HOLD_UNAUTHORIZED_ASSESSMENT_OR_PASS"):
            execute(f)
    def test_blanket_na_is_hold(self):
        f=fixture()
        f["items"][0]["applicability"]="NOT_APPLICABLE"
        with self.assertRaisesRegex(ValueError,"HOLD_UNAUTHORIZED_ASSESSMENT_OR_PASS"):
            execute(f)
    def test_evidence_or_reviewer_without_proof_is_hold(self):
        for key in ("evidence_ref","reviewed_by","test_method","scope_route_role","applicability_rationale"):
            f=fixture()
            f["items"][0][key]="invented-pass"
            with self.assertRaisesRegex(ValueError,"HOLD_PREEXISTING_UNATTESTED_EVIDENCE"):
                execute(f)
    def test_asvs_chapter_without_unique_description_is_hold(self):
        f=fixture()
        f["items"][-1]["requirement_text"]="Encoding and Sanitization"
        f["items"][-1]["title"]="Encoding and Sanitization"
        with self.assertRaisesRegex(ValueError,"HOLD_ASVS_REQUIREMENT_LOST_OR_TRUNCATED"):
            execute(f)
    def test_asvs_requirement_title_drift_is_hold(self):
        f=fixture()
        f["items"][-1]["title"]="Wrong chapter paraphrase"
        with self.assertRaisesRegex(ValueError,"HOLD_ASVS_REQUIREMENT_LOST_OR_TRUNCATED"):
            execute(f)
    def test_asvs_invalid_level_and_section_hard_hold(self):
        f=fixture();f["items"][-1]["level"]=3
        with self.assertRaisesRegex(ValueError,"HOLD_ASVS_REQUIREMENT_LOST_OR_TRUNCATED"):
            execute(f)
        f=fixture();f["items"][-1]["section_id"]="V1.5"
        with self.assertRaisesRegex(ValueError,"HOLD_ASVS_SECTION_ID_MISMATCH"):
            execute(f)
    def test_new_unmapped_chapter_requires_review(self):
        f=fixture()
        f["items"][-1]["chapter_id"]="V55"
        f["items"][-1]["section_id"]="V55.1"
        f["items"][-1]["id"]="v5.0.0-55.1.253"
        with self.assertRaisesRegex(ValueError,"HOLD_UNMAPPED_CHAPTER"):
            execute(f)
    def test_changed_framework_count_rejected(self):
        f=fixture()
        f["counts"]["asvs_l1"]=71
        with self.assertRaisesRegex(ValueError,"HOLD_WRONG_368_CONTROL_COUNTS"):
            execute(f)
    def test_extra_control_char_rejected(self):
        f=fixture()
        f["items"][-1]["requirement_text"] += chr(1)
        f["items"][-1]["title"] += chr(1)
        with self.assertRaisesRegex(ValueError,"HOLD_UNSAFE_REQUIREMENT_CONTROL_CHAR"):
            execute(f)
    def test_wstg_wrong_title_is_hold(self):
        f=fixture()
        f["items"][0]["title"]="unrelated"
        with self.assertRaisesRegex(ValueError,"HOLD_WSTG_TITLE_DRIFT"):
            execute(f)
if __name__=="__main__":
    unittest.main()
