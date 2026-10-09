# Ald1n Operations - CURRENT Build25 Authority (2026-10-09)

This header is the up-to-date **source and gate index at audit preparation time**.
Reverify live refs before any new batch. This file is NOT EAS, Play, billing or production source authority.

- GitHub `main` at Report575: `b5b942e645d28d3ed5f248eaae4180ebe9a54ee6` (does not contain pending Build25 audit work).
- Last verified audit parent: `7c4e4ef435b46a65bc0e0db57dd74509a62a5e89` (Report574 / Report575).
- Batch576 remediation candidate branch: `audit/build25-report575-targeted-fixes-20261009`; obtain its exact HEAD from GitHub and Report576 after push, never infer it here.
- Report575: `575-BATCH541-BUILD25-DEEP-FORENSIC-AUDIT-20261009-123747-575124.md` (read-only PASS; 2 code/documentation findings; 7 external blockers).
- App: `Ald1n CMS`; version `1.0.0`; runtimeVersion `1.0.0-build17`; Android applicationId `com.ald1n.mobile`.
- EAS production profile/channel: `production` / `production`; no production build/submit authorized.
- EAS_CLI=eas-cli@24.7.0 (from AGENTS.md and eas.json).
- REMOTE_ANDROID_VERSIONCODE=UNATTESTED. Read exact EAS remote authority at a separately authorized read-only gate; do NOT assume 23 or 24.
- BUILD25_AUTHORIZED=NO.
- PROGRESS=75_PERCENT_ESTIMATE; native CI audit run 37932584905 PASSED; no production release gate has passed.
- Native CI on candidate `dc775bd39af46764896ee5e204f22122e3018c58`: PASS, GitHub Actions run 37932584905 (one run, no artifact retained). Trigger branch is at the same SHA. New release-controller-only changes require new source approval before any additional CI.
- Direct production CLI and legacy submit are fail-closed. Automatic AAB/APK upload remains disabled.
- Open: EAS remote version command exit 1 (Report577), exact remote project identity (CLI version JSON does not carry it), public Git history/GitHub secret scan attestation, upload signing certificate, retained signed AAB/16 KB/ELF, devices, A03-A14 acceptance, source merge and owner build/Play authorization.
- Reports: `docs/operations/573-BATCH539-BUILD25-DEEP-OFFLINE-CONVERGENCE-SCOPE.md`, `docs/operations/574-BATCH540-BUILD25-CI-COST-SAFETY-SCOPE.md`, and `docs/operations/576-BATCH542-BUILD25-REPORT575-TARGETED-FIX-SCOPE.md`.

- Report577: read-only PASS, EAS version:get exited 1 and suppressed its raw cause, so remote versionCode is still not attested.
- Batch579: pinned eas-cli v24.7.0 source confirms version:get returns a decimal string; parser remediation is isolated, EAS auth/identity remain independent gates.

---

## Archived Build23/Build24 historical snapshot - NOT CURRENT

Obsolete historical entries below are not operational instructions.
They preserve earlier reports and commands strictly for forensic reference.
Ignore any historical next-versionCode, old EAS pin or Build23/24 authorization language below.

### Ald1n Operations - Historical source authority from Build23/24

- Canonical directory: `docs/operations`
- Archive policy: **append-only** for numbered reports.
- Current application version: **1.0.0**
- Current runtimeVersion: **1.0.0-build17**
- Current source authority after Batch525R2: `7066a7aee04a7bdb2c37e862905f74306737e78b`
- Last successful Android production build: **Build22 / versionCode 22**
- Latest attempted Android production build: **Build23 / versionCode 23 — ERRORED during Gradle configuration; no AAB and no Google Play submission**
- EAS remote Android versionCode: **23**; next possible versionCode is **24**.
- Build profile / channel: **production / production**
- Build23: **ERRORED** — versionCode 23 was consumed and the build failed before AAB creation because the local Restore Credentials Expo module lacked `android.defaultConfig.versionName`.
- Build24: **DEFERRED / NOT AUTHORIZED**. AutoSubmit is allowed again, but exactly one Build24 production AutoSubmit requires separate explicit owner authorization.
- Google Play release notes: **required as copy/paste text at Build23 completion**.
- Batch520: **PASS** — subagent shipment tracking visibility + Push/E-mail/Both preference.
- Batch521: **PASS_ACTIVATED via Batch522** — Android Zero-Tap Restore Credentials implementation is committed, migrated and production-enabled after exact Play App Signing SHA-256 + live Digital Asset Links verification.
- Next gate: **explicit owner authorization for exactly one Build24 production build with AutoSubmit; Build24 AAB must pass bundletool/ELF 16 KB verification before final acceptance.**

