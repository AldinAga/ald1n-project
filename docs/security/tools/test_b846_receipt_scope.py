import tempfile
import unittest
from pathlib import Path
from xml.etree import ElementTree as ET
from b846_receipt_scope import REQUIRED,build

class ScopeTest(unittest.TestCase):
    def setUp(self):
        self.temp=tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.a=Path(self.temp.name)/"a.xml"
        self.b=Path(self.temp.name)/"b.xml"
        self.names=sorted(REQUIRED)+["test_old_"+str(i) for i in range(5)]
        self.write(self.a,self.names)
        self.write(self.b,["test_boundary_"+str(i) for i in range(15)])
    def write(self,path,names,error=None):
        root=ET.Element("testsuite")
        for i,name in enumerate(names):
            case=ET.SubElement(root,"testcase",name=name,classname="Synthetic")
            if i==0 and error: ET.SubElement(case,error)
        path.write_bytes(ET.tostring(root))
    def test_valid_is_never_global_pass(self):
        r=build(self.a,self.b,"a"*40)
        self.assertEqual(r["tests"]["combined"],26)
        self.assertEqual(r["verified_security_pass"],0)
        self.assertEqual(r["release"],"HOLD")
    def test_failure_denied(self):
        self.write(self.a,self.names,"failure")
        with self.assertRaises(ValueError): build(self.a,self.b,"a"*40)
    def test_skipped_denied(self):
        self.write(self.a,self.names,"skipped")
        with self.assertRaises(ValueError): build(self.a,self.b,"a"*40)
    def test_error_denied(self):
        self.write(self.a,self.names,"error")
        with self.assertRaises(ValueError): build(self.a,self.b,"a"*40)
    def test_missing_negative_case_denied(self):
        self.write(self.a,self.names[1:]+["test_other"])
        with self.assertRaises(ValueError): build(self.a,self.b,"a"*40)
    def test_duplicate_denied(self):
        self.write(self.a,self.names[:-1]+[self.names[0]])
        with self.assertRaises(ValueError): build(self.a,self.b,"a"*40)
    def test_count_drift_denied(self):
        self.write(self.b,["test_boundary_0"])
        with self.assertRaises(ValueError): build(self.a,self.b,"a"*40)
    def test_unpinned_source_denied(self):
        with self.assertRaises(ValueError): build(self.a,self.b,"main")
    def test_malformed_xml_denied(self):
        self.a.write_text("<testsuite><testcase")
        with self.assertRaises(ET.ParseError): build(self.a,self.b,"a"*40)
    def test_missing_junit_denied(self):
        self.b.unlink()
        with self.assertRaises(FileNotFoundError): build(self.a,self.b,"a"*40)
if __name__=="__main__":
    unittest.main()
