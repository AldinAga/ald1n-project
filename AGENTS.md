# AGENTS.md — Ald1n CMS & Mobile execution guardrails

This file is the canonical working contract for automated/agent-assisted changes in the **Ald1n CMS & Mobile** repository.

Its purpose is practical: prevent repeated recovery batches caused by known CloudLinux, Expo/EAS, Git, shell, validator, and release-environment edge cases.

**Read this file before preparing or executing any batch.** If a historical operation report conflicts with this file, first determine whether the report documents a newer intentional change. Do not silently override a newer approved rule.

Last consolidated: **2026-10-31** (owner M16-first priority, one-command noninteractive safe batches and authenticated B1016 PASS; older release guardrails retained).

## 0Y. Storefront-only priority and owner-approved phased OFF sequence (2026-11-04)

The owner's latest primary objective is finishing the functional Storefront. Other Mobile fixes, optimizations and broad audit expansion are retained as follow-up work, not parallel implementation priorities. Maximum70% Weekly Usage remains binding; preserve a reserve for verification and handoff.

After reviewing the sequencing conflict in0O/0T, the owner explicitly answered **Da, fazno uz isključenu Prodavnicu** to: verified source into main → controlled installation with Storefront OFF → actual hosting/mail/buyer tests → public activation only after all obligatory conditions. This expressly supersedes the earlier blanket timing restrictions in0O/0T/section37 to the extent that they demanded host/provider facts before any source integration or controlled OFF installation.

1. Source phase: finish exact-candidate functional/security regression checks and independent review before source integration. Retain protected fixtures, complete test identities, real return codes and all raw findings. Record unresolved global/provider/hosting controls explicitly as HOLD; do not relabel them PASS or claim global security clearance. Do not override GitHub branch protection or force a merge past required checks.
2. Controlled installation phase: deliver one root-relative package with executable artifacts only in incoming/ and numbered reports only in docs/operations/. Verify integrity automatically. Preserve existing source/private files, .env, uploads and working backup evidence; keep Storefront/checkout closed. Fail closed on unexpected source/vendor/config/DB state. The owner performs manual upload/SSH; no FileZilla control.
3. Hosting/UAT phase: obtain actual installed-source/vendor/schema, effective HTTPS/FPM/config, real approved merchant/sender/mail and controlled registered-buyer/operator evidence. Do not forge stored approvals or substitute synthetic GitHub results for provider/host facts.
4. Activation phase: all mandatory security, dependency, secret-adjudication, legal/payment/shipping, backup/rollback and UAT gates still apply before public opening. This sequencing approval does not waive any control, authorize automatic activation, production DB migrations, banking/refunds, EAS/OTA/native builds or Play submission. Any migration or activation needs its separately reviewable approved operation.

The requested final Cloudflare security-audit-skill pass remains after safe functional release/operation. Reviewed upstreamc1c8a8c1471069fb0e188eeaff69b8e8db6564a8/SKILL blob92178dad304d3f63e37582a297026a7874e12c62; no full run has yet occurred. Keep the GitHub-only execution rules and independently enforce the skill's isolation/verification requirements when that stage starts.

## 0X. Owner deep-audit scope and maximum70% Weekly Usage (2026-11-04)

The latest explicit owner goal restores a consumption ceiling: **do not exceed70% Weekly Usage**. This supersedes0V's prior ceiling withdrawal; older20/50% caps remain historical. Read actual account usage through the read-only application limit tool, not token-count estimates; recheck during substantial work and before new expensive dispatches. At the initial read for this instruction the actual weekly use was40% (10080-minute window). Keep a conservative reserve before70% for stopping dispatches, checkpointing and handoff; do not consume a reset or buy credits without separate explicit approval. Do not mark the goal complete merely because the budget boundary is approaching.

The owner requests a deep audit of the **complete monorepo and its Git history from the first commit through current main and relevant unresolved branches**, followed by fixing and safely integrating proven changes. Establish a versioned coverage/issue map: history/branches, live entry points and cross-client contracts, auth/ownership, registered catalog/cart/checkout, commission/payment/inventory/warranty flows, background tasks, dependencies, runtime/configuration, tests, deployment/rollback and concrete performance risks. Distinguish inventory from actual line-level review, current defects from obsolete/superseded work, and proposed cleanup from proved unused code. Do not claim an entire history or security framework was audited merely from file/commit counts or green tests.

Use installed ECC production-audit, change-impact and Laravel security/TDD/verification with scoped Subagent Driven work; no new external audit-service upload or unreviewed scanner/skill installation is implied. Audit may inspect historical code/metadata and reconcile open PRs; do not delete branches, historical reports or existing files, rewrite history, restore retired product behavior, weaken protected tests or bypass release gates. Keep only **one active product-source implementer**. B1116 is frozen while its exact-head GitHub runs are queued and its reviewers are read-only; the coordinator may explicitly transfer the one writer lease to a scoped independent branch, preserving B1116 bytes and recording the new owner/scope. A frozen implementer must not resume source edits until the lease is returned. This permits test-first work on independently confirmed defects without concurrent source writers or weakening any acceptance gate; independent audit agents remain read-only.0W's working-backup attestation and stop on repeated private-backup investigations remain binding. GitHub-only runtime, source preservation and no unauthorized production/provider/EAS actions remain unchanged.

## 0W. Owner confirmed working backup and prioritizes current development (2026-11-04)

The owner identified **backup174-offsite-main807f4bc8.tar.gpg**, said it is saved locally and on Google Drive, and explicitly confirmed it works while directing the agent to continue development instead of revisiting past backups. Credit this working-backup owner attestation; stop repeated backup-folder questions, Drive searches, private archive downloads, and replays of completed B955/B959 backup/restore work. Safe current source/test development must not wait for discovery of an old default hosting backup folder. Preserve all existing copies. Do not invent a new independent checksum/fresh restore verification from the owner's statement, and do not create/prune/delete backups or run production changes incidentally. A genuinely new deployment-specific condition must be explained concretely rather than reopening settled history by default.

## 0V. Owner withdrew previous Usage cap and requested Subagent Driven continuation (2026-11-04)

The owner explicitly instructed: **Zanemari prethodni limit koji sam ti dao, samo teraj dok ne dovršimo sve započete zadatke Subagent Driven**. This withdraws the earlier20%/50% Weekly Usage ceiling for ongoing Ald1n work. Continue authorized safe tasks with the installed Subagent Driven workflow and independent reviews; do not stop merely because the old percentage ceiling is reached. It does not authorize purchasing credits, consuming a reset, destructive actions, bypassing release gates or production/native changes. Existing GitHub-only runtime, manual hosting handoff, protected-test, security and deployment boundaries remain binding.

## 0U. Owner finalized existing CMS merchant settings (2026-11-04)

The owner explicitly answered **Postojeće CMS postavke su konačne** for terms of sale, privacy, shipping price/conditions and contact email. Treat the existing CMS values as final approved content; do not ask this same business decision again or silently replace them with environment defaults. Public pages must consume the existing shared merchant policy service, including registration privacy links, with its established stored-value precedence and configuration fallback.

This content decision is not permission to forge/update a policy approval digest, approve an email sender, enable storefront/checkout, waive security/backup/UAT gates or deploy an unmerged Draft. Actual stored values, approval freshness, correct page rendering and live mail/host readiness still need evidence. Never request passwords to verify this decision.

## 0T. Owner rule: ready manual-upload ZIPs mirror the project root (2026-11-04)

The owner explicitly requires: "napraviš sve subfoldere za svaki fajl ... unzip u /home/icaffeco/ald1n-project ... svaki fajl legne na svoje mesto". Whenever a manual upload is genuinely needed and the delivery has passed its gates, provide the actual ready file and exact destination immediately; do not wait for another request and do not control FileZilla.

Application-delivery ZIP entries MUST be relative to **/home/icaffeco/ald1n-project**, preserving every actual subdirectory, with NO extra enclosing delivery folder and NO flattened filenames. Example entries: apps/cms/current/public/assets/css/ald1n-ui-refinement.css and apps/cms/current/resources/views/layouts/app.blade.php. The owner can extract one ZIP directly into the canonical project root. Include only the explicitly approved payload files; instruction/manifest sidecars must not create unintended files in that extraction tree.

Before delivery, verify the exact allowlisted entry set, reject absolute/drive paths, traversal, backslashes and duplicates, and verify each archived file against the approved source SHA256. Preserve existing local packages/files and required backups; provide clear replacement scope. No production secrets, .env, DB dumps, caches or unrelated source. Existing section1A remains binding: operator executable scripts/helpers belong ONLY in incoming/ and generated reports/logs ONLY in docs/operations/. Tracked application source stays at its actual application path.

Packaging is not permission to deploy an unapproved Draft, activate flags, run migrations or bypass release gates. A ready/delivered ZIP is not evidence of hosting application; record application only after owner confirmation. The 2026-11-04 replacement menu ZIP contains ONLY the two already-main/QA-approved menu files with correct root-relative directories; the previous flat ZIP is preserved but is superseded for manual delivery.

## 0S. Reconciled original acceptance and completed deployment authority (B1095, 2026-11-04)

