#!/usr/bin/env python3
"""B873: fail-closed source inventory for exactly five scriptless public HTML views.

Static evidence complements, but does not replace, Chromium CSP enforcement.
This script never sends HTTP, edits PHP, or marks an OWASP item PASS.
"""
from __future__ import annotations
import argparse
import hashlib
import json
import re
from pathlib import Path

FILES=(
    "layout.blade.php",
    "catalog.blade.php",
    "show.blade.php",
    "cart.blade.php",
    "checkout.blade.php",
    "order.blade.php",
)
PATTERNS={
    "script_element":re.compile(r"<\s*/?\s*script\b",re.I),
    "iframe_element":re.compile(r"<\s*/?\s*iframe\b",re.I),
    "style_element":re.compile(r"<\s*/?\s*style\b",re.I),
    "inline_handler":re.compile(r"\bon[a-z]{3,}\s*=",re.I),
    "inline_style":re.compile(r"\bstyle\s*=",re.I),
    "javascript_uri":re.compile(r"javascript\s*:",re.I),
    "blade_push_head":re.compile(r"@push\s*\(\s*['\"]head['\"]\s*\)",re.I),
    "blade_inline_script_stack":re.compile(r"@(?:push|stack)\s*\(\s*['\"]scripts['\"]\s*\)",re.I),
}
CSS_IMPORT=re.compile(r"@import\b|url\s*\(\s*['\"]?https?://",re.I)
EXPECTED_CSS="ald1n-public-storefront.css"

def scan(views:dict[str,str],css:str)->dict:
    if set(views)!=set(FILES):
        raise ValueError("HOLD_B873_MISSING_OR_EXTRA_VIEW")
    hashes={}
    for name in FILES:
        data=views[name]
        if not isinstance(data,str) or not data.strip():
            raise ValueError("HOLD_B873_EMPTY_VIEW")
        for key,pattern in PATTERNS.items():
            # The shared template may expose the standard @stack('head')
            # placeholder, but only non-pushed child templates are allowed.
            if pattern.search(data):
                raise ValueError(f"HOLD_B873_UNSAFE_VIEW_{name}_{key}")
        hashes[name]=hashlib.sha256(data.encode()).hexdigest()
    if "asset('assets/css/"+EXPECTED_CSS+"')" not in views["layout.blade.php"]:
        raise ValueError("HOLD_B873_NONLOCAL_STYLESHEET")
    if not isinstance(css,str) or len(css)<100 or CSS_IMPORT.search(css):
        raise ValueError("HOLD_B873_EXTERNAL_OR_EMPTY_CSS")
    return {"result":"PASS_SOURCE_INVENTORY_ONLY",
            "source_views":len(FILES),"views_sha256":hashes,
            "css_sha256":hashlib.sha256(css.encode()).hexdigest(),
            "runtime_browser_check":"REQUIRED",
            "whole_asvs_v3_4_3":"NOT_PROVEN_CMS_AND_REGISTRATION_EXCLUDED",
            "complete_owasp_item_passes":0,
            "global_release":"HOLD"}

def main()->None:
    p=argparse.ArgumentParser()
    p.add_argument("--views",type=Path,required=True)
    p.add_argument("--css",type=Path,required=True)
    p.add_argument("--out",type=Path,required=True)
    a=p.parse_args()
    sources={n:(a.views/n).read_text(encoding="utf-8") for n in FILES}
    receipt=scan(sources,a.css.read_text(encoding="utf-8"))
    a.out.write_text(json.dumps(receipt,indent=2,sort_keys=True)+"\n",encoding="utf-8")
    print("B873_SIX_SCRIPTLESS_VIEW_SOURCES=PASS_SOURCE_ONLY")
    print("B873_ASVS_3_4_3_WHOLE=HOLD_OTHER_SURFACES_NOT_TESTED")
if __name__=="__main__":
    main()
