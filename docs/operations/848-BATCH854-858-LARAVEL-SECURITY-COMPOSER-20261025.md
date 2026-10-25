# Report848 — B854–B858: Laravel public route inventory, critical Composer advisories and CI-only remediation

Date: 2026-10-25 (Europe/Belgrade). Status: **source-only scoped tests PASS; production release HOLD**. No user SSH required.

## Reconstruction and permanent rules

- Fresh GitHub `main` at intake `e85722aa57e8f46e27cc92c2f0ecea85be229a48`, Report847; read root `AGENTS.md`, `docs/operations/000-LATEST.md`, relevant numbered operations and canonical Google Drive `ALD1N-EXPO-CLOUDLINUX-UPDATE-RUNBOOK.md`. Parent PR93 canonical public shop `acb8b9c42ec9254b1a27567abca29a26c1f733f8` remains DRAFT; public storefront remains B798 **OFF**. No duplicate B804/805 batches, no protected B805/B809/B815 fixture mutation or safety-denial workaround.
- Previous latest numbered report 847. This is sequential Report848. The `000-LATEST.md` prefix remains stale (B832); prior direct edit was safety-denied, no bypass. Report848 and immutable GitHub PR/CI are current evidence, not a claim that local CloudLinux docs/operations was inspected.
- CMS Draft PR99 currently `659b247dc744a30ece29c064b92fb2bf70fa2c49` stacked on PR93. Dedicated dependency-repair Draft PR100 currently `bdf3f51e76bfdc684ba2fa1d46c442c96540cb62`, stacked on PR99. Android PR82 separately `6d11b766131ca3cfa946a984b1cc9ed834c00239`.

## B854–B856 — exact Laravel route enumeration, no active security probe