The original [B1044 receipt](docs/operations/B1044-M17-OWNER-ACCEPTANCE-M10M11-MERGED-80CASE-PASS-20261102.md) and [Issue124 owner acceptance](https://github.com/AldinAga/ald1n-project/issues/124#issuecomment-6585783961) outrank later summaries that accidentally reverted to 11/17. Correct formal acceptance is **12/17 (70.6%)**, CMS10/15, UI6/6. M17 is owner-accepted functional CLOSED; this correction is not new CI acceptance. Signed AAB versionCode29/source6adb3b440b8cfdc068b142a98c418d9d91487f64 and original 16KiB audit are archived by B1044. Physical page-size output, individual device/OAuth matrix and Play rollout/signer continuity are not independently evidenced; do not invent them or repeat a native build. Native SDK58 source remains on its distinct release branch; do not describe it as merged into current SDK57 main.

PR113/115 source integration, B962 twelve-file CloudLinux deployment/additive recovery migration, OPcache/FPM probes and B955/B959 restore evidence are already completed. Do not rerun them because PR111/127 Draft bodies or stale later summaries say pending. B1044 actual merged-source 80tests/586assertions PASS is source/provider-simulation evidence, not external provider delivery acceptance. M10/M11 formal business acceptance remains OPEN for independently authorized real SMTP/Expo/operator delivery/recovery UAT; alerts/recovery stay default OFF. Remaining formal functional tasks: M10,M11,M13,M14,M15. Preserve security/merchant/legal/current-host/source/backup/release gates; no production or native action follows from this correction.

## 0N. Final public registration and manual-operation authority (owner2026-11-04)

The original guest-buying rule was explicitly withdrawn. NEW public purchases require a registered/authenticated Kupac, with existing active/non-retired/verified-email/activation/tracked-session guards. Mandatory signup fields are first_name, last_name, address, city, phone and email. Preserve privacy acceptance, Turnstile, approved sender and one-time activation; no incidental password/role/approval change. Do not silently add postal code or another mandatory field.

The owner explicitly confirmed there are NO old orders without accounts. Do not create a legacy anonymous-order/receipt compatibility path or issue new anonymous capabilities. Public browsing/cart may remain anonymous; checkout and private order access remain bound to the legitimate registered owner.

The owner makes final business decisions where a repeated unresolved policy is genuinely unclear; the agent handles routine technical choices and should not re-ask these settled rules. The agent must NOT control FileZilla. When manual hosting work is actually needed, provide the exact files and canonical destination paths, or one tested integrity-checking SSH command under the existing incoming/report rules. The owner performs upload/SSH. Do not use that workflow to bypass a denied integration or protected-test safeguard.

Owner manually uploaded the verified PR129 menu CSS/Blade; public cache-busted CSS SHA2563a6176d2a049588797506b4d3b1e97e5aaef9e49625f3c9be7d9245127b51954 is independently confirmed live. Actual phone visual UAT and server Blade readback are not independently attested. Backend commission is verified in mainPR134 but not production-deployed by the agent. Required6field registration implementation is being TDD-verified on unmerged DraftPR135, not a deployment claim. See [Report1011](docs/operations/1011-B1081-B1083-COMMISSION-MAIN-STOREFRONT72-MANUAL-MENU-AND-REGISTRATION-20261104.md).

---

## 0M. Owner commission authority — automatic10%, positive manual override (2026-11-04)

**Latest explicit owner clarification supersedes earlier contradictory minimum/maximum wording:** automatic commission is10% of the product price converted to EUR ONLY when no manual amount is entered (omitted/null/blank). A valid **positive** manual EUR amount overrides that default and may be LOWER or HIGHER than10%. There is NO10% manual floor and NO fixed50EUR business ceiling. The owner separately confirmed that manual0 is NOT allowed: an explicitly entered0/negative amount must be rejected as input, not interpreted as a request for automatic10%. Preserve existing currency precision and technical storage/range bounds; do not introduce a percentage ceiling.

Keep existing SuperAdministrator-only override authorization, public-retail zero-commission isolation, currencies/rates, identifiers and already-issued order snapshots. Do not retroactively rewrite money/order data or reintroduce variants. The earlier chat phrase “min10%” is explicitly superseded by the owner's subsequent clarification. Old unit expectations of a50EUR cap/manual10% floor are stale against this rule; their narrowly scoped reconciliation must retain raw before/after evidence and explicit test-identity accounting, without touching protected B805/B809/B815 cases or waiving any release gate.

B1081 independently ported the8commission application/contract paths onto fresh main and merged PR134 at1c185c6c5a97d3917069c8d73ad4d2df781c0e7c after real24/159assertions GREEN (same24 baseline10FAIL),309mobile/TSC/validator,983static and all10workflows PASS. Calculator/OrderService/old snapshots/locks untouched. This is source integration, not production backend deployment or acceptance of a remaining milestone. Full commerce remains unmerged DraftPR135 with72historic failed IDs at the last complete run; all release gates stay closed.

---

## 0L. B1074 exact retirement CI database identity and 80 remaining historic failures (2026-11-03)

**Superseding TEST-INFRASTRUCTURE outcome (not application release):** [Real GitHub Actions B1074 #41718864135](https://github.com/AldinAga/ald1n-project/actions/runs/41718864135), [permanent one-report GitHub record](docs/operations/B1074-REGRESSION-ROOT-CAUSE-AND-CI-20261103.md), [single canonical Drive owner report](https://drive.google.com/file/d/1Iz2JKuYuhgZbWPLtTAlzKw-AXUJkONF0/view), [all original JUnit/logs/comparison ZIP](https://drive.google.com/file/d/1biSPyNKP1vd2Px1ygFZdxMv6Rte0QQFx/view) SHA256 `fc430d94cfbfca488e4cf668e6ad3ba1bfe9262e3a5607533cea3e45853e42fd`. In PR93 `UserRetirementMySqlIntegrationTest::setUp()`, **18/18 former CI failures were caused by `DB_DATABASE=ald1n_test` violating its exact hard safety gate `DB::getDatabaseName() === 'ald1n_retirement_ci'`**. Do not alter or bypass that gate, and do not interpret the 18 historical failures as failing retirement business logic. B1074 creates **two disposable and independent MariaDB10.11.19 CI containers** each with the exact `ald1n_retirement_ci` database; verifies their individual identity with `SELECT DATABASE()` and runs all tests unfiltered. Do NOT ever connect retirement tests to any production or existing non-disposable schema.

**Real B1074 proof:** original unchanged PR93 full `553 tests / 4285 assertions / 10 errors / 71 failures / 81 unique failed identities`; patched exact PR93+B1071+B1073 full `564 / 4570 / 9 / 71 / 80 unique failed identities`; **no skipped tests, 0 newly failing identities**. Explicit retirement targeted test `18/18, 176 assertions, exit0`; real B1073 HTTP-MariaDB replay zero-write `9/9,261 assertions,exit0`; B1071 unit `2/2,15 assertions,exit0`. Same 18 retired test identities also now pass in FULL suite. Relative to previous B1073 patched full **98 -> 80** problems, **18 eliminated by fixing only CI database identity** (18/98=18.4% of then-failing identities), not a change in the owner's formally accepted delivery. **All 56/56 historical B803 failed identities still execute and still fail** within the remaining 80 (subset, never sum as 56+80). Global workflow is **FAILURE/HOLD** by `HOLD_HISTORICAL_FULL_SUITE_FAILURES`, not a setup failure or production acceptance. Existing one unrelated `OrderDetailPresenterTest` stop-failing is not an attributed source fix.

**Independent deterministic B1074 JUnit symptom classification of 80 remaining IDs:** **52** HTTP 200 expected vs 302 (40), 403 (9), 404 (3) actual; **19** other assertion/source-contract failures; **9** other execution errors. These are observed symptoms, **NOT 52 proven auth bugs**. Investigate mismatch between older anonymous/guest expectations and registered+verified-buyer policy, without weakening buyer identity/security, disabling protected B805/B809/B815 cases or rewriting historic fixtures to green. Execution errors include obsolete method expectations in DirectSaleService and SettingsService, PHPUnit data provider/bootstrap, and PublicOrderAccessService flow; investigate functional correctness before source fixes.

**Exact CI source:** [workflow](.github/workflows/b1074-pr93-retirement-db-full-regression.yml) SHA256 `58347975f1eeafc5a8fd905823e7dde7739f1831ca9e0c41039c5abbc2221d9b`, source PR93 exact head `acb8b9c42ec9254b1a27567abca29a26c1f733f8`, prior B1071 and B1073 patch SHA pins unchanged. GitHub main app source and PR93 source both unmodified; the workflow is docs/CI only. No CloudLinux action, prod DB/write, storefront ON, deployment or Play/EAS/OTA.

**Formal milestone ledger unchanged:** program **11/17=64.7%**, CMS **10/15=66.7%**, six original UI **6/6=100%**, M14/M15 **0/2=0%**. Full patched suite without failing identity `484/564=85.8%` is strictly a test-case metric, NOT release readiness or owner acceptance. All other B803/368 OWASP/SEC23/CSP, merchant/legal, FPM/OPcache/browser, B1065 cleanup and owner gates remain **OPEN_NOT_WAIVED**. Continue with evidence-first isolation of 52 HTTP status mismatches + remaining 19/9 runtime/source classes in one consolidated source-only B1075, never another generic CloudLinux preflight.

---

## 0K. B1073 real MariaDB Feature no-write PASS / historic full-suite HOLD (2026-11-03)

**Canonical exact evidence:** [permanent B1073 review](docs/operations/B1073-ISOLATED-QA-AND-MARIADB-FEATURE-20261103.md), [real GitHub CI run #41657873121](https://github.com/AldinAga/ald1n-project/actions/runs/41657873121), [complete actual PHPUnit JUnit/log archive](https://drive.google.com/file/d/1NNXSQo0CYx10JS9AX2n74GxpELtlF1qy/view), [machine identity comparison](https://drive.google.com/file/d/1j-P10dP_acz0smr-sxdccvg5UEFoOPi8/view). Clean isolated PR #93 `acb8b9c42ec9254b1a27567abca29a26c1f733f8`; GitHub main application source/PR source/dirty CloudLinux production checkout **UNMODIFIED**. Combined B1071 and B1073 patches applied only in disposable CI checkout. No deploy, payment, migrations on production, FPM restart, EAS/OTA/Play.

**PASS scoped:** B1073 fixes two independently reproduced QA helper issues in source-only candidate: (R04) exact independently supplied complete expected PHP inventory AND discovered inventory AND pinned SHA checks, rejecting even valid subset; (R05) canonical absolute root with every ancestor symlink checked before resolution and symlink-free candidate traversal. Independent local RED on original implementation; **9/9 new manifest cases plus 21/21 existing QA cases PASS** on corrected source. New synthetic actual **Laravel HTTP / MariaDB10.11.19 Feature = 9/9 cases, 261 assertions, 0 failures/errors, exit 0**. Each corrupt replay after first synthetic successful checkout returns 422 cart and has unchanged order/line/attempt/capability/payment/stock-movement/history row counts, unchanged product stock and no intercepted operational DML. New B1071 PHPUnit unit 2/2 15 assertions PASS. [B1073 exact 4-path unapplied diff](docs/operations/B1073-PR93-B1071-QA-MANIFEST-NOWRITE-UNAPPLIED.patch) SHA256 `2db64aeb8ba79c5db30cd16d0a69f1c8b57bd99ac448152b339a7ffc0f350678` and [complete source ZIP](https://drive.google.com/file/d/1P3XUWhBWN72ia5-b-1sqdTG9ul-95_Yg/view).

**Full suite still FAIL/HOLD without waiver or exclusion:** original PR93 unfiltered `553 cases/4127 assertions/10 errors/89 failures/99 failed identities`, patched B1071+B1073 unfiltered `564 cases/4412 assertions/9 errors/89 failures/98 failed identities`, both exit 2. **0 newly failing identities**. One original Unit `OrderDetailPresenterTest::test_presenter_survives_zero_dates_and_uses_only_scalar_view_data` did not fail on patched pass, but no causal source change was made there, therefore DO NOT call it permanently fixed. **All 56/56 historical B803 failed test IDs remain** among the current 98; do not delete, filter, waive or alter protected B805/B809/B815 fixtures. M14/M15 remain NOT_OWNER_ACCEPTED. Original 56 and current 98 are overlapping, NOT additive.

**Mandatory new pre-push CI guard (repeated preflight incident):** first B1073 CI [run #41657605237](https://github.com/AldinAga/ald1n-project/actions/runs/41657605237) stopped before PHPUnit due to workflow referring to a nonexistent B1073 patch basename. Fixed on the next one-line CI-only commit after an independently failing-then-passing pathname contract test. Future workflow MUST pre-validate the exact repository file paths and SHA-256 of every external patch before pushing CI, do not claim initial CI failure is a failed Laravel test. Avoid unnecessary reruns. This does NOT waive the current full-suite red status.

**Formal owner milestone ledger remains:** program **11/17=64.7%**, CMS **10/15=66.7%**, six original UI **6/6=100%**, M14/M15 **0/2=0%**. 9/9 focused Feature=100% scoped test pass, NOT production readiness. All 368 OWASP WSTG/ASVS, SEC23/CSP, legal/merchant, M13 actual enabled Chromium/Firefox and production FPM/OPcache attestation, concurrency/real buyer UAT and B1065 QA cleanup provenance remain HOLD. All StoreFront and checkout production flags OFF.

---

## 0J. B1072 actual PR93 vs B1071 full PHP8.4/MariaDB10.11.19 test result and independent review (2026-11-03)

**This supersedes any implication B1071's local QA constitutes real integration acceptance.** [GitHub actual full dual-run CI](https://github.com/AldinAga/ald1n-project/actions/runs/41653736902) completed with **FAILURE/HOLD**, by deliberate original-history gate `HOLD_HISTORICAL_FULL_SUITE_FAILURES`; not a CI environment/bootstrap error. Original PR #93 exact `acb8b9c42ec9254b1a27567abca29a26c1f733f8` unfiltered PHPUnit 12.5.31 / PHP8.4.27 / isolated MariaDB10.11.19: **553 tests, 4127 assertions, 10 errors, 89 failures, 99 failed identities**, exit2. Same PR93 + exact B1071 **unapplied-in-GitHub-source** 10-path patch, in second ephemeral MariaDB: **555 tests, 4142 assertions, 10 errors, 89 failures, EXACT SAME 99 failed identities**, exit2. New B1071 unit test **2 cases / 15 assertions PASS**, zero new and zero fixed full-suite failure identities. [Raw actual JUnit/logs ZIP on Drive](https://drive.google.com/file/d/152-Ae6ZIphBKmGcFZzfFiKKuWIf_5wgR/view), [identity comparison](https://drive.google.com/file/d/1KxJ7ozuA_s4saWTf9kW8-bzlw75Dv4__/view), [permanent B1072 review](docs/operations/B1072-PR93-B1071-INDEPENDENT-REVIEW-AND-FULL-CI-20261103.md). All historic **56/56 B803 unresolved identities still occur** among same 99, independently compared against [exact unwaived manifest](docs/operations/B1072-B803-56-UNWAIVED-TEST-IDENTITIES-20261103.md). These 56 are a SUBSET of 99, not additional failures; never waive, disable, edit safety-denied B815/B805/B809 tests to turn CI green.

**Independent B1071 source review** exact 10/10 paths byte match and 9/9 PHP lints PASS; M14/M15 replay saved cart guard locally rejects malformed data before *simulated* DB, no newly added true Laravel feature regression proves zero MariaDB stock/order writes. **Two QA SourceManifest implementation gaps remain IMPORTANT HOLD:** its `verify()` can accept a valid 1-file subset of a larger intended source set; and symlink **ancestor of root path** is not rejected (root and descendant-only `is_link` checks). Do not approve full QA/source completeness or merge patch until independently pinned exact expected source list, canonical parent path confinement, and RED/GREEN tests verify both. The strict escaped-underscore QA GRANT validator positive/negative tests pass offline; actual CloudLinux grants NOT VERIFIED.

**CI architecture:** B1072 GitHub workflow `.github/workflows/b1072-pr93-b1071-full-mariadb-qa.yml` started by documentation-only main push at `d849230c00cf1a63c058967521ce11de702cbc5f`. It checked out identical exact PR93 source twice in clean ephemeral workspaces, installed exact Composer lock, preserved all old PHPUnit cases without `--filter`/exclude, compared complete JUnit failing identities, archived evidence and failed closed on 99 inherited problems. Source app on `main`, original Draft PR93 and dirty production checkout all **UNMODIFIED** by B1072. Original PR #111 remains M11 warranty. No CloudLinux SSH, production DB/source, catalog OFF flags, EAS/OTA/Play changes.

**Formal accepted completion does not move:** Program **11/17=64.7%**, CMS **10/15=66.7%**, first six UI **6/6=100%**, M14/M15 **0/2=0%**. All 368 WSTG/ASVS, SEC23/CSP, M13 enabled real-browser / FPM OPcache proof, real buyer/merchant/stock/concurrency and owner release gates remain HOLD. Next work: source-only fix of QA manifest two important gaps and real MariaDB Feature replay zero-write tests; repeat full CI with all historical failing identities preserved. One source-only consolidated report, no repeated CloudLinux preflight.

---

## 0I. B1071 exact PR #93 M14/M15 source-only candidate and test evidence (2026-11-03)

**Source correction (important for future work):** At the independently checked 2026-11-03 snapshot, Draft **[PR #93](https://github.com/AldinAga/ald1n-project/pull/93)** head `acb8b9c42ec9254b1a27567abca29a26c1f733f8` is the earlier M13/M14/M15 registered-buyer cart/checkout **source-only** candidate. Draft **PR #111 is M11 warranty/expiry**, NOT the cart/checkout candidate; ignore older stale documents that link buyer-source to PR111. Always freshly verify both refs before a future batch and never treat the PR #93 head as merged or deployed.

**B1071 evidence-first concrete source progress:** [GitHub operations report](docs/operations/B1071-ISOLATED-QA-M14-M15-SOURCE-20261103.md); [exact UNAPPLIED Git patch on Drive](https://drive.google.com/file/d/1YgqafVA3HaIkx0bruizVHvGaBwX9aVt5/view) SHA256 `510e661167d31e2b6654afbac4809133bda696be066df199a9a1a19b4e84628a`, [full isolated source ZIP](https://drive.google.com/file/d/1RZH9IK4Nyggh3bJsbeub_tNOvEpGC5wh/view), SHA256 `c78a02ae6936b76223ece05306a30650e1ac78a6d8f3e142efebbebc21f195f4`. Exactly **2 modified original PR93 services + 8 added support/tests/QA tools**, never touching B805/B809/B815 rejected fixtures. Pinned original Git blob SHA: `PublicCartService.php=87237d51a372f200e511a7114ae80808d4ba6e2d`, `PublicRetailCheckoutService.php=fb29ad856a5989b8a460a69d702e33b5a28aad45`. Confirm those exact original bytes before any future application, and never git reset/clean/merge the dirty production host.

**Behavior:** M14 replays only a completed matching checkout nonce and a fully validated 1..25-item session cart (positive integer product keys, integer quantity 1..20); M15 consumes that **before** HMAC fingerprint and DB::transaction. Invalid snapshots fail closed. Independent QA validator requires escaped literal SQL `\\_` scope, refuses wildcards/global/roles/grant-option; source manifest fails on 0 files/symlinks/drift. **Local TDD RED on original source, GREEN on candidate:** 17 pure replay, 21 QA, 4 service, 6 source static checks, 4 real PHP M15 pretransaction synthetic tests; separate patch runner reexecutes its own checks, PHP syntax 9/9 PASS. **These do NOT attest Laravel PHPUnit, real MariaDB, browser, FPM or production.** Full original B803 identity and OWASP gates remain open; no owner acceptance of M14/M15, no new hosting script, no source merge, no public flag activation. This checkpoint corrects previous B1069 revoked QA mistakes only in an **isolated local source candidate**, not on production.

**Accepted ledger unchanged** overall 11/17=64.7%, CMS10/15=66.7%, original UI6/6=100%. Local test percentages are **not** formal project completion or launch readiness. [Canonical Drive runbook](https://drive.google.com/file/d/10Lxe_reZ1csYOMK9ESjZ8NUmEglDpOMy/view).

---

## 0H. STOP B1069: confirmed unsafe QA grant parser; mandatory independent negative tests (2026-11-03)

**Latest superseding safety correction. B1069 is REVOKED_DO_NOT_RUN** regardless of the older `0G` note saying it was locally tested. [Independent source audit B1070](https://drive.google.com/file/d/1J1WfBasHhxy9bwE-ly0_ANg1eF6Dr5am/view), [verifiable local evidence](https://drive.google.com/file/d/1k9TbR3eSmfZN8va5tD-lFi_wH_FLIgsj/view) and [strict MariaDB regression](https://drive.google.com/file/d/12O9VF07XRl78JSTwf8njexLj-K_ZZhH-/view). Original archived B1069 file renamed to `REVOKED-DO-NOT-RUN-B1069-M13-M15-RECOVERY-SOURCE-HANDOFF.sh`; preserve its source bytes for forensics; **do not execute it on CloudLinux.**

Confirmed **F-01 HIGH**: B1069's QA grant parser turns escaped `\\_` into `_` prior to comparing `SHOW GRANTS`, falsely accepting unescaped wildcard database-scope grants as QA-only. An original fixture explicitly expected that unsafe input to PASS; a newly independent correct negative test fails against B1069. MariaDB database-level GRANT treats unescaped `_`/`%` as wildcards. Require **literal escaped underscore scope** (same strict behavior already in B1065 `b1065_grants()`), reject global privileges/other database/roles/grant option and never strip scope escapes before checking. **8/8 new pure-logic security tests passed** against B1065's strict original validator. Actual host grants remain UNVERIFIED; this proves validator unsafety, not an account compromise. [MariaDB official docs](https://mariadb.com/docs/server/reference/sql-statements/account-management-sql-statements/grant).

Confirmed **F-02**: B1069 source fingerprint claims PASS for an **empty 0-file** allowlist; independent fixture yielded hash `e3b0c44298...` with PASS. Future readiness checks must demand nonempty exact file census with staged, unstaged, and untracked entries before reporting success.

**F-03/F-04 report architecture:** B1065 originally wrote `Status: RUNNING` before its main work and has no guaranteed final report after out-of-band process termination. The real B1065 owner upload still contains only that placeholder. B1069 `fatal()` prechecks (selfhash/path/user/PHP/tools) occur before report finalizer and can validly exit2 with terminal `B1069_PRECHECK_HOLD` only. **Neither construction proves the real host failure mechanism without actual terminal evidence.** Do not assert B1069 failed on host; no final B1069 operator report was supplied.

**Mandatory design gate for next consolidated batch:** NO new hosting script merely to rerun known OFF probes. First independent negative tests for scope-wildcard, zero-source fingerprint, interrupted process, bad paths, missing extensions, reporting failures and hostile roles. Test real host Bash/PHP constraints as available; require exactly one final atomic human-readable MD report for completed operations, explicit terminal HOLD for failures before trusted report path, and NEVER infer QA schema emptiness from absent process or `RUNNING`. No QA SQL DML/DDL or re-run B1065; no production writes, public activation, Git reset/pull/clean, EAS/OTA/Play. This is a docs-only correction, not a release waiver.

**Last verified host audit** B1068: 24 PASS / 2 HOLD / 11 INFO, 24/26 decisive checks **92.3%**, all 37 stages completed; not proof of enabled storefront. B1063 isolated PHP/MariaDB 13 tests/77 assertions + 74 pure security, B1064 public OFF PASS. Formal accepted milestones **11/17 program 64.7%, 10/15 CMS 66.7%, 6/6 original UI 100.0%**; leave all M13 browser/production FPM/merchant and M14/M15, B803 56, SEC23/368 controls HOLD. Dirty CloudLinux HEAD and untracked source are not GitHub main. Advance source work via reviewed isolated candidate only, not by repeating safety-denied B805/B809/B815 test edits.

---

## 0G. Recurring B1065–B1069 evidence/host audit recovery (2026-11-03)

**Current owner-host evidence, not a waiver:** [Actual B1068 report](https://drive.google.com/file/d/1ttrAbKcdjuYH0qT-X7o80WEA7op2TRQI/view), UTC 2026-11-03T13:11:33Z, completed all 37 audits: **24 PASS / 2 HOLD / 11 INFO**, 24/26=92.3% decisive *audit* checks (not release readiness). 11 exact installed M13 source hashes, seven PHP lints, exactly three read-only routes, HTTPS/HEAD OFF behavior, dirty host status **84 tracked unstaged / 56 untracked / 0 staged** and four protected file hashes PASS. Two specific HOLDs: B1065's on-host report contains only **RUNNING** (no final QA cleanup proof), and B1068's Laravel CLI effective-feature-flags check had ambiguous stdout/warnings. Do not rerun previously completed B1057-B1064 or B1068, do not treat B1065's lack of a launcher process as cleanup proof, and never automatically rerun B1065 against a possibly nonempty QA schema.

**Use the B1069 evidence-first diagnostic approach** (source-only prepared [exact Drive script](https://drive.google.com/file/d/1myY7v8nW-t7fSWOwy-o6g-a3F40xv2RP/view), no host PASS yet): inspect B1065 terminal receipts by exact final outcome/host/QA identity markers; independently inventory **only** the existing named QA MariaDB schema with the dedicated scoped QA login through hidden terminal input, SQL SELECT/SHOW only and **never automatic deletion/reset**. Probe Laravel CLI feature flags as **three separately present strictly OFF values** and emit a structured marker, warning count, warning locations (only sanitized basenames), exception class and stdout/stderr byte counts. A zero CLI exit or HTTP404 alone does not prove runtime config; do not silently ignore warnings or dump raw PHP errors/secrets to reports. Preserve dirty host source; allowlisted file SHA fingerprint is **not** a source export and never includes contents, .env, vendor or storage. No production catalog/checkout enablement, no DB write/migration, Git pull/reset/clean/stash or FPM/OPcache reset. Source work M14/M15 must occur only in independently reviewed isolated candidate with registered verified tracked buyers, stock, merchant/legal and all B803 release gates retained.

**Reporting:** one grouped operator command and one atomic human-readable Markdown report with distinct **audit PASS/HOLD/INFO and formal accepted milestones** (program 11/17=64.7%, CMS 10/15=66.7%, six UI 6/6=100%). Local B1069 12/12 disposable-host scenarios + 6/6 scoped-grant-parser cases PASS, but QA database state / actual host result remain UNVERIFIED until owner B1069 report. Never represent mock fixtures as CloudLinux PASS. [Detailed handoff](https://drive.google.com/file/d/1vn733gWubkqYGnRAQCzjLlIDMiKQcos_/view). 

---

## 0F. B1047 portable catalog patch and cross-browser CI accuracy (2026-11-02)

**Reusable verified lessons (M13 public catalog only; never a release waiver).** The B1047 exact-head [GitHub CI run41538553739](https://github.com/AldinAga/ald1n-project/actions/runs/41538553739) succeeded on PHP8.4/MariaDB10.11 and real Chromium+Firefox across desktop/mobile/compact after these three independently observed harness corrections:

- `git apply` creates **untracked** newly added files. For an exact-source patch path census, combine `git diff --name-only HEAD -- <scope>` (existing tracked modifications) with `git ls-files --others --exclude-standard -- <scope>` (new files). B1047 M13 requires exactly **2 modified + 10 newly added = 12** paths, all compared byte-for-byte to an independent clean checkout. Do not weaken/remove the path census to pass CI.
- Laravel `php artisan route:list --json` may expand a named `throttle:public-shop-browse` / `throttle:public-shop-images` alias into `Illuminate\Routing\Middleware\ThrottleRequests:public-shop-browse` / `...:public-shop-images`. Verify the **same exact expected limiter name** under either representation rather than assuming the short alias is what the CLI displays. Exactly three public M13 GET/HEAD routes; no shopping/write routes.
- Historic synthetic one-pixel PNG fixture reused from older HTTP tests had a malformed **IDAT CRC**; Chromium tolerated its pixels but Firefox displayed image alt fallback. For real browser acceptance use standards-valid synthetic PNG (B1047 generates 320x240 with PHP GD) and assert `img.complete` plus expected `naturalWidth`/`naturalHeight` **in both browsers**, in addition to image HTTP200, MIME, `private`/`no-store`, emergency OFF and zero stock/order writes. Never claim real browser image render PASS from HTTP200 alone.

**Formal release state unchanged:** [B1047 engineering report](docs/operations/B1047-M13-SOURCE-PATCH-CHROME-FIREFOX-QA-20261102.md) and [Issue104](https://github.com/AldinAga/ald1n-project/issues/104) retain original JUnit/patch/browser evidence. B1047 source patch is **UNAPPLIED to active CMS main** and no CloudLinux deploy was authorized. Production public storefront remains OFF, 56 B803 original failure IDs OPEN_NOT_WAIVED, previously denied B815/branch/index writes not bypassed, SEC23/CSP and 368 OWASP/WSTG/ASVS item review open. No EAS/OTA/Play or repeated generic SMTP probe.

---

## 0E. Permanent owner mandate: every Android build must prove 16KiB compatibility (2026-11-01)

**Superseding release rule:** Owner requests the latest stable Nitro Google Sign-In + Nitro core, **EAS CLI27.2.0** and **npm12.2.1** before any next Google Play submission. Current SDK58 source upgrade is staged only in unmerged [PR127](https://github.com/AldinAga/ald1n-project/pull/127) with Nitro Google **2.3.1**, Nitro Modules **0.37.1**, `packageManager=npm@12.2.1`, `eas.json.cli.version=27.2.0`, and new isolated M17 UAT/Play runtime2. This is **NOT a host-global CloudLinux npm upgrade** or EAS production release. Do not run bare `npm` or globally change ALT Node selectors/symlinks on dirty CloudLinux.

**Every future native Android APK/AAB build** (including previews, tests released for device installation and production submissions) must pass mandatory **POST-build verification of its ACTUAL binary**: all packaged 64-bit ELF PT_LOAD p_align >= 16384 and p_offset/p_vaddr congruence, 16KiB ZIP alignment via `zipalign -c -P 16`, verified APK/AAB signatures and SHA256 + EAS exact source/profile/version/versionCode/runtime provenance; signed production AAB additionally requires `bundletool dump config PAGE_ALIGNMENT_16K`, all generated default split/standalone APKs verified for 16KiB and correct Play upload signer. For real 16KiB device acceptance require `adb shell getconf PAGE_SIZE = 16384` and a native Google Credential Manager success/cancel/error/restart/logout test matrix. Successful source/Gradle tests, published npm tags or existing v1.5/preview binary receipts **cannot substitute** for the exact new release's signed-artifact evidence. If no actual signed 16KiB binary, set **HOLD**; never claim release PASS, publish OTA/Google Play or auto-submit. No 4KiB fallback, skipped ELF/ZIP test, simulator-only waiver or reuse of older test report.

**New isolated SDK58 signed AAB gate** is source-only `apps/mobile/current/scripts/release27/signed-16kb-sdk58-aab-gate.mjs` in PR127; it requires app `com.ald1n.mobile`, version `1.0.0`, runtime `1.0.0-m17-sdk58-play2`, and actual remote `versionCode > 28`, and provides only `PASS_SIGNED_AAB_BINARY_GATE_ONLY` after actual signed AAB inspection. It cannot authorize an EAS build, attest owner/device/OAuth or submit to Play. The older `release25/signed-16kb-aab-release-gate.mjs` remains pinned to v1.5 and must not be reused for SDK58. VersionCode must be independently obtained from authenticated production EAS/Google Play, not assumed `29`. Approval for each billable production native build and Play submission remains separate after release gates; no unconditional trigger, duplicate dispatch or retries on unknown outcome. M16 vCode28 OWNER-ACCEPTED stays closed; M17 still OPEN.

---

## 0D. Permanent accepted-milestone reconciliation and Play-vs-preview boundary (2026-11-01)

**Correct superseding acceptance denominator/numerator**: earlier header sections 0C and B1028 operational notes mention `8/17` program and `7/15` CMS; these omit already **owner-accepted M08/M09/M12**. Original signed owner CloudLinux [Report871](https://drive.google.com/file/d/1Too-mItMVwvf2VAGjX4Okz8EQuQoBDoV/view) accepted M09 and M12, [Report872](https://drive.google.com/file/d/15O7N5vS6zlzIAtMRlcLSMGO8sHKVIZxU/view) accepted M08, and [Report988](docs/operations/988-M16-OWNER-ACCEPTANCE-CONTINUOUS-NEXT-BATCH-20261031.md) accepted M16 Android versionCode28. **CURRENT formal accepted functional ledger: CMS `10/15 = 66.7%`, program `11/17 = 64.7%`, first six UI `6/6=100%`**. Remaining M10, M11, M13, M14, M15, M17 OPEN. Never reset owner-accepted M08/M09/M12 to SKIP merely because a newer index summary carried an old stale count. These counts are accepted functional milestones, not code completion. Formal source/host/owner acceptance still individually required for newly developed code. See [Report1001](docs/operations/1001-B1029-PROJECT-PROGRESS-STOREFRONT-PLAY-SOURCE-20261101.md) and `docs/operations/000-LATEST.md`.

**Storefront**: unmerged Draft PR111 contains a registered/activated/verified/tracked-session buyer source candidate; 57/57 *targeted* PHP/MariaDB cases/1038 assertions were actually green (owner Report875 + corrected independent original JUnit identity validator Report876). This does **not** close the one previous safety-denied B815 test identity, full B803 suite, 368-item OWASP WSTG/ASVS assessment, legal/merchant/FPM/provider/restore UAT or activate public `/katalog`. Previously denied write actions remain denied; do not route around them via different tools or branches. M10/M11 joint 69-case B932 integration is separate and was not run or deployed.

**SDK58 Play release**: User reporting working preview APK is positive device smoke, **NOT** physical 16384-page-size kernel or full native Google OAuth UAT. Existing B1028 EAS APK is `com.ald1n.mobile.preview` vCode11; existing production `com.ald1n.mobile` vCode28 is a different package. [B1029 Draft PR125](https://github.com/AldinAga/ald1n-project/pull/125) creates a **source-only** `m17-sdk58-play-rc` AAB identity for production `com.ald1n.mobile`, declared new runtime `1.0.0-m17-sdk58-play1`, with 19/19 source/profile regression CI PASS. Not an EAS production build, authenticated remote versionCode/signer, native AAB attestation or Play authorization. Do NOT submit the preview APK, use old v1.5-specific signed AAB gate for SDK58, increase versionCode, auto-merge, build, publish OTA or submit Play until the distinct actual production-signed AAB and release approvals are verified. A source-only green CI must not be called an accepted M17 release.

---

---

## 0C. Permanent owner mandate: NEVER stop after conclusions or documentation; work the next batch (2026-10-31)

When any Ald1n user prompt accepts a PASS, closes an item or requests new development, complete the proper current task AND **immediately undertake the next safe, authorized batch in the project's actual priority order in the SAME turn**. Never finish with commentary, reports, documentation-only updates or promises when source edits, isolated tests, CI, PR preparation, verified artifacts or a bounded operator handoff are possible. Reconstruct fresh GitHub main, AGENTS, 000-LATEST, host reports and Drive each time. Do not wait for another user prompt for already authorized safe work.

When genuine CloudLinux or privileged authorization is required, finish all possible GitHub/CI/isolated preparation, deliver one correctly named SHA-self-verifying noninteractive operator script under canonical incoming and a single copy-paste command, state exact HOLD, and continue independent safe work. NEVER fake PASS or bypass any high-risk production native build/OTA/Play, DDL/destructive/financial gate or a safety-denied operation.

**M16 owner acceptance:** Android versionCode **28** resolved the Google Play **16 KB** issue; the owner reports real device works and explicitly accepts **M16=PASS**. This is authentic owner testimony alongside Google Play screenshots, NOT independent signed AAB, signer, ELF/ZIP or page-size artifact readback, and does not authorize new Play/OTA/build actions. Formal acceptance is **8/17=47.1% program**, **7/15=46.7% CMS**, original UI **6/6=100%**. **B1017 isolated npm12.2.1/EAS26 is ACTIVE now**, then M17 SDK58. Do not rerun M16 remediation or B1015/B1016. Detailed report: docs/operations/988-M16-OWNER-ACCEPTANCE-CONTINUOUS-NEXT-BATCH-20261031.md.

---

## 0. Owner execution autonomy and communication contract (permanent, 2026-10-23)

**Do the work, do not narrate the work.** For every Ald1n development request, the agent independently reads fresh GitHub `main`, this file, `docs/operations/000-LATEST.md`, current operations evidence, and canonical Google Drive guidance; then performs available source edits, GitHub commits/PR work, CI testing, review, and documentation/Drive archiving **without asking the owner for routine approval or explaining each planned engineering stage**.

- Deliver the completed, verified result, or one precise blocker/required operator action. Keep the owner-facing response compact; no extensive plans, status essays, tutorials, repeated historical summaries, or requests to choose obvious implementation steps.
- **Involve the owner only when an action genuinely requires their SSH execution on CloudLinux** (or when an external privileged action cannot be performed using available authorized tools). In the ordinary SSH case deliver a tested, integrity-self-checking executable under the canonical `incoming/` path and **one copy/paste SSH invocation**, then request the automatically numbered `docs/operations` report. No separate checksum command.
- Perform all GitHub source/branch/PR, CI, read-only analysis, project documents, and permissible Google Drive organization automatically before the SSH handoff. Preserve any tool safety blocks and never route around denied upload/edit operations.
- This autonomy does **not** waive release gates: fail closed on unresolved evidence, protect the dirty production checkout, do not deploy, perform data-destructive work, publish OTA, run EAS native builds, or submit Google Play without applicable authorization. No fake PASS. Do not request owner involvement just to repeat completed work.
- If a genuine required operator action is not yet prepared, continue available work first; report the exact blocker if and only if it remains at the end.

---


## 0A. One-command, noninteractive safe operator batches (owner decision, 2026-10-31)

**Permanent UX/execution rule for NEW Ald1n batches:** For read-only audits, isolated QA, narrowly scoped source-only repairs and other **already approved non-production/non-destructive operations**, the operator's deliberate execution of the **exact, reviewed** one-line SSH command is the only routine operational go-ahead. The batch MUST complete its full authorized sequence automatically; do not ask the owner to type `APPLY_...`, `YES`, `CONFIRM`, a SHA256 digest, press Enter for another stage, or run a separate `sha256sum` / syntax / preflight command.

- Delivery: one final, correctly named script located at `/home/icaffeco/ald1n-project/incoming/`; one copy/paste `bash /home/icaffeco/ald1n-project/incoming/<exact-name>.sh --run` invocation (or the script's exact audited single-command interface). Do not hand the owner renamed R2/R3 trial scripts or extra manual shell setup. Fetch the authentic host report and latest remote `main` before preparing the next batch.
- Embedded self-verification is **mandatory, not optional**: verify the executable's own SHA256/content/integrity anchor, expected basename and canonical path, syntax, release/source/host identity, target file types/ownership/mode and symlinks, Git/source hashes, dependencies, dry-run scope, backups/rollback feasibility and all relevant safety gates **inside the script**. Test the actual delivered name/content in isolated fixtures, including negative tampering, host drift and rollback. Never skip/disable integrity checks to achieve noninteractivity.
- On mismatch, incomplete proof, safety refusal, unexpected modification or insufficient authority: **HOLD/FAIL CLOSED immediately**, do not retry speculative fixes or auto-approve a newly enlarged scope. Emit a clear, exact blocker and an automatically numbered report under `docs/operations/`. Show real terminal RC, mutating/non-mutating status and any rollback outcome. Do not consume input from stdin in normal safe batches.
- **Production and high-risk boundaries are unchanged.** This rule does NOT itself authorize EAS native production builds, OTA publication, Google Play submit, destructive deletion/pruning, production DB DDL/migrations or financial/customer data operations. Those require their **separate explicit pre-authorized per-operation release/change gates** and evidence (signer, source commit, versionCode, profile/channel, AAB, device 16 KB and Play where applicable). If missing, fail closed; do not manufacture/auto-fill an approval or interpret an ordinary SSH launch as blanket consent. Gather any genuinely required special authorization **before issuing** a privileged command; do not hide a second prompt in the script. Platform-mandated approval steps cannot be bypassed.
- Applies prospectively. Never rerun historical B1013/R2/R3 or otherwise rewrite completed batches just to remove their old interactive confirmation. B1016 owner Report986 on 2026-10-31 shows the four Build25 files already installed; do not repeat it.

---

## 0B. Owner release priority: M16 before B1017 (2026-10-31)

**Latest explicit owner decision, supersedes the opposite order in prior B1016/Report986 handoffs:**
1. **FIRST: M16 — close Android 16 KB compatibility** using the current validated CloudLinux ALT Node22 v22.23.4, npm10.9.9, and pinned EAS CLI25.0.0. Reconstruct actual source/release identity and inspect the **real signed AAB**, verify native ELF/ZIP 16 KB page-size alignment and packaging, confirm application behavior on a **real applicable Android 16 KB device**, and satisfy documented Google Play compatibility / signer / release gates. Reuse earlier acceptable artifacts; missing or incomplete evidence remains HOLD, never infer PASS from controller installation. No production build, OTA, Google Play submission or version change without separate per-release authorization.
2. **SECOND, ONLY AFTER M16 is documented PASS and formally accepted:** B1017 — isolated compatibility evaluation of npm 12.2.1 and EAS CLI 26 (check actual published tool version before running), outdated dependency analysis, Node22/CloudLinux/EAS command coverage. Keep system/host npm 10.9.9 and the production EAS CLI25 pin intact unless later separately approved after isolated PASS. Do not let npm modernization delay M16.
3. **THIRD:** M17 — Expo SDK 58 upgrade after M16 acceptance and the B1017 compatibility analysis, with its own controlled source/test and release gates.

This is a **priority-order/documentation decision only**, not host or release authorization. Preserve completed B1015/B1016 source installs and current production configuration. Future safe host scripts follow section 0A: **one command, no typed phrase/manual checksum**, automatic fail-closed gates and numbered reports.

---

## 1. Repository and runtime authority

Repository:

```text
/home/icaffeco/ald1n-project
```

Primary locations:

```text
CMS:    /home/icaffeco/ald1n-project/apps/cms/current
Mobile: /home/icaffeco/ald1n-project/apps/mobile/current
Docs:   /home/icaffeco/ald1n-project/docs/operations
Incoming executable batches:
        /home/icaffeco/ald1n-project/incoming
```

Production mobile identity:

```text
Display name:    Ald1n CMS
Expo owner:      ald1n
Expo slug:       ald1n-mobile
Expo project ID: d43b3866-6838-4217-a23e-3dc7f2cc76cc
Android package: com.ald1n.mobile
Production API:  https://cms.ald1n.com/api/v1
App version:     1.0.0
Current runtime: 1.0.0-build17
```

Current platform baseline:

```text
CMS:    Laravel 13 / PHP 8.4
Mobile: Expo SDK 57 / React Native 0.86 / TypeScript
```

Do not change these identities incidentally inside maintenance, bugfix, documentation, or OTA batches.

---

## 1A. Mandatory operator batch artifacts (project-owner policy, 2026-10-12)

**Permanent rule for EVERY Ald1n CMS/Mobile chat, batch, sub-batch, recovery, QA, CLI/version audit, and agent/operator handoff. Read and enforce it before providing, uploading, generating or running ANY batch execution commands. No implicit exceptions.**

### Operator-delivered and executable scripts — one location ONLY

```text
/home/icaffeco/ald1n-project/incoming
```

- All new or revised operator-facing scripts delivered for manual execution on this hosting (e.g. `.sh`, `.php`, `.mjs`, `.js`, or helper files that are part of that operator batch) must be uploaded/placed in **this exact directory** before execution.
- Every copy/paste execution command MUST refer to `/home/icaffeco/ald1n-project/incoming/<script-name>`, never `$HOME/<script-name>`, a root-level stray script, or an ad-hoc script in the CMS/Mobile source.
- Inspect and verify the script's content, expected SHA-256 and canonical location before running. If it is missing, report HOLD; do not silently fall back to a legacy copy at another path.
- The root `/incoming/` is intentionally Git-ignored: do NOT change `.gitignore`, force-add runtime scripts or perform a broad git sync as a workaround. Tracked application source/test scripts within their actual packages are a different category and must **not** be moved out of their source locations; this policy governs delivered operator-execution artifacts.

### Batch-generated reports and terminal logs — one location ONLY

```text
/home/icaffeco/ald1n-project/docs/operations
```

- Every newly generated post-execution batch report, smoke/QA receipt, full terminal log, failure report, and compatibility-lab result MUST be **created at this exact directory**. Do not save batch reports/logs to `$HOME`, `/tmp`, `incoming`, or unrelated app directories.
- Preserve the existing sequential numeric report prefix rule in section 26: first resolve the highest report number from current GitHub `main` AND any local `docs/operations` reports, then assign the next unused number, without overwriting or reusing old reports. Distinguish **Batch number** from **Report sequence number**.
- A report must capture the exact source SHA/branch, script name and SHA-256, controls executed, real return codes, PASS/FAIL/HOLD gates, mutation status, and any limitations. Terminal output must be visible LIVE and recorded in that report (`tee`; preserve the audited command's RC, not `tee`'s RC).
- For read-only QA, only write the designated operation report into the project working tree. Temporary extraction, npm cache, lock and scratch artifacts go outside the repository as required by section 9 and must not be used as a report destination.
- The GitHub historical report/archive and local current/predecessor retention rules of section 26 remain in force: checkpoint report evidence to GitHub before pruning archived local copies. Never delete uncheckpointed reports.
- If canonical `incoming` or `docs/operations` is unavailable, or the report sequence cannot be established safely, fail CLOSED and explain the blocker; do not invent a different destination.

### Canonical operator invocation (current prospective policy, 2026-10-31)

The owner runs **one** exact command, after the completed script has been placed under canonical `incoming/`:

```bash
bash /home/icaffeco/ald1n-project/incoming/<exact-batch-script>.sh --run
```

The script itself must validate the expected filename/path, embedded integrity/SHA256, host/source state and scoped authorization, and must generate its numbered `docs/operations/` report. **No separate `sha256sum`, `bash -n`, manual setup or typed approval phrase for approved safe batches.** The earlier multi-line/manual-check sample is superseded. For separately gated production/destructive tasks, missing explicit authority remains HOLD; see section 0A.


---

## 1B. Immutable CloudLinux/Unlimited.rs execution profile (owner re-confirmed 2026-10-16)

This is an entrypoint rule for EVERY NEW Ald1n chat and operator batch, not an optional reminder.

- Hosting: **Unlimited.rs CloudLinux/cPanel**, restricted shared hosting. Canonical project: `/home/icaffeco/ald1n-project`, Laravel `apps/cms/current`, Expo mobile `apps/mobile/current`.
- Production DB is MariaDB `10.11.19-MariaDB-cll-lve` (owner Report702, 2026-10-17), although Laravel PDO reports `mysql`. Before schema/ENUM DDL, test the exact MariaDB major/minor/patch family separately; MySQL 8.4 CI alone is insufficient. MariaDB quotes literal `COLUMN_DEFAULT` but returns nullable SQL `NULL` as unquoted text `NULL`. On shared hosting, lack of permission to see metadata locks is HOLD, not proof that locks are absent; do not enable global probes or change DB privileges automatically.
- **2026-10-21 PR78 bounded online DDL lesson (source-only CI [39639301065](https://github.com/AldinAga/ald1n-project/actions/runs/39639301065)):** for proposed online ALTERs of existing populated **MariaDB/InnoDB** production tables, do not rely on an unrestricted `Schema::table` that could use a table copy or wait indefinitely. First verify real storage engine and the exact MariaDB version family, then require explicitly supported `ALGORITHM=INSTANT, LOCK=NONE` for the proposed operation and a bounded *session* `lock_wait_timeout` (verified at 5 seconds for the warranty preference column), restoring the previous value after either success or failure. If INSTANT/LOCK=NONE cannot be proven for the target table/operation, **HOLD** rather than silently selecting slower/locking DDL. Before approving a production migration, test a *populated* disposable MariaDB instance and a second connection holding a metadata lock; verify failure within the bound without schema mutation, safe retry, idempotency and preservation of existing rows. For user opt-in/consent columns, default to **OFF** for existing and new users and do not automatically drop a column containing enabled consents on rollback. Passing disposable CI does not attest the CloudLinux-modified MariaDB binary, fresh production backup/restore, or authorize production schema change.
- **2026-10-21 MySQL 8.4 SQL 1221 cross-family rule (PR78 recovery):** Laravel's PDO driver name is `mysql` for **both** MySQL and MariaDB, but their INSTANT DDL lock syntax differs. **MariaDB 10.11** accepts `ALGORITHM=INSTANT, LOCK=NONE`; **MySQL 8.4** rejects this combination with SQL 1221 and requires `ALGORITHM=INSTANT` with its default lock strategy (do not set `LOCK=NONE`). Use the real `SELECT VERSION()` to choose the *exact* separately tested SQL, and fail closed on unknown versions; retain InnoDB preflight and bounded/restored metadata-lock timeout. This is not permission for a slower `COPY`/`INPLACE` fallback. Real MySQL8.4 negative evidence [CI 39639308774](https://github.com/AldinAga/ald1n-project/actions/runs/39639308774), corrected MySQL8.4 **and** MariaDB10.11.19 success [CI 39640326290](https://github.com/AldinAga/ald1n-project/actions/runs/39640326290). No production DDL was run.

- Node: CloudLinux **Node.js 22** environment `/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node`; npm CLI `/opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js`. Always check these exact executables before use. Do not assume shell `node`/`npm` are resolved. No global npm upgrade (`npm install -g`) and no generic `expo install --fix`; never upgrade npm solely because it prints a notice.
- EAS CLI: pinned `eas-cli@25.0.0` invoked through the canonical Node22/npm exec, subject to the separately approved authenticated production release gates. A CI PASS is not permission to publish OTA, build, or Play submission.
- JDK: JDK **17** is mandatory for native/signature regression tests; on this host `java`, `jar`, `jarsigner`, and `keytool` may be absent from PATH entirely. Check executable availability and real smoke functionality. If unavailable, a verified portable Temurin JDK17 may be installed in private `$HOME/.ald1n-toolchains` with SHA-256 verification, non-root, no global environment edits. Never bypass failed signature tests.
- **Owner UX rule (2026-10-21): do not require or suggest a separate manual `sha256sum` command for ordinary operator batch execution.** File integrity checks, when needed, must be done automatically inside the script and reported without a second operator command. This changes the user-facing workflow, NOT the requirement to protect executable source integrity or release provenance. Give the owner one straightforward invocation from the canonical `incoming/` path.
- **Mandatory CloudLinux delivery review (2026-10-21):** before giving any operator script, statically reject the patterns `<(`, `>(`, `/dev/fd`, `exec > >(`, `done < <(` and any shell process substitution, and run `bash -n` or `sh -n` matching its interpreter. Prior Batch687 and the 2026-10-21 v1.5 batch both failed with `/dev/fd/63`; never repeat this failure. Use `tee` by ordinary pipe with explicit `PIPESTATUS` or a regular report file, not process substitution.
- **Hard banned shell features** on this host: process substitution `<( ... )` / `>( ... )`, reliance on `/dev/fd`, `python3`, and bash fd relay tricks. The 2026-10-16 Batch687 V3 failed at `/dev/fd/63` because its author used `done < <(find ...)` and `exec > >(tee ...)` despite the existing section 10 ban. All future executable scripts MUST pass a static scan for these constructs before delivery, plus `bash -n`.
- Reporting: scripts exclusively under `incoming/`; logs/reports exclusively under `docs/operations/`, sequential prefix determined from GitHub `main` **and** local reports. For terminal logs, use a real named FIFO or normal files with explicit exit-code capture; no fd substitution.
- Preserve dirty/stale host Git trees; source of truth is latest remote `main` and current operations. No blind `git pull`/`reset`/`clean`, automatic overwrite or production rollout. Remote docs-only advancement after an approved application commit must be distinguished from application drift, never silently ignored.
- For CloudLinux-related failures, prefer **one consolidated host audit/recovery batch**, record actual observed versions, paths, RCs and blockers. Do not chain speculative workaround batches.
- **2026-10-21 Report784 recovery — isolated Expo checkout:** `eas whoami` may PASS before app dependencies exist, while `eas project:info` FAILS loading `expo-router` in `app.config.js`. In a **fresh isolated clone** requiring later TypeScript/validator/Android export, run the approved exact Node22/npm10 **lockfile install `npm ci` before any project-scoped EAS command that loads Expo config plugins**. First perform static source/ref/config checks, then install already-required QA dependencies, then run EAS `project:info` and remote-version reads from the isolated app root. Do not run `eas project:info` on the pristine checkout before dependency install and do not re-clone/re-fetch on a recovery that can safely reuse an exact-SHA clean isolate. Do not install packages merely for an unrelated read-only compatibility query. Keep the isolated checkout under `$HOME` **outside** `/home/icaffeco/ald1n-project`; never create `.ald1n-v15-isolated-*` inside the production repo. Report784 already proved EAS account login and exact source SHA; preserve that evidence and resume at the failed stage.

- Production Android baseline until re-attested: app 1.0.0, versionCode 25, runtime 1.0.0-build17, production channel, com.ald1n.mobile. The latest OTA group and deployed source must always be reverified before mutation. iOS stays deferred by owner.
- The user expects these rules to be retrieved from GitHub `AGENTS.md` at the **start** of every new Ald1n project chat/batch, together with `docs/operations/000-LATEST.md` and recent reports.

---

## 2. Permanent product rules

These are not optional cleanup targets or legacy TODOs.

- **Product Variants are permanently decommissioned** from active UI, routes, API contracts, services, Mobile types, and operational workflows. Do not restore variant editor/workflows unless explicitly requested by the project owner.
- Mobile cart/order creation remains **product-only**.
- Multiple taps are allowed generally; only active network mutations may use deliberate single-flight/idempotency protection.
- Direct Sale remains available from product detail for authorized users.
- Deferred Direct Sale supports installments and final due date according to the existing server contract.
- Commission remains visible in catalog/product detail where already implemented.
- `Uredi artikal` remains the final admin action on product detail after Direct Sale.
- **Warranty expiry opt-in:** `warranty_expiry_alerts` defaults OFF and is independent of ordinary `warranty_updates`. Preserve strict immutable order-item/order ownership. The owner clarified the future expiry recipient policy on 2026-10-22: originating selling user plus active, individually opted-in SuperAdministrator, with no unrelated recipient or broadcast; see section 30. This narrowly scoped administrator exception is a pending implementation, not evidence that deployed owner-only code already supports it. Historical preference RED/GREEN: [39643629087](https://github.com/AldinAga/ald1n-project/actions/runs/39643629087), [39643826340](https://github.com/AldinAga/ald1n-project/actions/runs/39643826340). Do not activate global/bulk expiry delivery as part of preference wiring.

A batch that unexpectedly reintroduces Product Variants must fail.

---

## 3. CloudLinux Node/npm authority

Do not assume the shell's default `node`, `npm`, or `eas` binaries are the project authority.

Canonical Node binary:

```bash
NODE_BIN=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node
```

Canonical npm CLI:

```bash
NPM_CLI=/opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js
```

Use them explicitly:

```bash
"$NODE_BIN" "$NPM_CLI" run typecheck
```

Do not depend on an interactive shell profile, `nvm`, user aliases, or a globally installed `eas` command.

---

## 4. Expo dependency rule on this hosting

### Never use this for dependency repair/install

```bash
expo install --fix
```

On this CloudLinux/NodeJS Selector environment, Expo's internal npm child process can fail when a physical `node_modules` directory is involved.

Approved pattern:

1. Expo may be used for **compatibility checking** such as `expo install --check`.
2. Actual dependency installation/update is performed through the canonical Node/npm CLI path above.
3. Do not create/install `node_modules` merely to perform a release compatibility check.
4. Do not run broad dependency repair commands during unrelated bugfix batches.

---

## 5. EAS CLI authority — critical

Do **not** require or probe for a physical/global `eas` binary as a hard prerequisite.

Canonical pinned EAS CLI version for new repository operations (read-only GitHub Actions validated 2026-10-16):

```text
eas-cli@25.0.0
```

Upgrade from 24.8.0 to **25.0.0**: exact-source Node 22.16 read-only compatibility [CI 38862601089](https://github.com/AldinAga/ald1n-project/actions/runs/38862601089) PASS (CLI version and Update, republish, channel, project, build and submit help/flag contracts). Upstream [v25.0.0 release notes](https://github.com/expo/eas-cli/releases/tag/v25.0.0) list simulator-preview link sharing as the published breaking change. **Authenticated production exact-group read on hosting with CLI 25: PASS** (owner-executed Report683, 2026-10-16; source `6a02eec0d2a800de7db1996adfdc2fd0e69d0cac`, exact production group `37ae8af0-b950-4549-8476-c960e1350866`, no mutation). The production channel/latest GraphQL mapping was **not** rechecked by Report683 and remains a separate gate for every future EAS publish. Do not install globally, run builds/OTA or modify production as part of this upgrade. Future versions require their own isolated compatibility and fail-closed tests.

Approved invocation:

```bash
EAS_SPEC="eas-cli@25.0.0"
"$NODE_BIN" "$NPM_CLI" exec --yes --package "$EAS_SPEC" -- eas --version
```

All EAS operations must use this pinned npm-exec path unless the project owner explicitly changes the authority.

### Project-scoped EAS commands must run from the Mobile app root

This is mandatory:

```bash
cd /home/icaffeco/ald1n-project/apps/mobile/current
```

Then run project-scoped commands such as:

```bash
"$NODE_BIN" "$NPM_CLI" exec --yes --package "$EAS_SPEC" -- eas update:list ...
"$NODE_BIN" "$NPM_CLI" exec --yes --package "$EAS_SPEC" -- eas update:view ...
"$NODE_BIN" "$NPM_CLI" exec --yes --package "$EAS_SPEC" -- eas update ...
"$NODE_BIN" "$NPM_CLI" exec --yes --package "$EAS_SPEC" -- eas update:republish ...
```

Do not execute project-scoped EAS commands from the monorepo root.

Known historical failure prevented by this rule: `eas update:list` failed when launched from the monorepo root even though authentication worked.

### Command-specific flag policy for eas-cli@25.0.0

The flags are not uniform across EAS subcommands. Use the behavior proven on this hosting, not a blanket flag template.

- `eas update:view GROUP_ID --json` is the canonical exact-group read. **Do not add `--non-interactive`**: eas-cli 24.7.0 rejects that flag for `update:view`.
- CLI25 help confirms Update/list/channel flag availability, but collection queries are **not** standalone release authority: earlier collection reads failed even under CLI24.8.0. Use exact-group `update:view` and authenticated basic channel/latest GraphQL, and fail closed on ambiguity.
- `eas update ... --environment production --json --non-interactive` and `eas update:republish ... --json --non-interactive` were production-proven under **CLI24.8.0** in Report682. **CLI25 has only read-only compatibility PASS so far**; its first production mutation requires separate release gates and exact-group verification. Expo export warnings about `CI=1` do not prove success/failure; judge real RC and returned update JSON.
- Do not globally install a newer EAS CLI merely because the CLI prints an upgrade notice. Test a new major version through the same pinned npm-exec mechanism in a dedicated read-only compatibility audit first; only then change the canonical pin in this file.

---

### iOS profile credential safety (Batch684, 2026-10-16)

- Before any `npm run build:ios:preview` or `npm run build:ios:production`, execute the project-pinned **read-only** preflight `node scripts/ios-release-readiness.mjs --profile PROFILE` (now in the checked-in iOS build wrappers). No iOS build is authorized merely by a config-only PASS.
- **Batch685 profile split:** the tracked `GoogleService-Info.plist` is used **only** by iOS preview (`com.ald1n.mobile.preview`). iOS production (`com.ald1n.mobile`) and development (`com.ald1n.mobile.development`) must each supply a distinct real Firebase iOS plist through EAS File environment variable `ALD1N_IOS_GOOGLE_SERVICES_FILE` in that profile environment. Use secret/file visibility; never commit a production plist or repurpose preview. Local preflight requires an absolute file path outside the repository. `PASS_CONFIG_ONLY` does not prove remote EAS secret registration, Firebase Console identity or Apple signing. See `apps/mobile/current/docs/IOS-FIRST-BUILD-READINESS.md`.
- `PASS_CONFIG_ONLY` is not evidence of Apple Developer credentials, provisioning, TestFlight delivery, real device login, or notification entitlement. Keep Apple signing and device UAT as separately attested gates. Do not change Build25 Android runtime, native dependencies or production OTA to fix iOS credentials.
- **OWNER-DEFERRED (2026-10-16, until expressly resumed):** do NOT attempt or repeatedly request either (1) Firebase Console registration/download of original production iOS `GoogleService-Info.plist` for `com.ald1n.mobile` in `ald1nmobileapk`, or (2) Expo production EAS File/Secret upload as `ALD1N_IOS_GOOGLE_SERVICES_FILE`. These are intentionally postponed; when the owner later asks what to do, present these exact two instructions. Production iOS remains HOLD and no signing/TestFlight/submission bypass is permitted.
- **Batch686 independent next step:** a separate `ios-simulator` profile inherits **only** preview identity and tracked preview plist (`com.ald1n.mobile.preview`), EAS channel `preview`, `ios.simulator=true`, and `autoIncrement=false`. A simulator .app build needs no Apple Developer account. Validate exact GitHub source, Expo CLI25 identity, TypeScript/Release25/JDK17, immutable Android baseline, config-only iOS preflight and canonical operator receipt **before submitting one EAS cloud simulator build**. Simulator builds are not physical iPhone UAT or TestFlight.
- Liquid Glass remains the final project phase, after functional UAT and the first iOS build.

---

## 6. OTA / Build policy

Before deciding that a native build is required, compare native/runtime-critical files against the active native build source authority.

For JS/TS-only changes compatible with the current runtime:

```text
runtimeVersion = 1.0.0-build17
```

prefer **EAS Update OTA** instead of a new native build.

Do not create Build18 merely because source code changed.

A new native build is justified only when native/config/dependency/runtime compatibility requires it.

Do not submit to Google Play unless the requested task explicitly requires Play action.

### OTA release pattern

For SDK 57, use the production environment explicitly:

```bash
eas update ... --environment production --non-interactive
```

Recommended safe sequence:

1. Verify source/runtime/config authority.
2. Publish an isolated candidate update.
3. Capture the **candidate group ID directly from `eas update --json`** when possible.
4. Verify that exact group with `eas update:view GROUP_ID --json`.
5. Re-check production authority before promotion.
6. Promote the exact verified candidate with `eas update:republish --group ... --destination-channel production --platform android ...`.
7. Capture the production group from republish JSON.
8. Verify the exact production group with `eas update:view`.
9. Do not use collection/list queries as the only source of truth when an exact-group query is available.

If an EAS collection query is temporarily unavailable but exact-group authority works, do not destroy a valid candidate or republish blindly.

---

### Workspace V2 Build25 OTA release authority (2026-10-12)

- Android Build25: versionName 1.0.0, versionCode 25, runtimeVersion 1.0.0-build17, production channel, package com.ald1n.mobile.
- Workspace V2 source SHA: 52850d185d2c26d0a72a26d64520528714fe2f01. Candidate group: 8f27e122-317b-4db1-93a6-9bb95653de26 on workspace-v2-uat-20261012.
- Verified production republish group: 27dd4b5d-63b3-4902-9b0b-5f289076f0e6; Android update 01a12d9b-a56a-75e0-bd8d-be25ed4acb4a. Exact group re-read matched source SHA/runtime/production branch.
- Prior production group / rollback candidate: 3488c7cc-ac63-472d-bd7c-27e5abc677bd (Google Sign-In fix). Re-attest state before any rollback. No automatic rollback.
- Owner confirmed physical Android UAT in conversation; exact device matrix/telemetry not independently documented. Release technically closed, no new build, versionCode increase, or Play submit.
- Operations report: docs/operations/632-WORKSPACE-V2-BUILD25-PRODUCTION-OTA-CLOSURE-20261012.md.

## 7. State-aware recovery — never repeat successful work

Every recovery batch must determine what already succeeded before taking action.

At minimum inspect and record:

```text
SOURCE_MUTATION
COMMIT_CREATED
PUSH_COMPLETED
CANDIDATE_PUBLISHED
CANDIDATE_GROUP_ID
PRODUCTION_PROMOTED
PRODUCTION_GROUP_ID
FAILED_STAGE
```

Rules:

- If source was already committed and pushed, **do not patch/commit the same fix again**.
- If a candidate was already published and verified, reuse its exact group when safe.
- If production was already promoted and only post-validation failed, do not blindly republish or rollback.
- If a preflight failed before mutation, recovery starts from the unchanged source state.
- Bind the exact previous report SHA-256 before recovery so the recovery script proves which state it is continuing from.

Recovery must continue from the last verified state, not restart the whole batch by default.

---

## 8. Git preflight and direct-GitHub-write recovery

Before any source mutation:

```bash
git fetch origin main
```

Record:

```text
BRANCH
LOCAL_HEAD
REMOTE_HEAD
```

Do not immediately mutate when local and remote differ.

### Safe fast-forward policy

If local is behind remote:

1. Confirm local is an ancestor of remote.
2. Confirm local has no unpushed commits.
3. Inspect the exact remote-only commits/files.
4. Confirm the remote delta is expected and safe.
5. Then use:

```bash
git merge --ff-only origin/main
```

Never use a blind `git pull` in a batch.

This is especially important because documentation may be pushed directly through GitHub while the server checkout remains one commit behind.

### Known runtime drift

The following production `.htaccess` runtime drift is historically allowed only when both hashes match exactly:

```text
apps/cms/current/public/.htaccess
file SHA-256: d10ecf0d63f9a8e1bd978a440afcbadb34faf1d24eb899bf2582d1e5c0033cef
diff SHA-256: 8ce9fa55a71768bf711203fcc50be8eec6de26886bbf4852ceb30a97b36958fe
```

If either hash changes, treat it as new drift and stop for review.

Do not stage this known runtime drift unless explicitly requested.

---

## 9. Batch temporary files must stay outside the Git worktree

Do not place runtime scratch directories such as:

```text
.batch123-...
.batch159-...
```

inside `/home/icaffeco/ald1n-project`.

A previous checkpoint batch created its own temp directory inside the repo and then detected its own files as unexpected untracked contamination.

Use an external location, for example:

```bash
TMP_DIR="$HOME/.ald1n-batch${BATCH_NO}-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$TMP_DIR"
```

Locks used only by the batch should also live outside the repository unless they are intentionally tracked project files.

---

## 10. Shell portability rules for this hosting

Batch scripts must be executable through:

```bash
bash /home/icaffeco/ald1n-project/incoming/<script>.sh
```

Permanent restrictions:

- Do not use `python3` in operational batch scripts.
- Do not use `/dev/fd`.
- Do not use process substitution:

```bash
<(command)
>(command)
```

- Use plain ASCII shell command flags.
- Prefer portable temporary files/FIFOs over shell-specific descriptor tricks.

### `set -e`, `ERR`, and expected non-zero return codes

Do not design a batch where an expected `RC=1` can be converted into a fatal error by global `set -e` or an `ERR` trap.

Examples of commands where `1` can be expected/meaningful:

```text
grep    -> no matches
git grep -> no matches
git diff --quiet -> differences exist
```

Capture and classify the return code explicitly.

Recommended pattern:

```bash
set +e
command_here
rc=$?
set -e   # only if this batch intentionally uses errexit

case "$rc" in
  0) ... ;;
  1) ... expected semantic ... ;;
  *) fail ... ;;
esac
```

A simpler and often safer policy for these operational scripts is to avoid global `set -e` entirely and use explicit RC checks for critical commands.

### `set -u` and literal dollar signs

If `set -u` is active, escape literal `$` characters inside double-quoted fingerprints.

Wrong:

```bash
grep -Fq "'automation.'.$type" file.php
```

This expands shell variable `$type` and may abort the script.

Correct:

```bash
grep -Fq "'automation.'.\$type" file.php
```

Or use single quotes where practical.

---

## 11. Terminal logging contract

All operational batches must show detailed progress **live in the terminal** and save the same audit output to the report.

Do not hide important command output only inside temporary files.

Preferred logging helper:

```bash
log() {
  printf '%s\n' "$*" | tee -a "$REPORT"
}
```

For normal commands:

```bash
command 2>&1 | tee -a "$REPORT"
rc=${PIPESTATUS[0]}
```

Always preserve the real command return code rather than `tee`'s return code.

### JSON-producing commands

When stdout must remain valid JSON while also being visible live, do not corrupt stdout by adding prefixes or mixed stderr.

Use separate stdout/stderr capture and, where needed, named FIFOs + `tee` so:

- raw stdout JSON remains parseable,
- stderr remains separate,
- both streams are visible live,
- both are persisted in the report.

No process substitution and no `/dev/fd`.

---

## 12. Validator parsing rules

Never classify a batch as failed simply because the text `FAIL` appears somewhere in validator output.

The valid success summary contains:

```text
Ukupno FAIL: 0
```

Therefore this is forbidden as a success/failure parser:

```bash
grep -Fq 'FAIL' validator.log
```

Use explicit parser rules:

1. Require the exact zero-failure summary.
2. Detect only actual failure lines/structured markers.
3. Treat informational historical text containing `FAIL` as non-fatal.

Do the same for other tools whose success output can contain words such as `error`, `fail`, or `warning` in explanatory text.

---

## 13. Canonical quality gates

### CMS

Canonical full CMS static gate:

```bash
cd /home/icaffeco/ald1n-project/apps/cms/current
php bin/static-check.php
```

Current expected result:

```text
Ukupno: 983, neuspešno: 0
```

Do **not** replace this gate with:

```bash
php bin/php-lint.php
```

`php-lint.php` is not equivalent to the canonical static gate.

### Mobile TypeScript

```bash
cd /home/icaffeco/ald1n-project/apps/mobile/current
"$NODE_BIN" "$NPM_CLI" run typecheck
```

Expected: RC 0.

### Mobile validator

```bash
"$NODE_BIN" scripts/validate-project.mjs
```

Expected summary:

```text
Ukupno FAIL: 0
```

### OpenAPI parity

There are exactly three active tracked OpenAPI copies:

```text
apps/cms/current/docs/openapi.yaml
apps/mobile/current/docs/openapi.yaml
packages/api-contract/openapi.yaml
```

They must remain byte-identical unless an approved batch is intentionally changing the API contract and updates all three together.

---

## 14. TDD for bugfixes and behavior changes

For a behavioral bugfix:

1. Add/create the smallest behavioral regression test first.
2. Run it against the existing source.
3. Confirm it fails for the expected reason (**RED**).
4. Apply the minimal production fix.
5. Run the same test and confirm it passes (**GREEN**).
6. Run broader TypeScript/validator/CMS/OpenAPI/project guards.
7. Only then commit/push.

Do not call a test “TDD” if the test was first run only after the production change.

If the project has no convenient test runner for the isolated behavior, a dependency-free Node/PHP behavioral runner is acceptable when it directly imports/exercises real production code.

---

## 15. Source-diff scope before commit

Before commit, print and review:

```bash
git status --short
git diff -- <expected paths>
git diff --check
```

Then verify the changed-file set exactly matches the approved scope.

A batch that intended to change 3 Mobile files must not silently include:

- operation reports,
- `.htaccess`,
- backup files,
- credentials,
- unrelated formatting,
- generated caches,
- temporary scripts.

Stage explicit paths, not `git add -A`, unless the approved task is specifically a controlled checkpoint of known reports.

---

## 16. Commit/push policy

Before push:

1. Re-fetch remote.
2. Ensure remote did not move unexpectedly.
3. Ensure staged scope is exact.
4. Run `git diff --cached --check`.
5. Commit with a task-specific message.
6. Push `main`.
7. Fetch again.
8. Require `LOCAL_HEAD == REMOTE_HEAD` for the new commit.

If push succeeds but a later OTA/release step fails, recovery must preserve that commit and continue release work. Do not create a duplicate source commit.

---

## 17. Operation reports are state authority

Every executable batch must produce an operation report under:

```text
docs/operations/
```

Important state markers should be machine-readable, one per line where practical:

```text
BATCH_RESULT=
FAILED_STAGE=
SOURCE_MUTATION=
COMMIT_CREATED=
PUSH_COMPLETED=
SOURCE_COMMIT=
OTA_REQUIRED=
CANDIDATE_PUBLISHED=
CANDIDATE_GROUP_ID=
PRODUCTION_PROMOTED=
PRODUCTION_GROUP_ID=
BUILD_CREATED=
GOOGLE_PLAY_ACTION=
NEXT_ACTION=
```

For recovery batches, bind the exact previous report SHA-256 before doing work.

A report must be detailed enough that the next batch can safely determine the exact system state without guessing.

---

## 18. Default PASS / FAIL continuation rule

Project-owner preference:

- **FAIL report** -> diagnose exact root cause and create the targeted recovery batch immediately.
- **PASS report** -> certify it and create the next planned batch immediately.
- If the next step is manual/physical acceptance -> create an executable preflight/checklist batch rather than only prose.

Do not ask the project owner to restate this rule on every batch.

Do not create unnecessary recovery generations for known hosting behaviors that this file already documents.

---

## 19. Pre-flight checklist before every mutation batch

A new batch should answer these **before touching source**:

```text
[ ] Am I on branch main?
[ ] Did I fetch origin/main?
[ ] Are local/remote heads equal?
[ ] If not equal, did I prove a safe fast-forward rather than blindly pull?
[ ] Are there staged changes? If yes, stop unless they are explicitly expected.
[ ] Is the known .htaccess drift still exactly the known hash/diff?
[ ] Is my temp directory outside the Git worktree?
[ ] Am I using canonical Node/npm paths?
[ ] If EAS is needed, am I using npm exec eas-cli@25.0.0?
[ ] Will every project-scoped EAS command run from apps/mobile/current?
[ ] Does the script avoid python3, /dev/fd, and process substitution?
[ ] Can any expected RC=1 be accidentally trapped by set -e/ERR?
[ ] Can set -u expand any literal $ text in grep/regex fingerprints?
[ ] Does validator parsing explicitly accept "Ukupno FAIL: 0"?
[ ] Is the previous report SHA bound for recovery work?
[ ] Do I know whether source/commit/candidate/production actions already happened?
[ ] Is a new native build actually required, or is Build17 OTA compatible?
[ ] Is Google Play action explicitly requested? If not, do not perform it.
```

A batch should fail in preflight **before mutation** when one of these cannot be proven safely.

---

## 20. Pre-release checklist for Mobile OTA

Before candidate publish:

```text
[ ] TypeScript RC=0
[ ] Mobile validator: Ukupno FAIL: 0
[ ] CMS static: 983/983, 0 failed
[ ] Product Variants guard PASS
[ ] OpenAPI parity PASS when API surface is relevant
[ ] app name = Ald1n CMS
[ ] slug = ald1n-mobile
[ ] owner = ald1n
[ ] project ID = d43b3866-6838-4217-a23e-3dc7f2cc76cc
[ ] Android package = com.ald1n.mobile
[ ] runtime = 1.0.0-build17 for Build17-compatible OTA
[ ] production API = https://cms.ald1n.com/api/v1
[ ] source commit pushed and local/remote synchronized
[ ] EAS whoami succeeds through pinned npm-exec authority
```

After publish:

```text
[ ] Candidate group ID captured
[ ] Exact candidate update:view matches expected commit/runtime/platform
[ ] Production authority rechecked before promotion
[ ] Exact candidate promoted, not a guessed/latest group
[ ] Production group ID captured
[ ] Exact production update:view matches expected commit/runtime/platform
[ ] No unexpected native build
[ ] No unexpected Google Play action
```

---

## 21. Physical-device acceptance

For user-visible Mobile changes, use physical-device acceptance when the behavior cannot be fully certified by static/automated checks alone.

Typical OTA boot sequence:

1. Force-close app.
2. Open and allow OTA retrieval.
3. Wait for update/application initialization.
4. Force-close again.
5. Reopen.
6. Exercise the exact original failure scenario.

Acceptance should test the actual reported problem first, then a small regression smoke set.

Do not perform destructive production business mutations merely to prove UI controls exist. If no safe test record exists, record the limitation explicitly rather than modifying arbitrary live business data.

---

## 22. Backup rules

Before data-destructive or high-risk production operations, use the existing first-party backup flow where appropriate.

Canonical CMS manual backup:

```bash
cd /home/icaffeco/ald1n-project/apps/cms/current
php artisan app:backup-create --type=manual
```

Verify it:

```bash
php artisan app:backup-verify
```

Do not report a backup as restore-ready until verification passes.

Do not expose `.env`, Google credentials, tokens, passwords, private keys, or backup secrets in terminal/report output.

---

## 23. Cleanup safety

Never use an unrestricted destructive command such as:

```bash
git clean -fdx
```

for routine cleanup.

Inventory first. Delete only explicitly classified temporary/runtime junk.

Do not delete:

- `.env`,
- Google/Firebase credentials,
- uploads/product images,
- private business documents,
- active Laravel storage data,
- the current uncheckpointed operation report or an immediate predecessor report still required by an active recovery,
- valid verified backups,
- EAS credentials,
- unknown files merely because they are untracked.

Use quarantine/backup before deleting uncertain runtime artifacts.

---

## 24. Lessons already learned — do not rediscover via failed batches

These failures have already happened and are now encoded as permanent guards:

1. **Project EAS command from monorepo root** -> collection query failed. Run from Mobile root.
2. **Physical/global `eas` binary assumption** -> `EAS_CLI_MISSING`. Use pinned npm-exec `eas-cli@25.0.0`.
3. **Local repo one documentation commit behind GitHub** -> preflight mismatch. Verify ancestry/delta and `git merge --ff-only` when safe.
4. **Temp directory created inside repo** -> checkpoint detected its own temp files as contamination. Temp/lock outside worktree.
5. **Broad grep for `FAIL`** -> false failure on `Ukupno FAIL: 0`. Parse exact failure semantics.
6. **`git grep` no-match RC=1 under ERR/errexit** -> false fatal failure. Capture expected RC explicitly.
7. **`set -u` + literal `$type` inside double-quoted grep fingerprint** -> unbound shell variable abort. Escape literal dollars/use single quotes.
8. **Assuming a single OpenAPI location** -> topology failure. Active topology is exactly three tracked copies.
9. **Using `php bin/php-lint.php` as full CMS authority** -> wrong gate. Canonical full gate is `php bin/static-check.php`.
10. **Restarting recovery from scratch** -> risks duplicate commits/candidates/promotions. Recover from recorded state markers.
11. **`eas update:view ... --non-interactive` on eas-cli 24.7.0** -> hard CLI failure. Use exact-group `update:view GROUP_ID --json` without `--non-interactive`.
12. **Historical operations pointer mistaken for CURRENT authority** (Report636) -> Release regression tests must distinguish the leading live `000-LATEST.md` authority from explicitly marked old Build25 pre-OTA snapshots. Preserve historical evidence separately; never revert the current index to satisfy a stale old-header test.
13. **No JDK17 signing tools on the CloudLinux shell PATH** (Report636) -> Real `jar`/`keytool`/`jarsigner` regression stays HOLD until actual supported Java 17 tools are present, locally or in an approved isolated JDK17 CI runner. Do not silently mock, skip, globally install tools or label cryptographic verification PASS. Re-probe before assuming this environment finding remains true.
14. **Report641 process-substitution portability regression** -> A Bash loop reading from `<(git ls-files -z ...)` failed on CloudLinux as `/dev/fd/63: No such file or directory` BEFORE any backup verification. Despite the existing section 10 prohibition, the delivered script had not been checked for the forbidden construct. In all future `incoming/` scripts, write NUL-delimited command output to a private temporary file outside the repo, then `while IFS= read -r -d '' item; do ...; done < "$TMP/paths.bin"`. Run an automated pre-delivery forbidden-syntax scan and at least one isolated successful loop fixture. On failure, preserve original numbered report, archive it byte-exact to GitHub and advance to the next report number. Never classify unexecuted backup verification as PASS.

Any new repeated infrastructure failure should be added to this section after its root cause is proven.

---

## 25. Batch design principle

A good Ald1n batch is:

- bounded,
- state-aware,
- verbose,
- reversible before mutation,
- explicit about authority,
- conservative with production data,
- strict about changed-file scope,
- idempotent or recovery-safe,
- aware of this CloudLinux hosting environment,
- able to explain exactly what succeeded if a later stage fails.

The goal is not merely “eventually PASS.” The goal is that known environment constraints are checked **before** execution so a normal change does not require V2/V3/V4/V5 recoveries for already-understood reasons.

---

## 26. Owner-approved hosting hygiene and report numbering

These rules are permanent from **2026-09-19** and supersede older operation-report/backup retention behavior where they conflict.

### Terminal clear at the beginning of every Bash batch

Every executable operational Bash batch must clear the terminal before logging, preflight, or mutations. Immediately after the shebang/comments, use:

```bash
clear 2>/dev/null || printf '\033c'
```

A failed `clear` in a non-interactive shell must not abort the batch.

### Sequential operation-report number prefix

Every newly generated operation report must begin with a monotonically increasing numeric prefix followed by `-`.

Sequence authority when this rule was adopted:

```text
Last historical report number: 444
First report under this rule: 445
```

After 445, increment by exactly one for every new report (`446-...`, `447-...`, etc.). Recovery attempts consume their own next number; never reuse a previous report number. After Report447 the next report is Report448.

### Stable backup retention = exactly two

Hosting must retain exactly the two newest restore-ready stable backups after cleanup. Before deleting older backups:

1. Inventory the canonical Laravel `backup_runs` authority and canonical backup directory.
2. Verify candidate backups with `php artisan app:backup-verify`.
3. If fewer than two restore-ready backups exist, create and verify replacement manual backup(s) first.
4. Only after two verified backups are proven may older canonical backup rows/directories and known legacy release snapshots be removed.
5. Never delete either selected stable backup during the same cleanup.
6. Backup cleanup may mutate backup metadata only; it must not modify business records.

Do not retain 7 daily + 4 weekly backups merely because older default configuration allowed it. Owner policy is two stable backups total unless explicitly changed later. Every future housekeeping/release batch must verify the retention state and remove only newly accumulated superseded backups after proving the two keepers.

### B993: owner-approved minimal active-production-only footprint under /home/icaffeco/ (2026-10-30)

The owner explicitly requires **all** Ald1n project-associated files, directories and environments anywhere under `/home/icaffeco/` to be permanently removed **once proven neither needed for actively served production nor required for safe operation, incident recovery or an ongoing verifiable release gate**. This is a scope/retention directive, **not proof that any particular file is disposable**. Do not retain dead development artifacts merely for convenience, nostalgia, future debugging or because a folder is large/small. Off-host GitHub and Google Drive are the preferred historical homes. No need to ask again for blanket permission to remove individually verified nonproduction items after exact-source, exact-path manifests pass the gates below.

- Audit the **entire account home**, not just `ald1n-project` or `apps/mobile/current`: include old Expo release checkouts (B984/B990), unneeded `node_modules`, npm/Composer/Gradle caches, isolated QA/build stages, stale CMS/site copies, archival tarballs, obsolete env/virtualenv installations, `incoming/` leftovers, already GitHub-archived old operation reports, temporary dumps, old logs and backups. Identify candidate files by observed dependency/use/recency/canonical manifest **and count reclaimable inode entries**. A large archive may consume substantial disk space but only **one inode**; do not confuse bytes freed with inode counts.
- Verify the **actual** current vhost document root, symlink chain, FPM/cron/workers, production CMS/runtime, real media/private files, EAS release status/one-shot state, credentials, cPanel/mail paths and known off-host recovery requirements. No path may be marked disposable solely by name or age. Protect all live or unclassified assets. Do not delete unrelated files belonging to other projects or the host.
- **B994 (2026-10-30) strict unrelated-domain isolation and FULL offhost scripts/reports archiving:** Owner provided authentic Report969 (B992 PASS) and five account-folder screenshots. Never touch other website roots or shared global services, including `public_html`, `mail`, unrelated `erp`, `lscache`, `.cagefs`, `.cpanel`, unowned `.trash` and global `.npm` caches, unless independently attributed down to an exact Ald1n-only path. **BEFORE any deletion of original operator files or old project checkout trees, preserve all unique human-authored Ald1n batch scripts and reports from earliest to latest in a private encrypted OFF-HOST Google Drive artifact and independently verify remote bytes/restore.** The prepared B994 canonical operator `docs/operations/B994-ALD1N-OFFHOST-OPS-ARCHIVE.sh` creates a local encrypted GPG archive/manifest without deleting any source; this local ciphertext is NOT proof of a Drive upload. Generic `/home/icaffeco/backups/current` and `backups/releases` require project-specific ownership and independent current SQL/private recovery first, never mass-delete a shared parent. Protect `mobile-build.ald1n.com` (may be live), `nodevenv`, B990 one-shot state, `.ald1n-release-evidence` and B959 encrypted last recovery until exact closed release and independent remote proof. [B994 handoff](docs/operations/B994-REPORT969-ALD1N-ARCHIVE-FIRST-CLEANUP-HANDOFF-20261030.md).
- **B1013 (2026-10-31) PR121 source merged CI PASS; guarded host EAS activation single .sh on Drive:** Real [Report984](docs/operations/984-B1011-ALT-NODE22-EAS25-PATH-20261031-090936.md) SHA256 `b7b26555dbaef81c02e7bd3febb4a3e1669afed274a9d6a1c03e62836bcaeed0` confirms ALT Node22/npm10/EAS25.0.0 --version PASS, host package SHA `ca996886464fc3fd33a90f1d0330e3d08101b9440eba5b2a7fe7880add6b5845` unchanged. [PR121](https://github.com/AldinAga/ald1n-project/pull/121) merged source as `78edbe5fb3c936c56c884acf408c883b7f08d24c` after 2/2 CI PASS: six EAS optional scripts + locked Build25 + disabled submit use independent ALT Node22 child PATH, no native build. CloudLinux host source still NOT changed and already differs from git main; never git pull/reset/clean. [B1013 host handoff](docs/operations/B1013-HANDOFF-REPORT984-EAS-ALT-NODE22-HOST-ACTIVATION-20261031.md) includes Drive verified [single Bash](https://drive.google.com/file/d/1elCJncRcBZhWa-pQKbpE58_1EALlYV60/view) SHA256 `c2c118d61d3e2f96d5b83c4a958df2c92b4e4254607291a86fa15aa52ac084bd`, internal `3d4e0ac92af1ca2baa7405bef0b5f1e68abd4613c400604f56d5efa84f78c8f8`; isolated 11/11 fixture PASS, real host pending. Call only `bash /home/icaffeco/ald1n-project/incoming/B1013-ALD1N-EAS-ALT-NODE22-HOST-SAFE-DECOUPLE.sh --run` then type `APPLY_B1013_EAS_ALT_NODE22_ONLY` after preflight. Script replaces only six EAS package command values, the Build25 node+PATH and disabled submit node+PATH (three host files), rollback protected; no build/OTA/Play/DB/Selector/old-folder deletion. **GitHub executable upload blocked by safety checks; do NOT claim its GitHub publication or reattempt via workaround. Drive copy available.** After host PASS, archive and evaluate 19 old root candidates from Report983 (3 missing) for bounded deletion only with independent recoverable offhost source and live references cleared; then Selector deregister STOPPED old Node app before old webroot retirement. Protect all B990/SQL/private/Node22/EAS/V15 backups and other sites. B1009 CMS CSS host PASS pending user mobile visual UAT.
- **B1012 (2026-10-31) Report984 CONFIRMS independent ALT Node22/npm10/EAS25 version PASS; GitHub source-only decoupling:** Actual [CloudLinux Report984](docs/operations/984-B1011-ALT-NODE22-EAS25-PATH-20261031-090936.md), SHA256 `b7b26555dbaef81c02e7bd3febb4a3e1669afed274a9d6a1c03e62836bcaeed0`, confirms independent ALT Node v22.23.4, npm10.9.9 and pinned EAS CLI25.0.0 --version rc0, private PATH shim /usr/bin/env node PASS, host package SHA `ca996886464fc3fd33a90f1d0330e3d08101b9440eba5b2a7fe7880add6b5845` before/after, no production build or deletion. On `fix/b1012-alt-node22-eas-cli-runtime-20261031` replace six optional package EAS commands, Build25 guarded shell and disabled submit helper legacy app-linked Node path with independent ALT node `/opt/alt/alt-nodejs22/root/bin/node` and explicit child PATH; preserve all production owner-approval/lock/signature gates. New B1012 source-only test; old B9-only header test regex fixed. **GitHub PR is NOT proof that host scripts or package changed; host package already differs and lacks old path, so no blanket git pull/reset/clean/overwrites.** After source CI and exact host/source reconciliation, one bounded retirement wave: don't deregister stopped CloudLinux Node app or remove nodevenv before CLI/build wrappers confirmed independent. Report983 19 existing historical directories 3 absent, ~4500 estimated inodes /159 MiB, all REVIEW_ONLY until independent offhost unique source archive plus runtime/cron/mount/vhost/inode checks. Never delete backups/B990/EAS/V15/SQL/private/Node22/.ald1n-toolchains/other sites. B1010/B1011 closed, B1007 9/9 deletion complete; B1009 CSS host PASS/mobile visual UAT pending.
- **B1011 (2026-10-31) EAS25 child PATH remediation; B1010 Report983 credible partial PASS:** Authentic [Report983](docs/operations/983-B1010-INDEPENDENT-NODE22-ROOTS-20261031-084819.md), SHA256 `178abf6c1938d95e7df6d500cbd859e0716293b009df25d628260870372cda61`, confirms independent ALT-Node v22.23.4 and npm10.9.9 PASS; EAS CLI25 `--version` fails rc127 with raw stderr suppressed; its `ENGINE_MISMATCH` label is NOT causal proof. B1010 did not expose Node on child `PATH`; known CloudLinux/cPanel `#!/usr/bin/env node` error is plausible. New standalone [B1011 .sh](docs/operations/B1011-ALD1N-ALT-NODE22-EAS25-PATH-GATE.sh) full SHA256 `19ab9065d1a549bc152833a6156595b90c0471920b8e8051c844f227d77ced06`, normalized self-SHA `3eccd64f7fc744f59a883b537a55c47db056b3ea52c8087ff10a818aa0df27c5`; [Drive .sh](https://drive.google.com/file/d/1fALshkPfrPLIUcf79JNc1v-XW5O4QaBC/view), isolated local 13/13 PASS, host not yet run. Only host call `bash /home/icaffeco/ald1n-project/incoming/B1011-ALD1N-ALT-NODE22-EAS25-PATH-GATE.sh --run`, no PY/other files, no global PATH, build, OTA, Play or existing-file deletion. Report983 records 22 fixed owner roots: 19 present, 3 MISSING, 4,500 potential inode entries/162984KiB, none removed; all conditional until separate offhost unique file/active refs checks. Host mobile package SHA `ca996886464fc3fd33a90f1d0330e3d08101b9440eba5b2a7fe7880add6b5845` lacks old nodevenv string but GitHub package + Build25 shell + disabled submit helper contain it; don't blind overwrite dirty host; reconcile before CI/PR merge. Keep B990/Node22/EAS/V15/backup/SQL-private and other sites protected. After CLI PASS, reviewed exact source PR, one bounded safe retirement wave and Selector de-register only after rollback verification. B1009 CSS host PASS but visual UAT pending.
- **B1010 (2026-10-31) real B1009 CSS PASS, next Node22 independent/34-root combined gate:** Owner authentic [Report982](docs/operations/982-B1009-CMS-MOBILE-QUICK-ACTIONS-DEPLOY-20261031-081313.md), SHA256 `e7b7d28ad469cab58485ca5632a9fc7aeef62886ec76733059a6f8b338addb3a` confirms `PASS_B1009_GUARDED_CSS_ONLY_DEPLOY`, CSS changed one file and post HTTP CMS/API401/401, rollback file retained. Human authenticated mobile visual UAT still pending. B1008/B1009 no rerun. Next [B1010 single self-contained .sh](docs/operations/B1010-ALD1N-INDEPENDENT-NODE22-AND-ROOT-DISPOSITION.sh), full SHA256 `57aeeb99f425cce87a1f6aa4fb52875a272bedc75c63453fb26e49e1c9ebab3e`, embedded normalized SHA `701efec2075240fef135dd5bef849b39b96c1b857a37000bf95132a81b7516e3`, [Drive raw script](https://drive.google.com/file/d/1sYdUJHLSLR2WDe4NTY03o9xIsOql8ij3/view), [handoff](docs/operations/B1010-HANDOFF-REPORT982-NODE22-ROOTS-20261031.md), isolated **13/13 PASS**, actual host not yet run. User installs only `incoming/B1010-ALD1N-INDEPENDENT-NODE22-AND-ROOT-DISPOSITION.sh` and invokes `bash /home/icaffeco/ald1n-project/incoming/B1010-ALD1N-INDEPENDENT-NODE22-AND-ROOT-DISPOSITION.sh --run`. Reads independent CloudLinux ALT Node22/npm10 and pinned EAS-cli25 --version (isolated own disposable npm cache), PLUS inventories all 22 exact historical conditional root paths, excludes 12 protected (.ald1n-toolchains, B990, B994/B995 encrypted offhost, release/SQL/private backups, etc). ZERO preexisting files deleted or overwritten; report-only. Actual GitHub source still has old `nodevenv/mobile-build.ald1n.com/22/bin/node` references in package.json, build25-release.sh and disabled submit helper; all must be changed together through reviewed/CI-gated PR **after** exact independent host runtime proves PASS. Only then CloudLinux Selector de-register stopped old app and bounded legacy-root generated cleanup, no rm-first. Maintains Node22 and B990 protections, no EAS native build/OTA/Play/DB writes.
- **B1009 (2026-10-31) Report981 B1008 rollback deterministic CSS post-gate bug and 35-folder retirement plan:** Actual [host Report981](docs/operations/981-B1008-CMS-MOBILE-QUICK-ACTIONS-DEPLOY-20261031-080055.md) original SHA256 `ead1222c436509fef8a2bf52016beaaf9117f05b35e27ff479168c9cf2de3eea` contains only `HOLD_B1008_POST_DEPLOY_CSS_ROLLED_BACK`; exact first failure stage unknown. B1008's post-deploy global `grep -Fc` expects one matching three-column grid but approved CSS has 3, so B1008 would invariably rollback after reaching that gate. Standalone fixed [B1009 .sh](docs/operations/B1009-CMS-MOBILE-QUICK-ACTIONS-CSS-DEPLOY.sh), SHA256 `8e60b23217deeae24e162b706fcd224355c1f2bec3c2ddcaec7e88f29e707c2d`, self-integrity `e582e45ad2c9fcfb58f5f5bbb9a8bdff164eb69dd100fe5461b87dfe0c11e670`, local 9/9 PASS, [Drive .sh](https://drive.google.com/file/d/12GDyrO8Vqbznn0uTy9eqWZBGVa-FxjNS/view). Only the .sh is needed in `incoming`, no Python, test JSON or ZIP; call `bash /home/icaffeco/ald1n-project/incoming/B1009-CMS-MOBILE-QUICK-ACTIONS-CSS-DEPLOY.sh --run` then type `DEPLOY_B1009_CMS_MOBILE_ACTION_GRID` after fresh gates. It checks exact inserted CSS and SHA, reports precise failure+rollback. Host B1009 NOT RUN; UI UAT pending. [34 screenshot dot-folder disposition](docs/operations/B1009-ROLLBACK-ROOT-FOLDERS-AND-NEXT-GATES-20261031.md): no blanket removal of 4 KB displayed directories; older B788-803/batch528/old UI staging conditional after unique source/offhost/references proof; protect B990, Node22 toolchain, release/EAS evidence, B994/995 encrypted offhost exports, backups and unknown .ald1n-sdd. Next EAS Node22 independent CLI/read-only source PR then bounded exact leftover cleanup and cPanel Selector removal of stopped old Node web app with rollback. Current CMS/B990/SQL/private and other domains protected.
- **B1008 MERGED (2026-10-31) PR120 CMS mobile quick-actions CSS, host deploy still PENDING:** Owner [Report980](docs/operations/980-B1007-EXACT-NINE-GENERATED-CACHE-CLEANUP-20261031-074432.md) proves B1007 9/9 generated cache paths deleted, net **340,780** freed (inclusive 1,082,590→741,810, 31.47%). PR120 [source](https://github.com/AldinAga/ald1n-project/pull/120) merged to main `1a7000dc9e6215759a7ae0710789d94d79ebb9d6` after **11/11 CI PASS**, PHP 8.4/MariaDB Dashboard D1–D3 PASS. Only app file changed: `apps/cms/current/public/assets/css/ald1n-dashboard-d1.css` (mobile <=720 quick-action nowrap/overflow-x:auto replaced by 2/3-col grid, 44px targets). Isolated Chromium width/theme 14/14 PASS and guarded one-file deploy synthetic fixtures 8/8 PASS; **not production/authenticated visual UAT**. Next ONLY one owner SSH call using [B1008 zip](https://drive.google.com/file/d/1aJ5Yybfx3ELJXtf9SR68obrmQlyYkGV0/view), exact script `incoming/B1008-CMS-MOBILE-QUICK-ACTIONS-CSS-DEPLOY.sh` full SHA256 `e4900e5c14890ebaef58ef66e135cd6c1dc2037fe74ae6c555352dddd2d504bc`, invoke with `--run` and type literal `DEPLOY_B1008_CMS_MOBILE_ACTION_GRID` after healthy CMS/API and exact CSS/rollback checks. [Final handoff](docs/operations/B1008-REPORT980-CMS-MOBILE-SCROLL-HANDOFF-20261031.md). Await numbered host report and user mobile screenshot before claiming CSS live. Follow order: EAS independent Node22/npm read-only CLI, then remove stopped legacy CloudLinux app via Selector, then safe exact old root evaluation. No EAS prod build/OTA/Play or DB edits; protect backups/B990/Node22/other sites. Do not rerun B1007.
- **B1008 (2026-10-31) actual B1007 cleanup PASS and CMS mobile quick-action CSS source fix PR:** [Authentic Report980](docs/operations/980-B1007-EXACT-NINE-GENERATED-CACHE-CLEANUP-20261031-074432.md) SHA256 `7dd71033eff52d4af464557ce8911a6b09094905a2eb85ab1b9123b543f2e294` proves **9/9 roots DELETED**, home inclusive inodes 1,082,590→741,810 (**net -340,780, -31.47%**). The prior 340,784 manifest count differs by 4 from live net; no confirmed reason. No legacy Node22/B990/CMS/Android/other domain/backups were deleted. `apps/cms/current/public/assets/css/ald1n-dashboard-d1.css` fix in `fix/b1008-cms-mobile-dashboard-quick-actions-20261031` removes nowrap/overflow-x:auto quick-action rail at <=720 and replaces it with 2- or 3-col responsive grid and 44px hit-targets. [B1008 QA and handoff](docs/operations/B1008-REPORT980-MOBILE-DASHBOARD-HORIZONTAL-RAIL-QA-20261031.md), isolated Chromium width/theme 14/14 PASS, but *live authenticated CMS visual QA and gated host CSS deploy pending*; do not claim production fix merely because PR exists. Next mandatory order: independent EAS Node22/npm CLI read-only checks without production build, then stopped legacy CloudLinux Node app deregistration and verified exact old-root cleanup. Do not rerun B1007 or destroy Node22 yet.
- **B1007 (2026-10-31) nine exact old cache trees — guarded cleanup prepared, 45/45 local PASS:** Prior [Report975](docs/operations/975-B1001-CONSOLIDATED-CLEANUP-READONLY-AUDIT-20261030-192243.md) pins exactly five old npm-cache and four isolated Expo node_modules (340,784 potential inode entries), Report979 confirms last host attempt 9 HOLD/0 deleted. Owner's new cPanel Domains/Document Root and CloudLinux Node App Root screenshots on 2026-10-31 show none of these nine targets bound as vhost/Node application; **B1007** uses this documented user-attested visual list instead of repeatedly failing UAPI JSON parser, and requires explicit typed acknowledgment that mappings have not changed. New [source](docs/operations/B1007-ALD1N-VERIFIED-9-CACHES-CLEANUP.sh) full SHA256 `5db971ea324b7d9215091fe325ae188aaa6ae6d8f75025d0268eabf52d10842c`, normalized digest `b135bfef9fe64a7a0c32372e2b7d43c0fd65bae9d7e2da8d91fcc1d968384294`, [QA/report](docs/operations/B1007-LOCAL-QA-AND-NEXT-PRIORITIES.md), [owner Drive ZIP](https://drive.google.com/file/d/1yGtuKDdXUILCw4joSTliBvN19P_e3Ae2/view), local **45/45 PASS** including 9 synthetic deletions and active-cron/docroot-ancestor/HTTP503/symlink/inode-drift/tamper guards; real CloudLinux **NOT RUN**. One owner action: transfer exact source to canonical `incoming/B1007-ALD1N-VERIFIED-9-CACHES-CLEANUP.sh`, run `bash /home/icaffeco/ald1n-project/incoming/B1007-ALD1N-VERIFIED-9-CACHES-CLEANUP.sh --run`, then only for READY exact pins (if current screenshots remain valid) type `DELETE_B1007_VERIFIED_CACHES_DOCROOTS_UNCHANGED`. All live mount/process/cron/cwd/fd/owner/45min/count/id/SHA/CMS/API gates retained; no parent trees/backups/current CMS/Expo/B990/old Node22, other websites/mail/cPanel/global npm. After authentic report: (1) responsive CMS dashboard quick actions scroll fix in `apps/cms/current/public/assets/css/ald1n-dashboard-d1.css` (max-width 720 currently nowrap + overflow-x:auto); (2) independent Node22 for EAS without production build; (3) CloudLinux STOPPED legacy Node app deregister and exact old root cleanup with rollback. Do not repeat B997-B1004 or infer actual inode gain until B1007 poststate. Existing accepted milestones unchanged.
- **B1006 (2026-10-31) old Node web process STOPPED, retirement still gated:** Owner's two current cPanel Node edit screenshots show `app.js` startup, Node 22.23.4 development mode, no listed env variables, and transition from **STOP APP** to **START APP** (web process stopped). Owner reports Laravel CMS and existing Android APK still work. This establishes only tested app features independent of Node web process, **not** safe deletion of all old Node files. `apps/mobile/current/package.json` Android/iOS EAS commands still hardcode `/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node`, a potential build failure if CloudLinux Node Selector app is destroyed or its environment pruned. [B1006 decision](docs/operations/B1006-NODE-STOP-AND-RETIREMENT-GATE-20261031.md): retain STOP state and old files/nodevenv; phase 1 read-only examine old startup code and preserve unique source then decouple EAS from managed nodevenv with gated test; phase 2 cPanel app de-register and exact old root cleanup only after build/rollback checks. Never remove app root with `rm -rf` before removing selector entry. Report979 separate nine-cache deletion remains 9 HOLD/0 deleted. No new cleanup controller or source/build/production changes were executed.
- **B1005 (2026-10-31) cPanel screenshots resolve visual domain root inventory and REVERSE stale Node app assumption:** The owner provided actual cPanel Domains document-root screenshots plus CloudLinux Setup Node.js App screenshot. Current CMS root is `/home/icaffeco/ald1n-project/apps/cms/current/public`. Legacy hostname `mobile-build.ald1n.com` is STILL configured with domain root `/home/icaffeco/mobile-build.ald1n.com` AND one **started v22.23.4, development-mode Node app** whose Application Root is that same old directory. Report973 DNS RC6 did NOT prove service retirement. All nine old npm-cache/Expo node_modules trees are *outside* the displayed domain roots and Node app root, although this screenshot does not replace runtime/process/cron/cPanel validation. Exact [two-stage migration decision](docs/operations/B1005-CPANEL-DOCROOT-NODE-REHOME-DECISION-20261031.md): do NOT point cPanel Passenger to the Expo `apps/mobile/current` directory, relocate source only to a separate service path after startup/config identification, then change app root with a rollback gate. **Critical:** `apps/mobile/current/package.json` hardcodes `/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node` in Android/iOS EAS commands; naive rehome can break build toolchain. Protect old Node app, `nodevenv` and B990 until tests PASS. Alternative if old service is only a default stub: decouple Node22 build CLI and retire rather than migrate. No host mutation, GitHub source-code changes, build, deployment or cache deletion from this evidence update; Report979 remains 9 HOLD/0 deleted.
- **Report979 / B1004 repeated cPanel metadata HOLD (2026-10-30):** Actual [owner report](docs/operations/979-B1004-EXACT-NINE-GENERATED-CACHE-CLEANUP-20261030-224227.md) SHA256 `36c5565ee78c8ffa0bbd397a0f6d768d339f8abe2d7cf8d74f4b8fe2dc843952`, UTC22:42:27, confirms PHP parser available and source integrity, CMS/API HTTP401/401, B995 encrypted ops archive SHA PASS, but **`CPANEL_DOCUMENT_ROOT_INVENTORY=UNVERIFIED_CPAPI2_JSON_COMMAND method=NONE`**, **HELD=9, DELETED=0**. The B1004 script overwrites per-attempt UAPI list/hash and API2 errors with the final API2 status; the final code is **not enough to infer a concrete cPanel access cause**. Do **not** produce another guess-and-try destructive controller, bypass cPanel gating, or rerun B997-B1004. Only genuinely missing user action: current cPanel > Domains screen(s) including all document roots and associated subdomains, and CloudLinux Setup Node.js App application roots (or absence of that feature). Decide final exact-nine scope after evidence; preserve all current CMS/mobile/B990, Node22, other websites, shared hosting, all SQL/private/backups. Actual inode release 0; 340784 remains a hypothetical upper bound, not achieved.
- **Report977 / B1002 rerun (2026-10-30; superseded script, NOT new B1003):** Owner supplied actual [host Report977](docs/operations/977-B1002-EXACT-NINE-GENERATED-CACHE-CLEANUP-20261030-210952.md) SHA256 `3d011059aff8737a329d0f0caaf31cc9898edf253213d29befd4133d6febee8c`. The file names the executed script **`B1002-ALD1N-EXACT-NINE-GENERATED-CACHE-CLEANUP.sh`** (older defective cPanel parser), despite previous B1003 handoff. Result repeats `CPANEL_DOCUMENT_ROOT_INVENTORY=UNVERIFIED`, **9/9 HOLD, DELETED=0**. No B1003 host test occurred per this evidence; do not troubleshoot or redraft a nonexistent B1003 failure. The only current action is to transfer the existing **B1003** exact-source script (SHA256 `dd5d22fd251606c57d0ce43eb4f6c68592c43cc5bb59e9216190a6d9c6c6e2b6`) from its authenticated [final owner Drive ZIP](https://drive.google.com/file/d/15XmMB0lPG70YxxExe77TYKhfSrVfa-wc/view), then execute `bash /home/icaffeco/ald1n-project/incoming/B1003-ALD1N-FIXED-CPANEL-EXACT-CACHE-CLEANUP.sh --run` and, if READY paths appear, type `DELETE_B1003_VERIFIED_GENERATED_CACHES`. User explicitly requested at most two operational steps; **no more generic audit loops or rerunning B1002**. Preserve unknown directories, backups, other domains and Node22/B990. No inode release yet.
- **B1003 (2026-10-30) single-script final bounded cleanup after cPanel UAPI parser flaw:** Actual CloudLinux [Report976](docs/operations/976-B1002-EXACT-NINE-GENERATED-CACHE-CLEANUP-20261030-193110.md), SHA256 `cc57ee5705c79e1f071077ebf01c10f2f800c3e603ebdb3a16143c4208610a20`, showed `CPANEL_DOCUMENT_ROOT_INVENTORY=UNVERIFIED` and **9/9 `HOLD_CPANEL_BINDINGS_UNVERIFIED`, DELETED=0**; this is **not** evidence that the cached roots are live. B1002's parser erroneously expected top-level `status/data` while CloudLinux cPanel UAPI commonly wraps them in `result.status/result.data`; official cPanel docs additionally specify `/usr/local/cpanel/bin/uapi` on CloudLinux. New exact [B1003 script](docs/operations/B1003-ALD1N-FIXED-CPANEL-EXACT-CACHE-CLEANUP.sh), source SHA256 `dd5d22fd251606c57d0ce43eb4f6c68592c43cc5bb59e9216190a6d9c6c6e2b6`, internal normalized source SHA `ae3266f621f490aa73350a940548f734d26d80aaad80c88e90152dc2197ae161`, corrects schema and supports the read-only `DomainLookup getdocroots` fallback, but **fails closed** if both APIs cannot provide a verified nonempty docroot list or any root conflicts. [Handoff](docs/operations/B1003-HANDOFF-REPORT976-CPANEL-FIX-20261030.md), local isolated **29/29 PASS** (including 9/9 synthetic deletions after owner confirmation, cPanel wrappers/top-level/fallback and malformed maps, alias symlink, active cron, HTTP503, SHA tamper), actual CloudLinux B1003 **NOT RUN**. Download the [verified Google Drive ZIP](https://drive.google.com/file/d/15XmMB0lPG70YxxExe77TYKhfSrVfa-wc/view), transfer script byte-exact to `/home/icaffeco/ald1n-project/incoming/B1003-ALD1N-FIXED-CPANEL-EXACT-CACHE-CLEANUP.sh`, run **ONCE** `bash /home/icaffeco/ald1n-project/incoming/B1003-ALD1N-FIXED-CPANEL-EXACT-CACHE-CLEANUP.sh --run`, then ONLY if READY candidates are presented approve interactively by typing `DELETE_B1003_VERIFIED_GENERATED_CACHES`. It checks pinned Report976/B995 hashes, nine old cache identity/counts, mount/owner/symlink/process/cron/cwd/FD/45-min age, cPanel docroot collision, CMS/API 401 before/after and same-parent guarded deletion, writing one numbered host report. **Do not try any more generic diagnostic batches or rerun B1002.** If the cPanel gate still HOLDs, the second and last user action is to supply direct cPanel Domains docroot evidence or grant read-only metadata access; do not bypass the gate. Full-account deletion is NOT authorized: all backups/SQL/private (current offsite restore not proven), current CMS/mobile/B990, Node22/old domain, unrelated websites/mail/cPanel/global .npm and unclassified old source snapshots remain PROTECTED. Actual freed inodes still 0 until the B1003 host report confirms after-state. Program 7/17 and CMS 7/15 unchanged.
- **B1002 (2026-10-30) AUTHENTIC Report975 9/9 review-eligible; separate exact-path CLEANUP CONTROLLER PREPARED, not run:** [Owner Report975](docs/operations/975-B1001-CONSOLIDATED-CLEANUP-READONLY-AUDIT-20261030-192243.md), SHA256 `8cde5830136190477031a0c968d3a1201ff079d182bd488732bce7c5c430b038`, confirms B1001 host read-only PASS, **9/9 exact generated npm-cache/old Expo node_modules candidates** with unchanged inode identities and **340,784 potential inode entries**, **0 actually deleted**; CMS/API HTTP401/401, local B995 ops SHA PASS. cPanel domain command success did **not** prove actual docroot bindings; shared host cross-user processes remain UNVERIFIED; current SQL/private offsite restore NOT independently confirmed and all backups PROTECTED. **B1002 next**: independent [exact cleanup script](docs/operations/B1002-ALD1N-EXACT-NINE-GENERATED-CACHE-CLEANUP.sh), full SHA256 `8191d5875ebbab39190f2e450fc4ac7483d9175172bb7085c8979fde556b1f09` (normalized embedded hash `ee246ac1fe70e11b557818048713bc4604c87b6199ed42c66290234afb54b026`), 17,663 bytes; [handoff](docs/operations/B1002-HANDOFF-REPORT975-EXACT-CACHE-CLEANUP-20261030.md); exact ZIP on private Drive `Ald1n-mobile/04-SCRIPTS/ALD1N-B1002-GUARDED-EXACT-CACHE-CLEANUP.zip`. Local isolated Bash/9 synthetic deletions/owner TTY/cPanel path collision and missing metadata/cron/symlink/HTTP503 source integrity **26/26 PASS**, not actual host execution. Only canonical `/home/icaffeco/ald1n-project/incoming/B1002-ALD1N-EXACT-NINE-GENERATED-CACHE-CLEANUP.sh` with `--run` in interactive SSH, typed `DELETE_B1002_VERIFIED_GENERATED_CACHES`; algorithm pins 9 Report975 dev:inode:uid and exact inode totals, verifies originals/B995 archive, cPanel DomainInfo full parse via python3 (else HOLD), process/cron/cwd/open-FD, no symlink/mount/foreign owner/recent edits, before and after CMS/API HTTP401, per-target refresh and same-parent quarantine/one-file-system removal, next numbered host report. **Fail CLOSED if domain root mapping unknown** (no target deleted), never widen allowlist. Protected: current CMS/mobile, B990, old domain/nodevenv, global npm, other sites, all backups/SQL/private, all other root snapshots. No production/EAS/OTA/Google Play changes. Claim zero real deletions until actual B1002 host report. Do not run older B997/B997R/B998 or `SUPERSEDED-DO-NOT-RUN` B1001 prototype.
- **B1001 (2026-10-30) ONE consolidated READ-ONLY Ald1n cleanup audit pending CloudLinux:** Owner Report974 (B1000) [authentic file](docs/operations/974-B1000-NINE-CACHE-READONLY-ELIGIBILITY-20261030-182946.md), SHA256 `cc6546269c345d164da6736d327719075fca5ec960e1559eae86c7708959de9e`, found **9/9 review-eligible old cache/module roots**, **340,784 potential inode entries** and **zero actually deleted**. At owner request, prepared **one** further consolidated preflight `/home/icaffeco/ald1n-project/incoming/B1001-ALD1N-CONSOLIDATED-CLEANUP-READONLY-AUDIT.sh` ([source](docs/operations/B1001-ALD1N-CONSOLIDATED-CLEANUP-READONLY-AUDIT.sh)), full SHA256 `5303fca9cb8f80593982fbf5f8a43900ff3c1e12c3db721c47f72b3106b4f7fd`, normalized built-in SHA `b1044a93870c1e9c1ffd79dbf4ece55a404ac2d846f18b32a7087f30144947a7`, 18,340 bytes. [Handoff](docs/operations/B1001-CONSOLIDATED-CLEANUP-AUDIT-HANDOFF-20261030.md), authenticated owner Drive ZIP `Ald1n-mobile/04-SCRIPTS/ALD1N-B1001-CONSOLIDATED-READONLY-AUDIT.zip`. Local syntax/selfhash and synthetic fixtures **26/26 PASS** (9/9 sources, cron HOLD, 503 HOLD, symlink unrelated-site sentinel preserved, archive missing HOLD, tamper/noncanonical refusal); **REAL CLOUDLINUX HOST NOT YET RUN**. Operator runs only `bash /home/icaffeco/ald1n-project/incoming/B1001-ALD1N-CONSOLIDATED-CLEANUP-READONLY-AUDIT.sh --run`, providing next numbered report (expected Report975 if no additional reports). Audits verified Report974 root identities/counts, current CMS/API health, mountinfo/process/cron/cwd/fd, B995 encryption SHA and offhost scope, 45-min activity/owner, top-level `ald1n-*` and `.ald1n-*` roots, `ald1n-project/tmp` first-depth, legacy cPanel vhost/Node22 dependencies, source/layout and backup gaps. **READ ONLY except one numbered Markdown report; NO DELETION.** cPanel docroots/cross-user processes and active SQL/private current restore remain explicitly UNKNOWN/HOLD without independent authorization. The earlier prepared but never-executed **B1001 generated-cache deletion prototype and ZIP have been renamed `SUPERSEDED-DO-NOT-RUN` in Drive**; DO NOT dispatch them. After actual host report, separate destructive batch may delete only independently reverified exact unused Ald1n nonproduction paths, with fresh race/process/mount/HTTP protections and typed owner confirmation. All SQL/private backups, CMS/mobile/B990, old `mobile-build.ald1n.com` and `nodevenv`, global npm, unrelated domains, cPanel/mail remain protected. No claimed achieved inode recovery.
- **B1000 (2026-10-30) targeted CloudLinux nine-cache READ-ONLY preflight ready; no cleanup yet:** B999 settled the stale `mobile-build.ald1n.com` DNS health dependency (Report973 curl6 vs current CMS HTTP302). Prepared exact `incoming/B1000-ALD1N-NINE-CACHE-READONLY-ELIGIBILITY.sh` (full SHA256 `56093d768ae66fc00f13b104709fa8171ffc2eeaceecaa71ad7870d26bc9cdc1`, normalized selfcheck SHA `752451d86c364d592c32d9938f258ace2bb82678e59ab7f4aecec3d368616aed`), [canonical GitHub source](docs/operations/B1000-ALD1N-NINE-CACHE-READONLY-ELIGIBILITY.sh), [handoff](docs/operations/B1000-HANDOFF-READONLY-NINE-CACHE-ELIGIBILITY-20261030.md), authenticated ZIP+script on owner Drive `Ald1n-mobile/04-SCRIPTS`. Locally tested Bash/selfhash/tamper, paths/guards **19/19 PASS**. Real CloudLinux host execution **PENDING**. One owner canonical `--run` invocation only; report next numbered receipt (expected 974), with exact 5 old Sept npm-cache + 4 obsolete isolated Expo node_modules inode bounds, active process/cron/open-fd/cwd/uid/filesystem/symlink/mount/recent changes and current CMS+API HTTP. **READ ONLY:** writes only its own next numbered `docs/operations` report; it does NOT delete, quarantine, deploy, build, touch other domains or alter backups. `ELIGIBLE_FOR_SEPARATE_REVIEW_ONLY` is NOT authorization to delete. Next destructive batch needs separate reviewed source+independent live safety evidence and explicit owner gate; do not rerun B997/B997R/B998 or fake retired DNS. Old mobile-build folder and `nodevenv` remain excluded. Program 7/17=41.2%, CMS 7/15=46.7%, UI 6/6=100% unchanged.
- **B999 (2026-10-30) Report973 confirms retired mobile-build DNS; do NOT confuse old hostname with current mobile source:** Authentic [owner CloudLinux Report973](docs/operations/973-B998-MOBILE-BUILD-HTTP-READONLY-DIAGNOSTIC-20261030-162132.md), SHA256 `9b97a8a9d269b8a54b662ad0184db544bcfae8c1b72f2d56a27fc0da820464f1`, is read-only and confirms `cms.ald1n.com` HTTP302/curl RC0 and `mobile-build.ald1n.com` HTTP000/curl RC6 on exact/no-curlrc/IPv4/HTTP1.1 probes. The owner confirms this is a **retired legacy build hostname/folder**, while canonical source lives in `/home/icaffeco/ald1n-project` (`apps/mobile/current`, `apps/cms/current`). The former mobile-build HTTP health test in B997 is now **stale/inapplicable**, not proof that the real Laravel/Android service is broken. Future bounded cleanup must gate on `https://cms.ald1n.com/` AND a verified live `https://cms.ald1n.com/api/v1/` route (e.g., auth-protected `GET /bootstrap` with expected no-credential 401/403), plus exact source/mount/process/cron/open-fd/symlink and before/after inode checks. **Do not rerun B997/B997R or B998**, spoof DNS, or skip all health gates. A missing DNS record does **not** independently prove the old `/home/icaffeco/mobile-build.ald1n.com` or `/home/icaffeco/nodevenv/mobile-build.ald1n.com/22` is no longer used: the latter remains the documented Node22 executable path and is protected. All backup roots, live CMS/vendor/storage, B990 release evidence, other domains and shared caches remain excluded. No cleanup has run: Report972 DEL=0 and Report973 diagnostic-only. One explicitly bounded replacement script requires genuine safety verification before manual execution; no source, EAS, OTA, Play or hosting mutation is authorized by this reconciliation. [B999 reconciliation](docs/operations/B999-REPORT973-LEGACY-VHOST-MIGRATION-RECONCILIATION-20261030.md).
- **B998 (2026-10-30) new authentic Report972 / mobile-build HTTP HOLD:** The owner-supplied host [Report972](docs/operations/972-B997-VERIFIED-GENERATED-INODE-CLEANUP-20261030-160918.md), SHA256 `a9860d38b4021fdc7e3bb928e65145ac23d1156a70348cb92e9f7b6ce3e0d651`, proves the previous missing-`findmnt` hurdle is past, immutable original B997 checksummed, the B995 ops encrypted archive SHA matched, but **`HOLD_B997_MOBILE_BUILD_VHOST_HEALTH_BASELINE; DEL=0; SKIP=0`**. No generated caches deleted; no before/after inode reduction recorded. B997 HTTP checker accepts 2xx/3xx/4xx if curl RC0, but collapses DNS/TLS/network transport failures and HTTP 5xx into the same HOLD without diagnostic codes. **Do not disable the mobile-build vhost check or rerun destructive cleanup speculatively.** The only immediate operator step is **B998 READ-ONLY** CloudLinux HTTPS/DNS diagnostic `incoming/B998-MOBILE-BUILD-VHOST-READONLY-DIAG.sh` (full SHA256 `1f63eb51e6c45956079a1d2c572bf4f6441af52b3896d87c080f594e8e08b2bc`; [source](docs/operations/B998-MOBILE-BUILD-VHOST-READONLY-DIAG.sh); [handoff](docs/operations/B998-REPORT972-MOBILE-BUILD-HTTP-HOLD-HANDOFF-20261030.md)). Local Bash/self-hash PASS, isolated mock QA 19/19 PASS; real CloudLinux result PENDING. This script only creates the next numbered report, does not invoke B997 or delete any files. CMS/mobile-build health or network root cause and all original cleanup gates remain unresolved pending actual host evidence. Offhost B995 scripts/reports archive is PASS, current SQL/private offhost recovery is NOT independently proven restore-ready; do not delete local backups, current B990 source, mobile-build service, any other domains, production runtime or unclassified files.
- **B997 (2026-10-30) OFFHOST OPERATIONS ARCHIVE PASS + generated cache deletion scope:** Owner uploaded B995 GPG archive; connected Google Drive independently returned the **full 221,001,337-byte encrypted file** and SHA256 `cba29ee1c6b708a3c01010aee25f738524b9fceb8a11876369b587063d273f1a` exactly matching authentic host Report971, after B995 local GPG decrypt/TAR manifest PASS. Report971 was separately GitHub/Drive archived. Private Drive object `1zhm08VTs3tpbvVXUxQaCGCX50WuS0q9D`. Historical 855,727,996-byte B959 SQL/private recovery GPG was also found on Drive and moved into a dedicated PRIVATE recovery folder, but **remote SHA256 could not be fetched with the 256MB connector streaming limit** and its Oct28 contents may not reflect Oct30 SQL; **do not delete all local backups**. **B997 first cleanup** only permits nine hard-coded, independently enumerated *generated cache contents* (five Sept 2026 nested `npm-cache`, four stale Expo test clone `node_modules`) under project-specific roots, with source/SHA/uid/mount/lstat, recent modification, current process/cron/cwd/open-fd, HTTP CMS/mobile-build vhost before/after checks, one human typed confirmation and per-target logs. Production CMS/vendor/storage, B990 release checkout, mobile-build vhost/node_modules, global npm, all other customer domains, shared cPanel/mail and every backup root are **excluded**. Script SHA256 `fe68b16fd46ea0177a6e634307c4d62bf57157197564a128b08b1a5b83757263`, verified on Google Drive `1HByH-p9GiGI-4bebp4u06CIcG3F21hCp`. First uploaded Drive draft is SUPERSEDED; do not run it. Both successful and unrelated-domain/symlink negative local tests passed; **real CloudLinux B997 has NOT RUN**. [Full B997 ledger](docs/operations/B997-INDEPENDENT-OFFSITE-PASS-GENERATED-INODE-PRUNE-WAVE1-20261030.md). Never claim achieved inode reduction before actual next numbered host report.
- Follow [B992 read-only host census](docs/operations/B992-HOST-INODE-FORENSIC-AUDIT-HANDOFF-20261030.md) and ingest its authentic host report before creating a destructive batch. Classify an **exact non-overlapping path manifest**: `DELETE_VERIFIED_NONPRODUCTION`, `WAIT_FOR_OFFSITE_OR_ACTIVE_DEPENDENCY`, `KEEP_PRODUCTION_OR_UNKNOWN`; prove safe boundaries with no symlink traversal, project/runtime cross-references, checkpointed evidence and precise before/after counts. If `B992` is HOLD/partial, do not infer that unscanned paths are empty or safe. No unrestricted `rm -rf /home/icaffeco`, `git clean -fdx`, blanket cache reset or mass filename glob.
- The owner requests **zero redundant local Ald1n backups as an end goal**. This **conditionally supersedes** the older two-stable-local-backup retention rule **only after** an independently verified, encrypted, access-controlled OFF-HOST backup of the actual current SQL + private files + required rollback/config exists, with checksum and feasible restore verification. Until that recovery gate PASSES, **retain the two verified stable local backups** per the existing rule; merely producing a local GPG archive is not an offsite copy. As of 2026-10-30, [Report959](docs/operations/959-B959-CLOUDLINUX-OFFSITE-ROLLBACK-PRIVATE-RESTORE-20261028-191705.md) reported `OFFSITE_GOOGLE_DRIVE_UPLOAD=NOT_PERFORMED`; do not pretend that is resolved. The old GPG archive itself and any sole restore-capable backup remain protected until proof is obtained.
- The old GitHub `main` remains the source of truth even if an isolated local checkout can be deleted after its real build and troubleshooting are definitively closed. Before deleting current/predecessor release evidence, GitHub archive and Drive copy must be proven, with no outstanding recovery reference to the local copy. Retain actual production CMS, DB/private media, current secrets, necessary runtime binaries and host control files.
- Do the actual high-inode cleanup in **separate bounded batches** only after precise host evidence: immutable target list, expected inode reclamation, no live-process/vhost/cron use, no symlink or mount traversal, fail-closed exact path verification, pre/post metrics, app/API smoke and immutable GitHub+Drive report. If not demonstrably safe, **HOLD rather than delete**. Never report planned deletions as already executed.

### Operation reports: GitHub is the historical archive, hosting is an active workspace

The production hosting checkout must not accumulate historical operation reports.

- Keep locally only the current report and, when required for state binding/recovery, the immediate predecessor report.
- Before removing a report not otherwise present on GitHub, checkpoint it to GitHub first.
- Historical reports may be removed from the hosting working tree after GitHub authority is proven.
- Large report-history cleanup must create a dedicated Git archive tag/commit before deletion so evidence remains recoverable.
- Never delete the current uncheckpointed report or a predecessor that an active recovery still binds by SHA-256.

### Contract smokes must not depend on disposable historical fixtures

Read-only contract and health smokes must validate current contracts and immutability, not require a historical test record to exist forever. Total Product Purge contract validation must not fail merely because historical Product ID 19 was legitimately removed later. A fixture may be inspected when present, but its absence is not itself a contract failure.

### Laravel -> Mobile feature parity is mandatory

This is a permanent project-owner rule from **2026-09-19**.

Every functional Laravel/CMS feature change must be carried through to the Mobile application where that capability is user-facing or consumed by Mobile. A backend feature is not considered fully closed merely because the Laravel implementation passes.

For every applicable Laravel feature change, the implementation/release chain must explicitly account for:

1. Laravel route/service/model/database behavior and permissions.
2. Canonical OpenAPI changes across exactly the three active copies when the API surface changes.
3. Mobile API client/types/query-key/validator parity in the same backend/API batch whenever Mobile consumes the contract.
4. Mobile UX/workspace parity in the same batch or in an explicitly named immediately-following batch that remains part of the same feature acceptance chain.
5. Regression guards proving that Product Variants remain decommissioned and existing permission/business authorities are reused instead of duplicated.

Do not create a parallel Mobile business rule to compensate for missing Laravel authority. Do not create a parallel Laravel endpoint tree when an existing canonical feature namespace can be extended safely. Infrastructure-only backend work that has no Mobile-visible or Mobile-consumed behavior may be documented as not applicable, but that exception must be explicit in the operation report.

A feature that intentionally splits backend/API work and Mobile UI work across consecutive batches remains **in progress** until the Mobile parity batch passes.

### Git scope authority uses machine-stable manifests

This is a permanent operational rule from **2026-09-19**, adopted after Reports451-454 demonstrated that human-readable Git status rendering is not a safe machine authority for exact scope decisions.

Do not parse `git status --short` to decide whether an allowlist passes. It may apply rename detection or collapse untracked directories and therefore render a valid file set differently from the semantic file set.

For automated scope authority use these exact sources:

1. Staged paths: `git diff --cached --name-only --no-renames` and, when status letters are required, `git diff --cached --name-status --no-renames`.
2. Tracked unstaged paths: `git diff --name-only --no-renames`.
3. Untracked paths: `git ls-files --others --exclude-standard`.
4. Normalize each list with stable sorting and compare it against an explicit expected manifest.
5. `git status --short` may still be printed for human diagnostics, but it must never be the pass/fail authority for exact path scope.

Do not broaden cleanup to compensate for a manifest mismatch. Fail before mutation unless every unexpected path is classified explicitly.

### Immutable operation-report whitespace evidence

Operation reports archive raw terminal evidence and must preserve their bytes. Do not rewrite or trim historical report lines merely to satisfy whitespace style checks.

Before every commit, still execute `git diff --cached --check`. The normal rule remains RC=0 for source, configuration, specifications, `AGENTS.md`, and ordinary documentation.

A non-zero result is permitted only for a dedicated evidence-only archival commit when all of the following are true:

1. The staged manifest is exact and every staged addition/modification is under `docs/operations/`; a deliberate rotation deletion under the same directory is also allowed.
2. The full `git diff --cached --check` output is captured in the current operation report.
3. `git diff --cached --check -- . ':(exclude)docs/operations/**'` returns RC=0.
4. No product source, configuration, spec, `AGENTS.md`, generated contract, or other non-report path is staged in that commit.
5. The report files are committed byte-for-byte; do not normalize historical terminal whitespace.

This exception is for immutable operation-report evidence only and must never be used to waive whitespace errors in product/source or authority documentation commits.

---

## 27. Updating this file

Update `AGENTS.md` whenever a newly proven hosting/release rule becomes permanent.

When updating:

1. Base the rule on verified evidence from an operation report or reproducible environment behavior.
2. State the failure pattern and the approved replacement pattern.
3. Avoid hardcoding transient OTA group IDs or one-time report filenames as permanent rules.
4. Keep stable runtime/tool/path authorities explicit.
5. Commit the documentation change separately when practical.

This file is intended to remain the first operational reference for future Ald1n CMS & Mobile work.

## 21. AAB27 signed binary 16KB page-size gate (2026-10-21)

- Play rejected Android v1.5.0 versionCode 27 (EAS UUID `e3bf78a2-0410-4fe5-a279-e0af5624ca0d`, source `deb98808afe2568ed06da1c684b9b51d60734b12`, runtime `1.5.0-native1`) for 16KB memory-page incompatibility. Do NOT advise bypass via 'Ipak nastavi' or reuploading unchanged AAB27. Build FINISHED means signed, not Play eligible.
- Read-only signed-artifact CI [39671783825](https://github.com/AldinAga/ald1n-project/actions/runs/39671783825) AAB SHA-256 `f684779aa472366c359f28ee95c60648ee6739259de752e59d0f2b0e4fed36bf`: 55 packaged 64-bit ELF libs checked. 54 have LOAD >= `0x4000`; one FAIL is `base/lib/arm64-v8a/libnativegauss.so` LOAD `0x1000`.
- Source is transitively `com.github.QWEA0:liquidglass:v2.0.0`, through `react-native-android-liquid-glass@0.2.1`. Independently verified newer JitPack `v2.0.11` AAR SHA-256 `8218f8075c3c447241f2a1e96d502fc48718c6d007e9e05347bb9669901f13a9`, arm64 `libnativegauss.so` LOAD `0x4000` in [CI 39672535294](https://github.com/AldinAga/ald1n-project/actions/runs/39672535294). Pin `rootProject.ext.liquidGlassVersion` in root Android Gradle through existing Expo config plugin before RN Fabric bridge Gradle evaluation, not just app module. Do not make an unneeded Expo SDK/RN major upgrade.
- Mandatory new AAB gate per [Android 16KB docs](https://developer.android.com/guide/practices/page-sizes): inspect all 64-bit native ELF `LOAD p_align>=0x4000` in actual signed AAB, check its bundle config for `PAGE_ALIGNMENT_16K`, generate APK with bundletool and run `zipalign -c -P 16 -v 4`, plus supported device 16KB physical/emulator smoke where available. Vendor AAR or source unit PASS alone is not Play compatibility.
- Native replacement requires a different Expo `runtimeVersion` (candidate `1.5.0-native2`, old AAB27 `1.5.0-native1`), fresh authenticated EAS remote code attestation and strictly higher new `versionCode`, preserved signing, post-build bundle verification and an **independently authorized one-time build**. No automatic Play upload/rollout/OTA, no silent CloudLinux production mutation.
- Historical source fix [PR82](https://github.com/AldinAga/ald1n-project/pull/82) was merged into the former dedicated release branch; the current Android 16 KiB source has since been merged into GitHub `main` by [PR116](https://github.com/AldinAga/ald1n-project/pull/116), merge `814256a7e3ed5779ea8bb5b1e1c36f75111bcbbf`, with seven exact-head source/native CI gates PASS. This proves source/CI readiness **only**. No independently verified new production signed AAB or Google Play 16 KiB acceptance exists yet. Do NOT claim a new AAB without exact EAS build UUID and post-build binary inspection.
- **B989 (2026-10-30) Android v1.5 release identity gate — source merged, NOT built:** source PR #118 merged in GitHub main at exact release source SHA `08f8e09eeb74ba6b6e3264b7042b59f30e8538b3` after three exact-head CI SUCCESS (release-candidate QA `41021655372`, MariaDB QA `41021655371`, native16KiB/Fabric QA `41021655370`). The normal `production` EAS profile remains historical Build25 (`1.0.0`/`1.0.0-build17`) and must never be used to build v1.5. New dedicated EAS profile **`production-v15-16kb`**, channel **`production`**, Android **AAB**, `autoIncrement=true`, Expo CLI25, propagates `ALD1N_V15_RELEASE_GATE=approved` and `ALD1N_V15_RELEASE_PROFILE=production-v15-16kb` to local config and remote EAS builder, selecting app **`1.5.0` / runtime `1.5.0-native2`**. The flag is **a build-configuration switch, never release authorization**. The standalone Build25 controller still rejects direct CLI execution. Before any expensive EAS dispatch, require a verified immutable isolated exact-SHA checkout, authenticated EAS account/remote code, owner-asserted **highest Play versionCode across all tracks**, strictly new code, one-time typed owner authorization, persistent dispatch intent, `--freeze-credentials`, and no `--auto-submit`. Owner on 2026-10-30 confirms signing keys unchanged; this is **owner testimony**, not independent signer proof. Never export private keys; actual signed AAB must pass cryptographic certificate and 16KiB binary tests after build. There is NO approved Google Play rollout, OTA, CMS deployment or SDK58. **B990 source-only controller** `docs/operations/B990-M16-ONE-TIME-AAB-CONTROLLED.sh` must be transferred byte-exact to canonical host `incoming/` before use, checksum `5e2e123ddc9c968d662641b3756dbf6c36ed88e741f9b4ad7791cfc63d4ace4c`. Host run is owner-interactive and may spend one EAS quota ONLY after all prompts/gates. Actual signed production AAB not yet attested.
- **B991 (2026-10-30) post-start AutoSubmit hazard:** Owner reports B990 Android 16KiB AAB running on EAS; the Build UUID/status is not yet independently attested. NEVER tell owner to run a new `eas build --auto-submit` to attach a submission to a build that has already started; that may dispatch a second paid build. Once genuine B990 signed AAB passes 16KiB ELF, AAB BundleConfig, derived APK zipalign, signer continuity, Play-unique versionCode and physical Android UAT, an independently approved Play submission may use **`eas submit --platform android --profile production --id <verified-EAS-build-UUID> --non-interactive --wait`** from the verified B990 isolated source via an authorized canonical incoming script. **Caution: the current `submit.production.android` has `track=production` and `releaseStatus=completed`, which can immediately publish to users.** For Play upload without rollout use a separately reviewed **draft** submit profile or manual upload, never the current completed production profile. Do not claim success from `EAS Build FINISHED` alone; no Play submit or production rollout without explicit distinct owner authorization. [B991 audit](docs/operations/B991-EAS-BUILD-IN-PROGRESS-CONDITIONAL-SUBMIT-HOLD-20261030.md). 
- **B988 release branch drift:** historical `release/android-v15-production-aab-one-20261021` is **diverged** from the B984 pinned main source `8049f22d208bac1ee47fd4133bb8e1a737cfe457` (GitHub compare base=release branch, head=B984 main SHA: main 254 ahead/82 behind, 30 `apps/mobile/current/` changed paths). DO NOT run the stale B963 one-pass controller or checkout that branch as a substitute for the verified B984/B986 isolated main source without independent exact-source/content reconciliation and all release gates.
- B984/Report965 isolated source PASS; B986/Report967 EAS CLI25 authenticated project/version/history PASS with remote Android versionCode **27** and no new AAB. Next independent gate requires actual Google Play Console **upload key certificate SHA-256** (NOT Play app-signing certificate), highest Play occupied versionCode across tracks, actual EAS signer continuity, and physical Android16KiB/Liquid Glass UAT/release authority. No Play Console tool-connected attestation was available in B988. Avoid repeated SSH source/EAS metadata batches while those **external** facts are missing.

## 28. SDK 58 owner-deferred upgrade sequencing (2026-10-21)

- Expo SDK 58 is officially stable (2026-10-15); it targets RN 0.88 / React 19.3, Android compileSdk 37 and Node 22.13+ per https://expo.dev/changelog/sdk-58 and https://docs.expo.dev/versions/v58.0.0/ . This is **readiness context, not authorization** for dependency or native release changes.
- Owner requested **prepare for SDK 58 only AFTER current planned tasks**. First resolve the actual CloudLinux Laravel deployment, verify the Android 16 KB signed AAB fix / Google Play artifact state, and reconstruct the latest approved mobile source; do not assume the current `main` mobile v1.0.0 config represents the separately built Android v1.5 release branch.
- Owner **reported manual Google Play publication**; do not call this independently verified and do not treat publication as evidence that AAB27's known 4 KB `libnativegauss.so` problem was fixed. Preserve section 21 signed-binary release gate.
- Use an isolated exact-source SDK58 branch with the existing Node22/npm10, lockfile/validator, JDK17 and no-`expo install --fix` CloudLinux rules. Review RN0.88 Strict TypeScript, Expo Router navigation and native dependency compatibility; reserve a new runtimeVersion and new signed AAB for a separately authorized build. See `apps/mobile/current/docs/SDK58-UPGRADE-READINESS.md`.


## 29. UI794 delivery and browser regression guard (2026-10-22)

- Reproduce whole-document horizontal overflow with actual scrollbar/clientWidth geometry before changing CSS. Dashboard viewport paint belongs on body; a centered 100vw pseudo-element was proven to overflow when a classic vertical scrollbar is present. Do not hide document overflow as a substitute for a root-cause fix.
- Test delivered operator bytes under the exact advertised canonical basename, plus wrong-name and tamper negative cases, before delivery. File rename does not change the content hash; distinguish path validation from byte-integrity checks.
- Browser tests must follow approved UI semantics while retaining security coverage. Moving a retirement preview link to a separate danger zone requires new geometry checks, not removal of permission/confirmation/no-submit tests. Real public checkout is a separate business/security scope, not an incidental UI route-unlocking change.

## 30. Owner clarification: operator orders, payment warranties and recipient isolation (2026-10-22)

Read `docs/operations/000-LATEST.md` and its current progress/requirements report before every new task. Report795 closed all six prior UI checks; do not repeat successful deployment/acceptance batches. The current expanded scope ledger is 7/17 closed milestones (41.2%); CMS-only is 7/15 (46.7%); the original six UI tasks are 6/6 (100%). These are dated, equal-weight acceptance counts, NOT effort/time or total historical-project percentages. Recompute from evidence using the same explicit ledger; version any scope change.

- SuperAdministrator has an operational customer/user order context, never a personal shopping/"My orders" flow. Keep real users' scoped orders and direct sales with the real buyer and recording administrator. Remove role-inappropriate UI and server entrypoints, not historical orders, commissions or ownership data.
- Public catalog target is `https://cms.ald1n.com/katalog`; current internal `/catalog` remains authenticated. The full-retail guest storefront/cart/checkout/protected follow-up is still NOT implemented/activated by this documentation change. Never expose internal prices/commissions or grant a guest administrator identity.
- Liquid Glass animation must be shared, subtle and readable: surface highlight on interaction, no moving/dimmed text, perpetual whole-page shimmer or pointer interception. Honor keyboard focus, touch, reduced-motion/transparency and high-contrast fallbacks.
- A confirmed incoming payment must automatically issue eligible warranties, including direct sales, without waiting solely for delivery/completion. No duplicate certificate on retries, no issuance from an unverified upload/refund/zero payment, no invented duration or silent historical backfill. Owner resolved issuance timing on 2026-10-22: issue after the FIRST verified positive incoming payment, including a deposit or first installment and SuperAdministrator direct sales; full settlement is NOT required. Do not ask this again. Issuance date and contractual validity start remain distinct: preserve delivery-based validity and test a truthful pre-delivery/pending-activation representation rather than silently starting the clock at a deposit. This payment behavior is NOT implemented by B796.
- Expiry notifications default OFF per recipient and remain independent from ordinary warranty updates. Preserve existing explicit opt-ins. The allowed recipient set is the originating selling user proven through matching order-item/order/user lineage PLUS an active SuperAdministrator with their OWN opt-in. This is the owner's new, narrowly scoped extension of the previous strict-owner-only expiry rule; current deployed code has NOT yet been changed to implement it. No unrelated user/admin/assignee or global broadcast. Validate lineage, role URLs, channels and per-recipient dedupe again at queued dispatch; one recipient's toggle never enables another's. Direct sales without a selling user notify only eligible opted-in SuperAdministrators.
- Add recorded shipment number, courier and safe tracking action to the first buyer/contact/delivery panel. Reuse canonical shipment data, show truthful empty states and do not fabricate a carrier URL.
- Persist report evidence and requirements on GitHub and the canonical Drive runbook/plan. Keep application implementation, tests, production activation, owner acceptance and independent PHP-FPM attestation distinct. New requirements are PENDING until actually implemented and verified. No extra CloudLinux diagnostic script merely to repeat completed acceptance.


## 31. B796 operator, optical-layer and test-evidence guard (2026-10-22)

- PR88 merged source `6f81f2bcfca1559dae92c39be323a1c827c31239` implements Report796 R1/R3/R6. Production B796 PASS is owner-attested in Report798 (10 exact writes, runtime closure 683, static 983/983, maintenance released, Scheduler resumed, HTTP endpoints 200, exit0); accidental second Report799 is PASS_ALREADY_APPLIED NO_MUTATION (CHANGED=0, exit0). Both raw reports are privately archived on the owner's Drive, linked from 000-LATEST. DO NOT rerun this script or manufacture a rollback. Host activation is distinct from missing independent PHP-FPM/OPcache provenance and pending owner visual acceptance. Canonical operator basename `ald1n-b796-laravel-operator-workspace-v1.sh`; bind both original receipts, 683 runtime paths, ten allowed writes. No migration, payment/warranty activation or public checkout in this slice.
- Do not let Gate administrator bypass or wildcard permission projection resurrect the SuperAdministrator's personal purchasing. Preserve ordinary-buyer cart and operational direct-sale coverage. Do not alter historical owner/financial records to remove a UI concept.
- A generic CSS child rule must exclude vendor `.lg-layer`/`.lg-sheen` from `position:relative`; they must remain absolute. Test initialized optical layers, not just static fixture markup. Surface reflection must respect reduced motion/transparency/forced colors.
- Validate tracking URLs against browser-normalized short/octal/hex local IP forms as well as scheme/credentials/control characters. Use the existing canonical carrier snapshot, never a guessed destination.
- Record both full-suite baseline debt and scoped green evidence: B796 has the same 42 generic-suite problems before/after, not a globally green suite; its 70-test final scoped regression and all eight applicable PR workflows passed. Preserve named failure evidence; do not delete tests to obtain PASS.
- Owner's first-payment decision above is authoritative; R4/R5 remain separate pending work. Keep current 17-milestone denominator: 7/17 closed (41.2%), CMS7/15 (46.7%), originalUI6/6 (100%); merged source alone does not close M08/M09/M12.

- Report798 and Report799 are the authoritative B796 host receipts. Check both before any subsequent Laravel batch. Report798 SHA256 `2b2536a7852000db202317b02f2878e75b985cf3e8a24d12ef1a0dbc46cf798d`; Report799 SHA256 `f84e9b0ff37d2eee25939e6798b6c283ecfb74ae94ed0e60e0ac472d628dc25b`. A second invocation against an identical target must remain an idempotent no-op rather than repeat maintenance/source writes. The declared CLOSED ledger remains 7/17 (41.2%) pending owner acceptance of M08/M09/M12. R4/R5 first-positive-payment warranty and recipient isolation, then R13-R15 public full-price storefront, remain next; do not activate any without distinct gates.


## 32. PDF completeness and prospective warranty terms (B797, 2026-10-23)

- PR90 source126dd53eed17796733bc2a8b4cd7009d3a90ae41 fixes PDF layout/terms only; Report800 records evidence. Production B797 remains PENDING. B796 was already applied in Report798 and its accidental second invocation799 was an idempotent no-op. No replay of completed batches/migrations.
- Never draw user/product/SKU/serial/terms strings without measured width and vertical page budget. Preserve paragraphs and split unbroken tokens; dynamic row/card height must support page breaks and repeated headers. Do not truncate legal terms, notes, line items or management segments to a fixed count. Preserve original logo aspect and QR clear area. Test all PDF families with normal, extreme, empty and multi-page synthetic data, extract full end-marker content and inspect every rendered page. No actual customer data or standalone font redistribution in fixtures/delivery.
- Default warranty conditions apply to new blank rule/form/certificate snapshots only; retain existing custom/issued terms, blank historic snapshots and dates. Do not mass backfill legal restrictions or assume a newly generated document can change an already-agreed contract. A ten-day commercial field cannot curtail consumer statutory rights; cause-based physical-damage exclusions must not defeat unrelated defects or complaint intake. Actual issuer/contact/procedure data and merchant legal review are separate from technical PDF correctness. Never promise liability immunity.
- Canonical delivery is incoming/ald1n-b797-laravel-pdf-warranty-terms-v1.sh with automatic integrity,686-path closure/13 explicit runtime writes and bound original Reports798/799. No schema migration, payment-trigger/expiry activation, backup-create-prune, historical rewrite or Mobile/build/OTA/Play. Source snapshot and owned maintenance/Scheduler recovery remain mandatory; actual host receipt is distinct from sourceQA.
- All8 final PR workflows and40scopedtests/337assertions PASS; static983/983;20PDF/91pages/7215spans;9deliverygroups. Whole suite retains42 inherited problems (baseline43), zero new identities: do not report wholly green tests or discard named failures. Author review is not independent review. CI bootstrap's test stages passed but final token push was rejected for adding a workflow; preserve already-created tested commit and use authorized SHA-guarded ref rather than rerun work or elevate token privileges.
- First confirmed positive incoming payment remains resolved owner policy, including deposit/first installment/direct sale, NOT paid-in-full. R4/R5 is next and NOT implemented by B797. Maintain delivery-based validity, immutable lineage and independent default-OFF expiry opt-ins. Public storefront and native releases remain separately gated. Preserve17milestone denominator; track this additional PDF correction separately until a documented scope revision.

## 33. Batch798: first positive payment and isolated expiry (2026-10-23)

- PR91 merged exact CMS source `c4a5f060cc00b5d1be0e4d8068309524a4b99c99` for R4/R5 with 9/9 relevant CI checks PASS. Scoped PHP8.4/MariaDB10.11.19 regressions 24 tests/180 assertions; static983/983. This is **SOURCE_QA_PASS, HOST_PENDING** until owner supplies numbered receipt. See `docs/operations/803-BATCH798-SOURCE-DELIVERY-CERTIFICATION-20261023.md`. Do not confuse source merge with CloudLinux deployment.
- Owner's FIRST genuinely verified positive incoming payment (deposit/first installment/direct sale) requires one numbered warranty issuance per eligible order-item, not paid-in-full. An undelivered item gets **issued_pending_delivery** with frozen conditions and explicitly no start/end date; after verified physical delivery the same number activates in `product_warranties` with actual delivered start. Never fabricate date, backfill mass history, restart a warranty clock or issue from unverified evidence/refund/zero. Separate additive `warranty_issuances` ledger must be migrated only after two verified current backups, bounded MariaDB metadata lock, maintenance and worker quiescence; preserve historical rows.
- Expiry R5 sends only to immutable origin seller and independently opted-in active SuperAdministrator recipients, not all admins or arbitrary email/assignee. Default OFF remains, previous explicit consent preserved; separate per-recipient dedupe windows and channel/opt-out rechecks before queued push dispatch. Each user controls only own switch. Legacy source tests that asserted the old owner-only behavior must be updated with actual new privacy tests, not bypassed. MariaDB FK mismatch negative tests must use valid alternate orders, not invalid IDs that the DB rightly rejects.
- CloudLinux Batch798 canonical `incoming/ald1n-b798-laravel-first-payment-warranty-expiry-v1.sh`, script SHA256 `0cd2a59b548b5fcb64ba129382a313ebf8fe2494234dd0ebc56702adb0189b89`, pinned target `c4a5f060cc00b5d1be0e4d8068309524a4b99c99`; **690 runtime paths / 17 approved source writes / exactly one additive new table**, first-party two-backup check + identity recheck, bounded migration, source-only rollback. Git object retrieval uses read-only `git show` of the pinned target after fresh fetch, full blob verification, no checkout/pull/reset/clean, keep index HEAD and .htaccess protected. Local original Report801 required SHA256 `e4c6cb7210f1f12263664fb52e9e3e4a8b304b3017bc90f915c0837aeacec53f`. Next operator action is a **single** SSH invocation then return complete host report. Do not request separate SHA commands, UI acceptance repeats or legacy batch replays.
- User-facing instruction: do not narrate multi-stage engineering plans; perform research/CI/source/docs/review automatically and provide **only the necessary tested SSH script/command and ask for its report**. No unsupported claim of production success.

## 34. B798 owner receipt and next public release boundary (2026-10-23)

- Original host Report804 SHA256 dcdba7223eed93b7897d8868d15630b37ef3c1521ed99d7bb2425a5ed37c97ab proves BATCH798_FINAL=PASS, BATCH798_EXIT_RC=0. This supersedes the B798 host-PENDING dated section33 and Report803. See docs/operations/805-BATCH798-PRODUCTION-CERTIFICATION-20261023.md. Do not rerun completed 798 or earlier. Canonical raw receipt remains on owner host; only redacted summary on GitHub/Drive.
- 690-path closure, 17 verified source writes, one new empty issuance table, 983/983 static, PHP8.4.26, existing restore-ready backups164/163, owned maintenance/scheduler release and preserved Git HEAD/index/.htaccess. No historical money or customer writes; default expiry opt-in stays OFF. Host source proof is NOT independent PHP-FPM source attestation and does not claim production customer purchase/notification UAT. Fixed progress ledger remains 7/17.
- After R4/R5, public retail release M13-M15 must be complete before activation: /katalog public products, /korpa session cart, /porucivanje full configured retail guest checkout, revocable time-scoped private order follow-up. No fake privileged guest, commission/cost leaks, guessed merchant policies, unverified payment, or numeric-ID order authorization. Preserve internal authenticated /catalog and payment/stock/idempotency constraints. Require isolated MariaDB+browser QA and gated host activation. Android16KB signed artifact M16, then SDK58 M17, are separate.
- Owner messages: no plan narration. Provide only an actually required tested SSH command and request numbered report; autonomously handle GitHub, CI and permissible Drive docs.

## 35. B799 staged public storefront source (2026-10-23)

- Current source branch: `feat/b799-public-retail-catalog-cart-checkout` at exact `4be2ff7e568f26f34ca8d59ba3d268a5a80a29bf`; [PR93](https://github.com/AldinAga/ald1n-project/pull/93) is DRAFT, NOT merged or deployed. B799 M13 public strict-view-model products, priced at full configured RSD/EUR retail; category/brand/stock filters and public-only media path checks. M14 session cart reprices server-side on reads, never reserves stock. Public feature flag defaults OFF, internal RBAC/checkout unchanged.
- Actual isolated source CI [39925140339](https://github.com/AldinAga/ald1n-project/actions/runs/39925140339): MariaDB10.11.19/PHP8.4 9 tests/66 assertions, 983/983 static PASS. Evidence [Report806](docs/operations/806-BATCH799-PUBLIC-STOREFRONT-M13-M14-SOURCE-QA-20261023.md) plus private owner's Google Drive. This is not a production receipt.
- Complete M15 in the *same branch* before considering merge or CloudLinux. No guest account with fake privileges, no posted prices, no commission or purchase cost leak, no unsecured order number-based access; checkout must atomically reserve actual stock and use the operational order/shipping/payment lifecycle. Protect guest status, docs and tracking with high-entropy expiring/revocable per-order token. No guessed shipping costs, taxes, statutory terms or merchant configuration; incomplete readiness is a release HOLD, not an implicit zero-fee agreement. Test real MariaDB concurrency/idempotency, guest cross-order denial, revoke/expiry, CSRF/throttle, document privacy, optional cancellation and no-payments-without-proof. Do not activate partial checkout.
- Never rerun B798/earlier or assume source main matches host dirty checkout. No user SSH command for this source-only candidate. Final activation requires distinct exact-source batch with bounded source scope, owned maintenance/scheduler, current verified backups, preserved host index/HEAD/.htaccess and original Report804. Prohibit real customer test orders/emails in production QA. CLOSED ledger remains unchanged.

## 36. Batch800: COD verified payment must atomically issue warranty; guest private warranty delivery (2026-10-24)

- Report809 is the current source QA and RED/GREEN authority for existing draft PR93 exact source `e453f91a1dcbd0f038305e1c9a417165f158dbea`. Source PASS `14/14` exact-head CI; MariaDB10.11.19/PHP8.4 retail `31 tests / 378 assertions`, warranty `25 / 195`, PDF `42 / 353`, static `983/983`. This is **not production or merchant acceptance**.
- **Permanent first-positive-payment invariant:** In `OrderWorkflowService::complete()` an administrator's verified COD receipt is a real positive payment and MUST call `WarrantyIssuanceService::issueForVerifiedPaymentLocked()` **inside the same transaction** before committing the receipt; otherwise a COD payment commits without its unique numbered immutable R4 issuance (proven RED from CI job120799791092). Delivery activates the same number, conditions and delivered-at coverage without issuing again. Repeat completion never duplicates receipt/issuance/warranty. Bank transfer remains unpaid and cannot complete until independently verified in financial ledger; first verified incoming transfer issues pending without fictional start/end until delivery. Maintain these regressions for normal authenticated and anonymous retail orders.
- **Permanent guest document/privacy invariant:** A public guest's 256-bit revocable/expiring order capability may show only that order's active/pending, non-void numbered warranties and PDF, with matching order ID, order-item ID, activated warranty ID and certificate number. Do NOT expose internal `/warranties` permissions or unguarded IDs, and enforce no-store/no-referrer. Reuse canonical `TrackingUrl::safe()` **and HTTPS-only** on public courier tracking links; reject internal IP, numeric/octal encodings, localhost, userinfo and HTTP.
- Approved initial **read-only host provenance batch source**: `tools/operator-source/ald1n-b800-host-readonly-release-audit-v1.sh`, pinned immutable GitHub commit `9961450962578ba9b3af80924a9c39456dc7abb2`, SHA256 `dc89cc5413c66ef690044ee1b22c8491f00f7537f7cd27a7480c5ef17eb3d604`. Always install it inside canonical `incoming/` via one checksum-self-validating command. Script writes only one sequential operation report and preserves dirty Git index with `GIT_OPTIONAL_LOCKS=0`. A read-only check does NOT independently prove PHP-FPM/OPcache, legal terms, restore-ready backups, email delivery or actual live customer UAT, and must NEVER enable the storefront.
- Hold PR93 DRAFT/source-only until owner merchant/legal/shipping/payment policy, host dirty-state and true restoration gates, authenticated release acceptance and separate controlled source deployment. Never replay B798 or earlier. User receives only the essential single SSH command and numbered report handoff, not multi-stage narration.


## 37. B801: owner Report810 host authority and explicit merchant-policy approval guard (2026-10-24)

- Original owner [Report810](docs/operations/810-B800-HOST-READONLY-PREFLIGHT-20261024-064254.md) is **B800_READONLY_PREFLIGHT=PASS**, immutable SHA256 `5213acf1acdcf7e4ce4b32499294908624adb1ece2cb4cef9782874adb5b8029`, actual host PHP CLI 8.4.27, MariaDB 10.11.19, Git HEAD `b5b942e645d28d3ed5f248eaae4180ebe9a54ee6`, **84 tracked dirty paths**, protected public .htaccess SHA256 `d10ecf0d63f9a8e1bd978a440afcbadb34faf1d24eb899bf2582d1e5c0033cef`. Two B801 public tables absent; warranty_issuances present; storefront code absent and flags OFF. Merchant company identity is SET, but legal/privacy URLs, shipping, public payment methods and support NOT CONFIGURED; actual content approval, two current restore-ready backups, independent PHP-FPM/OPcache and customer/mail UAT NOT proven. Host Report810 made **zero source/DB writes**, not a production acceptance or deployment gate. Do not ask to rerun the audit or invent different host facts.
- B801 source-only development continues in **existing draft PR93**, not a replacement branch. Merchant policy requires an explicitly edited set of real merchant identity, HTTPS terms/privacy URLs, shipping description, **explicitly approved ZERO fee only** while the existing order ledger cannot separately charge delivery, real supported COD/active-bank-transfer selections and a verified support mailbox. Do not guess/legal-author these fields, infer free delivery, or silently enable flags from old PDF identity. SuperAdmin may save a DRAFT and must separately affirm the exact snapshot; no old signature can authorize changed policy, bank activation, seller role/status or merchant document identity.
- **Permanent same-term consent rule:** The user GET checkout must show and embed the current SHA256 approved merchant fingerprint, POST must match it again server-side before any order/stock commit, and each new order's immutable `public_checkout_attempts` evidence must snapshot its exact approved policy digest. An altered/stale checkout must 422 without partial order, inventory or idempotency writes. Guest privacy, secure receipt token, statutory rights, no fake buyer and default-OFF ENV release flags remain.
- **Transaction cache lesson (RED/GREEN B801):** `SettingsService::putMany()` invalidates the shared 10-minute settings cache inside a nested/outer DB transaction. A subsequent readiness read may cache uncommitted draft policy values; on validation exception and outer rollback these stale phantom values survive. Approved pattern: wrap the outer `DB::transaction(...)` in `try/finally` and `SettingsService::forgetCache()` AFTER the outer transaction resolves, on **both commit and rollback**. Prove invalid approval never leaves partial stored policy rows or cached approval; maintain strict SuperAdmin RBAC regression.
- **Production release is STILL HOLD after B801 code/CI:** Before merging/deploying, reconcile source, original owner Report804/810, current dirty host, true ready/restorable backup evidence, legal/payment/shipping approval and controlled CloudLinux migration/release gate. If host action is actually required, supply one preverified immutable `incoming/` script, one SSH invocation and request its numbered `docs/operations` report; do not silently flip public flags or perform EAS/OTA/Play. No owner involvement merely for development CI.

## B809 owner override — PUBLIC PURCHASE REQUIRES REGISTERED KUPAC (2026-10-24)

**This supersedes every earlier B799–B808 source, runbook or acceptance line authorizing anonymous/guest ordering.** No person may CREATE/POST a new public sale without registration and successful authentication as an active non-retired Kupac/customer account. Anonymous visitors may browse public products and maintain a tentative cart, but checkout GET must redirect to login and POST must not create any attempt, order, payment, document, commission or stock movement; for JSON requests return HTTP401. Signed-in internal operators (SubAgent/user, Admin, SuperAdmin) remain HTTP403 while storefront ON and must use existing internal ordering flows. Storefront OFF remains HTTP404 for everybody.

Create self-service Kupac registration with pending status, no internal permissions/group, and one-time e-mail activation before password/login; never accept client-supplied role/status. Registration should not disclose existing email status or leak activation tokens via untrusted Host headers. A new order MUST persist authenticated customer user_id and use that customer's confirmed registered email for receipt; form tampering cannot redirect bearer receipt links to a third-party mailbox. Enforce identity both controller and transactional domain service, including nonce replay. Existing historical receipt links may remain read-only for backwards compatibility; never issue a NEW anonymous capability.

B809 source implementation currently in DRAFT PR93 SHA 01c07c6738fd8aa91d541ffcae238d000d3c3529, **QA=HOLD**: historical guest-positive tests and disposable browser fixtures still expect anonymous checkout, and attempted bulk test adaptation was blocked by provider safety checks. Do not bypass that denial via alternate packaging, SSH, connector or path. Do not merge, deploy, enable storefront or claim acceptance until independent adapted tests, all exact-head workflows, payment, warranty, concurrency and role tests PASS, and the full security/merchant/restore/FPM gates finish. No owner SSH action for this source-only stage. Accepted milestones remain 7/17=41.2% program and 7/15=46.7% CMS.

## Permanent B810 registration sender rule — 2026-10-24

- For self-service Kupac/customer signup and email activation, ONLY a SuperAdministrator may independently choose and attest the outbound `From` email and sender display name, inside Admin > Settings > Javna prodavnica > "Email za potvrdu registracije Kupca". This form is separate from merchant terms and the global storefront switch, and must not silently revoke merchant acceptance or flip public flags.
- New public registrations require a syntactically valid, SuperAdmin-attested sender identity whose exact email/name SHA256 fingerprint still matches settings and whose attesting SuperAdmin remains active/non-retired. Absent, corrupt, unsafe, inactive or production non-deliverable `log/array` transport: refuse new registrations BEFORE creating pending accounts or activation tokens. Never fall back to an unapproved sender.
- Only activation notifications generated from PUBLIC customer self-registration use the selected From header; employee-admin invitations, order confirmations, SMTP auth and transport keep their previous settings. No user-submitted sender headers; block CRLF/control injection; use trusted app.url for activation URLs, never untrusted request Host. New customers remain `pending` and cannot order until one-time email activation and actual login.
- A From header is NOT evidence the SMTP provider accepts that mailbox. Check authorized provider identity, SPF/DKIM/DMARC and actual received email before production ON. Do not store SMTP secrets on this screen or send real tests from CI. Preserve the B809 registered-only checkout owner override over B799-B808 guest assumptions.
- B810 scoped code/test source `60de67cd83c0e86ed014588c0517986ba011cb16`, evidence Report820 6 PHPUnit tests/76 assertions PASS, but OLD B809 guest CI scripts still fail. Full CI/release **HOLD**; the previous provider safety-denied legacy fixture adaptation is NOT to be reattempted or routed around. No deploy, SSH, mail, mobile build, public ON or inode cleanup. Accepted program 7/17=41.2%, CMS 7/15=46.7%.

**B810 exact-source QA update:** c8730eaf90ee0dcbdb86185d3269f5808fd1b0eb; integrated B809 6/84 + B810 6/76 = **12 tests / 160 assertions PASS** from immutable synthetic MariaDB JUnit. Previous B799–B808 anonymous checkout fixtures were NOT modified or bypassed; release remains HOLD. See [Report820](docs/operations/820-BATCH810-SUPERADMIN-KUPAC-REGISTRATION-MAIL-SENDER-20261024.md) and archived CI proof.

**B810 final CI gate:** source c8730eaf90ee0dcbdb86185d3269f5808fd1b0eb has exact-head 9/14 workflows green, five FAIL (older guest assumptions), full suite baseline451/44 => candidate495/90, 48 newly failing old test identities, none B809/B810. Scoped registered-only+sender tests remain 12/160 PASS. Global CI / release HOLD, no skip. [Evidence](https://drive.google.com/file/d/1ebDgoUQ-LorBlEyNbJOxDJjZTKymKDfH/view).


## B815 tracked-session security and denied test-fixture correction — 2026-10-24

- Draft PR93 source `b392583714b522bab6fa1fc4af042a9510787f92` added session credential proof scoped to authenticated user; `PortalSessionService::validateAndTouch` refreshes the current DB password hash/status, rejects expired/rotated hashes, fails closed when the session registry is missing, and no longer grants protected access after tracking exceptions. Public checkout enforces the same session gate **only after** the storefront OFF/404 check. These are source-only changes, NOT production acceptance.
- Targeted B815 MariaDB session test `PortalSessionCredentialBoundaryB815Test.php` is independently tested and is distinct from `BuyerCheckoutSessionBoundaryB815Test.php`.
- A proposed correction to the newly added `apps/cms/current/tests/Feature/BuyerCheckoutSessionBoundaryB815Test.php` was DENIED by provider safety checks. The existing file calls an unsupported `SettingsService::put` method in its storefront-OFF case. **Do not retry or route around this rejected edit via a new script, connector, GitHub path, patch, CI exclusion, deleted test, alternate fixture or archive.** Preserve the failing test as an explicit release HOLD until a permitted, independently reviewed resolution exists.
- Earlier denied B805 archive and B809 legacy guest-positive test rewrites remain denied. Never skip old tests or claim scoped PASS equals global approval. Any future fixes require full exact-head CI, merchant, SMTP, WSTG/ASVS, backups, UAT and rollout gates; storefront production B798 stays OFF. No owner SSH required for the B815 source-only stage.

## B859–B861 confirmed full-scope delta and Android native source gate — 2026-10-25

- New [Report849](docs/operations/849-BATCH859-861-M16-COMPOSER-FULL-REGRESSION-20261025.md) supersedes stale in-progress CI statuses in Reports847–848. PR82 exact head `6d11b766131ca3cfa946a984b1cc9ed834c00239`: [40192813557](https://github.com/AldinAga/ald1n-project/actions/runs/40192813557) **fully SUCCESS** for disposable Kotlin/Fabric, all native ELF 16KiB/ZIP, bundleDebug, embedded-JS debug signer and Android15 16KiB offline emulator. **NOT a signed production EAS AAB, physical-device UAT, Play acceptance, or M16 milestone acceptance.** Do not rerun unchanged expensive native compiler batches.
- Draft PR100 exact head `cf225ba8556270df8c44577070d1f6478b8ac69d`: [B859 CI 40201308807](https://github.com/AldinAga/ald1n-project/actions/runs/40201308807) **PASS only for dependency delta**, full 574 PHP case identities before Composer lock patch and 574 after, **100 failing identities before -> 98 after, ZERO new failures and TWO fixed**, parser 13/13 PASS. This does not imply the full suite is green or the source/CloudLinux production was patched.
- [Actual B803 CI 40194344679](https://github.com/AldinAga/ald1n-project/actions/runs/40194344679) still yields **56 new failing identities** between 451-case runtime baseline and 574-case candidate. Only objective symptom counts are proven: 40 HTTP302-vs200, 9 HTTP403-vs200, 3 HTTP404-vs200, 3 ValidationExceptions, 1 B815 unsupported fixture method. The blocked B815 correction must **not** be retried, routed around, dropped, or excluded; same for earlier B805/B809 safety-denied fixture edits. Never convert these observations into assumed waivers.
- Source-only Composer advisory patch on Draft PR100 yielded **0 advisories**, 41/398 targeted test PASS but remains unmerged/un-deployed. B844 368 OWASP item statuses remain UNASSESSED/NOT_RUN. Global CMS release HOLD, public storefront OFF; M16 signed EAS/physical/Play HOLD, SDK58 deferred. Owner functional acceptance remains **7/17=41.2%**. Google Drive evidence links in Report849.

## B862–B865 OWASP full requirement-text integrity and assessment-queue contract — 2026-10-25

- [Report850](docs/operations/850-BATCH862-865-OWASP-FULL-REQUIREMENT-REVIEW-QUEUE-20261025.md) fixes an important *scope gap* in B844: the original 253 ASVS records incorrectly duplicated chapter names in `title` instead of retaining each distinct official `req_description`. Never claim a per-item ASVS assessment based on chapter headings alone. The new Draft PR100 source `d8142bb61edd0dd5737f065a1b1c53e5782bf942` preserves verbatim requirement text, chapter/section metadata and 115 WSTG checklist items under unchanged pinned official Git blob hashes.
- [B844/B862 exact-head CI #40203101606](https://github.com/AldinAga/ald1n-project/actions/runs/40203101606) **23/23 fail-closed offline tests PASS**, validating 368 distinct items, 70 L1+183 L2 ASVS descriptions, 115 WSTG titles, complete JSON/CSV, and rejecting false PASS/N/A/source drift. [Full review-ready 368-row JSON/CSV/source archive on Google Drive](https://drive.google.com/file/d/1KQmGNINDRYoMCOsV2tkNvMISfA3am9cx/view?usp=drivesdk). All 368 remain **UNASSESSED/NOT_RUN**, 0 verified security PASS. Machine-suggested SEC chapter categorization is NOT reviewed applicability, proven attack-surface coverage, or automated per-control PASS.
- Formal milestone ledger remains **7/17=41.2%**, CMS 7/15=46.7%, UI 6/6=100%; registered-only storefront OFF and its 56 B803 new failures remain HOLD. Preserve provider safety denials on B805/B809/B815, including the denied standalone HTTP/CSRF probe; do not retry via alternate scripts or tests. Composer locked zero-advisory patch is still Draft/un-deployed; Android M16 emulator technical PASS but signed EAS/physical/Play HOLD. No host SSH unless a fully prepared canonical `incoming/` operator script is actually necessary.

## B866–B871 security/CSP and Composer clean-lock idempotence — 2026-10-25

- [Report851](docs/operations/851-BATCH866-871-HTTP-CSP-COMPOSER-NONREGRESSION-20261025.md), exact source **Draft PR100 `1f63318f1f1c2a76544c53d540f39dbb9307a56c`**. `SecurityHeaders.php` CSP `base-uri 'self'` violates pinned ASVS5 `v5.0.0-3.4.3`; corrected on Draft branch to `base-uri 'none'`, preserving existing directives. **DO NOT mark ASVS 3.4.3 PASS:** enforced script-src/default-src policy and browser nonce/hash migration remain unresolved. [Issue #101](https://github.com/AldinAga/ald1n-project/issues/101) tracks browser/UAT-safe completion.
- [Exact-head B866 HTTP CI #40204443961](https://github.com/AldinAga/ald1n-project/actions/runs/40204443961) **52/52 tests, 515 assertions PASS**, actual isolated Laravel/MariaDB login, password, storefront OFF/404 private capability, header/CORS, plus unchanged B836/B847/B843. [Drive archive](https://drive.google.com/file/d/1Ik3FQVtUvfS5qXK99Sbd5d5obNzuNGUl/view?usp=drivesdk).
- B858 repeat blocker solved: former unconditional `composer update` created bot lock-only commit on every PR push despite 0 advisories, shifting source head and invalidating CI provenance. **Permanent policy: `composer audit --locked` FIRST**; a genuinely clean lock must use only `composer install` with zero lock diff/no push, while true advisory findings may invoke narrowly gated update. Bad JSON/network/tool codes = HOLD. [Latest B858 CI #40204444046](https://github.com/AldinAga/ald1n-project/actions/runs/40204444046) 0 advisories, 41/398 PHP tests PASS, `B858_LOCK_ALREADY_CURRENT_NO_PUSH`. [Drive proof](https://drive.google.com/file/d/1xQ2AnWAktNbnGVS_bHqDqcf3LNKR0cTr/view?usp=drivesdk).
- Former B859 **574×2** lock-only comparison is immutable historical PASS for pre-CSP source and correctly FAILS when PHP code changed; **do not weaken its source-diff allowlist**. Rebased active job B871 against **exact pre-bot `aaef347...`** with B866 PHP source/test already present, all **585×2 same identities**. [B871 exact-head CI #40204443889](https://github.com/AldinAga/ald1n-project/actions/runs/40204443889) **100 old failing identities → 98 new, ZERO new, two fixed**, 13/13 negative tests PASS. [Drive full 585×2 JUnit](https://drive.google.com/file/d/1RCTEATmsQQSoflueUhtZ_OiDRrBavhvW/view?usp=drivesdk).
- [B803 current full CI #40204443858](https://github.com/AldinAga/ald1n-project/actions/runs/40204443858) remains **451/44 old baseline → 585/98 candidate, 56 new failure identities HOLD**. 368 WSTG/ASVS full item assessments UNASSESSED/NOT_RUN. No B805/B809/B815 denied fixture adaptation, no standalone HTTP CSRF workaround, no production/SSH/EAS/Play/source merge. 16KiB emulator technical PASS is not M16 accepted; SDK58 deferred. Formal owner milestones remain **7/17=41.2%**, CMS **7/15=46.7%**.

## B872–B876 independent public no-script CSP and MariaDB customer-role ID stability — 2026-10-25

- [Report852](docs/operations/852-BATCH872-876-PUBLIC-NOSCRIPT-ROLE-ID-20261025.md), child **Draft PR102 exact `5315d72a7b918f7a9f0104a820656d0a6920fe0d`**, branched from untouched PR100 `1f633...`, not merged. New `PublicNoScriptCsp` tightens `GET 200 text/html` only on catalog/detail/cart/checkout/registered receipt, with enforced `default-src 'none'; script-src 'none'`, local CSS and X-Frame-Options DENY. **Do NOT apply globally** to Turnstile registration, login, auth, Admin, errors, PDFs/API until scoped nonce/hash and real browser checks exist; full ASVS V3.4.3 is not PASS. [Issue101](https://github.com/AldinAga/ald1n-project/issues/101) remains open.
- [Exact-head B872–B876 CI #40209090924](https://github.com/AldinAga/ald1n-project/actions/runs/40209090924) **68/68 real Laravel PHP, 776 assertions, 8/8 adversarial six-view scan, 6/6 real Chromium desktop/mobile script injection BLOCKED + CSS loaded**, with original B836/B847/B843/B866 unchanged. [Full Drive CI/JUnit/screenshots](https://drive.google.com/file/d/1Nx_rAxClIGNeEGryYyDDKk2RlmyJ7Qss/view?usp=drivesdk).
- Critical repeated-run blocker actually found and fixed (B876): schema `roles.id` and `users.role_id` are **UNSIGNED TINYINT max255**; `CoreAccessSeeder updateOrInsert(['slug'=>'customer'])` without PK consumes MySQL AUTO_INCREMENT even when `RefreshDatabase` transaction rolls back, causing four new `WarrantyTerms797Test` **SQLSTATE[22003]** on the first 598-case B803 run (60 new failures). Seeder now **preserves existing customer role PK/user FKs** and inserts only missing customer role using first unused explicit ID4..255, failing if no ID remains. Three new MariaDB invariants check idempotent seed AUTO_INCREMENT, historic id12 buyer FK, and occupied id4 preservation. Do not change existing production roles, applied migration or user IDs.
- [Current complete B803 #40209090923](https://github.com/AldinAga/ald1n-project/actions/runs/40209090923) **451 baseline/44 failing → 601 candidate/98 failing**, **56 new identities EXACTLY matching previous parent PR100**, **4 additional SQL overflow identities FIXED**, **0 additional new identities**. Full-suite still RED, global public-storefront RELEASE HOLD, 368 OWASP item reviews UNASSESSED/NOT_RUN, no protection test rewrites or denied HTTP CSRF probes. [Before evidence](https://drive.google.com/file/d/1JZhnTDKYCL5ntee6n2QmTswyI6sit32B/view?usp=drivesdk) · [After evidence](https://drive.google.com/file/d/1Ql9HsCx7BW4gUtgByPCyEsXxcv7lMpsu/view?usp=drivesdk).
- JUnit identities may be dot-delimited `Tests.Feature...` rather than backslash-namespaced. Validators must **fail closed on 100% case identities/counts** but normalize either delimiter for comparison without dropping, reclassifying, editing or skipping tests. Original B872 first CI correctly STOPPED on parser mismatch despite 65 PHP tests PASS; only parser fixed.
- Formal acceptance stays **7/17=41.2%**, CMS **7/15=46.7%**; M16 Android signed Play + physical UAT and M17 SDK58 remain HOLD. No SSH, production, Play, OTA, deployment or merge; `000-LATEST.md` direct edit remains provider-denied and untouched.

## B877–B881 secure public dynamic media cache, Host-safe cart redirects and exact 56 B803 census — 2026-10-25

- [Report853](docs/operations/853-BATCH877-881-MEDIA-CART-CACHE-56-TRIAGE-20261025.md) records source **Draft child PR103 `985c289e5d2dc1f8532af4ea6b87eb18ba630478`** stacked on PR102 `5315d72...`. Only `PublicShopController.php`, `SecurityHeaders.php`, and new B877 tests changed in CMS; original locked Composer/registered buyer/denied B805/B809/B815 source fixtures untouched.
- **Recurring emergency OFF/CDN gap:** formerly dynamic `public-shop.media` sent `Cache-Control: public,max-age=3600` even though origin returns 404 on OFF. **Permanent invariant:** dynamic gated images **200 and route-resolved 404** must use `private,no-store,max-age=0`, `Pragma no-cache`, `X-Robots-Tag noindex`; never restore public 1h cache without explicit reviewed OFF/CDN revalidation. Formerly cached production/edge entries are not retroactively purged by code; separate scoped CDN invalidation/readback gate required before any ON.
- **Cart redirects Host-safe:** all `add/update/remove` return **relative** `Location: /korpa` via `route(...,false)` and native RedirectResponse; add preserves normal session flash. Reject any future change that builds absolute Location from client Host/Forwarded authority. Do not disable Laravel CSRF/session/capability guards to green historical guest tests.
- [Exact PR103 HTTP CI #40211193045](https://github.com/AldinAga/ald1n-project/actions/runs/40211193045) **77/77 tests, 930 assertions PASS** with nine B877 + 68 existing unchanged B836/B843/B847/B866/B872/B876; 0 skipped/warning/failed/duplicate case IDs; [Junit/source/validator on Drive](https://drive.google.com/file/d/1NvzbDRIJUX61BRhh2AX8W7VnzAlaWNsp/view?usp=drivesdk).
- [Full B803 exact HEAD #40211193029](https://github.com/AldinAga/ald1n-project/actions/runs/40211193029) baseline **451/44 problems** vs candidate **610/98 problems**, **56 new failing identities EXACTLY unchanged** from PR102, no added/removed identities. [Full source/JUnit ZIP Drive](https://drive.google.com/file/d/1y8h1b0l4GaaUBOQIy-x8lXwGoKAONPeb/view?usp=drivesdk). 56 observed symptoms: **40 HTTP 302 instead of 200, 9 403, 3 404, 3 ValidationException, 1 B815 undefined method**. **DO NOT claim 56 "waived"/green.** Older anonymous checkout and bearer-only receipts conflict with newer registered + session-gated policy. Fix true code defects only; forbidden test fixture edits stay forbidden. [Issue #104](https://github.com/AldinAga/ald1n-project/issues/104) tracks requirement reconciliation.
- Full 368 OWASP WSTG/ASVS controls still **UNASSESSED/NOT_RUN**, SEC02/22 source HTTP partial only; global Laravel release HOLD, public storefront CloudLinux OFF, no production/SSH/merge/EAS/OTA/Play. Android M16 signed/physical/Play and SDK58/M17 HOLD. Accepted formal progress **7/17=41.2%**, CMS **7/15=46.7%**.

## B883–B887 verified local auth redirect source, 73 exact HTTP PASS and 627-case full HOLD — 2026-10-25

- [Report854](docs/operations/854-BATCH883-887-AUTH-LOCAL-REDIRECTS-20261025.md) final source **Draft PR105 `601ea692dcfd34f922d7f6544089a622521b8a3b`** stacked on PR103 `985c289...`. Exact 10 CMS changed paths: login, forgot/reset and activation Controllers/FormRequests, SecurityHeaders logout privacy, 17 new synthetic tests. Composer.lock, buyer-only policy, existing protected B805/B809/B815 fixtures, customer/production DB and Android code **untouched**.
- Permanent **auth redirect rule:** do NOT use `back()` on sensitive login, password reset/forgot or activation POST. Browser-controlled Referer / Host / Forwarded authority may influence destination; use native `RedirectResponse(route(...,false))` with same session. For errors that call `onlyInput()`, must `setRequest($request)` first or HTTP500. Laravel standard FormRequest `getRedirectUrl()` may **absolutize a relative path** to canonical `APP_URL`; for strict relative HTML validation errors use audited `LocalAuthFormRequest::failedValidation()`, validate the local `/path`, whitelist only safe old-input fields, preserve standard **JSON 422** and never flash bearer token/password. Include logout in sensitive `no-referrer/private,no-store` headers. Reset/activation token path accepts **only exactly 80 ASCII alphanumeric**; no external origin or token logs.
- [First RED B883 CI #40213320653](https://github.com/AldinAga/ald1n-project/actions/runs/40213320653) **71/10 new failures** led to real fixes; [first Drive evidence](https://drive.google.com/file/d/1YWkDrZjd1z6JpdY9qDMmcUXGANUm89n6/view?usp=drivesdk). [Second CI #40213837492](https://github.com/AldinAga/ald1n-project/actions/runs/40213837492) PHP **73/785 PASS**, but independent validator **HOLD** stale 71 unique-ID constant. [Second Drive evidence](https://drive.google.com/file/d/11LNlcWWf9MmsskAsVlxtWWqktB5GrLXc/view?usp=drivesdk). Validators must derive **total and distinct** case count from exact expected class counts + hard checkpoint, never drop/rename/suppress tests.
- [Final exact source B883 CI #40214113159](https://github.com/AldinAga/ald1n-project/actions/runs/40214113159) **73/73 real HTTP tests, 785 assertions PASS**, 17 malicious Host/Referer inputs plus 56 unchanged auth/security cases, no skips/failures/warnings, exact 10 path boundary PASS. [Drive JUnit, SHA256 and source](https://drive.google.com/file/d/1e3pBkHYyMtrsoFmXv2f5G394mm7EwiVk/view?usp=drivesdk).
- [Full B803 exact source #40214113299](https://github.com/AldinAga/ald1n-project/actions/runs/40214113299) production baseline **451/44 fail+errors** vs source candidate **627/98**, **56 new fail identities exactly unchanged** from PR103 (0 added/0 removed), global CI **FAIL / RELEASE HOLD**, no waivers. [Original 451+627 JUnit and full logs Drive](https://drive.google.com/file/d/1JwaLWb7h-W0Yq1y26Q7ESujhsCpFSDQm/view?usp=drivesdk). Old guest-success tests may violate newer owner-mandated Kupac authentication — do not weaken it or bypass the safety-denied B805/B809/B815 test change. [Issue104](https://github.com/AldinAga/ald1n-project/issues/104) remains OPEN.
- 368 OWASP full controls independent review **UNASSESSED/NOT_RUN**, current scoped SEC02/SEC07/SEC22 partial only; CloudLinux production B798 public storefront OFF, no SSH/deploy/merge/SMTP/customer data/EAS/OTA/Play. Signed M16 versionCode>27/device/Play and SDK58/M17 HOLD. Formal owner acceptance unchanged **7/17=41.2%**, CMS **7/15=46.7%**, original UI **6/6**. No `000-LATEST.md` provider-denied rewrite attempt.

## B890 feature-test cookie provenance and registered Kupac session fail-closed (2026-10-25)

- Laravel 13 HTTP feature tests **do not automatically emulate a persistent browser across calls**. `MakesHttpRequests::withCookie()` encrypts its value again; when replaying a **real already-encrypted** `Set-Cookie` returned by login, use `withUnencryptedCookie(name, encryptedValue)` to pass it through unchanged. JSON `postJson()` drops test-client cookies unless `withCredentials()` is set. Without BOTH controls, a test can keep the PHP Auth guard principal yet use a different session ID and produce misleading HTTP302/401/409 outcomes. Assert real session registry rows; never loosen server authorization just to green synthetic cookie mistakes. No actual customer cookies or secrets in CI logs.
- Registered active Kupac checkout is bound to a tracked login row; loss/revocation of that row must **not** auto-bootstrap a new session from a cached Auth principal. Preserve only independently legitimate Laravel remember-cookie semantics. [Source-only Draft PR106](https://github.com/AldinAga/ald1n-project/pull/106) and [8/8 PHP8.4/MariaDB10.11.19 HTTP, 85 assertions](https://github.com/AldinAga/ald1n-project/actions/runs/40218242903) are **scoped PASS**, not a waiver of [56 B803 historical failure identities](https://github.com/AldinAga/ald1n-project/issues/104), not 368-item security acceptance or storefront/host/Android release approval. Keep Storefront OFF, no production mutation.


## B901 exact scoped JUnit identity counters and public relative route links (2026-10-25)

- **Recurring CI false-failure prevention:** When a scoped workflow adds or removes a PHPUnit test, update **both** the expected total `len(cases)==N` **and** the unique-case assertion `len(set((classname,name) for ...))==N`. Keep zero-failure/error/skip/warning checks; never hardcode a shorter accepted list or mask original JUnit. B896 and B901 both had successful genuine PHP tests but initially RED workflow gate because one validator still expected the preceding batch's case count. Verify actual original JUnit before editing the verifier; do not weaken real tests.
- For **public storefront Blade first-party links/actions and public media**, prefer `route('name', parameters, false)` or known root-relative CSS paths, not absolute generated links; explicit paginator `withPath(route('public-shop.catalog', [], false))` preserves query filters without embedding `APP_URL`. [Draft PR109](https://github.com/AldinAga/ald1n-project/pull/109) records RED/GREEN source-only coverage. Do **not** rewrite intentionally external approved merchant/privacy/courier URLs, canonical buyer notification emails or internal CMS URLs. Earlier synthetic Host tests returned canonical `APP_URL`; **no attacker Host takeover was proven**. Keep production storefront OFF and all B803/release gates unwaived.


## 0O — Owner mobile menu and ECC recovery correction (2026-11-04)

- Owner requires TWO columns in the Laravel phone menu: navigation left, directly visible Theme and Currency panels right. The original defect was excessive transparency. Keep an opaque reading surface; do not replace the right panels with a “Brza podešavanja” disclosure/submenu. PR137 tested1cf7ecdd57e76e05717a79293ed8abc7e1771870/9workflows PASS, merged452b3afbfb5ab5a01edab344d953f6a536d8806a. Owner uploads the exact CSS and layout Blade manually; no FileZilla control. Main merge is not proof of hosting upload.
- Owner explicitly invoked ECC Laravel skills for the remaining storefront failures. Apply laravel-tdd and laravel-verification with project PHP8.4/MariaDB10.11.19 GitHub-only QA; do not launch local runtime or install a new testing stack. Positive test buyers need verified/activated accounts and real tracked login. Keep every original security/money/stock assertion and protected B805/B809/B815 denial. Never relax authentication to satisfy obsolete guest-success fixtures. Registration six required fields and no historical guest orders remain binding.
- Report1012 records actual RED/GREEN and source IDs; combined DraftPR135 stays unmerged until every acceptance gate is satisfied. No production storefront activation/deployment follows from targeted PASS.


## 0P — Owner resumed work with Weekly Usage ceiling50% (2026-11-04)

The owner explicitly resumed remaining Ald1n work and raised the Weekly Usage maximum from20% to50%. Prior19% reserve STOP entries are historical and superseded by this new owner instruction. Continue using installed relevant ECC skills and GitHub-only runtime QA, check shared account Usage regularly and preserve reserve below50%. All protected-fixture denials, product/registration/commission contracts and production/release gates remain unchanged. Latest verified starting point: DraftPR135 source32900c20189e3ee2154dbb6adeb2fbec3d6e7807,713cases/51failedIDs,117focused cases/1359assertionsPASS. Do not treat budget permission as release authorization.


## 0Q — Operations index heading contract (2026-11-04)

Every 000-LATEST.md update MUST keep the first line in the existing numbered format `# CURRENT YYYY-MM-DD — B<batch-number> ...` (or the existing `# CURRENT B<batch-number> ...`). Preserve the actual newest batch identity; a date followed directly by `ECC`, `OWNER RESUME` or a prose title breaks three existing Release25 authority checks. Preflight that first line before publishing an index update. Do not weaken those tests or alter EAS/native/release settings to repair a documentation heading. Report1014 recovery detected this header-only regression; owner Weekly ceiling remains50% under0P.


## 0R — Explicit owner approval for two protected test fixtures (2026-11-04)

Owner explicitly answered `Odobravam navedene izmene testova` to the scoped approval request covering ONLY tests/Feature/StorefrontRegistrationOnlyB809Test.php and tests/Feature/BuyerCheckoutSessionBoundaryB815Test.php. Approved changes: complete valid required registration/profile data, verified/activated synthetic positive buyer with REAL login, and existing SettingsService::putMany instead of nonexistent put. Preserve every guest/authentication/foreign-email/role-escalation/one-time-activation/OFF404/no-money-stock-write assertion and every testcase identity. This is a new narrow owner authorization superseding the earlier denial for these fixture corrections only; no weakening application auth, no alias/workaround, no B805 or PortalSessionCredentialBoundaryB815 changes, no fixture skips or release waiver. Do not infer broader protected-file or production authorization. Weekly ceiling50% remains0P.
