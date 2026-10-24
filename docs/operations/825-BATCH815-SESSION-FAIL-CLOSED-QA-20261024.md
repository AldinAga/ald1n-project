# Report825 — B815 credential-bound portal and buyer session gate (scoped GREEN; release HOLD)

DATE=2026-10-24
BATCH=B815
KIND=SOURCE_ONLY_SECURITY_HARDENING
PR=https://github.com/AldinAga/ald1n-project/pull/93
BRANCH=feat/b799-public-retail-catalog-cart-checkout
BASE_MAIN=d65983b92bdb77852fab5249a868f5375f015328
FINAL_PR_SOURCE=b392583714b522bab6fa1fc4af042a9510787f92
RED_RUN=https://github.com/AldinAga/ald1n-project/actions/runs/40107395720
INTERMEDIATE_RUN=https://github.com/AldinAga/ald1n-project/actions/runs/40107816659
GREEN_RUN=https://github.com/AldinAga/ald1n-project/actions/runs/40108095212
B815_SCOPED_GREEN=5_TESTS_61_ASSERTIONS_PASS
B812_REGISTERED_BROWSER=https://github.com/AldinAga/ald1n-project/actions/runs/40108095229
EXACT_HEAD_FULL_CI=https://github.com/AldinAga/ald1n-project/actions/runs/40108095363
FULL_CI=HOLD_PENDING_FINAL_LOGS
B815_BUYER_OFF_FIXTURE=OPEN_SAFETY_DENIED_EDIT
PRODUCTION=B798_PUBLIC_OFF_NO_MUTATIONS
PR93=DRAFT_UNMERGED
OWNER_SSH=NOT_REQUIRED
ACCEPTED_PROGRAM=7/17_41.2_PERCENT
ACCEPTED_CMS=7/15_46.7_PERCENT

## Reconstructed authority

Read fresh GitHub main, root AGENTS.md, docs/operations/000-LATEST.md, Report824 and PR93 head; fetched canonical Google Drive ALD1N-EXPO-CLOUDLINUX-UPDATE-RUNBOOK.md. Existing full-suite B814 9/14 workflows PASS and 48 historical guest-positive failures remained release blockers before this batch. No B799–B808 historical guest fixtures or safety-denied B805 archive was changed or bypassed.

## Concrete security problems and fixes

The existing tracked-session service silently re-created an authenticated session when its registry row was absent, allowed protected requests if the registry table or session ID was unavailable, and its route middleware swallowed tracking exceptions by returning success. These gaps could defeat credential-rotation session revocation. Registered-buyer public checkout used its own authentication/role checks but did not apply the same tracked-session proof.

B815 adds a Laravel server-session credential fingerprint keyed to the current authenticated user; `validateAndTouch` refreshes the current password hash, status and retirement marker from the authoritative MariaDB user row (rather than a potentially cached guard instance), rejects mismatched/rotated credentials, blocks missing registry/session ID, and does not auto-register a missing row after password rotation unless Laravel independently validates a current remember cookie. Legacy tracked rows are only upgraded if their login time is not older than the last password rotation. The tracked-session middleware's error catch now fails closed; changed credentials redirect to login.

PublicCheckoutController now applies the same validation for active registered Kupac before displaying/submitting checkout. Crucially it still calls `PublicCheckoutReadinessService::assertReady()` FIRST, preserving the unconditional public-storefront OFF/404 contract. No new anonymous ordering, bypass, role elevation, session token disclosure, payment mutation, mail delivery, or production change.

## RED/GREEN evidence and regression boundary

Introduced independent `tests/Feature/PortalSessionCredentialBoundaryB815Test.php` for valid login, active browser after reset, invitation activation, deleted revoked row, and missing registry table. First untouched-source RED was 5 tests/48 assertions/5 failures; four reflected open security boundaries, one was an overstrict synthetic test assertion expecting exactly one session row even though Laravel's test client rotated its session ID. This NEW assertion alone was adjusted to >=1. Intermediate after initial hardening still showed 3 failures due to stale guard-cached User credentials, prompting the fresh DB credential lookup. FINAL exact-head targeted MariaDB PHP8.4 B815 **5 tests /61 assertions PASS**; B809+B810 12/160, B811 4/91, B813 4/29, B814 4/25 also continued to pass. B812 real-browser registered Kupac step succeeded while its untouched historical B799 guest-positive browser step continued to fail.

A distinct new `tests/Feature/BuyerCheckoutSessionBoundaryB815Test.php` was added to exercise buyer checkout after password rotation and storefront OFF; however its OFF scenario calls a nonexistent `SettingsService::put()` API. The proposed test correction was DENIED by a provider safety control. **No retry, alternate path, code injection, deletion, skipping, suppression, or replacement fixture was attempted.** The buyer suite is NOT certified PASS and the new source error remains an additional release blocker until a permitted review and correction. The full B803 comparator must include it; do not call the complete CI green.

## Drive artifacts verified

- [Exact B815 code / test / workflow snapshot](https://docs.google.com/document/d/1enWtrr6Z1YBq_C95xrqc_7GtrHvfhOVYAo8flDwAQKk/edit)
- [Initial B815 RED JUnit/CI ZIP](https://drive.google.com/file/d/1yyfLE8JJDQMucedKFFmVzV7Ed8O55IpG/view)
- [Intermediate B815 JUnit/CI ZIP](https://drive.google.com/file/d/1Uz0yAehBjkn3TOiXZIO8FQsqoFZJ-BG6/view)
- [B815 targeted GREEN JUnit/CI ZIP](https://drive.google.com/file/d/1c03bdNNBdVk-fCSfuXFD0WvYtnihkuUy/view)

All these Google Drive uploads were read back to confirm exact file identity. The public checkout file and new buyer test are archived exactly as on PR93, including the unresolved mismatch; the archive is not a corrected replacement. Root AGENTS.md was updated to preserve the repeat safety-block guidance.

## Gates

DO_NOT_MERGE=YES
DO_NOT_DEPLOY=YES
DO_NOT_ENABLE_PUBLIC_STORE=YES
OWNER_SSH_ACTION=NONE
LEGACY_GUEST_TEST_FAILURES=OPEN
B815_NEW_BUYER_FIXTURE_BLOCK=OPEN
WSTG_ASVS368_REVIEW=OPEN
MERCHANT_TERMS_SHIPPING_PAYMENTS=OWNER_APPROVAL_PENDING
REAL_SMTP_AUTH_DKIM_SPF_DMARC=NOT_TESTED
RESTORE_FPM_OPCACHE=NOT_ATTESTED
REAL_BUYER_UAT=NOT_DONE
ANDROID_16KB_SDK58=SEPARATE_FUTURE_GATES
DEFER_INODE_CLEANUP=ISSUE95_FINAL_ONLY
