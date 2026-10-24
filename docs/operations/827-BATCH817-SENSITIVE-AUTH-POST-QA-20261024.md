# Report827 — B817 sensitive auth POST response privacy

DATE=2026-10-24
BATCH=B817
PR=https://github.com/AldinAga/ald1n-project/pull/93
EXACT_SOURCE=510c36791452167c7cf3dc901afaf74c4a701e39
WORKFLOW=https://github.com/AldinAga/ald1n-project/actions/runs/40113002141
SCOPED_CI=PENDING_UNVERIFIED
FULL_CI=NOT_GREEN_RELEASE_HOLD
PRODUCTION=B798_PUBLIC_STOREFRONT_OFF
SSH_REQUIRED=NO

Preflight read fresh main f95e9513c429d88dcba370d9aee4c8c55418e32b, AGENTS.md, 000-LATEST.md, Report825 and canonical Google Drive ALD1N-EXPO-CLOUDLINUX-UPDATE-RUNBOOK.md. Found Draft PR93 has already advanced to B816 source fa8a1892dc9bc925c524a6184a660fd16d0bc5a8; this batch starts after it, not on stale B815. Existing exact-head B816 9/14 workflow success with historical guest-positive failures still active.

Added a NEW synthetic MariaDB test file: apps/cms/current/tests/Feature/SensitiveAuthPostBoundaryB817Test.php. It checks invalid login, reset-link, password reset, activation and public registration POST responses for no-referrer/private/no-store/noindex/nosniff, plus a no-account/no-order/no-token-mutation test when supplied an invalid activation/reset token. No real email, external provider, new account or customer data.

Updated the EXISTING .github/workflows/b806-storefront-security-qa.yml to lint, archive source and execute B817 with PHPUnit on isolated PHP8.4/MariaDB10.11.19. All historical guest-positive tests, earlier privacy suites and other security gates remain active. No production behavior changes or application service/controller edits.

The exact-source run 40113002141 was started and not completed at report creation. DO NOT claim scoped PASS until job logs/JUnit establish it. Do not infer complete security certification from isolated tests. The existing 49 old+new B815 failure identities are unresolved (48 stale guest-positive plus one B815 buyer-OFF fixture correction explicitly safety-denied); do not bypass, retry, suppress or repackage that blocked edit. Full CI, merchant/payment/shipping, real SMTP, restore, FPM/OPcache, WSTG/ASVS and real buyer UAT remain release gates.

Drive B817 exact source snapshot: https://docs.google.com/document/d/1iOXKqE9amTfa4O33-nB5erImyeTa4cQfXwmIKIPyJdo/edit
Accepted program7/17=41.2%, CMS7/15=46.7%, original UI6/6=100%. No owner SSH needed and no production change.
