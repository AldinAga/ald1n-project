# 839 — B836 registered buyer receipt ownership and session protection

Date: 2026-10-24. SOURCE/CI-ONLY. Canonical GitHub authority before B836: `main` `28022845a1156507766684b96e0cf98f68aee9f3`; AGENTS.md B809/B815 and Google Drive `ALD1N-EXPO-CLOUDLINUX-UPDATE-RUNBOOK.md` reviewed.

## Actual product/security correction

Draft storefront [PR #93](https://github.com/AldinAga/ald1n-project/pull/93) verified B836 head `aaf4bc9a19d44d04e7c4ce042e2ec4a91e0323bb`. Changed five files in this batch (three production source paths, one new regression test, one isolated CI workflow):

1. `apps/cms/current/app/Services/PublicOrderAccessService.php`: a registered Kupac order's private capability link now requires an authenticated actor whose user ID matches the order, active/non-retired customer role and a live `PortalSessionService::validateAndTouch` browser session. Anonymous visitors, other users, staff and retired buyers receive `404` for current registered-buyer order, document and warranty views. A real user logout closes the receipt. A missing registry table or a matching explicitly revoked current session fails closed; do not equate merely deleting a session row in the PHPUnit array-session fixture with a logout, because `validateAndTouch` may legitimately bootstrap a still-authenticated session. Historical `public_retail` records without a `user_id` retain their existing token-limited, revocable read path. A token cannot be used to create or modify an order.
2. `apps/cms/current/app/Http/Controllers/PublicCheckoutController.php`: confirmation email and checkout success message explain same-account login for the private order link; trusted origin and existing checkout restrictions retained.
3. `apps/cms/current/app/Services/PublicRetailCheckoutService.php`: newly created order history now says registered Kupac rather than `bez registracije`.
4. `apps/cms/current/tests/Feature/PublicRegisteredReceiptBoundaryB836Test.php`: four PHP 8.4/MariaDB tests for anonymous and cross-account denial, legitimate buyer login then real logout, and retired buyer. Test assertions were refined after two exact CI failures caused by fixture assumptions (hardcoded post-login redirect, and simulated session-row deletion/rotation); no denied test was touched. Standalone PHP syntax `PASS`.
5. `.github/workflows/b836-registered-receipt-qa.yml`: new source-only PHP 8.4/MariaDB 10.11.19 integration workflow with synthetic data, exact PR checkout, JUnit evidence and no production effects.

This is a distinct B809 registered-buyer product-access fix. Previously provider-denied `BuyerCheckoutSessionBoundaryB815Test.php` and legacy guest-positive fixtures have **not** been edited, deleted, skipped or bypassed. All historical global CI failures remain release HOLD and cannot be reclassified as PASS.

## Exact evidence checkpoint

- Test-file PHP syntax: **PASS**; CI YAML parse: **PASS**; ZIP integrity: **PASS**.
- New isolated scoped [GitHub CI run 40136917704](https://github.com/AldinAga/ald1n-project/actions/runs/40136917704), exact test commit `aaf4bc9a19d44d04e7c4ce042e2ec4a91e0323bb`: **PASS, 4 PHPUnit tests / 25 assertions / 0 failures**, PHP 8.4.27 and MariaDB 10.11.19, with an unmodified source checkout. GitHub job `121000271360`; log confirms `B836_REGISTERED_RECEIPT_SCOPED=PASS_ONLY_ALL_OTHER_CI_AND_RELEASE_GATES_STILL_REQUIRED`. This scoped pass is not a full PR93 CI pass.
- Existing full PR93 checks still report historical B809-contradicting guest tests and other failures, so M13-M15 cannot close. PR93 remains draft/unmerged; PR98 remains a separate draft.
- B835 Android source CI 58/58 PASS, full native/emulator [run 40135270378](https://github.com/AldinAga/ald1n-project/actions/runs/40135270378) **IN_PROGRESS** at the offline Android 15 16KB emulator smoke step at latest B836 report checkpoint; `Prepare KVM` was PASS. No emulator final PASS/Play/signed-production-AAB PASS is inferred.
- Google Drive source/test archive: [B836 final v4 ZIP](https://drive.google.com/file/d/187wkGQ2i557-fKC3LVHk4UeqwTbfCin4/view?usp=drivesdk), **5,823 bytes**, SHA256 `b87ac7a11c8cd4743620968572ead7af970da45c03516c6750a49626357701c7`. Supersedes earlier v1/v3 archives.

## Release boundaries and progress

No CloudLinux SSH, production write, DB migration, web activation, live mail, production EAS AAB, OTA or Google Play upload. Current production remains B798 with public storefront OFF. No SSH request.

Acceptance ledger: **7/17 = 41.2% overall; 7/15 = 46.7% CMS; original UI 6/6 = 100%**. Development-source and scoped QA are separate from formal acceptance. M16 release still HOLD until new authorized signed AAB, post-build ELF/bundletool/zipalign/signer verification and 16KB device/Play evidence. SDK58 remains deferred.

## Next unblocked gates

1. B836 isolated MariaDB PASS is complete; keep PR93 DRAFT and correct only permitted distinct security issues without touching provider-denied historical fixture rewrites. Full storefront/global CI, explicit merchant/SMTP/OWASP security gates and release activation remain HOLD.
2. Resolve B835 full-native emulator CI by its exact final log (do not assume it passed); inspect only verified errors, no duplicate native builds.
3. After CI and authorized source/version/release gate, perform one signed EAS build and post-build checks. Do not auto-authorize native build or Play submit.

`docs/operations/000-LATEST.md` remains on B832 because the B833 update was safety-denied; do not bypass the previous provider rejection. This numbered report and the current PR/main source are used for reconstruction.
