# Batch632 — Workspace V2 Build25 production OTA closure and toolchain/UX reconciliation
Date: 2026-10-12
Scope: documentation and read-only GitHub source audit. No EAS, Play, CMS production or Android native mutations in this batch.

## Authority
- GitHub main at entry: 52850d185d2c26d0a72a26d64520528714fe2f01.
- Build25: appVersion 1.0.0; versionCode 25; runtimeVersion 1.0.0-build17; Android production channel.
- Current EAS project: @ald1n/ald1n-mobile, ID d43b3866-6838-4217-a23e-3dc7f2cc76cc.
- Published UAT Android group: 8f27e122-317b-4db1-93a6-9bb95653de26, source 52850d185d2c26d0a72a26d64520528714fe2f01; exact-group verified.
- Production republish on 2026-10-12T00:55:37.578Z: group 27dd4b5d-63b3-4902-9b0b-5f289076f0e6, update 01a12d9b-a56a-75e0-bd8d-be25ed4acb4a, source 52850d185d2c26d0a72a26d64520528714fe2f01, Android runtime 1.0.0-build17, production branch. The owner ran eas update:view and returned matching exact-group JSON.
- Previous production group for recovery assessment: 3488c7cc-ac63-472d-bd7c-27e5abc677bd (Google Sign-In).
- Source checks: TypeScript PASS; Mobile validator 0 FAIL; Workspace V2 targeted tests 52/52; expo install --check PASS; Android Metro/Hermes export PASS.
- Physical Android UAT: owner explicitly confirmed PASS in conversation; no independent device matrix or runtime telemetry supplied. Preserve this evidence distinction.
- Production OTA release status: PUBLISHED_AND_EXACT_GROUP_VERIFIED. End-user uptake across every device is not directly measured.

## EAS CLI and dependency safety
- Active hosting eas.json was observed at eas-cli 24.8.0; GitHub eas.json was updated to 24.8.0 in commit 52850d185d2c26d0a72a26d64520528714fe2f01; owner successfully executed eas whoami, project:info, candidate publish, republish and update:view with CLI 24.8.0.
- Canonical AGENTS.md historically pinned 24.7.0; aligned to 24.8.0 in Batch632. npm-exec via explicit CloudLinux Node/npm paths remains required.
- Host Node observed v22.23.3, npm 10.9.9. Expo SDK 57 and RN 0.86.3. Expo documents SDK57 Node >=22.13.x and RN 0.86. Do not arbitrarily upgrade native dependencies or change runtimeVersion.
- EAS CLI advertised 24.12.1 as available on 2026-10-12, but its compatibility and release-controller behavior have not been proven on this host. LATEST_AVAILABLE is not VERIFIED_COMPATIBLE; use isolated smoke/negative tests before changing this pin.
- No global npm installation, package/lockfile modification or production build authorized by this documentation-only work.

## UX source audit
- Notification inbox code routes through resolveBusinessNotificationNavigation; push bridge routes through resolvePushNotificationNavigation and handles cold-start responses. These are code-presence findings only, not device verification.
- Admin Order detail validates positive numeric ID, gates orders.manage, uses API detail query and ErrorState retry, and renders OrderBuyerCard using shipping recipient snapshots. It has no special explicit #134 exception. A missing/deleted/archived/unauthorized #134 requires live API status/role/session evidence before patching code.
- Mobile 320px, large text, landscape, screen reader, offline/timeout, role-restricted actions, unsaved drafts and notification deep links need an explicit device-by-device QA matrix. Source regression tests cannot substitute for full hardware coverage.

## Hosting vs GitHub CMS audit — initial baseline before scoped layout correction
- Last operator observation: server root checkout HEAD b5b942e645d28d3ed5f248eaae4180ebe9a54ee6 versus GitHub main 52850d185d2c26d0a72a26d64520528714fe2f01, and 97 dirty/untracked entries, including tracked Blade/CSS files and historical docs.
- This means server production CMS presentation cannot be declared byte-identical to current GitHub main. Do not run blind git pull/reset/stash, auto-sync Web assets, or redeploy until a scoped manifest/diff and CMS acceptance prove parity.
- Direct server filesystem/read-only host verification was unavailable in this assistant session. No CMS live UI or server API claims are made.

