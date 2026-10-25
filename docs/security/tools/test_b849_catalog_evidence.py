import tempfile
import unittest
import xml.etree.ElementTree as ET
from pathlib import Path
from b849_catalog_evidence import EXPECTED,admit

CLASS = "Tests\\Feature\\PublicCatalogSurfaceSecurityB847Test"
class CatalogEvidenceTest(unittest.TestCase):
    def setUp(self):
        tmp=tempfile.TemporaryDirectory()
        self.addCleanup(tmp.cleanup)
        self.path=Path(tmp.name)/"junit.xml"
        self.make()
    def make(self,names=None,anomaly=None,count=None):
        names=sorted(EXPECTED) if names is None else names
        root=ET.Element("testsuites")
        suite=ET.SubElement(root,"testsuite",tests=str(len(names) if count is None else count),
            failures="0",errors="0",skipped="0")
        for i,name in enumerate(names):
            case=ET.SubElement(suite,"testcase",name=name,classname=CLASS)
            if i==0 and anomaly:
                ET.SubElement(case,anomaly)
        self.path.write_bytes(ET.tostring(root))
    def test_scoped_success_is_not_owasp_or_release(self):
        result=admit(self.path,"a"*40)
        self.assertEqual(result["scoped_tests"],15)
        self.assertEqual(result["owasp_verified_security_pass"],0)
        self.assertEqual(result["global_release"],"HOLD")
    def test_failed_case_denied(self):
        self.make(anomaly="failure")
        with self.assertRaises(ValueError): admit(self.path,"a"*40)
    def test_skipped_case_denied(self):
        self.make(anomaly="skipped")
        with self.assertRaises(ValueError): admit(self.path,"a"*40)
    def test_error_case_denied(self):
        self.make(anomaly="error")
        with self.assertRaises(ValueError): admit(self.path,"a"*40)
    def test_missing_case_denied(self):
        self.make(names=sorted(EXPECTED)[:-1])
        with self.assertRaises(ValueError): admit(self.path,"a"*40)
    def test_duplicate_case_denied(self):
        n=sorted(EXPECTED)
        self.make(names=n[:-1]+[n[0]])
        with self.assertRaises(ValueError): admit(self.path,"a"*40)
    def test_fake_suite_count_denied(self):
        self.make(count=16)
        with self.assertRaises(ValueError): admit(self.path,"a"*40)
    def test_nonimmutable_sha_denied(self):
        with self.assertRaises(ValueError): admit(self.path,"HEAD")
    def test_malformed_xml_denied(self):
        self.path.write_text("<testsuites><testsuite")
        with self.assertRaises(ET.ParseError): admit(self.path,"a"*40)
    def test_unrelated_test_class_denied(self):
        self.path.write_bytes(self.path.read_bytes().replace(CLASS.encode(),b"Fake\\UnrelatedTest"))
        with self.assertRaises(ValueError): admit(self.path,"a"*40)
if __name__=="__main__":
    unittest.main()
