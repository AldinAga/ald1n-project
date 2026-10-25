#!/usr/bin/env python3
"""B856 fail-closed synthetic route contract checks, no network."""
import json
import unittest
from b854_route_surface import CONTRACT,inventory

SHA="a"*40
def expected_rows():
    rows=[]
    for name,(methods,uri,handler,required) in CONTRACT.items():
        mids=["web",required]
        if name.startswith("public-shop.register"):
            mids.append("guest")
        rows.append({
            "name":name,
            "uri":uri,
            "action":"App\\Http\\Controllers\\"+handler,
            "method":methods,
            "middleware":mids,
        })
    rows.append({"name":"admin.settings","uri":"admin/settings","method":"GET|HEAD",
                 "action":"App\\Http\\Controllers\\SettingsController@index",
                 "middleware":["web","auth","active","tracked-session","permission:settings.manage"]})
    return rows

def compile_rows(rows):
    return json.dumps(rows,sort_keys=True).encode()

class RouteInventoryTest(unittest.TestCase):
    def test_valid_static_inventory_not_owasp_or_release_pass(self):
        result=inventory(compile_rows(expected_rows()),SHA)
        self.assertEqual(result["public_contract_total"],14)
        self.assertEqual(result["source_routes_total"],15)
        self.assertEqual(result["verified_owasp_security_pass"],0)
        self.assertEqual(result["release"],"HOLD")
    def test_missing_expected_public_route_denied(self):
        rows=expected_rows()
        rows.pop(0)
        with self.assertRaisesRegex(ValueError,"HOLD_MISSING_PUBLIC_ROUTE"):
            inventory(compile_rows(rows),SHA)
    def test_extra_public_endpoint_denied(self):
        rows=expected_rows()
        rows.append({"name":"public-shop.debug","uri":"katalog/debug","method":"GET|HEAD",
                     "action":"App\\Http\\Controllers\\DebugController@index","middleware":["web"]})
        with self.assertRaisesRegex(ValueError,"HOLD_UNREVIEWED_NEW_PUBLIC_ENDPOINT"):
            inventory(compile_rows(rows),SHA)
    def test_wrong_route_method_denied(self):
        rows=expected_rows()
        rows[0]["method"]="POST"
        with self.assertRaisesRegex(ValueError,"HOLD_UNEXPECTED_PUBLIC_METHOD_OR_URI"):
            inventory(compile_rows(rows),SHA)
    def test_wrong_route_uri_denied(self):
        rows=expected_rows()
        rows[0]["uri"]="katalog/changed"
        with self.assertRaisesRegex(ValueError,"HOLD_UNEXPECTED_PUBLIC_METHOD_OR_URI"):
            inventory(compile_rows(rows),SHA)
    def test_wrong_controller_denied(self):
        rows=expected_rows()
        rows[0]["action"]="App\\Http\\Controllers\\WrongController@catalog"
        with self.assertRaisesRegex(ValueError,"HOLD_UNEXPECTED_CONTROLLER"):
            inventory(compile_rows(rows),SHA)
    def test_resolved_laravel_middleware_class_names_accepted(self):
        rows=expected_rows()
        for route in rows:
            route["middleware"]=[
                item.replace("throttle:","Illuminate\\Routing\\Middleware\\ThrottleRequests:",1)
                    if item.startswith("throttle:") else
                "Illuminate\\Auth\\Middleware\\RedirectIfAuthenticated"
                    if item=="guest" else item
                for item in route["middleware"]
            ]
        result=inventory(compile_rows(rows),SHA)
        self.assertEqual(result["public_contract_total"],14)
        self.assertEqual(result["verified_owasp_security_pass"],0)
    def test_spoofed_throttle_class_name_denied(self):
        rows=expected_rows()
        rows[0]["middleware"]=["web","App\\Fake\\ThrottleRequests:public-shop-browse"]
        with self.assertRaisesRegex(ValueError,"HOLD_PUBLIC_ROUTE_MIDDLEWARE"):
            inventory(compile_rows(rows),SHA)
    def test_throttle_removal_denied(self):
        rows=expected_rows()
        rows[0]["middleware"]=["web"]
        with self.assertRaisesRegex(ValueError,"HOLD_PUBLIC_ROUTE_MIDDLEWARE"):
            inventory(compile_rows(rows),SHA)
    def test_guest_middleware_removal_denied(self):
        rows=expected_rows()
        row=next(r for r in rows if r["name"]=="public-shop.register.submit")
        row["middleware"].remove("guest")
        with self.assertRaisesRegex(ValueError,"HOLD_SIGNUP_GUEST_BOUNDARY"):
            inventory(compile_rows(rows),SHA)
    def test_duplicate_public_route_denied(self):
        rows=expected_rows()
        rows.append(dict(rows[0]))
        with self.assertRaisesRegex(ValueError,"HOLD_DUPLICATE_EXPECTED_ROUTE"):
            inventory(compile_rows(rows),SHA)
    def test_bad_or_unknown_sha_denied(self):
        with self.assertRaisesRegex(ValueError,"HOLD_UNPINNED_HEAD"):
            inventory(compile_rows(expected_rows()),"main")
    def test_unparseable_json_denied(self):
        with self.assertRaises(json.JSONDecodeError):
            inventory(b"{invalid",SHA)
    def test_string_middleware_denied(self):
        rows=expected_rows()
        rows[0]["middleware"]="web,throttle"
        with self.assertRaisesRegex(ValueError,"HOLD_MALFORMED_ROUTE"):
            inventory(compile_rows(rows),SHA)
    def test_unknown_route_method_denied(self):
        rows=expected_rows()
        rows[-1]["method"]="DELETE|EVIL METHOD"
        with self.assertRaisesRegex(ValueError,"HOLD_BAD_METHOD"):
            inventory(compile_rows(rows),SHA)
if __name__=="__main__":
    unittest.main()