## Batch632 follow-up — verified CMS layout source parity and isolated Blade compile (2026-10-12)
- Operator read-only audit compared seven named CMS presentation files against the isolated checkout at source 52850d185d2c26d0a72a26d64520528714fe2f01: six MATCH; only resources/views/layouts/app.blade.php differed. The only observed diff was a module-visibility <style> element outside the HTML doctype in GitHub but inside <head> on the active host; CSS rules were identical.
- GitHub-only layout correction commit: 8064ff9a1bba1cb74856f2e3732094dcfe524d84. Moved the unchanged module-visibility style into <head>; no PHP/business/CSS-rule modifications. Production hosting file was not changed.
- Owner re-fetched GitHub main into the isolated checkout and compared git show origin/main:apps/cms/current/resources/views/layouts/app.blade.php against the active host using sha256sum and cmp. Both SHA-256 values identical: 324dbae89961d3b48d8cacf062a15060d3af380a681027c7b25feb0d717b5737. Explicit cmp PASS; php -l on source PASS.
- Isolated workspace-v2 test discovery re-run correctly: seven test files, 52 tests, 52 pass, 0 fail. Earlier shell-glob runner returned TAP 0/0 and was a false PASS; it must not be reused as evidence.
- First standalone compiler attempt failed with Illuminate\\Contracts\\Container\\BindingResolutionException (Illuminate\\Contracts\\View\\Factory not instantiable) because the hand-built compiler lacked a View Factory. This was a test-harness setup issue, not evidence of a Blade production regression.
- Corrected standalone smoke script SHA-256: d3ca3dbe5a30812447a812cf2e1fec5e020e16008d0798d4517fc5ca0f6bf2b6; executed by owner against the existing Laravel vendor without loading production .env, booting production app, connecting database or mutating production source. Its output: CSS in HEAD count=1 PASS; SOURCE_SHA256 above; anonymous x-icon component resolved PASS; compiled template PHP parser PASS; Blade compileString and component compilation PASS. Independent php -l on compiled layout: No syntax errors detected.
- Scope distinction: byte parity is attested for the corrected layout file, with six other specified files matching in the earlier comparison. A complete repository/production CMS parity audit and a real authenticated HTTP render were **not** performed. Do not claim HTTP/render PASS from Blade compile alone.
- GitHub main at documented layout-fix check: 8064ff9a1bba1cb74856f2e3732094dcfe524d84. Operations documentation commits after this check do not imply EAS/Play/OTA or hosting runtime deployment.

Scoped gates:
CMS_LAYOUT_SOURCE_PARITY=PASS
CMS_LAYOUT_SHA256=324dbae89961d3b48d8cacf062a15060d3af380a681027c7b25feb0d717b5737
CMS_LAYOUT_CSS_POSITION=PASS
CMS_BLADE_COMPONENT_RESOLUTION=PASS
CMS_BLADE_COMPILE=PASS
CMS_COMPILED_PHP_LINT=PASS
WORKSPACE_V2_TESTS=52_OF_52_PASS
CMS_FULL_HOST_GITHUB_PARITY=UNATTESTED
CMS_AUTHENTICATED_HTTP_RENDER=NOT_RUN
PRODUCTION_MUTATION_FOR_LAYOUT_CORRECTION=NO

