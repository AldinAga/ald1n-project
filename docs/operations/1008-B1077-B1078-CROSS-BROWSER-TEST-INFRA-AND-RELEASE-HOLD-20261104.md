# Report1008 — B1077/B1078 cross-browser QA, test discovery and production HOLD
Date: 2026-11-04 Europe/Belgrade. Owner requests all planned work and production only when fully safe. That request remains incomplete: mandatory release gates below are still open. No production release was attempted.

## Exact source and completed work
[Draft PR131](https://github.com/AldinAga/ald1n-project/pull/131), base Draft PR93 acb8b9c42ec9254b1a27567abca29a26c1f733f8, remains unmerged. Tested final candidate **692e751e0dec407d998c0d1655c4bde5a4600d99**. B1077/B1078 change exactly six TEST/CI files, no production application, mobile, dependency, database or identity change.

B1077 reuses the existing registered-buyer browser journey for Chromium AND Firefox. Unknown browser engines are explicitly rejected before Playwright starts. Each engine receives a separately reset disposable MariaDB10.11.19 fixture. The server uses Laravel's locked vendor router through one directly managed PHP process, avoiding an orphaned artisan child between engines. Artifacts are separated by engine. The old guest-success test remains included and red.

The synthetic fixture now generates a valid GD PNG320x240, reusing existing public storage/ProductImage contracts. Each catalog/product image must actually decode, report complete=true and naturalWidth320/naturalHeight240, return HTTP200/image/png/private/no-store. Catalog/registration layouts are checked at1440 and390; the complete purchase journey runs at1440. These are synthetic fixtures, not customer data.

B1078 repairs two existing test-runner issues only:
- LegacyReadOnlyGuardTest: old ignored data-provider docblock becomes PHPUnit12 DataProvider attribute. All seven ORIGINAL mutating/locking query rows and all assertions are unchanged and now execute.
- DeliveryNoteMigrationContractTest: imports existing Tests\\TestCase so Laravel base_path resolves; the original migration assertions are unchanged.
CI reconstructs the exact allowed metadata-only transformation from original PR93 and rejects any other change to either file. B805/B809/B815 and Composer manifests/lock remain byte-identical. No security fixture expectation was weakened.

## Actual GitHub results
- Browser-only baseline263994b01392eaae2316d0a1ecab06e0d9405d81: both engines' real signup/activation/login/cart/order/replay PASS.
- Image candidate768ca441cb0011b82e62471160e73c7e116d20c6, [browser41743416827](https://github.com/AldinAga/ald1n-project/actions/runs/41743416827): Chromium AND Firefox registered flow plus actual PNG decoding/header assertions PASS. Manual artifact inspection included Firefox390 catalog and Chromium checkout.
- Same image candidate [full41743416799](https://github.com/AldinAga/ald1n-project/actions/runs/41743416799): focused51/772 assertions PASS; complete candidate615/5342 assertions/9 errors/71 failures/80 failed IDs. This is the RED evidence for the two existing harness errors before B1078.
- Final [full41744562332](https://github.com/AldinAga/ald1n-project/actions/runs/41744562332): **focused62 tests/792 assertions/0 errors/failures/skips**. Full original553/4285 assertions/10 errors/71 failures/81 failed IDs. Full candidate **621/5352/7 errors/71 failures/78 failed IDs**, zero newly failing identities and zero skips.
- Identity accounting is explicit: raw comparison has ONE missing old discovery-error identity, LegacyReadOnlyGuardTest::test_mutating_and_locking_queries_are_blocked. It is replaced by all seven exact original provider data sets#0..#6, each executing and passing. Raw comparison is retained; no identity is silently hidden. CI verifies the exact replacement and records legacy-provider-identity-migration.json. Six extra cases are the difference between one broken discovery placeholder and seven genuine original cases.
- Relative to B1077, exactly TWO existing harness failures were causally eliminated. The third baseline stop-failing, OrderDetailPresenterTest, remains unrelated and is not attributed to this batch.
- Final [browser41744562313](https://github.com/AldinAga/ald1n-project/actions/runs/41744562313): both registered Chromium/Firefox journeys PASS at the FINAL candidate. Per engine: one order, one stock decrement, zero payments/commissions, replay unchanged and staff forbidden. Synthetic activation delivery only, not SMTP.
- Final [canonical static41744562417](https://github.com/AldinAga/ald1n-project/actions/runs/41744562417): **983 checks/0 unsuccessful**, workflow SUCCESS.
- Full workflow remains **FAILURE/HOLD_HISTORICAL_FULL_SUITE_FAILURES:78**. Containing browser workflow remains FAILURE at unchanged B799 guest-success checkout-link expectation. Neither is overall green.

## Remaining78: evidence, not guessed production bugs
The archive contains every failed identity:40 HTTP302,9 HTTP403,3 HTTP404 mismatches expecting200;19 other assertion/source-contract failures;7 execution errors. The52 statuses are symptoms, not52 proven auth defects. Seven errors still include three obsolete DirectSale method expectations, the protected B815 SettingsService::put expectation and three legacy PublicOrderAccess fixtures rejected by current registered-buyer policy. Do not add obsolete application APIs just to hide test errors.

A concrete financial contract conflict was independently verified without changing money logic: [owner Report144](144-MOBILE-V1.0-COMMISSION-ORDER-POLICY-AND-TEST-ORDER-PURGE-BATCH20-V2-20260824-095125.md) records COMMISSION_POLICY=PASS_AUTO_10_PERCENT_NO_CAP_SUPERADMIN_ANY_POSITIVE_MANUAL_SILENT_UI; commit b1ee04451e6526b28fa32412c5258d4b67a73955 deliberately removed the old50EUR cap/manual10% floor. Current main and candidate CommissionCalculator match that source. Three old test assertions still demand the removed rules. Other literal source tests reference decommissioned variant markup/maps. Do not restore forbidden variants or revert accepted financial rules to green these tests. Remaining literal-source mismatches require actual behavior/contract investigation, not blanket waivers.

## Complete remaining release plan
Formal accepted ledger stays **11/17=64.7%;CMS10/15=66.7%;original UI6/6=100%**. Test pass percentages are not owner acceptance.

| Work | Current evidence | Required before closure/release |
|---|---|---|
| M13 public catalog | Unmerged PR131; two real engines, synthetic PNG, responsive sample PASS | Full regression/contract resolution, actual staging/host browser and FPM/OPcache evidence, owner acceptance |
| M14 cart | Strict bounded snapshot and real cart/browser flow PASS in isolate | Concurrent/real-buyer UAT and all shared security/owner gates |
| M15 checkout | Real registered buyer, server-owned identity/price, replay/stock/privacy denial proofs PASS in isolate | Full historical gate, merchant/legal/shipping/payment approvals, real approved registration-mail delivery, concurrency/restore/UAT, owner acceptance |
| M10 first-positive-payment warranty | Earlier scoped source/tests; consolidated B932 remains unintegrated | Existing source integration restrictions remain; no bypass of prior safety denial; integrated real69-case M10/M11 evidence and owner acceptance still missing |
| M11 expiry consent | Freshly verified Draft PR111 head3acc37e90332b0d8fde659503c5cc1a8b5fcc6aa | Integrated/source and owner proof; preserve defaultOFF and original-seller/opted-in-active-SuperAdmin recipient boundaries |
| M17 SDK58 production | Freshly verified Draft PR127 head6c27cd9f5387a3a12fb8381a98e0fac32eb6c3d5 | Exact source/runtime/OAuth/signing/remote versionCode; actual signed APK/AAB16KiB ELF/ZIP/bundletool checks; physical PAGE_SIZE16384 and native Google login success/cancel/error/restart/logout; all required build/submission gates |
| Cross-cutting security/host | Scoped CI is useful evidence only | Independent368-item WSTG/ASVS assessment, SEC23/global CSP, real merchant/legal, backup/restore and dirty-host provenance including B1065 cleanup remain OPEN_NOT_WAIVED |

M16 owner-accepted versionCode28 stays closed; do not rebuild it or assume the next remote versionCode is29. The older preview package is not production SDK58 acceptance. iOS remains owner-deferred until expressly resumed. No partial public activation, test deletion/filtering, guest-buying relaxation or alternative write path around denied changes. Production authorization is conditional on actual gates, not a waiver of them.

The immediate blockers are contract reconciliation under the existing fixture restrictions, independent security/host evidence, real merchant/mail/buyer/device attestations and restricted integrations. Available synthetic CI cannot substitute for those external facts. Do not claim all tasks completed.

## Durable handoff
[Permanent complete archive](https://drive.google.com/file/d/1cn_46eSjOQEHAiEnAGwfs-pIBzFPPjeN/view), SHA256 **ded5a5f9e55ccb384691ee591aadcb981ad2a225c9a51c85b0b8e4ad9295e76f**. It contains both pre-fix/final full JUnit/logs/comparison, final browser screenshots, exact six-file13edc756→692e751e patch and remaining78 identity census. It excludes production secrets, .env, private passwords, database data/dumps and vendor.

Local evidence: D:\GPT\Ald1n-mobile-tools\storefront-qa\b1077.
Current recovery source: C:\Users\ProDesk Mini\.codex\worktrees\storefront-recovery\Ald1n-mobile; existing candidate files are locally uncommitted and preserved. All six new batch files were byte-compared with the pushed source. Original D:\GPT\Ald1n-mobile project/history/handoff remains retained; its separate checkout is not automatically converted into main or PR131. Fresh origin/main docs and PR131 pin distinguish them. No local runtime/QA database started.

All future runtime checks remain on GitHub per owner. Weekly Usage last read14%,ceiling20%; monitor and retain reserve. AIVM topic writes remain blocked by plan/role403 from the prior turn; no repeat or alternate Brain write path. GitHub/Drive are the durable continuation sources. GPT-account data deletion remains limited by available account tools; do not delete the local project, GitHub or Drive.

No production application/source/DB/flags/CloudLinux action, no real customer message/payment, no merge, EAS/native build, OTA, Google Play or iOS publication occurred.
