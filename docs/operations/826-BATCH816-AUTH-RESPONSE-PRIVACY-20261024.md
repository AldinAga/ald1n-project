# Report826 — B816 sensitive auth response privacy RED / GREEN (source-only)

DATE=2026-10-24
BATCH=B816
SCOPE=AUTH_TOKEN_REFERRER_CACHE_NO_INDEX
BRANCH=feat/b799-public-retail-catalog-cart-checkout
PR=https://github.com/AldinAga/ald1n-project/pull/93
PR_EXACT_HEAD=aafcba888807ab13d0c12109d423c27734f60741
RED_COMMIT=d8647211ae1bebbdb36757f7436ef0c363d385a5
GREEN_COMMIT=5e31333e9e2c7d48366b890e2e2ff3e88b969123
RED_RUN=https://github.com/AldinAga/ald1n-project/actions/runs/40110116447
GREEN_RUN=https://github.com/AldinAga/ald1n-project/actions/runs/40110368273
B816_RED=8_TESTS_17_ASSERTIONS_7_FAILURES
B816_GREEN=8_TESTS_51_ASSERTIONS_PASS
FULL_EXACT_HEAD_CI=PENDING_COMPARATOR_40110799327
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