## Batch632 continuation — Order #134 notification route candidate (source only)
- Entry main source HEAD: 2309ec4b22db75abba3ea95efa0ffce5e6df4bb1.
- Historical context: Order #134 previously produced a model-not-found response on notification tap. The source contains a demonstrable second cause independent of archival/ownership: old database notification rows with an admin web URL and no explicit mobile route are serialized by NotificationResource as /orders/{id}. Ordinary mobile order detail requires user ownership or assigned supplier; an admin-only notification can therefore produce an intentional 404.
- TDD using real PHP source in isolated stubs: legacy route cases RED 6/8 => GREEN 8/8, new order inbox/push role matrix RED 0/6 => GREEN 6/6. Combined dependency-free real-source test: 14/14 PASS, exit 0. PHP lint PASS. Recovered historical route requires exact /admin/orders/{id} URL path and matching positive ID, honors explicit route and preserves customer fallback. Newly emitted notifications get role-appropriate route for both database and push.
- Draft PR #4: https://github.com/AldinAga/ald1n-project/pull/4; branch audit/batch632-order-notification-route-recovery-20261012, HEAD 25d29162c01938b7a58abe6914fee403beb72879. Scope exactly five files: NotificationResource.php, OperationalNotificationService.php, MobileApiFoundationTest.php, MobilePushPhase3BTest.php, and bin/notification-routing-smoke.php.
- Both modified production source PHP blobs verified byte-equal to locally GREEN-tested versions: Resource 58cb0c856964c47401bbe77908a6857b22b57bd5; Service c1301c941103abaac34d22a87695484dc8f6edc4; runner b996b50927b5f834f3a1e62a54a1ba67b5b58a21.
- HOLD: Real Laravel Feature tests against a separately verified testing database, canonical CMS static-check, and actual authenticated Android/HTTP #134 behavior. Neither actual #134 archived/purged/ownership state nor physical push cold-start UAT is attested. Historical delivered push payloads cannot be retroactively repaired.
- PR remains DRAFT, unmerged, and undeployed; no production/DB/build/OTA mutation. Do not declare Order #134 end-to-end fixed.

ORDER134_SOURCE_ROUTE_TDD=14_OF_14_PASS
ORDER134_LARAVEL_FEATURE=UNATTESTED
ORDER134_LIVE_HTTP=UNATTESTED
ORDER134_PROD_FIX=NOT_DEPLOYED
ORDER134_PR=4_DRAFT

## Batch632 owner-mandated script and report location contract (2026-10-12)

- Permanent cross-chat GitHub authority: root AGENTS.md, section 1A; newly delivered operator scripts go ONLY under `/home/icaffeco/ald1n-project/incoming`; new post-execution reports/full logs and QA receipts go ONLY under `/home/icaffeco/ald1n-project/docs/operations`.
- No script execution or report storage under `$HOME/<filename>` or arbitrary `/tmp` paths. Temporary extract/npm cache remains outside the Git worktree and is removed after tests. The Git-ignored `/incoming/` remains Git-ignored (do not force-add or remove the ignore rule). Existing tracked app source/test scripts stay in their canonical package directories.
- Both Batch632 operator scripts were revised to enforce their canonical invocation directory and automatically reserve the next sequential report prefix after inspecting current GitHub main and local operations report filenames. Their full terminal output and final real RC are recorded in the new numbered `.md` report.
- `incoming/ald1n-b632-pr4-readonly-gates-v2.sh` expected SHA-256 `d42c70df4463d620503a6f34abdd1d79666290390dab7b6d20819a3f502ee382`. Scope: exact isolated PR4 candidate, five PHP syntax checks, actual-source 14-case routing smoke, canonical CMS static-check. DB-backed Laravel/Android gates remain HOLD.
- `incoming/ald1n-b632-cli-compat-lab-v2.sh` expected SHA-256 `ff88d25a3cdc178c8e05ae026399225eee2f5cd4391428a042a05daefce879be`. Scope: read-only npm latest CLI, isolated help/runtime checks, release25 tests, source immutability. It does not change EAS pin or publish an update.
- Created/revised scripts pass local Bash syntax (`bash -n`). Their execution on the CloudLinux host and final PASS/HOLD outcomes are NOT yet attested. The operator must upload each script into incoming, verify SHA-256, then run by absolute canonical path. Do not reuse the earlier scripts previously suggested under HOME.
- No changes to production CMS, database, Google Play, native build, active EAS CLI pin or production OTA.

BATCH632_SCRIPT_LOCATION_RULE=GITHUB_AGENTS_PERMANENT
BATCH632_SCRIPT_DIR=/home/icaffeco/ald1n-project/incoming
BATCH632_REPORT_DIR=/home/icaffeco/ald1n-project/docs/operations
BATCH632_PR4_V2_EXECUTION=NOT_RUN_ON_HOST
BATCH632_CLI_V2_EXECUTION=NOT_RUN_ON_HOST