## Batch524 / Batch525 / Batch525R / Batch525R2 - Build23 Failure + Gradle Source Fix

- Build23 versionCode **23** is consumed with status **ERRORED**; no AAB was produced and no Google Play submission occurred.
- Exact EAS Gradle root cause: local project `:ald1n-restore-credentials` lacked `android.defaultConfig.versionName`.
- Batch525 stopped before mutation on a stale diagnostic-file dependency. Batch525R re-proved the exact root cause from the live EAS log but stopped before mutation because its worktree gate did not classify known historical operations residue.
- Batch525R2 binds Batch525R by exact report SHA, preserves the known historical residue read-only, adds only `defaultConfig { versionCode 1; versionName "1.0.0" }` to the Restore Credentials Expo module, and passes Mobile/Expo/CMS/OpenAPI plus isolated Android prebuild gates.
- Source fix commit: `7066a7aee04a7bdb2c37e862905f74306737e78b`.
- EAS remote Android versionCode remains **23**; the next possible versionCode is **24**.
- AutoSubmit is allowed again, but Build24 remains not authorized until a separate explicit owner approval.
- Build24 AAB still requires mandatory bundletool/ELF 16 KB acceptance after a successful build.
- Report: `docs/operations/548-BATCH525R2-BUILD23-GRADLE-VERSIONNAME-FIX-RECOVERY-20261007-100059.md`.

## Batch523 / Batch523R / Batch523R2 - Fresh Pre-Build23 Release Readiness

- Status: **PASS_READY_FOR_OWNER_AUTHORIZATION via Batch523R2 recovery**
- Source commit: `8a776f8b8820f51791d990ae436c782944525eac`
- Original Batch523 generated a 937-entry CMS release manifest sorted by relative path and passed release-integrity, CMS 983/983, stable strict+render, Mobile, Expo, OpenAPI and live DAL gates.
- Original Batch523 stopped before source commit because its local Restore-options checker looked for rpId at the JSON root instead of the controller's canonical data.options envelope.
- Batch523R corrected the Restore-options checker but stopped before mutation because its replacement manifest helper accidentally sorted complete hash-prefixed lines instead of sorting by relative path, producing a different manifest SHA from the same source authority.
- Batch523R2 binds both failed reports by exact SHA, reuses the original Batch523 path-sorted generator byte-for-byte, requires the exact original 937-entry manifest SHA, validates the canonical data.options Restore contract, and commits only that manifest source change.
- Live Restore options are verified as rpId=cms.ald1n.com and userVerification=discouraged; live DAL remains bound to the exact Play App Signing SHA-256.
- EAS remote Android version remains 22; the next single production AutoSubmit build is versionCode 23.
- Build23 remains deferred until explicit owner authorization.
- Final bundletool/ELF 16 KB verification remains mandatory on the Build23 AAB before final acceptance.
- Failed original report: `docs/operations/541-BATCH523-FRESH-PRE-BUILD23-RELEASE-READINESS-20261006-222816.md`.
- Failed first recovery report: `docs/operations/542-BATCH523R-PRE-BUILD23-RELEASE-READINESS-RECOVERY-20261006-223757.md`.
- Successful recovery report: `docs/operations/543-BATCH523R2-PRE-BUILD23-RELEASE-READINESS-RECOVERY-20261006-224326.md`.

## Batch522 - Play App Signing + Digital Asset Links + Native Compliance Gate

- Status: **PASS_ACTIVATED_GATED**
- Source commit: `33316a89165c49f78215dd33e7ab03513f13f8a3`
- Exact Google Play App Signing SHA-256 was supplied from Play App Signing authority and explicitly rejected if equal to the historical EAS upload certificate.
- Production Digital Asset Links is live at `https://cms.ald1n.com/.well-known/assetlinks.json` for `com.ald1n.mobile`, including handle_all_urls and get_login_creds relations.
- Restore Credentials production config is enabled only after live HTTP 200 + application/json + exact fingerprint verification.
- Restore server origin is derived as android:apk-key-hash from the Play app-signing SHA-256.
- Native source readiness covers API 36, R8/resource shrinking, optimized ProGuard, edge-to-edge/adaptive posture, DAL metadata and SecureStore backup exclusions.
- 16 KB source/toolchain readiness is accepted, but final bundletool/ELF alignment remains an artifact gate on Build23.
- No database mutation, EAS build, EAS submit, OTA publish or Google Play mutation was performed.
- Play fingerprint recorded in public DAL authority: `1D:08:DB:67:06:C6:53:4B:18:AE:E6:D5:AA:C2:3C:B6:0B:CE:EA:28:9B:9D:AD:07:3B:E6:A1:63:5C:2E:DB:21`.
- Report: `docs/operations/540-BATCH522-PLAY-APP-SIGNING-DAL-NATIVE-COMPLIANCE-20261006-221245.md`.