- [PR99 original exact-source CI #40193734313](https://github.com/AldinAga/ald1n-project/actions/runs/40193734313) **PASS** for scoped route census on `6af1e49df4f96d8a7fb284b3f93cc7c90075c264`: PHP8.4 + MariaDB10.11.19 executed `php artisan route:list --json`; recorded **549 registered routes**, including **14 precisely defined public-shop endpoints** with expected method, URI, controller and throttles, and registration guest middleware. The offline parser allows Laravel's exact resolved middleware class names, rejecting missing, spoofed, duplicated or unexpectedly added public routes. **15/15** adversarial parser unit tests PASS. This is SEC01 route inventory and partial SEC04/SEC21 source evidence, NOT auth/CSRF/browser proof.
- Full route JSON, parser, source and audit archive on [Drive](https://drive.google.com/file/d/1mOUnGfdKcZ6fk3NruKj4svS9czM4pv3e/view?usp=drivesdk), readback confirmed 35,394 bytes. In this batch no standalone CSRF active probe attempted (earlier edit was provider-denied).

## B857 — real PHP advisory discovery and fail-closed result

- [Original Composer lock audit](https://github.com/AldinAga/ald1n-project/actions/runs/40193434750) and [readback-verified Drive archive](https://drive.google.com/file/d/1Ydq3c8DdBdH47et-camGPez6-LM5R7MJ/view?usp=drivesdk) proved **19 distinct advisory records** across 5 packages: 1 critical, 10 high, 5 medium, 2 low, 1 without specified severity.
- Packages and old locked versions: `laravel/framework` v13.20.0 (3 advisories including critical TokenGuard); `league/commonmark` 2.8.3 (12); `guzzlehttp/guzzle` 7.15.1 (2); `league/flysystem` 3.35.2 (1); `symfony/http-foundation` v8.1.1 (1).
- Advisory presence establishes affected package versions, not automatically an exploited app or confirmed reachable attack. Initial workflow correctly FAILED/HOLD. A parser false-negative was repaired: Composer 2 clean result uses `{"advisories":[],"abandoned":[]}`; empty arrays now count as zero while nonempty unknown arrays still fail CLOSED. **14/14** B857 adversarial parser regressions PASS.
- 19 old production-version advisory matches remain a production patch priority until a verified deployment/restore/release gate, despite zero findings in the Draft branch below.

## B858 — verified, bounded PHP security remediation on source-only Draft PR100

- [Initial isolated B858 solver + tests CI #40194083864](https://github.com/AldinAga/ald1n-project/actions/runs/40194083864) **PASS**. Restricted Guzzle to patched **7.x**, not unreviewed major 8. The solver updated **51** Composer lock entries as needed, left application code and `composer.json` unmodified; Composer audit returned **0 advisories / 0 abandoned**, RC 0. Real synthetic MariaDB targeted suites: B843 buyer checkout replay **11 tests / 271 assertions PASS**; B836 private receipt **15 / 78 PASS**; B847 catalog/media **15 / 49 PASS**; sum **41 tests / 398 assertions PASS**.
- Version upgrades: Laravel **v13.20.0 → v13.38.0**, CommonMark **2.8.3 → 2.10.4**, Guzzle **7.15.1 → 7.16.0**, Flysystem **3.35.2 → 3.36.0**, Symfony HTTP Foundation **v8.1.1 → v8.1.9**.
- The CI runner checked exact branch source and changed-file allowlist, verified lockfile-only mutation and pushed commit `703e657e205ec906ef8e49455d3250b05779f084` to **Draft PR100 only**. Locked Composer file Git blob `e945b577dbaebbe172ace98fa7776f115b19394d`, SHA-256 `498f133764970cf8250688a31c0c44f6459d433f89cd2577cf10df3b0b82c636`. [Full original signed-source CI artifact on Drive](https://drive.google.com/file/d/1kzSWSZ45Ihlc5aDdC9lX16-B-f5e02zd/view?usp=drivesdk), exact file size 48,689 bytes confirmed.
- Added documentation-only commit `bdf3f51e76bfdc684ba2fa1d46c442c96540cb62` without modifying the lock. [Exact-head repeat CI #40194344706](https://github.com/AldinAga/ald1n-project/actions/runs/40194344706) **PASS again: 0 advisories/abandoned and 41 PHPUnit / 398 assertions**; no unnecessary second lock commit (`B858_LOCK_ALREADY_CURRENT_NO_PUSH`). [Exact-head route inventory and Composer SCA #40194344742](https://github.com/AldinAga/ald1n-project/actions/runs/40194344742) both jobs **PASS**. [Exact-head CI proof archived on Drive](https://drive.google.com/file/d/1uikhttidysQUj9aTtHwM3MLYAixI091S/view?usp=drivesdk).
- **Neither PR99 nor PR100 was merged.** Production Composer is NOT verified patched; no CloudLinux deployment, PHP-FPM restart, migration, SMTP, real customer/order data, OTA/EAS/Play or SSH command executed.

## Release and security HOLD remain binding

- Historical full B803 baseline/candidate still has 56 distinct new failing test identities (Report847) with protected B805/B809/B815 paths; all remain release HOLD, not silently waived. The latest exact-head global B803 for PR100 may still be running and is not a global PASS claim.
- Initial B844 inventory has **368 OWASP WSTG4.2 + ASVS5.0.0 L1/L2 control IDs**, all individually UNASSESSED/NOT_RUN; none are marked fully verified PASS. Route inventory, patched package audit and synthetic regression do **not** constitute comprehensive penetration testing. Independent active/staging tests, buyer UAT, merchant terms, actual SMTP sender, safe backup/restore/FPM, full exact-source CI and approved rollback remain prerequisites.
- Android M16 PR82 previous 16KB emulator offline PASS, but current longer native CI #40192813557 was still IN_PROGRESS at last checked stage (debug APK/ELF validation). No fresh signed EAS production AAB with remote versionCode above 27, signer provenance, physical 16KB UAT or Google Play approval. Expo SDK58 (M17) remains deferred.

## Formal owner acceptance ledger

- **Program: 7/17 = 41.2%**, CMS **7/15 = 46.7%**, original UI **6/6 = 100%**. Source-only security repair is meaningful but not a new accepted production milestone. No invented weighted work-completion percentage.

BATCH_RESULT=PASS_SCOPED_STATIC_AND_DEPENDENCY_PATCH_SOURCE
SOURCE_MUTATION=DRAFT_PR99_CI_TESTS_AND_PR100_COMPOSER_LOCK
COMMIT_CREATED=703e657e205ec906ef8e49455d3250b05779f084
PUSH_COMPLETED=YES_DRAFT_ONLY
SOURCE_COMMIT=bdf3f51e76bfdc684ba2fa1d46c442c96540cb62
BUILD_CREATED=NO
GOOGLE_PLAY_ACTION=NONE
PRODUCTION_PROMOTED=NO
OTA_REQUIRED=NO
NEXT_ACTION=INDEPENDENT_B858_SECURITY_REVIEW_FULL_B803_RECONCILIATION_ANDROID_M16_RELEASE_GATES
