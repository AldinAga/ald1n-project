# Report827 — B817 sensitive auth POST response privacy

DATE=2026-10-24
BATCH=B817
PR=https://github.com/AldinAga/ald1n-project/pull/93
RED_SOURCE=510c36791452167c7cf3dc901afaf74c4a701e39
GREEN_SOURCE=ab91681052e782306f2aa2d371207db6eeb176a5
RED_WORKFLOW=https://github.com/AldinAga/ald1n-project/actions/runs/40113002141
GREEN_WORKFLOW=https://github.com/AldinAga/ald1n-project/actions/runs/40113258566
SCOPED_CI=6_TESTS_40_ASSERTIONS_PASS
FULL_CI=NOT_GREEN_RELEASE_HOLD
PRODUCTION=B798_PUBLIC_STOREFRONT_OFF
SSH_REQUIRED=NO

Preflight read fresh main f95e9513c429d88dcba370d9aee4c8c55418e32b, AGENTS.md, 000-LATEST.md, Report825 and canonical Google Drive ALD1N-EXPO-CLOUDLINUX-UPDATE-RUNBOOK.md. Found Draft PR93 has already advanced to B816 source fa8a1892dc9bc925c524a6184a660fd16d0bc5a8; this batch starts after it, not on stale B815. Existing exact-head B816 9/14 workflow success with historical guest-positive failures still active.

Added a NEW synthetic MariaDB test file: apps/cms/current/tests/Feature/SensitiveAuthPostBoundaryB817Test.php. It checks invalid login, reset-link, password reset, activation and public registration POST responses for no-referrer/private/no-store/noindex/nosniff, plus a no-account/no-order/no-token-mutation test when supplied an invalid activation/reset token. No real email, external provider, new account or customer data.

Updated the EXISTING .github/workflows/b806-storefront-security-qa.yml to lint, archive source and execute B817 with PHPUnit on isolated PHP8.4/MariaDB10.11.19. All historical guest-positive tests, earlier privacy suites and other security gates remain active. No production behavior changes or application service/controller edits.

The exact-source run 40113002141 was started and not completed at report creation. DO NOT claim scoped PASS until job logs/JUnit establish it. Do not infer complete security certification from isolated tests. The existing 49 old+new B815 failure identities are unresolved (48 stale guest-positive plus one B815 buyer-OFF fixture correction explicitly safety-denied); do not bypass, retry, suppress or repackage that blocked edit. Full CI, merchant/payment/shipping, real SMTP, restore, FPM/OPcache, WSTG/ASVS and real buyer UAT remain release gates.

Drive B817 exact source snapshot: https://docs.google.com/document/d/1iOXKqE9amTfa4O33-nB5erImyeTa4cQfXwmIKIPyJdo/edit
Accepted program7/17=41.2%, CMS7/15=46.7%, original UI6/6=100%. No owner SSH needed and no production change.

## Verified GREEN and limitations
At exact candidate `ab91681052e782306f2aa2d371207db6eeb176a5`, targeted B817 PHP8.4 MariaDB tests PASS: **6 tests /40 assertions**. The first red run failed one new case because public registration intentionally returned 404 while storefront OFF, not a privacy bypass. The B817 test-only expected status was corrected to assert OFF/404; no application runtime changes. The original historic B806 suite still fails with 24 tests/88 assertions/1 error/23 failures on the same CI run. Source privacy checks alone are not a security certification or release approval.

[Original B817 JUnit/source evidence ZIP verified on Google Drive](https://drive.google.com/file/d/1oz0M4PscYBDrljQwu682QZEWxIcQILt8/view). [Exact earlier source snapshot](https://docs.google.com/document/d/1iOXKqE9amTfa4O33-nB5erImyeTa4cQfXwmIKIPyJdo/edit) predates the minor test-only 404 expectation correction, so GitHub commit above is canonical. PR93 stays Draft; inherited 48 anonymous guest tests and the separate B815 buyer fixture safety denial stay HOLD, no bypass or release.
