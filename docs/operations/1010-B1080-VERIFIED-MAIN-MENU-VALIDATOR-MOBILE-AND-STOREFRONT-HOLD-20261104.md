# Report1010 — B1080 verified main integration and storefront safety hold

Date2026-11-04. Owner requested resolving failures and integration into main, weekly Usage <=20%. Batch1080 differs from sequential report1010; latest main/local maximum1009 checked. Runtime verification GitHub-only; local project runtimes remained stopped.

## Completed and merged into main

1. **PR130 Windows/POSIX validator**, tested head89f13dc0bdb124498845e6656127ea62db9836b1, [GitHub41784996853](https://github.com/AldinAga/ald1n-project/actions/runs/41784996853). Both jobs SUCCESS: actual complete-validator3-case Windows/POSIX/forbidden-consumer regression, TypeScript, validator0FAIL and existing MariaDB integration. Merge0b73ce6eed7fe671b960693fbe74a7db36fdc47f. No check disabled and no package/lock change.
2. **PR129 Laravel mobile-browser menu**, tested head af346f30b6693a7715ec2d4999f42d99b5c1a293. Current-main synchronization had exactly one conflict: operations000-LATEST. Resolved by retaining current operations plus accurate menu pointer, keeping all three menu application/browser files byte-identical to prior f814c3bf. All9 fresh PR workflows SUCCESS, including [LiquidGlass41785139491](https://github.com/AldinAga/ald1n-project/actions/runs/41785139491): PHP8.4/Laravel13/MariaDB and actual authenticated Chromium seven-viewports/dark-light-auto. Mergeba8cabefeccb9ca299e67a7669115bc3381654a4. Private temporary Git index outside worktree; dirty source/index untouched. Initial commit-tree stopped for missing local author identity before commit/push; corrected using per-command Codex attribution, no global config edits.
3. **PR132 current-main mobile contracts**, tested head150cb93474e84275d94713739d81f191bbcde2f4, [GitHub41785641219](https://github.com/AldinAga/ald1n-project/actions/runs/41785641219). Full actual baseline309tests/305PASS/4FAIL versus candidate **309/309PASS/0FAIL**, zero skipped/cancelled/todo/new failed IDs. TypeScript, existing full validator0FAIL and MariaDB integration PASS. Validator-path job deliberately not applicable to this head and skipped by its original PR130 condition; its real3-case PASS is separately evidenced above. Merge **91b2b647a58cef5ce26171b6289eadf9f858ae1d**. Main delta reviewed and git diff --check PASS.

PR132 retains newer main test bodies and all their assertions/identities. Three title assertions now accept dated sequential batch headings as well as original undated headings; the fourth failure was an exact old nodevenv wrapper string contradicted by already-approved B1012 independent ALT Node22/PATH. It now checks the exact approved ALT binary/PATH, npm CLI, pinned EAS25, all existing iOS preflights and production fail-closed contracts. No runtime application/dependency/release setting changed.

**Detected preparation mistake, not concealed:** older storefront lineage has222mobile tests, current main has309. First main-target preparation transplanted two older test bodies and incorrectly expected222; [run41785292520](https://github.com/AldinAga/ald1n-project/actions/runs/41785292520) caught this (baseline309/4FAIL; invalid intermediate candidate309/2FAIL including one new identity). It was NEVER merged. Original newer test bodies were restored, only three stale contract files minimally reconciled, workflow requires309 and exact3-path delta, and final independent309GREEN preceded merge. Do not transplant old branch tests/config over newer main. Original failed run remains available.

## Storefront candidate: four mobile failures fixed, 75 PHP identities remain

DraftPR131 final tested application head **d5d7a62cf8e2a6b374bc93bbdc131b23a9b5ae18**, still stacked on PR93 acb8b9c42ec9254b1a27567abca29a26c1f733f8, **NOT merged or deployed**.

[Actual GitHub41784834001](https://github.com/AldinAga/ald1n-project/actions/runs/41784834001): storefront-lineage full mobile222baseline218PASS/4FAIL =>candidate **222PASS/0FAIL**, TypeScript/validator/OpenAPI PASS. Those old forensic tests now read their unique existing archived CLI25 record; all original assertions remain. This is independent of main's309-case reconciliation above.

Same run backend: original553cases/10errors/71failures/81failedIDs versus candidate640cases/7errors/68failures/**75failedIDs**;0new,0skips,rawmissing3 still explicitly mapped as Report1009 (provider-discovery identity=>7original datasets;2owner-confirmed commission renames). Focused86/951assertions PASS. Overall workflow FAILURE/HOLD_HISTORICAL_FULL_SUITE_FAILURES. MobileGREEN is not a PHP/full commerce/release acceptance.

Reproducible classification from raw unchanged JUnit/comparison:
-40HTTP302,9HTTP403,3HTTP404 where tests expect200.
-16other assertion failures, including forbidden variants, obsolete DirectSale catalog ceiling, moved/delegated source markers and renamed UI text.
-7execution errors:3obsolete reflected DirectSale price-cap methods; protected B815 calls nonexistent SettingsService::put;3capability fixtures rejected by registered-buyer issuance policy.
These are75 **test identities**, not75 proven production application defects.

Evidence: many B799/B803/B806/B808 fixtures explicitly require anonymous checkout, forbidden by owner-accepted registered/active/verified/activated-buyer policy. B809 and B811 manually created active buyers omit verified/activated fields; actual registration/activation flow passes separately. B818 fixture accesses a receipt without establishing the required authenticated buyer. Do not relax application gates, reintroduce guest sales or add obsolete APIs solely to satisfy these fixtures.

DirectSaleMaxUnitPriceContractTest expects catalogUnitPriceRsd/assertSalePriceWithinCatalogUnitPrice plus price ceiling. Existing Build20 deliberately supports actual-currency DirectSale without catalog ceiling (e.g.Report521). Do not restore that retired ceiling. Variants remain permanently decommissioned. Assertions mentioning variant UI/maps or normalizeVariantTable cannot be implemented as new active variant support.

**Specific unresolved constraint:** root AGENTS0J/0K/0L and prior automatic safety rejection preserve B805/B809/B815 historical fixtures and forbid rewriting/skipping/waiving them or repeating denied integration through another path. This turn did NOT retry the denied fixture rewrite, add a SettingsService alias, suppress tests, or merge PR93/131 despite red full suite. A separate review of exact historical policy/test conflicts remains required before a full registered-commerce integration can be approved. Generic “fix all” is not evidence that these explicit safeguards have been safely reconciled.

## Fresh-main assembly, preserve newer work

Main currently does not contain public-retail implementation/test lineage from PR93; differences shown as removals in PR93->main are not actual new deletions by this batch. Main also has later warranty recovery/push/payment changes, updated309mobile regressions and independent ALT Node22 wrappers. Never replace main with an old PR tree or force ref.

Next complete assembly must preserve fresh main and layer reviewed PR93+PR131 source deltas, resolve genuine authorization/registration/session/payment conflicts, retain all protected historic cases and all newer main warranty/push tests, run complete original/main/candidate evidence and registered browser/concurrency/security checks. PR111 warranty and PR127 SDK58/npm12/EAS27 remain separate refs, not inferred included in main or PR131. Port B1079 positive manual commission/default10% changes into final mobile assembly before SDK58 QA/build. Do not run builds merely because source tests are green.

Local recovery HEAD remains acb8b9c42ec9254b1a27567abca29a26c1f733f8 with preserved tracked/untracked candidate files. Its original50status entries were not reset, cleaned, rebased or broadly staged. Main merges are remote; local origin/main refreshed. Original D:\GPT\Ald1n-mobile migration/history/handoff remains preserved. Fresh snapshot/evidence paths are recorded in local checkpoint receipt.

## Evidence, mutation, and remaining scope

Storefront final backend artifact13300614057 SHA256efb7c567126b581c9b390489b25e83e36f62f8ffe34946213bbd9f3988719bcd.
Storefront mobile artifact13300353671 SHA256ac1fa8375cb38101bf63172eefb873b230daaadd3382a81002d6b2f1bcb584a7.
Main309RED/GREEN artifact13300479731 SHA2564aaea6071ad320ebd35c4506f56cbf160b7b5fef3bd565a62da738d7b38b8b96.
Local external evidence D:\GPT\Ald1n-mobile-tools\storefront-qa\b1080 includes raw ZIPs and all75classified identities. Permanent aggregate archive/readback receipt appended after upload; synthetic CI only, no production secrets/dumps/dependency caches.

SOURCE_MUTATION=yes; COMMIT_CREATED=yes; PUSH_COMPLETED=yes; MAIN_MERGED=PR129/130/132; STOREFRONT_MAIN_MERGED=no; PRODUCTION_PROMOTED=no; EAS_CANDIDATE_PUBLISHED=no; FAILED_STAGE=75PHP+protectedpolicyfixtureconflicts+fullassembly/releasegates.

No production hosting access/deployment/FPM/OPcache/DB/schema/data/flag/SMTP change, no local runtime, no EAS/native/OTA/Play, no force reset/clean/push, no release bypass. All Report1008/1009 remaining M10/M11/M13/M14/M15/M17/security368/SEC23/merchant/legal/mail/concurrency/restore/dirty-host/device gates remain open. Menu/validator source merge is not physical production UAT. Formal accepted11/17=64.7%,CMS10/15=66.7%,UI6/6 unchanged. Weekly Usage last15%,ceiling20%; no reset redeemed. Prior brain capture403 remains, no unchanged retry or brain-save claim.

## Permanent archive checkpoint

[Complete B1080 evidence](https://drive.google.com/file/d/1kWtrvv6rrA6s2fG2FH8EUaiIDDdQsFVf/view), SHA256 **b4c11b4b38f2febdae27df53b06b7ca96b356a82e90ebc95d2f2e5d6378eff8b**. Archive includes preceding report revision before this receipt. All original artifact hashes retained.

## Final integrated-main verification and subsequent CI-only merge

After PR129/130/132 and the new current Report1010 heading were present together, [actual run41786165779](https://github.com/AldinAga/ald1n-project/actions/runs/41786165779) on54431899c40008eb4178d5ccdbcfd85e4a899609 independently passed both applicable jobs: **main mobile309/309PASS**, TypeScript, full validator0FAIL, baseline309/305/4FAIL comparison, and MariaDB integration. No skipped test cases. PR130-only validator-path job is not applicable here; its prior3-casePASS is retained. No new app changes were made for this verification.

Local gh manual dispatch was unavailable (not authenticated); no key was requested/read/copied. A single-line CI-only PR133 enabled the connected GitHub PR trigger for this final application tree; final run passed before merge. PR133 merged as **dc9adf6a6f8a06928f922b9b7d771f71a9001bef**. Git diff from tested54431899 to this main across apps is empty; whitespace check PASS. Documentation-only checkpoint updates after it are not app drift.

[Permanent menu Chromium evidence](https://drive.google.com/file/d/1nEFSirjX2dwGyVi6gIewFOCQjAcQfiTr/view) SHA256ddb46ecf88c43f6c7b72ee7cc87817054f6feaec4448e6a3063e585b2cb6574d. Actual browser artifact is from PR129 combined merge refdff64fe8f40f4bdc8a7100af985e9e421d96077c for head af346f30, including current-main validator; historical report's head reference denotes the PR source, not a claim that merge-ref checks run directly on head.

[Permanent final integrated-main309 evidence](https://drive.google.com/file/d/1dc9xgXKLtkY4Ltiu6v1pWvUAlft75DLq/view) SHA256083fa8a23c03e69da8a8f703643330ae4d1fd404373425a9c5e6dacd54b0d288. Full TAP and real exits retained. Original core archive predates this final verification; these linked artifacts supplement it, never replace earlier RED.

**Result:** menu129, validator130 and mobile contract132 plus verification133 merged; commerce93/131 and SDK58 are not claimed merged. No production release. All75 commerce PHP identities remain and must be reconciled under the existing safeguards. Main309GREEN is a scoped mobile result, not all-project/full-CMS certification.
