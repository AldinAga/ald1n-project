#!/usr/bin/env python3
"""B873 negative controls — no test modifications or production access."""
import unittest
from b873_public_blade_csp_inventory import FILES,scan

def base():
    v={x:"<section>synthetic public content</section>\n" for x in FILES}
    v["layout.blade.php"]="<link rel=\"stylesheet\" href=\"{{ asset('assets/css/ald1n-public-storefront.css') }}\">\n@stack('head')"
    return v

CSS="body { color: #fff; background: #000; max-width: 1200px; margin: auto; display: block; padding: 1rem; }"

class NoScriptInventoryTest(unittest.TestCase):
    def test_six_expected_views_and_local_css_pass_source_only(self):
        z=scan(base(),CSS)
        self.assertEqual(z["source_views"],6)
        self.assertEqual(z["complete_owasp_item_passes"],0)
        self.assertEqual(z["global_release"],"HOLD")
        self.assertEqual(z["runtime_browser_check"],"REQUIRED")
    def test_dropped_page_is_hard_hold(self):
        v=base();v.pop("checkout.blade.php")
        with self.assertRaisesRegex(ValueError,"HOLD_B873_MISSING"):
            scan(v,CSS)
    def test_extra_unassessed_page_is_hard_hold(self):
        v=base();v["other.blade.php"]="x"
        with self.assertRaisesRegex(ValueError,"HOLD_B873_MISSING"):
            scan(v,CSS)
    def test_inline_script_is_hold(self):
        v=base();v["order.blade.php"]="<script>alert('untrusted')</script>"
        with self.assertRaisesRegex(ValueError,"HOLD_B873_UNSAFE_VIEW"):
            scan(v,CSS)
    def test_injected_blade_head_push_is_hold(self):
        v=base();v["catalog.blade.php"]="@push('head') <script src='//bad'></script> @endpush"
        with self.assertRaisesRegex(ValueError,"HOLD_B873_UNSAFE_VIEW"):
            scan(v,CSS)
    def test_inline_event_and_style_are_hold(self):
        for example in ["<img src='x' onerror='alert(1)'>",
                        "<span style='display:none'>Hidden</span>",
                        "<form action='javascript:alert(1)'>oops</form>",
                        "<iframe src='about:blank'></iframe>",
                        "<style>body{display:none}</style>"]:
            v=base();v["cart.blade.php"]=example
            with self.subTest(example=example):
                with self.assertRaisesRegex(ValueError,"HOLD_B873_UNSAFE_VIEW"):
                    scan(v,CSS)
    def test_third_party_stylesheet_not_silently_allowed(self):
        v=base()
        v["layout.blade.php"]="<link href='https://evil.test/not-local.css'>"
        with self.assertRaisesRegex(ValueError,"HOLD_B873_NONLOCAL_STYLESHEET"):
            scan(v,CSS)
    def test_css_external_import_and_cdn_url_are_hold(self):
        for bad in ["@import url('https://evil.test/a.css'); "+CSS,
                    "body{background:url(https://evil.test/a.png)} "+CSS]:
            with self.assertRaisesRegex(ValueError,"HOLD_B873_EXTERNAL_OR_EMPTY_CSS"):
                scan(base(),bad)
if __name__=="__main__":
    unittest.main()
