#!/usr/bin/env python3
"""B849 scoped synthetic JUnit admission, not an OWASP certification."""
import argparse
import hashlib
import json
import re
import xml.etree.ElementTree as ET
from pathlib import Path

EXPECTED = {
    "test_media_serves_real_png_with_nosniff_header",
    "test_media_rejects_unlisted_storage_disk_without_fallback",
    "test_media_rejects_parent_directory_segments_before_realpath",
    "test_media_rejects_cross_product_directory_even_when_file_exists",
    "test_media_rejects_symlink_to_file_outside_product_directory",
    "test_media_rejects_php_payload_with_forged_png_metadata",
    "test_media_rejects_inactive_and_unpriced_product",
    "test_emergency_off_closes_existing_media_capability",
    "test_sql_boolean_injection_is_treated_as_plain_search_text",
    "test_like_percent_is_escaped_as_literal_character",
    "test_like_underscore_is_escaped_as_literal_character",
    "test_unlisted_sort_and_stock_values_are_validation_errors",
    "test_brand_category_identifier_injection_is_rejected",
    "test_array_and_oversized_search_params_are_rejected",
    "test_hidden_and_unpriced_rows_never_enter_public_search_builder",
}

def admit(path: Path, source_sha: str) -> dict:
    if re.fullmatch("[a-f0-9]{40}", source_sha) is None:
        raise ValueError("HOLD_SOURCE_SHA")
    data=path.read_bytes()
    root=ET.fromstring(data)
    if root.tag not in {"testsuite","testsuites"}:
        raise ValueError("HOLD_BAD_JUNIT_ROOT")
    cases=list(root.iter("testcase"))
    names=[str(case.get("name") or "") for case in cases]
    if len(cases)!=15 or len(set(names))!=len(cases) or set(names)!=EXPECTED:
        raise ValueError("HOLD_CASE_IDENTITY")
    if any("PublicCatalogSurfaceSecurityB847Test" not in str(
            case.get("class") or case.get("classname") or "") for case in cases):
        raise ValueError("HOLD_TEST_CLASS")
    if any(any(e.tag in {"error","failure","skipped"} for e in case.iter() if e is not case)
            for case in cases):
        raise ValueError("HOLD_FAILED_SKIPPED_CASE")
    for suite in root.iter():
        if suite.tag not in {"testsuite","testsuites"}:
            continue
        for field in ("errors","failures","skipped"):
            if suite.get(field) is not None and int(suite.get(field))!=0:
                raise ValueError("HOLD_JUNIT_DECLARED_"+field.upper())
        if suite.get("tests") is not None and int(suite.get("tests"))!=15:
            raise ValueError("HOLD_JUNIT_DECLARED_COUNT")
    return {
        "source_sha":source_sha,
        "scope":"ISOLATED_SYNTHETIC_PHP84_MARIADB101119_HTTP",
        "scoped_tests":15,
        "scoped_test_identities":sorted(names),
        "junit_sha256":hashlib.sha256(data).hexdigest(),
        "workstreams_tested_in_part":["SEC04","SEC11","SEC13","SEC22"],
        "owasp_items_applicability":"UNASSESSED",
        "owasp_verified_security_pass":0,
        "independent_penetration_test":"NOT_RUN",
        "global_release":"HOLD",
        "production_deployed":False,
    }

def main() -> None:
    parser=argparse.ArgumentParser()
    parser.add_argument("--junit",type=Path,required=True)
    parser.add_argument("--source-sha",required=True)
    parser.add_argument("--output",type=Path,required=True)
    args=parser.parse_args()
    result=admit(args.junit,args.source_sha)
    args.output.write_text(json.dumps(result,indent=2)+"\n",encoding="utf-8")
    print("B849_SCOPED_CATALOG_SECURITY_15_CASES_PASS_ONLY_GLOBAL_RELEASE_HOLD")

if __name__=="__main__":
    main()