## Batch632 latest operator Reports 633/634 and targeted Report634 recovery (2026-10-12)
- Operator Report633 `633-BATCH632-PR4-READONLY-QA-20261012-112337.md` (generated on hosting `docs/operations` by canonical `incoming/ald1n-b632-pr4-readonly-gates-v2.sh`, script SHA d42c70df4463d620503a6f34abdd1d79666290390dab7b6d20819a3f502ee382): exactly candidate PR4 SHA 25d29162c01938b7a58abe6914fee403beb72879, PHP lint of five candidate files PASS, actual-source routing smoke 14/14 PASS, canonical CMS static-check 983 checks/0 failures, audited command RC 0, no production mutations. Laravel DB Feature, live Order134 state/API and Android UAT still HOLD. PR4 remains DRAFT and unmerged.
- Operator Report634 `634-BATCH632-CLI-COMPATIBILITY-LAB-20261012-112348.md` (canonical `incoming/ald1n-b632-cli-compat-lab-v2.sh`, script SHA ff88d25a3cdc178c8e05ae026399225eee2f5cd4391428a042a05daefce879be): Node 22.23.3/npm 10.9.9, Expo ~57.0.27/RN0.86.3, `eas.json` pin 24.8.0. Newest registry EAS 24.12.1 started and CLI help checks PASS in isolated cache; this is not a complete compatibility or upgrade approval.
- Report634 release25 Node tests: 125 total, 116 PASS, 9 FAIL. Two test assertions compare intended canonical `eas.json` 24.8.0 against outdated literal 24.7.0; seven tests in `verify-signature.test.mjs` share a `jar` fixture setup hook failure (`jar failed: undefined`), so real JDK17 jar/keytool/jarsigner toolchain is unverified, not a signed-AAB logic verdict. Report634 audited command RC1 and production mutations NONE. Do not mark full CLI test suite PASS.
- Deeper source check found **real** version drift beyond stale test assertions: five optional build scripts in Mobile package.json, `scripts/submit-android-production.mjs` helper constant, and `scripts/validate-project.mjs` marker still hardcoded `24.7.0` while eas.json and AGENTS mandate 24.8.0. Canonical source contract RED 1/8 (only eas.json matched). Do not solve by changing tests alone.
- Corrective **DRAFT PR5**: https://github.com/AldinAga/ald1n-project/pull/5, branch `audit/batch632-release25-pin-parity-20261012`, HEAD `596026f2e7ce48c1c3b06fc4083526c17337dbf6`. Two tests updated before three source changes. Exact scope five files: package.json, submit-android-production.mjs, validate-project.mjs, deep-offline-contract.test.mjs, validator-pin-recovery.test.mjs. All five optional EAS source commands and helper/validator now use canonical 24.8.0; Node-driven Build25 production and Play submit blocked controllers unchanged; eas.json/runtimeVersion/Android identity unchanged. Source inspection GREEN 13/13, but real 125-test runner not yet rerun against this candidate.
- Targeted operator QA script `incoming/ald1n-b632-release25-pin-recovery-v3.sh`, SHA256 `36dd2913ff74cf34e8b2e87893ab5cea4ffc7be4822f57cd0a5e315540963fa1`. It resolves latest report number from GitHub+hosting, writes a new numbered report to `docs/operations`, fetches/verifies exact PR5 source, runs source-pin and Node syntax/targeted Release25 test gates, then runs the entire 15-file Release25 suite **only when real JDK17 jar/keytool/jarsigner + zip/unzip are available**. Otherwise non-crypto tests run but signature gate stays explicit HOLD, not a silent skip. No production or EAS mutation, no global npm upgrade or test DB access.
- Current independent gates: PR4_SOURCE_STATIC=PASS, PR4_LARAVEL_DB_AND_LIVE=HOLD; PR5_SOURCE_PARITY=13_OF_13_PASS, PR5_FULL_RELEASE25_TESTS=PENDING; EAS_24_12_1_STARTUP_HELP=PASS, EAS_UPGRADE=NOT_APPROVED; no new build/OTA/Play.
- Reports633/634 currently exist as owner-uploaded on-host artifacts. Their full byte-identical GitHub archival status is not yet independently attested. Preserve them locally until checkpointed under the numbered files in GitHub history and reconcile before historical cleanup.

