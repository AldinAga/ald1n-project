# B825 — Exact JUnit root cause verified

2026-10-24. PR98 test-only branch, source `df9c0b363b19b996730513853b531f5d811cf2f3`, compared against PR93 exact source. Final B803 CI run 40125965473: baseline 451 tests/44 problems, candidate 535 tests/92 problems; 50 new failing identities (49 previously catalogued plus B821 header test). The 10 latest distinct workflows finished 5 PASS / 5 FAIL.

**New finding verified from original candidate JUnit and test log:** B821 `test_live_receipt_response_rejects_shared_caches_and_referrers` expects `Cache-Control: private, no-store, max-age=0` in a fixed textual order. Actual response is `Cache-Control: max-age=0, no-store, private`. All three directives are present, only ordering differs. This is a strict string-order test mismatch, **not evidence of missing no-store/private**. The first B821 invalid-token case passes (JUnit: 10 assertions); second B821 case fails after four assertions at the Cache-Control comparison, so its later headers have not been certified by that case.

Original full run JUnit/log/manifest ZIP downloaded and archived to Google Drive: https://drive.google.com/file/d/1KWEm8RBsxln0S4KIBl-kwT5ewajlPcyO/view?usp=drivesdk. The SHA of the GitHub Actions artifact is sha256:c626b4b39cc914dda18977f64415ca6f8d8bcdb6d881d988666d5de85d964a58.

Previously attempted edits of related tests were safety-denied; no attempt is made to bypass that restriction, skip assertions, or declare the test PASS. Full suite and security review remain HOLD. No production, SSH, merge or deployment. Production B798 public storefront OFF, PR93 Draft, PR98 Draft. Accepted functional ledger 7/17=41.2%, CMS 7/15=46.7%, UI6/6.