## Batch521 / Batch521R / Batch521R2 / Batch521R3 / Batch521R4 / Batch521R5 - Android Zero-Tap Restore Credentials

- Status: **PASS_IMPLEMENTED_GATED via Batch521R5 recovery**
- Source commit: `42f4207ef106d8567a267bd4e99972a43a25e48f`
- Original Batch521 stopped before mutation on the Report533 post-checkpoint tracked residue.
- Batch521R normalized that exact append-only residue and then rolled back after the server rejected an unsupported Composer require flag.
- Batch521R2 proved the supported Composer path and WebAuthn 5.3.9 resolution, then rolled back after a cwd-relative source-scope inventory mismatch.
- Batch521R3 fixed source inventory and proved TDD green, CMS 983/983, Mobile typecheck/validator and zero new Composer advisories; it rolled back when Expo's live SDK57 compatibility authority advanced to a new same-day patch set.
- Batch521R4 aligned expo 57.0.27, expo-constants 57.0.21, expo-linking 57.0.12, expo-notifications 57.0.22, expo-router 57.0.25 and expo-updates 57.0.25 through canonical CloudLinux npm, proved exact source routes and all canonical quality gates, then rolled back because its native audit incorrectly required allowBackup=false.
- Batch521R5 preserves the existing Android backup posture, verifies expo-secure-store exclusions for encrypted SecureStore data when backup is enabled, and keeps Restore Credentials independent of allowBackup as required by Android guidance.
- Android Credential Manager Restore Credentials client + Laravel WebAuthn server foundation are implemented.
- Restore verification uses userVerification=discouraged to match Android passive GetRestoreCredentialOption semantics.
- Resolved web-auth/webauthn-lib is guarded at 5.3.3+ within the package's 5.3.x contract for Android apk-key-hash origin support.
- Restore keys are stored separately from future user-managed passkeys.
- Normal password and Google sign-in remain canonical and unchanged in user behavior.
- Logout and HTTP 401 clear the Restore Credential state; create flow uses cloud backup with E2EE-unavailable fallback.
- Production activation remains fail-closed until exact Google Play App Signing SHA-256, android:apk-key-hash origin and HTTPS Digital Asset Links are verified.
- Recovery report: `docs/operations/539-BATCH521R5-ZERO-TAP-RESTORE-CREDENTIALS-RECOVERY-20261006-182159.md`
- Failed Batch521R4 report: `docs/operations/538-BATCH521R4-ZERO-TAP-RESTORE-CREDENTIALS-RECOVERY-20261006-175355.md`
- Failed Batch521R3 report: `docs/operations/537-BATCH521R3-ZERO-TAP-RESTORE-CREDENTIALS-RECOVERY-20261006-155312.md`
- Failed Batch521R2 report: `docs/operations/536-BATCH521R2-ZERO-TAP-RESTORE-CREDENTIALS-RECOVERY-20261006-153616.md`
- Failed Batch521R report: `docs/operations/535-BATCH521R-ZERO-TAP-RESTORE-CREDENTIALS-RECOVERY-20261006-152558.md`
- Failed original Batch521 report: `docs/operations/534-BATCH521-ZERO-TAP-RESTORE-CREDENTIALS-20261006-143308.md`
- No EAS build, submit, OTA or Google Play action.

## Batch520 - Subagent Shipment Tracking Notifications

- Status: **PASS**
- Source commit: `dff990823507719d5e2f476523cdeeb9e15cdd5b`
- Creator/subagent sees shipment tracking prominently near the top of the customer order detail with courier, shipped time, copy and safe HTTPS tracking actions.
- Notification settings expose exactly Push, E-mail or Push + E-mail for shipment tracking alerts; no disabled/none option is introduced.
- Canonical OrderShipmentService sends the creator/subagent shipment alert using the chosen channel while keeping a persistent in-app event record.
- Push selection reuses existing device registration; email reuses existing operational notification mail channel.
- Existing supplier/operational order email flow remains separate and creator duplication is suppressed for the shipment event.
- CMS static 983/983, Mobile typecheck/validator, Expo checks and OpenAPI parity passed.
- Production migration applied only after verified backup.
- No EAS build, submit, OTA or Google Play action.