## Batch632 operator Report635 and focused regex recovery (2026-10-12)
- Owner uploaded authentic hosting report `635-BATCH632-RELEASE25-PIN-RECOVERY-20261012-115257.md`. Local copied report SHA-256 `682d388132ddef905dad12884220ca06545621c2717b62cc2848862264d4a2dc`. v3 script from canonical `incoming` saw exact PR5 source SHA `596026f2e7ce48c1c3b06fc4083526c17337dbf6` and verified PIN_PARITY=13/13, source Node syntax PASS. Two targeted Node files ran 9 tests: **8 pass, 1 fail** (RC=1), before reaching the JDK and complete Release25 suite.
- Exact ROOT_CAUSE: `apps/mobile/current/scripts/release25/validator-pin-recovery.test.mjs` uses `assert.match(agents,/eas-cli@24\\.7\\.0/)` even though the canonical `AGENTS.md` and `eas.json` both require 24.8.0. Report635's assertion output dumps full AGENTS text. This is a *test expectation* defect; there is no reason to modify actual EAS pin again. JDK17 availability, seven cryptographic tests and complete 125-test suite remain UNATTESTED by Report635.
- Targeted correction: original draft PR5 branch `audit/batch632-release25-pin-parity-20261012` received **one-line-only** test change to regex `/eas-cli@24\\.8\\.0/`, commit `bbfd4d2723a389eea7c465cf22ade610aeb60063`, file blob `2d987294e7315f7d6ab1128814f94f21d66de559`. RED vs fetched AGENTS for old regex, GREEN for correct regex. The EAS helper, validator, pin in eas.json, production Build25 guard and app identity are unchanged.
- Canonical operator recovery script `/home/icaffeco/ald1n-project/incoming/ald1n-b632-release25-pin-recovery-v4.sh`, SHA-256 `1de33671519f3c4bc6cab9ce83d4a7ac776527037dfb16b3bd475ed1293e4a76` (Bash syntax passed offline). It requires Report635 exact SHA, fetches exact updated branch commit, enforces five-file diff scope and whitespace, reruns source pin and two targeted Node tests, then runs full 15-file Release25 suite with *real JDK17+zip/unzip*. If JDK17 unavailable, run remaining noncrypto tests and classify full signature gate HOLD; never fake signing or report full PASS. Generates next numbered report automatically under `docs/operations`.
- Source fix is on **DRAFT PR5 only**, unmerged, untested on hosting after regex change, unshipped. No new EAS/OTA/native build/Play action. This does not alter PR4 Order134.
- Uploaded Report633/634/635 bytes are local owner/host evidence; GitHub archive status for these numbered reports is still not independently proven. Preserve them until archived.

REPORT635_PIN=13_OF_13_PASS
REPORT635_TARGETED_TESTS=8_OF_9_FAIL_ONE_REGEX
REPORT635_FULL_RELEASE25=NOT_REACHED
REPORT635_PRODUCTION_MUTATIONS=NONE
PR5_HEAD=bbfd4d2723a389eea7c465cf22ade610aeb60063
PR5_RETEST=AWAITING_HOST_V4

