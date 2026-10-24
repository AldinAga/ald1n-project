# Report823 — B813 retired-but-active login denial (RED / GREEN)

DATE=2026-10-24
BATCH=B813
SCOPE=LOGIN_AUTHENTICATION_GUARD_ONLY
PR=https://github.com/AldinAga/ald1n-project/pull/93
RED_TEST_COMMIT=a924dd857ec120ac02b6e7dcc94fe4e2cd6f21cc
RED_WORKFLOW_COMMIT=7045b77c48f60b61f4fd0af5dd5a0641a39addeb
GREEN_SOURCE_COMMIT=0810136443151c3aa62d5429368d3c82df04aec8
RED_CI=https://github.com/AldinAga/ald1n-project/actions/runs/40104013814
GREEN_CI=https://github.com/AldinAga/ald1n-project/actions/runs/40104186654
RED_SCOPED=4_TESTS_19_ASSERTIONS_2_FAILURES
GREEN_SCOPED=4_TESTS_29_ASSERTIONS_0_FAILURES
FULL_CI=HOLD_PENDING_EXACT_HEAD_FINAL
PRODUCTION=B798_STOREFRONT_OFF_NO_CHANGES
SSH_REQUIRED=NO
FUNCTIONAL_ACCEPTANCE=7_OF_17_41.2_PERCENT
CMS_ACCEPTANCE=7_OF_15_46.7_PERCENT

## Fresh authority and issue

Reconstructed GitHub main d3784147fcd2ee49f5c24efa2fd7b20acd77bdc4, root AGENTS.md, docs/operations/000-LATEST.md, Report822, exact Draft PR93 76be91d1fe434708dd5f4a47ea635bb59f1fbfd6, and canonical Google Drive ALD1N-EXPO-CLOUDLINUX-UPDATE-RUNBOOK.md before implementation.

Observed authorization inconsistency in apps/cms/current/app/Http/Controllers/Auth/AuthenticatedSessionController.php: web login checked status=active and password, but did NOT check non-null users.retired_at. A malformed/imported or inconsistent retired-but-still-active user row could therefore establish an authenticated session and record successful login telemetry. The downstream EnsureActiveUser middleware blocked protected routes, so evidence does NOT establish ability to reach protected back-office functionality. This is a preventive guard fix, not an invented proven authorization bypass into protected content.

## Synthetic RED proof before code fix

Added NEW tests/Feature/RetiredActiveLoginB813Test.php with four cases: deny retired active staff, deny retired active Kupac, preserve blocked-user denial and preserve valid login for non-retired staff/Kupac. Added a named independent B813 PHP8.4/MariaDB10.11.19 target gate inside the existing B806 security workflow; original guest checkout tests remain active and untouched.

At red head 7045b77c48f60b61f4fd0af5dd5a0641a39addeb, workflow 40104013814, job 120902036587, the B813 step FAILED as intended: four tests, 19 assertions, **two failures**, exactly the two retired-but-active identities; the valid and blocked controls did not fail. This is source-level evidence that the defect existed before the fix.

## Minimal fix, GREEN proof

Updated ONLY login condition in app/Http/Controllers/Auth/AuthenticatedSessionController.php:

BEFORE: user absent OR status not active OR invalid password -> deny.
AFTER: user absent OR status not active OR retired_at NOT NULL OR invalid password -> deny.

At green source 0810136443151c3aa62d5429368d3c82df04aec8, workflow 40104186654, job 120902341035, B813 tests **4/4 PASS, 29 assertions**, 0 errors/failures. Existing B809+B810 **12/160 PASS**, B811 **4/91 PASS**, B808 expected RED witness passes its deliberately negative predecessor proof. No separate package, new workflow, production authentication, real credentials or production DB changes.

The larger B806 workflow continues to FAIL in the untouched historical B799–B808 guest-positive security tests (24 tests / 88 assertions, one error / 23 failures on green job); no waiver. The other exact-head workflows and B803 full-suite comparison were still finishing when this report section was recorded. Final source/report handoff must label overall CI HOLD even with B813 scoped PASS; do not assert an overall green suite without exact-head evidence.

## Boundaries, evidence and next gates

B813 source snapshot archived and read back on Google Drive:
https://docs.google.com/document/d/11VLBhI5l86PUnH01cXE1UbeOlfvJzyTCyS5HL5wbE1s/edit

No B799–B808 historical guest test modifications, test skips, fixed CI suppressions, SMTP sends, CloudLinux SSH, real user/account operations, database migrations, storefront ON, mobile/EAS/OTA or Play builds. PR93 remains DRAFT and NOT MERGED. Existing provider-denied fixture adaptation was NOT retried or rerouted.

Unresolved: 48 old guest-positive regression identities from last B812 9/14 run, remaining OWASP WSTG4.2/ASVS5.0 L2 368-item security review, owner-approved merchant/legal terms and shipping/payment conditions, actual SMTP deliverability, FPM/OPcache proof, restore drill and real buyer UAT. Authenticated-only public checkout and registration rules remain mandatory. Final inode cleanup remains issue95, last phase.

Accepted milestones unchanged: program 7/17=41.2%, CMS 7/15=46.7%, initial UI 6/6=100%. These are accepted production milestones, not total source-development progress.
