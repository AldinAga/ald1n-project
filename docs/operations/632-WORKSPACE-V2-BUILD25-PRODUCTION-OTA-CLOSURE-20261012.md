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
