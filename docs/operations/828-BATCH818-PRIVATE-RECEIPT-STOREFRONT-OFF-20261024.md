# Report828 — B818 private receipt emergency OFF closes existing capability links (RED/GREEN)

DATE=2026-10-24
BATCH=B818
SCOPE=SOURCE_ONLY_PRIVATE_ORDER_CAPABILITY_GATE
PR=https://github.com/AldinAga/ald1n-project/pull/93
SOURCE_RED=7d7e3bcd6b6e91bba532e10ebed18229a501afe8
SOURCE_FIXED=fab3f4991fe5792e28e5a402dbf402617fb5c303
RED_RUN=https://github.com/AldinAga/ald1n-project/actions/runs/40115578447
GREEN_RUN=https://github.com/AldinAga/ald1n-project/actions/runs/40115830200
RED_SCOPE=5_TESTS_20_ASSERTIONS_2_FAILURES
GREEN_SCOPE=PENDING_NOT_ATTESTED
FULL_EXACT_HEAD_CI=PENDING_RELEASE_HOLD
PRODUCTION=B798_STOREFRONT_OFF_UNCHANGED
PR_STATE=DRAFT_UNMERGED
OWNER_SSH=NOT_REQUIRED
ACCEPTED_PROGRAM=7/17_41.2_PERCENT
ACCEPTED_CMS=7/15_46.7_PERCENT

## Fresh authority and issue

Before modifications read GitHub main 434c1ae2ab2901abb1dfb1e102c74202f34f3835, root AGENTS.md, docs/operations/000-LATEST.md, Report827 and canonical Google Drive ALD1N-EXPO-CLOUDLINUX-UPDATE-RUNBOOK.md. PR93 head was ab91681052e782306f2aa2d371207db6eeb176a5, Draft/unmerged. No earlier batches were repeated.

Confirmed from source that `PublicShopController::guarded()` correctly required `PublicStorefrontStateService::isOpen()`, but `PublicOrderAccessController::guard()` checked only the static ENV `public_storefront.enabled`. Thus SuperAdmin's dynamic emergency OFF switch could hide catalog/cart/checkout while an already-issued bearer receipt remained readable — including a route family exposing private documents/warranties. The desired OFF-uniform-404 contract and the need to keep past order capabilities private remain mandatory.

## New source and verified RED

Created a completely NEW independent synthetic test `apps/cms/current/tests/Feature/PublicReceiptSwitchBoundaryB818Test.php`, exercising actual approved SuperAdmin ON then OFF, previously issued buyer-linked private capability, receipt/subroute denial, independent ENV OFF, checkout-only disable preserving old receipt, and permanent revocation across OFF/reopen. It uses the existing public order issuance and SuperAdmin state-change APIs on disposable PHP8.4/MariaDB10.11.19. This is NOT a replacement or rewrite of the separate safety-denied B815 buyer checkout fixture.

Attached the test to the existing B806 MariaDB workflow without removing, skipping or weakening old security/guest tests. On exact unmodified application controller source, RED run40115578447 job120936280122 gave **5 tests, 20 assertions, 2 failures**. Both failures proved that a registered-buyer receipt returned **HTTP 200 instead of 404** after SuperAdmin OFF. Other three controls passed. No live orders, mail, account logins, backups or host writes.

## Minimal fix awaiting GREEN

Updated ONLY `apps/cms/current/app/Http/Controllers/PublicOrderAccessController.php` guard to require the fresh `PublicStorefrontStateService::isOpen()` state instead of the ENV flag alone. The same guard is called before show/document/warranty access. This preserves the static ENV OFF gate and intentionally does not depend on `checkout_enabled`, so already issued receipts remain available when new checkout is closed but storefront is still ON. Bearer entropy/hash/expiry/revocation and payment/business logic were NOT changed.

The exact GREEN workflow was still running at report creation. **Do not claim PASS** until its JUnit and job logs verify the five scenarios. Historical B799–B808 guest-positive suites and the previously safety-denied B815 buyer OFF fixture correction stay untouched, and full CI/security/merchant/SMTP/restore/FPM/UAT gates remain HOLD. Production B798 remains OFF. No PR merge or SSH.

## Drive archives

[Exact RED source archive](https://docs.google.com/document/d/1HqGskE2wrqHjzDY0C0RrOFEBoxpRtPMCfJfvU-crHJQ/edit)
[Exact GREEN candidate source archive](https://docs.google.com/document/d/18Q3lC-9dGW1RzIi3BdbmUHP9Qn536b_gT1d36KlmNs0/edit)
[RED PHP8.4 MariaDB JUnit/source ZIP](https://drive.google.com/file/d/1jR-pO_QjV0oqoKDgKA4LiyMBCfH3q8AJ/view)

All listed Drive objects uploaded and verified by readback. Pending: GREEN ZIP and full B803 comparator final result, neither may be inferred from targeted source edits.

Accepted functional stages stay program7/17=41.2%, CMS7/15=46.7%, initial UI6/6=100%. B818 is security engineering, not a new production-accepted milestone.
