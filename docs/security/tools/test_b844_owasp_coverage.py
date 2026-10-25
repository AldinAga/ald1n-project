#!/usr/bin/env python3
"""Synthetic-only regression checks for B844 initial OWASP coverage register."""
import unittest
from contextlib import ExitStack
from unittest.mock import patch

import build_b844_owasp_coverage as census

WSTG = """# Checklist
| Test ID | Test Name | Status | Notes |
|---|---|---|---|
| **WSTG-INFO** | **Information Gathering** | | |
| WSTG-INFO-01 | Test synthetic route | | |
"""
ASVS = """chapter_id,chapter_name,section_id,section_name,req_id,req_description,L
V1,Synthetic Encoding,V1.1,Inputs,V1.1.1,Synthetic requirement one must describe a requirement,1
V1,Synthetic Encoding,V1.1,Inputs,V1.1.2,Synthetic requirement two must describe a requirement,2
V17,Synthetic WebRTC,V17.1,Other,V17.1.1,Excluded level three,3
"""
TARGET = "a" * 40


class CensusTests(unittest.TestCase):
    def standards(self, wstg=WSTG, asvs=ASVS):
        stack = ExitStack()
        for key, value in {
            "WSTG_BLOB": census.git_blob_sha(wstg.encode()),
            "ASVS_BLOB": census.git_blob_sha(asvs.encode()),
            "WSTG_EXPECTED": 1,
            "ASVS_L1_EXPECTED": 1,
            "ASVS_L2_EXPECTED": 1,
            "ASVS_L3_EXPECTED": 1,
        }.items():
            stack.enter_context(patch.object(census, key, value))
        return stack

    def test_item_census_is_precisely_id_scoped_and_all_not_run(self):
        with self.standards():
            result = census.generate(WSTG.encode(), ASVS.encode(), TARGET)
        self.assertEqual(result["counts"]["total"], 3)
        self.assertEqual(result["counts"]["verified_security_pass"], 0)
        self.assertEqual(result["counts"]["unassessed"], 3)
        self.assertEqual([x["id"] for x in result["items"]],
            ["WSTG-INFO-01", "v5.0.0-1.1.1", "v5.0.0-1.1.2"])
        self.assertTrue(all(x["status"] == "NOT_RUN" for x in result["items"]))
        self.assertTrue(all(x["applicability"] == "UNASSESSED" for x in result["items"]))
        self.assertEqual(result["items"][0]["requirement_text"], "Test synthetic route")
        self.assertEqual(result["items"][1]["requirement_text"], "Synthetic requirement one must describe a requirement")
        self.assertEqual(result["items"][1]["title"], "Synthetic requirement one must describe a requirement")
        self.assertEqual(result["items"][1]["chapter_name"], "Synthetic Encoding")
        self.assertEqual(result["items"][1]["section_name"], "Inputs")
        self.assertNotIn("v5.0.0-17.1.1", [x["id"] for x in result["items"]])

    def test_very_short_or_empty_asvs_text_refuses_to_be_reviewed(self):
        for damaged in ["", "short", "Only a chapter", "x"+chr(1)*40]:
            bad=ASVS.replace("Synthetic requirement one must describe a requirement", damaged)
            with self.standards(asvs=bad):
                with self.assertRaisesRegex(ValueError, "HOLD_ASVS_MISSING_OR_MISMATCHED_REQUIREMENT_TEXT"):
                    census.generate(WSTG.encode(), bad.encode(), TARGET)

    def test_inconsistent_asvs_chapter_section_is_not_silently_accepted(self):
        bad=ASVS.replace("V1.1,Inputs,V1.1.1", "V2.2,Inputs,V1.1.1")
        with self.standards(asvs=bad):
            with self.assertRaisesRegex(ValueError, "HOLD_ASVS_MISSING_OR_MISMATCHED_REQUIREMENT_TEXT"):
                census.generate(WSTG.encode(), bad.encode(), TARGET)

    def test_wstg_headings_and_non_test_rows_are_not_counted(self):
        rows = census.wstg_items(WSTG)
        self.assertEqual(len(rows), 1)
        self.assertEqual(rows[0]["section"], "INFO")

    def test_unexpected_upstream_blob_is_hard_hold(self):
        with self.standards():
            with self.assertRaisesRegex(ValueError, "HOLD_WSTG42_UPSTREAM_BLOB_DRIFT"):
                census.generate((WSTG + "malicious change").encode(), ASVS.encode(), TARGET)
            with self.assertRaisesRegex(ValueError, "HOLD_ASVS500_UPSTREAM_BLOB_DRIFT"):
                census.generate(WSTG.encode(), (ASVS + "unexpected").encode(), TARGET)

    def test_duplicate_ids_cannot_silently_enter_the_census(self):
        row = "| WSTG-INFO-01 | Test synthetic route | | |\n"
        repeated = WSTG + row
        with self.standards(wstg=repeated), patch.object(census, "WSTG_EXPECTED", 2):
            with self.assertRaisesRegex(ValueError, "HOLD_DUPLICATE_SECURITY_REQUIREMENT_ID"):
                census.generate(repeated.encode(), ASVS.encode(), TARGET)

    def test_invalid_commit_and_unexpected_count_are_holds(self):
        with self.standards():
            with self.assertRaisesRegex(ValueError, "HOLD_INVALID_TARGET_COMMIT"):
                census.generate(WSTG.encode(), ASVS.encode(), "main")
            with patch.object(census, "WSTG_EXPECTED", 2):
                with self.assertRaisesRegex(ValueError, "HOLD_UPSTREAM_CHECKLIST_CENSUS_MISMATCH"):
                    census.generate(WSTG.encode(), ASVS.encode(), TARGET)

    def test_malformed_or_unknown_asvs_level_fails_closed(self):
        with self.assertRaisesRegex(ValueError, "HOLD_ASVS_UNEXPECTED_LEVEL"):
            census.asvs_l2_items(ASVS.replace(",3\n", ",4\n"))
        with self.assertRaisesRegex(ValueError, "HOLD_ASVS_MALFORMED_ID"):
            census.asvs_l2_items(ASVS.replace("V1.1.1,", "BROKEN,"))


if __name__ == "__main__":
    unittest.main()