## Batch632 operator Report636 and current-pointer regression recovery (2026-10-12)
- Owner uploaded canonical host Report636 `636-BATCH632-RELEASE25-PIN-RECOVERY-R635-20261012-120300.md`; byte SHA-256 `17af104f3f445f734a2c018750f47c151a92f4b3b0ddbf3d9edab14adb55cf51`; generated in `docs/operations` by `incoming/ald1n-b632-release25-pin-recovery-v4.sh`. Previous Report635 hash checked, candidate source `bbfd4d2723a389eea7c465cf22ade610aeb60063`, exact five-file scope + whitespace PASS, canonical EAS pin checks 13/13 PASS, Node syntax PASS, targeted two-file tests **9/9 PASS**, no production mutations.
- Report636 JDK17 evidence: `java`, `jar`, `keytool`, `jarsigner` MISSING on host PATH; `zip` and `unzip` present. The true `verify-signature.test.mjs` suite was **not** executed. Its cryptographic/signer gate remains **HOLD**, not FAIL of signing logic and not PASS. No global JDK install or crypto mock is authorized by this report.
- Report636 noncrypto release25 (14 files) **117/118 PASS, 1 FAIL** (audited RC 1). Single failure in `scripts/release25/batch576-report575-remediation.test.mjs` checks that `docs/operations/000-LATEST.md` begins with the historical `# Ald1n Operations - CURRENT Build25 Authority (2026-10-09)`. This header was intentionally moved into `## Historical Build25 pre-OTA snapshot — NOT CURRENT AUTHORITY` when Workspace V2 became current. The current leading title is `# CURRENT AUTHORITY — Workspace V2 production OTA / Batch632 (2026-10-12)`. The failure is a stale test expectation, not release state regression.
- TDD for scoped fix: old first-header condition **RED** against GitHub index; new live/current header, Build25 versionCode25, deployed OTA source, production group, 24.8 pin and preserved historical Report575 facts independently inspected **GREEN** (read-only source contract). Existing draft PR5 branch received targeted test-only change `0efb11e91a557dd478d8501158076dd82871c2b8` (`batch576-report575-remediation.test.mjs` blob `b8f1ce392f0275b7ae78e7f8bfe9f4140313a13d`). This explicitly preserves historical 24.7/UNATTESTED records as history without making them current. **Do not modify `000-LATEST.md` to satisfy an obsolete assertion**.
- Draft PR5 scope updated from five to exactly **six** Mobile source/test files, still no native/config/OTA/Play mutation. Host runner not yet re-run for this updated SHA. Script: `/home/icaffeco/ald1n-project/incoming/ald1n-b632-release25-pointer-recovery-v5.sh`, SHA-256 `f8d3f085dd1101454397b2c1ce3508e3a85d84fa21c1fc6cfd8621e8a9dc487a`; ties exact Report636 SHA, verifies six-file source diff and PIN_PARITY 13/13, runs targeted and complete noncrypto tests with 15-file full suite only if true JDK17 available; creates next sequential report in canonical `docs/operations`.
- Release state immutable: Android Build25 version `1.0.0`, versionCode `25`, runtimeVersion `1.0.0-build17`, production channel/profile and OTA group unchanged. EAS CLI production pin remains 24.8.0; 24.12.1 unapproved. Draft PR4 Order134 remains independent and unmerged.
- AGENTS.md permanent lessons expanded to reject historical-index-as-current assertions and false signing PASS when actual JDK17 is missing.
- Uploaded Report633/634/635/636 historical raw byte GitHub archive status is not attested. Keep on-host report files until archived; never clean up this fresh evidence prematurely.

REPORT636_PIN_PARITY=13_OF_13_PASS
REPORT636_TARGETED_NODE=9_OF_9_PASS
REPORT636_NONCRYPTO_RELEASE25=117_OF_118_FAIL_ONE_STALE_INDEX_CONTRACT
REPORT636_JDK17_SIGNATURE=HOLD
REPORT636_PRODUCTION_MUTATIONS=NONE
PR5_SOURCE_HEAD=0efb11e91a557dd478d8501158076dd82871c2b8
PR5_POST_FIX_HOST_GATES=PENDING

