# 839 — B836 registered buyer receipt ownership and session protection

Date: 2026-10-24. SOURCE/CI-ONLY. Canonical GitHub authority before B836: `main` `28022845a1156507766684b96e0cf98f68aee9f3`; AGENTS.md B809/B815 and Google Drive `ALD1N-EXPO-CLOUDLINUX-UPDATE-RUNBOOK.md` reviewed.

## Actual product/security correction

Draft storefront [PR #93](https://github.com/AldinAga/ald1n-project/pull/93) latest B836 head `4df1c609d8d7d816ec860d36a4d41c2ca23241a3`. Changed five files in this batch (three production source paths, one new regression test, one isolated CI workflow):

1. `apps/cms/current/app/Services/PublicOrderAccessService.php`: a registered Kupac order's private capability link now requires an authenticated actor whose user ID matches the order, active/non-retired customer role and a live `PortalSessionService::validateAndTouch` browser session. Anonymous, another user, staff, retired buyer and missing/revoked session are `404` for order, document and warranty. Historical `public_retail` records without a `user_id` retain their existing token-limited, revocable read path. A token cannot be used to create or modify an order.
2. `apps/cms/current/app/Http/Controllers/PublicCheckoutController.php`: confirmation email and checkout success message explain same-account login for the private order link; trusted origin and existing checkout restrictions retained.
3. `apps/cms/current/app/Services/PublicRetailCheckoutService.php`: newly created order history now says registered Kupac rather than `bez registracije`.
4. `apps/cms/current/tests/Feature/PublicRegisteredReceiptBoundaryB836Test.php`: four PHP 8.4/MariaDB negative/positive tests for anonymous and cross-account denial, live buyer session and registry revocation, retired buyer. Standalone PHP syntax `PASS`.
5. `.github/workflows/b836-registered-receipt-qa.yml`: new source-only PHP 8.4/MariaDB 10.11.19 integration workflow with synthetic data, exact PR checkout, JUnit evidence and no production effects.

This is a distinct B809 registered-buyer product-access fix. Previously provider-denied `BuyerCheckoutSessionBoundaryB815Test.php` and legacy guest-positive fixtures have **not** been edited, deleted, skipped or bypassed. All historical global CI failures remain release HOLD and cannot be reclassified as PASS.

## Exact evidence checkpoint

- Test-file PHP syntax: **PASS**; CI YAML parse: **PASS**; ZIP integrity: **PASS**.
- New isolated CI workflow: [run 40136427573](https://github.com/AldinAga/ald1n-project/actions/runs/40136427573), **QUEUED** at last verified checkpoint; no integration PASS asserted until executed.
- Existing full PR93 checks still report historical B809-contradicting guest tests and other failures, so M13-M15 cannot close. PR93 remains draft/unmerged; PR98 remains a separate draft.
- B835 Android source CI 58/58 PASS, full native/emulator [run 40135270378](https://github.com/AldinAga/ald1n-project/actions/runs/40135270378) **IN_PROGRESS** during `assembleDebug` at last checkpoint. No 16KB emulator/Play/signed-AAB PASS is inferred.
- Google Drive source/test archive: [B836 ZIP](https://drive.google.com/file/d/17_ogetw2QipVgzW5mOoPwZ3mTOuozPat/view?usp=drivesdk), 5,692 bytes, SHA256 `a632f373468527f2090dd97b91b86437ce6ec9b3e91e8ca2204c281a92ecf92f`.

## Release boundaries and progress

No CloudLinux SSH, production write, DB migration, web activation, live mail, production EAS AAB, OTA or Google Play upload. Current production remains B798 with public storefront OFF. No SSH request.

Acceptance ledger: **7/17 = 41.2% overall; 7/15 = 46.7% CMS; original UI 6/6 = 100%**. Development-source and scoped QA are separate from formal acceptance. M16 release still HOLD until new authorized signed AAB, post-build ELF/bundletool/zipalign/signer verification and 16KB device/Play evidence. SDK58 remains deferred.

## Next unblocked gates

1. Finish B836 isolated MariaDB CI, fixing any defect in the new implementation without modifying denied historical fixtures. Existing global failures remain explicit.
2. Resolve B835 full-native emulator CI by its exact final log (do not assume it passed); inspect only verified errors, no duplicate native builds.
3. After CI and authorized source/version/release gate, perform one signed EAS build and post-build checks. Do not auto-authorize native build or Play submit.

`docs/operations/000-LATEST.md` remains on B832 because the B833 update was safety-denied; do not bypass the previous provider rejection. This numbered report and the current PR/main source are used for reconstruction.
