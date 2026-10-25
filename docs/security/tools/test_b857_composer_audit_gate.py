#!/usr/bin/env python3
"""B857 offline synthetic advisory ingestion and safety checks."""
import json
import unittest
from b857_composer_audit_gate import classify

SHA="a"*40
LOCK=json.dumps({"packages":[{"name":"vendor/package","version":"1.0.0"}],"packages-dev":[]}).encode()
def audit(items=None,abandoned=None):
    return json.dumps({"advisories":{} if items is None else items,
                       "abandoned":{} if abandoned is None else abandoned}).encode()

class AuditGateTest(unittest.TestCase):
    def test_clean_lockfile_sca_is_still_not_release(self):
        r=classify(audit(),SHA,LOCK,0)
        self.assertEqual(r["result"],"PASS_LOCKED_COMPOSER_AUDIT")
        self.assertEqual(r["release"],"HOLD")
        self.assertEqual(r["owasp_items_verified_pass"],0)
    def test_composer_clean_empty_arrays_are_genuinely_zero(self):
        r=classify(b'{"advisories":[],"abandoned":[],"filter":[]}',SHA,LOCK,0)
        self.assertEqual(r["result"],"PASS_LOCKED_COMPOSER_AUDIT")
        self.assertEqual(r["advisory_count"],0)
        self.assertEqual(r["abandoned_count"],0)
    def test_unexplained_nonempty_audit_array_is_denied(self):
        with self.assertRaisesRegex(ValueError,"HOLD_BAD_AUDIT_JSON"):
            classify(b'{"advisories":[{"invalid":"unknown"}],"abandoned":[]}',SHA,LOCK,0)
    def test_vulnerability_is_hold(self):
        a=audit({"vendor/package":[{"advisoryId":"PKSA-EXAMPLE","severity":"high"}]})
        r=classify(a,SHA,LOCK,1)
        self.assertEqual(r["advisory_count"],1)
        self.assertEqual(r["result"],"HOLD_ADVISORIES_OR_ABANDONED")
    def test_abandoned_package_is_hold(self):
        r=classify(audit(abandoned={"vendor/package":"replacement/package"}),SHA,LOCK,2)
        self.assertEqual(r["abandoned_count"],1)
        self.assertEqual(r["result"],"HOLD_ADVISORIES_OR_ABANDONED")
    def test_no_audit_json_is_not_clean(self):
        with self.assertRaises(json.JSONDecodeError):
            classify(b"",SHA,LOCK,0)
    def test_null_advisories_are_not_clean(self):
        with self.assertRaisesRegex(ValueError,"HOLD_BAD_AUDIT_JSON"):
            classify(b'{"advisories":null}',SHA,LOCK,0)
    def test_zero_rc_with_advisory_is_denied(self):
        a=audit({"vendor/package":[{"advisoryId":"CVE-2026-0000"}]})
        with self.assertRaisesRegex(ValueError,"HOLD_ZERO_EXIT_BUT_ADVISORIES"):
            classify(a,SHA,LOCK,0)
    def test_failed_request_without_findings_is_denied(self):
        with self.assertRaisesRegex(ValueError,"HOLD_NONZERO_WITHOUT_EXPLAINED_FINDING"):
            classify(audit(),SHA,LOCK,1)
    def test_missing_advisory_id_is_denied(self):
        a=audit({"vendor/package":[{"severity":"critical"}]})
        with self.assertRaisesRegex(ValueError,"HOLD_MISSING_ADVISORY_ID"):
            classify(a,SHA,LOCK,1)
    def test_missing_lockfile_is_denied(self):
        with self.assertRaises(json.JSONDecodeError):
            classify(audit(),SHA,b"",0)
    def test_bogus_git_sha_is_denied(self):
        with self.assertRaisesRegex(ValueError,"HOLD_SOURCE_HEAD"):
            classify(audit(),"main",LOCK,0)
    def test_unknown_tool_exit_code_is_denied(self):
        with self.assertRaisesRegex(ValueError,"HOLD_AUDIT_TOOL_EXIT"):
            classify(audit(),SHA,LOCK,128)
    def test_advisory_list_type_must_match_contract(self):
        with self.assertRaisesRegex(ValueError,"HOLD_BAD_ADVISORY"):
            classify(audit({"vendor/package":{"CVE":"value"}}),SHA,LOCK,1)
if __name__=="__main__":
    unittest.main()