## Batch632 Report637 and real JDK17 signature closure (2026-10-12)
- Operator Report637 `637-BATCH632-RELEASE25-POINTER-RECOVERY-R636-20261012-121405.md`, immutable byte SHA-256 `010458e9515ef42401ac33e6053e67770983e87656986dc9890456e876014572`, script `incoming/ald1n-b632-release25-pointer-recovery-v5.sh` SHA `f8d3f085dd1101454397b2c1ce3508e3a85d84fa21c1fc6cfd8621e8a9dc487a`: exact PR5 source `0efb11e91a557dd478d8501158076dd82871c2b8`; six-file diff scope PASS; EAS pin parity 13/13 PASS; targeted Release25 pointer/pin 13/13 PASS; noncrypto 14 files **118/118 PASS**, Node syntax and source immutability PASS. Host lacked `java`, `jar`, `keytool`, `jarsigner`; real local signing suite was **HOLD** (audited command RC 2). No production mutations/EAS upgrade.
- The JDK17 gate was completed WITHOUT installing tools on CloudLinux by a purpose-built audit-only GitHub Actions branch `audit/batch632-signature-jdk17-20261012`, **not PR5/main**. It uses `actions/setup-java` Temurin JDK17 and `actions/setup-node` Node 22.16, checks out exactly PR5 source SHA `0efb11e91a557dd478d8501158076dd82871c2b8`, and has no Gradle/Android SDK, EAS, Play, credential upload or artifact-upload step.
- Real signing run `38235367810` (workflow commit `ca490f23111b5800654fa6febdace8beef46fce4`), https://github.com/AldinAga/ald1n-project/actions/runs/38235367810: **7/7 real Java signature tests PASS**, 0 FAIL, Temurin `17.0.20.1`, Node `22.16.0`, candidate source SHA verified, git immutability PASS, 0 artifacts.
- Consolidated offline run `38235861342` (workflow commit `64439ed2c3119e1bf5a43735bf20f576ca7aa12d`), https://github.com/AldinAga/ald1n-project/actions/runs/38235861342: same exact candidate SHA, **15 Release25 test files, 125/125 PASS, 0 FAIL, 0 SKIPPED in ONE JDK17 runner**, plus standalone real signing 7/7 PASS, source immutability PASS, 0 artifacts, no native build/OTA/Play changes. This supersedes Report637's local JDK availability HOLD **for the source's Release25 signing/test gate only**; it does NOT imply Java was installed on the hosting or that a production signed AAB was freshly generated.
- All five owner-supplied raw terminal reports **633, 634, 635, 636, 637** were archived under `docs/operations/` on GitHub `main` with verified byte-identical Git blob hashes, preserving original FAIL/HOLD results without rewriting history. Report637 blob `52b7e5223f475e0c7326c86b914ee9b035a938f2`. This fulfills their historical GitHub checkpoint; host-side cleanup still requires separate explicit safety/retention gates.
- PR5 is still **DRAFT and unmerged**, head `0efb11e91a557dd478d8501158076dd82871c2b8`, changed files six. A final candidate-specific Mobile TypeScript and full project validator audit plus merge authorization remain separate; Release25 125/125 success does NOT authorize publishing, building, submitting to Play or changing EAS CLI 24.8.0. PR4 is independent and still Draft.
- Production Build25 remains appVersion `1.0.0`, versionCode `25`, runtimeVersion `1.0.0-build17`, source of active OTA `52850d185d2c26d0a72a26d64520528714fe2f01`, production channel/profile unchanged. Production OTA group `27dd4b5d-63b3-4902-9b0b-5f289076f0e6` unchanged. No production hosting file, database, EAS OTA, native build or Google Play mutation in these audits.

REPORT637_LOCAL_NONCRYPTO=118_OF_118_PASS
REPORT637_LOCAL_JDK17=HOLD_TOOLS_NOT_ON_HOST
GITHUB_RUN_38235367810_JDK17_SIGNING=7_OF_7_PASS
GITHUB_RUN_38235861342_RELEASE25=125_OF_125_PASS
REPORTS_633_TO_637_GITHUB_BYTE_ARCHIVE=PASS
PR5_RELEASE25_SOURCE_TESTS=PASS
PR5_TYPECHECK_AND_VALIDATOR=PENDING
PR5_MERGE_AND_RELEASE=NOT_AUTHORIZED
PRODUCTION_MUTATIONS=NONE


## Follow-on gated scopes
1. Separate isolated CLI upgrade compatibility lab: npm view on live host; read-only eas version/whoami/project:info/build:list/update:view; fail-closed release25 controller negative tests; no auto pin bump.
2. #134 + notification cold-start / archived-order end-to-end QA with permission matrix and HTTP codes, first reproduce; patch only proven defect.
3. Read-only hosting/GitHub manifest parity and rendered CMS smoke tests; preserve all production drift.
4. 320px/large font/a11y/access roles/offline draft preservation and Android API device matrix, with signed QA receipts.
5. Android v1.5.0 version convergence is a separate explicitly gated future source/build task, not implicitly started here.

## Output statuses
OTA_CLOSED=YES
PRODUCTION_GROUP_VERIFIED=YES
PHYSICAL_UAT=OWNER_ATTESTED_PASS
CANONICAL_EAS_CLI=24.8.0
LATEST_CLI_UPGRADE=HOLD_UNTIL_COMPATIBILITY_AUDIT
ORDER_134=HOLD_UNTIL_REPRODUCTION
CMS_HOST_GITHUB_PARITY=UNATTESTED
CMS_LAYOUT_SHA256_PARITY=PASS
CMS_BLADE_COMPILE=PASS
CMS_HTTP_RENDER=NOT_RUN
WIDE_DEVICE_QA=PARTIAL
PRODUCTION_MUTATION_IN_BATCH632=NO
