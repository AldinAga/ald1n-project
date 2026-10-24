# Report826 — B816 sensitive auth response privacy RED / GREEN (source-only)

DATE=2026-10-24
BATCH=B816
SCOPE=AUTH_TOKEN_REFERRER_CACHE_NO_INDEX
BRANCH=feat/b799-public-retail-catalog-cart-checkout
PR=https://github.com/AldinAga/ald1n-project/pull/93
PR_EXACT_HEAD=fa8a1892dc9bc925c524a6184a660fd16d0bc5a8
RED_COMMIT=d8647211ae1bebbdb36757f7436ef0c363d385a5
GREEN_COMMIT=5e31333e9e2c7d48366b890e2e2ff3e88b969123
RED_RUN=https://github.com/AldinAga/ald1n-project/actions/runs/40110116447
GREEN_RUN=https://github.com/AldinAga/ald1n-project/actions/runs/40110368273
B816_RED=8_TESTS_17_ASSERTIONS_7_FAILURES
B816_GREEN=8_TESTS_51_ASSERTIONS_PASS
FINAL_SCOPED_CI=https://github.com/AldinAga/ald1n-project/actions/runs/40111258792
FINAL_FULL_CI=https://github.com/AldinAga/ald1n-project/actions/runs/40111258786
EXACT_HEAD_CI=9_PASS_5_FAIL_OF_14
B803_BASELINE=451_CASES_44_PROBLEMS
B803_CANDIDATE=522_CASES_91_PROBLEMS
B803_NEW_FAILURE_IDENTITIES=49
B803_HISTORIC_GUEST_FAILURE_IDENTITIES=48
B803_B815_SAFETY_BLOCKED_FIXTURE_FAILURE_IDENTITIES=1
PRODUCTION_MUTATION=NONE
STORE_B798=OFF
MERGE=HOLD_DRAFT_PR93
SSH_REQUIRED=NO
PROGRAM_ACCEPTED=7/17_41.2_PERCENT
CMS_ACCEPTED=7/15_46.7_PERCENT

## Preflight

Read fresh GitHub main 76056d746d441100672362dd184e94e479544001, root AGENTS.md, 000-LATEST.md, Report825 and PR93 exact head b392583714b522bab6fa1fc4af042a9510787f92. Also fetched the canonical Google Drive runbook and Drive Report825. B815 scoped portal session 5/61 was PASS but the full comparator had 49 new failures, including a safety-DENIED attempted fixture edit. No attempt was made to modify, retry, delete, skip, relabel, or otherwise evade that denial or any prior B805/B809 restrictions.

## Source security finding and fix

Previous global `SecurityHeaders` used `Referrer-Policy: same-origin` for password reset, account activation, Google OAuth callback and public buyer registration. Token-bearing URL paths could be transmitted to another same-origin surface as a Referrer. Some auth/registration/OAuth responses also lacked uniform explicit private, no-store, noindex protection. This is a verified HTTP header/privacy gap in isolated source, NOT observed real-world token leakage or a production exploit.

Added independent `tests/Feature/SensitiveAuthResponsePrivacyB816Test.php` without changing old guest assumptions or blocked B815 fixture. Six data-driven GET scenarios cover login, forgot/reset token, account activation token, public buyer signup, Google OAuth callback with intentionally invalid local state. Another POST scenario checks invalid registration and no pending user/token creation, and a negative public-catalog test ensures ordinary browsing retains its previous same-origin referrer and caching eligibility.

