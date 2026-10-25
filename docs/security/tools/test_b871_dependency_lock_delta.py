#!/usr/bin/env python3
"""B871 negative-only source evidence gate regression tests."""
import unittest
import xml.etree.ElementTree as ET
from b871_dependency_lock_delta import PREPATCH,compare

AFTER="f"*40
OLD_LOCK="a"*64
NEW_LOCK="b"*64

def junit(failed=(),warned=(),skipped=(),remove=(),duplicate=False):
    r=ET.Element("testsuites")
    suite=ET.SubElement(r,"testsuite",tests="585")
    names=["test_"+str(i) for i in range(585)]
    for name in names:
        if name in remove: continue
        t=ET.SubElement(suite,"testcase",name=name,classname="Synthetic")
        if name in failed: ET.SubElement(t,"failure",type="synthetic")
        if name in warned: ET.SubElement(t,"warning",type="synthetic")
        if name in skipped: ET.SubElement(t,"skipped")
    if duplicate:
        ET.SubElement(suite,"testcase",name="test_0",classname="Synthetic")
    return ET.tostring(r)

def run(a=None,b=None,base=PREPATCH,head=AFTER,old=OLD_LOCK,new=NEW_LOCK):
    return compare(junit() if a is None else a,
                   junit() if b is None else b,base,head,old,new)

class DeltaTest(unittest.TestCase):
    def test_red_before_and_red_after_identical_is_scoped_only(self):
        a=junit(failed=("test_0","test_1"))
        result=run(a,a)
        self.assertEqual(result["result"],"PASS_DEPENDENCY_ONLY_NO_NEW_FAILURE_IDENTITIES")
        self.assertFalse(result["global_suite_green"])
        self.assertEqual(result["global_release"],"HOLD")
        self.assertEqual(result["new_failure_count"],0)
    def test_new_failure_is_release_hold(self):
        r=run(junit(failed=("test_0",)),junit(failed=("test_0","test_4")))
        self.assertEqual(r["result"],"HOLD_NEW_DEPENDENCY_REGRESSION")
        self.assertEqual(r["new_failures"],["Synthetic::test_4"])
    def test_new_error_is_same_as_failure(self):
        a=junit()
        b=a.replace(b'<testcase name="test_4" classname="Synthetic" />',
                   b'<testcase name="test_4" classname="Synthetic"><error/></testcase>')
        r=run(a,b)
        self.assertEqual(r["new_failure_count"],1)
    def test_fixed_previous_case_accepted_but_not_release(self):
        a=junit(failed=("test_4",))
        r=run(a,junit())
        self.assertEqual(r["fixed_count"],1)
        self.assertEqual(r["global_release"],"HOLD")
    def test_new_warning_is_hold(self):
        r=run(junit(),junit(warned=("test_9",)))
        self.assertEqual(r["new_warning_count"],1)
        self.assertEqual(r["result"],"HOLD_NEW_DEPENDENCY_REGRESSION")
    def test_old_existing_warning_does_not_claim_release(self):
        r=run(junit(warned=("test_9",)),junit(warned=("test_9",)))
        self.assertEqual(r["result"],"PASS_DEPENDENCY_ONLY_NO_NEW_FAILURE_IDENTITIES")
        self.assertEqual(r["global_release"],"HOLD")
    def test_skipped_test_is_hard_failure(self):
        with self.assertRaisesRegex(ValueError,"HOLD_SKIPPED"):
            run(junit(),junit(skipped=("test_0",)))
    def test_dropped_test_is_hard_failure(self):
        with self.assertRaisesRegex(ValueError,"HOLD_EXPECTED_585_CASES"):
            run(junit(),junit(remove=("test_0",)))
    def test_duplicated_test_is_hard_failure(self):
        with self.assertRaisesRegex(ValueError,"HOLD_DUPLICATE_CASE_IDENTITY"):
            run(junit(),junit(duplicate=True))
    def test_invalid_commit_source_is_hard_failure(self):
        with self.assertRaisesRegex(ValueError,"HOLD_BAD_SOURCE_SHA"):
            run(base="main")
        with self.assertRaisesRegex(ValueError,"HOLD_SOURCE_PROVENANCE_MISMATCH"):
            run(base=AFTER)
    def test_unchanged_lock_is_hard_failure(self):
        with self.assertRaisesRegex(ValueError,"HOLD_UNCHANGED_LOCKFILE"):
            run(new=OLD_LOCK)
    def test_malformed_xml_fails_closed(self):
        with self.assertRaises(ET.ParseError):
            run(b=b"<testsuites>")
    def test_wrong_root_fails_closed(self):
        with self.assertRaisesRegex(ValueError,"HOLD_JUNIT_ROOT"):
            run(b=b"<bad/>")
if __name__=="__main__":
    unittest.main()
