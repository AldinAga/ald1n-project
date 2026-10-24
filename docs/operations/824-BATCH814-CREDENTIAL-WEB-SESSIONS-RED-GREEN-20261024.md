# Report824 — B814 credential recovery / activation revoke tracked web sessions (RED/GREEN)

DATE=2026-10-24
BATCH=B814
BRANCH=feat/b799-public-retail-catalog-cart-checkout
PR=https://github.com/AldinAga/ald1n-project/pull/93
BASE_MAIN=c32c70ac6b7426572876cdaf41e1bb40447a6e84
RED_SOURCE=3d0a1f963d3643e6d7930a72926cf53120156cb0
GREEN_SOURCE=6154b5ca277e5a20588e231e54a0650941c5a7b0
RED_RUN=https://github.com/AldinAga/ald1n-project/actions/runs/40105667989
GREEN_RUN=https://github.com/AldinAga/ald1n-project/actions/runs/40105788567
RED_B814=4_CASES_17_ASSERTIONS_3_FAILURES
GREEN_B814=4_CASES_25_ASSERTIONS_0_FAILURES
EXACT_HEAD_FULL_CI=9_OF_14_PASS_5_FAILED
B803_BASELINE=451_CASES_44_PROBLEMS
B803_CANDIDATE=507_CASES_90_PROBLEMS
B803_NEW_FAILURE_IDENTITIES=48
PRODUCTION_MUTATIONS=0
STORE_B798=OFF
MERGE=HOLD_DRAFT_PR93
SSH_REQUIRED=NO
ACCEPTED_PROGRAM=7/17_41.2_PERCENT
ACCEPTED_CMS=7/15_46.7_PERCENT

## Scope and reproducible finding

Verified current GitHub main, root AGENTS.md, newest 000-LATEST.md and Report823, Draft PR93 exact head and canonical Google Drive ALD1N-EXPO-CLOUDLINUX-UPDATE-RUNBOOK.md before source changes.

Two credential-rotation services previously deleted API tokens and rotated persistent remember credentials, but did NOT invalidate existing tracked web session records:
- `PasswordResetService::resetPassword`: expired or stolen session cookies remained admitted where an active tracked web session already existed.
- `CustomerActivationService::activate`: activating an invitation for an already-active employee account changed the password but left existing tracked web sessions admitted.

Added NEW four-case MariaDB test suite `tests/Feature/CredentialWebSessionRevocationB814Test.php`: reset staff revokes 2 tracked browsers + API token, reset registered Kupac revokes existing web session, existing employee activation revokes old session, expired reset is harmless and must not revoke a valid session. No old guest-positive tests modified or suppressed.

Original initial test harness had a naming collision with inherited Laravel TestCase::session; corrected just the new helper to trackedSession and then reran the same RED suite. **True RED** on unchanged application services, PHP8.4/MariaDB10.11.19: 4 tests /17 assertions /3 expected failures, exactly the three real active-session revocation cases. This is genuine behavior, not a test-infrastructure error.

**Minimal GREEN**: both services now call existing `PortalSessionService::revokeAll($user,$user)` inside the SAME database transaction that rotates the credential and deletes Sanctum tokens; username, role, status, activation and user-facing flows otherwise unchanged. GREEN on exact commit 6154b5ca277e5a20588e231e54a0650941c5a7b0: 4 tests /25 assertions PASS, 0 errors or failures; B809+B810 12/160, B811 4/91 and B813 4/29 remain PASS. No real credentials, messages, host writes or payments involved.

## Boundaries / residual risk

This specifically invalidates TRACKED browser sessions. The existing `PortalSessionService::validateAndTouch` accepts/recreates an unmatched session when the tracking record is missing, and fails open if tracking is unavailable; a comprehensive session-lifecycle/security claim still needs independent coverage of that pre-existing behavior and a complete WSTG/ASVS review. Do not overclaim that every theoretical session, or all production transport paths, have been tested.

Original B799/B802/B803/B806/B808 tests that still assert now-forbidden guest checkout remain active and fail; earlier safety-denied fixture rewrites NOT retried. PR93 remains Draft, production B798 public storefront OFF. No deploy, SSH, real SMTP, EAS/OTA/Play or cleanup. OWASP368 inventory, legacy full CI, merchant/legal, actual SMTP deliverability, restore/FPM/OPcache and owner buyer UAT remain release gates.

## Verified owner Google Drive archives

- [B814 RED MariaDB JUnit/source ZIP](https://drive.google.com/file/d/1MRjwMYWYmFjlW6a4hbFZhayMT16SI2J0/view)
- [B814 GREEN MariaDB JUnit/source ZIP](https://drive.google.com/file/d/11K6R_2fEcjKV8vkLK-LkEaAT4IDXrq9g/view)
- [B814 exact-source PHP/YAML snapshot](https://docs.google.com/document/d/1b89CCElaH6W3gGiIMh8j7WlCL7ixN6uPbzTTocMV0CY/edit)

All three uploaded and verified by independent connector readback. GitHub workflow records remain the authoritative CI outcomes. Functional milestones unchanged: 7/17=41.2% overall, 7/15=46.7% CMS, initial UI 6/6=100%.

## FINAL B814 exact-source CI and release gate — 2026-10-24

PR93 exact-source `6154b5ca277e5a20588e231e54a0650941c5a7b0`: **14 workflows complete, 9 PASS / 5 FAIL**. B803 [full candidate vs baseline run40105788552](https://github.com/AldinAga/ald1n-project/actions/runs/40105788552) job120907185187: **451 baseline cases /44 problems**, **507 candidate cases /90 problems**, **48 new failure identities**, unchanged historical anonymous checkout expectations. B814 adds four passing test cases without increasing the count of inherited new failures. All five known jobs still FAIL: B799 catalog/cart, B802 role, B806 security, B799 Chromium and B803 full comparator. No skipped tests; full suite not green.

[Original exact-head B814 B803 evidence ZIP on Google Drive](https://drive.google.com/file/d/1VjusOct0Aw2_YAUGkQ7gGPV0JGHUFeFr/view) — successful upload and metadata readback, artifact12564434076. Earlier B814 RED [ZIP](https://drive.google.com/file/d/1MRjwMYWYmFjlW6a4hbFZhayMT16SI2J0/view) and GREEN [ZIP](https://drive.google.com/file/d/11K6R_2fEcjKV8vkLK-LkEaAT4IDXrq9g/view) remain archived/readback. Independent production UAT, actual mail, merchant and full WSTG/ASVS gates remain OPEN.

PREVIOUS HISTORICAL SAFETY-DENIED LEGACY TEST MODIFICATIONS=NOT_RETRIED
PRODUCTION_DEPLOY=NO
STORE_FRONT=OFF
OWNER_SSH_REQUIRED=NO
RELEASE=HOLD