---

---

## Batch518A - APK Upload & Order Continuation

- Status: **PASS**
- Source commit: `e55cbc9d9062c96f405d83e57a592ae5b545776e`
- Android payment-proof upload now uses the existing Expo File + Expo multipart transport instead of legacy React Native uri/name/type multipart.
- Successful customer proof upload refreshes order/payment context and leaves explicit success feedback on the same order.
- Successful admin shipment now continues directly to Isporuka, where tracking is prominent with copy and safe HTTPS open actions.
- CMS static remains 983/983; Mobile typecheck/validator, Expo and OpenAPI parity gates passed.
- No schema/database mutation, EAS build, submit, OTA publish or Google Play action. Build23 remains deferred.
- Next grouped batch: Batch518B Navigation UX Consolidation.

---

## Batch517 - Catalog Image Availability & Legacy Media Publication

- Status: **PASS**
- Source commit: `6ddb27ba26bf893ca1659f814eaa02398b607556`
- Legacy product images for the targeted production SKU set are published to canonical public storage with validated originals and derivatives.
- Catalog API primary-image presentation now falls back to the first ordered image when historical primary flags are missing.
- The authenticated legacy Web media route remains private; Mobile continues using public thumbnail/display/original URLs.
- Production repair was dry-run gated and, when needed, executed only after a verified database backup.
- CMS static remains 983/983; Mobile/Expo/OpenAPI gates passed.
- No EAS build, submit, OTA publish or Google Play action. Build23 remains deferred.
- Next grouped chain: Batch518A APK payment-proof upload + order continuation, then Batch518B navigation UX consolidation.

---


## Batch516 - Financial State Canonicalization

- Status: **PASS**
- Source commit: `318c0cb9f7225f2f2139f72debbb5723872e6316`
- `order_payments` is the canonical received/refunded money ledger; order financial status fields are derived through one projector.
- Manual Web/API/Mobile payment-status mutation is removed; real ledger operations remain authoritative.
- GET order detail no longer persists IPS cache; downstream IPS/receivables reconciliation runs after canonical mutations.
- Controlled production derived-state repair completed only after dry-run census and verified database backup.
- CMS static remains 983/983; Mobile/Expo/OpenAPI parity gates passed.
- No EAS build, submit, OTA publish or Google Play action. Build23 remains deferred.

---


## Batch515R3 - Customer Order Amendment Recovery V3

- Status: **PASS**
- Source commit: `5ceb1cbc43aaeaa132fe9abc2e4a1ea8e2555d27`
- Customer can amend products, quantities, delivery data and note until shipment/completion/cancellation lock.
- Inventory deltas, idempotency and optimistic concurrency protect retries and stale user/admin screens.
- Order documents now persist immutable item snapshots; customer amendment invalidates active confirmation/proforma/invoice for later revision.
- Payment ledger is preserved; payment state, receivables and commission workflow are reconciled with the amended subtotal/items.
- Laravel product save labels use canonical UTF-8 `Sačuvaj` copy.
- Controlled migration `2026_10_03_000200_create_order_document_items_batch515.php` applied after DB backup and source gates.
- No EAS build, EAS submit, OTA publish or Google Play action. Build23 remains deferred.

---


## Historical planned Build23 command - OBSOLETE, DO NOT EXECUTE

```bash
/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package="eas-cli@24.8.0" -- eas build --platform android --profile production --auto-submit-with-profile production --non-interactive --wait --json
```

## Google Play - Napomene o verziji

Poboljšana kompatibilnost i stabilnost aplikacije na Android uređajima.
Ažurirane sistemske komponente u okviru Expo SDK 57.
Dodatna interna poboljšanja pouzdanosti i performansi.

---

## Batch513R2 - Shipment / Delivery Dual Action UX Recovery V2

- Status: **PASS**
- Source commit: `3cbd0d0a67023352292020722441b29185649ca4`
- Recovery: Batch513 stopped in preflight on pre-existing audit/runtime residue; Batch513R then reached CMS static and exposed a stale beta7.7 literal sentinel. Batch513R2 preserves the same domain logic, keeps residue read-only, and aligns that static regression sentinel with the approved dual-action UX.
- Laravel order detail now exposes one logistics action hub with separate confirmation for shipment sent and shipment delivered.
- Mobile exposes the two primary shipment/delivery actions vertically, with COD-aware delivery wording.
- Existing shipment, completion and payment domain semantics are preserved.
- No migration, database mutation, EAS build, EAS submit, OTA publish or Google Play action.
- Build23 remains deferred.
