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
GREEN_SCOPE=5_TESTS_27_ASSERTIONS_PASS
EXACT_HEAD_FULL_CI=9_PASS_5_FAIL_OF_14
B803_BASELINE=451_CASES_44_PROBLEMS
B803_CANDIDATE=533_CASES_91_PROBLEMS
B803_NEW_FAILURE_IDENTITIES=49
B818_NEW_FAILURE_IDENTITIES=0
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

## B818 GREEN source-locked proof — 2026-10-24

[Exact-source B818 GREEN run40115830200](https://github.com/AldinAga/ald1n-project/actions/runs/40115830200), job120936783757: **5 PHPUnit tests / 27 assertions PASS** under PHP8.4/MariaDB10.11.19. The two earlier RED receipt access failures became GREEN after the single controller guard change. Tests also confirm the independent static ENV OFF flag, existing receipt access when only new checkout is closed, persistent revocation across OFF/reopen, and unchanged order/access rows after emergency OFF. This does NOT attest successful real private PDF rendering, which requires separate authorized artifact-level testing.

The historical B806 security job still fails separately (24 old tests, 88 assertions, 1 error, 23 failures), previously inherited guest assumptions. None of these tests were modified, skipped, replaced or masked. B815 buyer-OFF test fixture correction remains provider safety-DENIED and was not retried or bypassed.

Drive evidence archived AND independently read back:
- [B818 RED original PHP8.4 MariaDB ZIP](https://drive.google.com/file/d/1jR-pO_QjV0oqoKDgKA4LiyMBCfH3q8AJ/view).
- [B818 GREEN original PHP8.4 MariaDB ZIP](https://drive.google.com/file/d/1LoCPfNdlXd-o-9z5Pv5U3VatO2VuRxLo/view).
- [B818 RED source snapshot](https://docs.google.com/document/d/1HqGskE2wrqHjzDY0C0RrOFEBoxpRtPMCfJfvU-crHJQ/edit).
- [B818 GREEN source snapshot](https://docs.google.com/document/d/18Q3lC-9dGW1RzIi3BdbmUHP9Qn536b_gT1d36KlmNs0/edit).

**Full exact-head 14-workflow CI**: still running when this addendum was authored, so do not infer or claim full-green status. Production B798/public storefront OFF unchanged, Draft PR93 unmerged. No SSH or production build.

## Terminal exact-head comparator and release disposition — 2026-10-24

Draft PR93 exact source `fab3f4991fe5792e28e5a402dbf402617fb5c303`: **all 14 workflows completed; 9 PASS, 5 FAIL**. [B803 full candidate-vs-main comparison run40115830268](https://github.com/AldinAga/ald1n-project/actions/runs/40115830268), job120936920271, conclusively records:
- `BASELINE_CASES=451 BASELINE_PROBLEMS=44`.
- `CANDIDATE_CASES=533 CANDIDATE_PROBLEMS=91`.
- `NEW_FAILURES=49 WHOLE_SUITE_GREEN=NO`, same 48 historical B799/B802/B803/B806/B808 guest checkout assumption identities plus exactly one untouched **`BuyerCheckoutSessionBoundaryB815Test::test_storefront_off_remains_404_before_any_session_gate`** fixture error whose correction was previously safety-denied.
- **B818 introduced 0 additional failure identities.** Five B818 scoped tests /27 assertions PASS on exact source; full suite is NOT green and no tests were disabled/waived.

Five failing jobs remain B799 storefront, B802 role, B806 security, B799 historical Chromium, and B803 full comparator. Previously established B812 registered-buyer Chromium step remains a distinct scoped PASS, NOT full browser approval.

[Original B803 exact-source evidence ZIP, JUnit and 49 failure identities archived to owner Google Drive](https://drive.google.com/file/d/1IpbOL4Pm0wnzZ9qrysM73mBMPS-1eaIW/view). This file was independently verified by Drive metadata readback. RED JUnit, GREEN JUnit, source snapshots and this report were also independently archived as above.

**DO_NOT_MERGE=YES; DO_NOT_DEPLOY=YES; DO_NOT_ENABLE_STOREFRONT=YES; NO_OWNER_SSH.** Production remains unchanged B798/OFF. Owner may be involved later only when a validated necessary operator SSH batch is actually prepared. Accepted program 7/17=41.2% and CMS 7/15=46.7% unchanged. Next closing gate is honest resolution of historical guest-positive failures and previously safety-denied B815 fixture through an independently permitted review, followed by complete security/merchant/SMTP/restore/FPM/real-buyer UAT checks. No bypass attempted.