The exact RED source `d8647211ae1bebbdb36757f7436ef0c363d385a5` showed 8 tests/17 assertions/**7 failing privacy headers**; the public catalog negative control passed. Fixed only `apps/cms/current/app/Http/Middleware/SecurityHeaders.php`: auth `login`, `login.store`, `password.*`, `customer-activation.*`, `public-shop.register`, `public-shop.register.submit`, and `auth.google.*` now consistently return `Referrer-Policy: no-referrer`, `Cache-Control: private, no-store, max-age=0`, `Pragma: no-cache`, `X-Robots-Tag: noindex, nofollow, noarchive`. Existing admin settings no-store, storefront cart/checkout headers, bearer receipt no-referrer/no-store, CSP, nosniff, HSTS, permissions, clickjacking guards all preserved.

On corrected head `5e31333e9e2c7d48366b890e2e2ff3e88b969123` the new PHP8.4/MariaDB10.11.19 scoped test **8/8 PASS, 51 assertions**; B809+B810 12/160, B811 4/91, B813 4/29, B814 4/25, B815 5/61 remained PASS in same security CI. The original B799–B808 guest-positive security suite is still enabled and FAILS separately. Full B803 run https://github.com/AldinAga/ald1n-project/actions/runs/40110368286 was pending during this first report write: never infer a complete PASS from scoped GREEN.

## WSTG / ASVS and remaining gates

[Security traceability](../security/STOREFRONT-B816-AUTH-RESPONSE-PRIVACY.md) maps partial evidence to WSTG4.2 WSTG-ATHN-06, WSTG-SESS-04, ASVS5.0.0 cumulative L2 V3.4.x and SEC07/SEC22/SEC23. The original 368-row B808 census remains NOT_RUN / REVIEW_REQUIRED pending complete item-level applicability and testing. Targeted headers are not a full penetration-test certification.

No external OAuth token exchange, real passwords, production accounts, SMTP, payment, migration, real customer browsing, production cache/CDN or hosting FPM/OPcache was touched. PR93 Draft, production B798 OFF, no SSH, OTA/EAS/Play or inode cleanup. Remain blocked: B815 new OFF test's denied fixture correction, 48 historical guest-assumption test identities, WSTG/ASVS census, legal/payment/shipping approvals, authentic mail deliverability/SPF/DKIM/DMARC, backup restore, FPM/OPcache and owner UAT.

## Drive records (verified)

- [B816 RED MariaDB JUnit/source evidence ZIP](https://drive.google.com/file/d/1BYbWFZ7Lla173FvTUf2xmUemzc5xI7OI/view)
- [B816 GREEN MariaDB JUnit/source evidence ZIP](https://drive.google.com/file/d/11msZ-9TAxS4QqAEElnUgKpVXtRcl2hhi/view)
- [Exact B816 PHP/test/workflow/security traceability source snapshot](https://docs.google.com/document/d/1nUhggcy6_bv82VQd_UTZPwO2cFsrcMWCrx1OBirxf2U/edit)

Functional accepted milestones unchanged: program7/17=41.2%, CMS7/15=46.7%, initial UI6/6=100%. B816 source remediation is separate from production acceptance.

## Stronger existing security regression — final commit integration

The first B816 corrected runtime head 5e31333e9e2c7d48366b890e2e2ff3e88b969123 had a targeted 8/51 GREEN but exact-head B803 candidate522/92 problems and 50 new failure identities. Forty-eight were the old guest-positive fixtures, one was the pre-existing B815 safety-denied buyer OFF fixture, and one was `SecurityHealthBackupTest::test_security_headers_and_request_id_are_present`: it expected a weaker `same-origin` header at login. The existing security test was STRENGTHENED, not removed or waived, to require the newly approved `no-referrer`, `private, no-store` and `noindex` headers while retaining the existing nosniff, frame-policy and request-ID assertions. Final corrected PR93 source is `aafcba888807ab13d0c12109d423c27734f60741`; B816 scoped [CI](https://github.com/AldinAga/ald1n-project/actions/runs/40110799346) remained PASS. The final exact-head B803 comparison [run40110799327](https://github.com/AldinAga/ald1n-project/actions/runs/40110799327) is the authoritative global release gate; do not infer outcome from the prior run.

[Final B816 exact-source snapshot on Drive](https://docs.google.com/document/d/1oSk2nVphbJo5nTTLY-SdbziBbkytE38L6gmxIljh4Y0/edit) and [final-target GREEN ZIP on Drive](https://drive.google.com/file/d/1_1fL9TCjPi1aSC2HWwicHZtVDbEyBgcl/view) were uploaded and read back. Previous RED and initial GREEN archives preserved. Prior safety-denied B815 buyer fixture remains unchanged/unwaived; production B798 OFF.

## Final B816 exact-source CI and archival proof — 2026-10-24

**FINAL SOURCE `fa8a1892dc9bc925c524a6184a660fd16d0bc5a8`, Draft PR93, no merge/deploy.** After the first stronger login test update, B803 detected one extra failure due to Symfony normalizing `Cache-Control` directive order. `SecurityHealthBackupTest::test_security_headers_and_request_id_are_present` now requires all three exact semantic directives (`private`, `no-store`, `max-age=0`) independently, plus `no-referrer`, `noindex`, `nosniff`, `SAMEORIGIN` and request ID. This is stricter than the prior baseline header coverage and is not a skipped/waived test. Runtime middleware was unchanged by these later assertion refinements.

Exact-head [B816 targeted CI](https://github.com/AldinAga/ald1n-project/actions/runs/40111258792) remains **8 tests / 51 assertions PASS**; B809+B810, B811, B813, B814, B815 targeted steps PASS. Exact-head [B803 full comparator](https://github.com/AldinAga/ald1n-project/actions/runs/40111258786), job120923278145, reports:
- `BASELINE_CASES=451 BASELINE_PROBLEMS=44`;
- `CANDIDATE_CASES=522 CANDIDATE_PROBLEMS=91`;
- `NEW_FAILURES=49 WHOLE_SUITE_GREEN=NO`;
- **48** original B799–B808 guest-positive failures, plus **one** unchanged `BuyerCheckoutSessionBoundaryB815Test::test_storefront_off_remains_404_before_any_session_gate` whose proposed correction was safety-DENIED;
- no new `SecurityHealthBackupTest` failure on final head.

All **14** exact-head CI workflows finished: **9 PASS, 5 FAIL**. The five existing release blockers are B799 catalog/cart, B802 role isolation, B806 security, B799 Chromium original guest-positive step, and B803 full suite comparison. B812 registered-buyer Chromium separately still passes. Neither a scoped PASS nor unchanged inherited failures satisfies the global release condition.

### Drive readback (exact source and immutable evidence)

- [Final source snapshot `fa8a189...` with security PHP/test/workflow](https://docs.google.com/document/d/1HrKsITp08XrWZVajdiOuP-xS-Zvrf4lxIYVlhMf_tyg/edit)
- [Final B816 targeted GREEN MariaDB JUnit ZIP](https://drive.google.com/file/d/1_1fL9TCjPi1aSC2HWwicHZtVDbEyBgcl/view)
- [Final 522-case B803 JUnit and full failure identity ZIP](https://drive.google.com/file/d/1AVjxQX4B153XeiE7zuCPt39mdhw5fN0I/view)
- [B816 RED JUnit ZIP](https://drive.google.com/file/d/1BYbWFZ7Lla173FvTUf2xmUemzc5xI7OI/view)
- [Intermediate 522-case B803 report showing Cache-Control ordering mismatch](https://drive.google.com/file/d/1Rw-Hl_nda793Q77D38AYMNBP7_b-kd8O/view)

All source and evidence artifacts have been stored with readback/metadata verification on Google Drive. [Security traceability](../security/STOREFRONT-B816-AUTH-RESPONSE-PRIVACY.md) records partial WSTG4.2/ASVS5 L2 links without claiming the 368-control penetration gate is closed.

END_STATE=SOURCE_B816_PRIVACY_FIX_TARGETED_PASS
FULL_QA=HOLD_49_FAILURE_IDENTITIES
PRODUCTION_B798=UNCHANGED_STOREFRONT_OFF
PR93=DRAFT_NOT_MERGED
OWNER_SSH=NOT_NEEDED
FUNCT_ACCEPTED=7/17_41.2_PERCENT
CMS_ACCEPTED=7/15_46.7_PERCENT
