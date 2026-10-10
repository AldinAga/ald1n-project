# Ald1n Android v1.5.0 Version Convergence & Release Gate Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver a separately gated future Ald1n Android application update whose user-visible version is **v1.5** and canonical Android `versionName` is **`1.5.0`**, after UX and notification acceptance, while maintaining all native safety and Google Play checks.

**Architecture:** Treat the version bump as an independent release-source change, not a by-product of a UI patch. Inventory every application `1.0.0` validation/identity gate and update only application versionName contracts deliberately, keeping distinct Android library metadata unchanged. Independently re-attest the remote EAS versionCode, runtime/native compatibility, signing and 16 KB requirements before any authorized build.

**Tech Stack:** Expo SDK57/RN0.86.3, EAS CLI pinned 24.7.0, Android Gradle Plugin 8.12.0, Gradle 9.3.1, Kotlin 2.1.20, NDK 27.1.12297006, Java17, R8 full-mode resource shrinking, Android SDK36.

**Spec:** `docs/superpowers/specs/2026-10-10-ald1n-mobile-operational-workspace-v2-design.md`, especially **Owner release-label decision — v1.5.0** and §7.

## Global Constraints
- Owner selected **Android v1.5.0** as a release target; no source version bump, EAS build, OTA, versionCode reservation or Play submit has yet been authorized by this document.
- Current GitHub `main` still has `app.config.js` `version: '1.0.0'`, `package.json` `version: '1.0.0'`, `runtimeVersion: '1.0.0-build17'`, and `scripts/validate-project.mjs` requiring 1.0.0; snapshot test `scripts/batch528-native-contract.mjs` also expects app `versionName: '1.0.0'`.
- `eas.json` is `appVersionSource: "remote"`, production `autoIncrement: true`, profile/channel `production`/`production`. `versionCode` must be determined through fresh remote authority and may not be inferred from local versionName or older Report589/590.
- Preserve internal library versionName 1.0.0/versionCode1 used by `ald1n-restore-credentials` module's Gradle metadata; do not replace globally.
- Preserve applicationId `com.ald1n.mobile`, compile/target SDK36, minSDK24, AGP8.12.0, Gradle9.3.1, Kotlin2.1.20, NDK27.1.12297006, Java17 and all R8 optimized resource shrink/ProGuard/16 KB/ABI guards.
- Current Build25 two-phase controller is pinned to exact source/identity/version and separately owner-authorized **build-only** then **submit-only**; do not repurpose or weaken it automatically for v1.5.0.
- Expo runtimeVersion must be assessed rather than set equal to versionName; changing it affects OTA binary groups.
- Global Expo app `version` also affects iOS metadata. The v1.5 request does **not** authorize a separate iOS production build.
- No raw signing material, private keystores, or production keys in source/report. No EAS/Play production action until fresh gates and distinct explicit release authorization.

## Review Focus
1. Android v1.5.0 but backend/OTA runtime still pinned to older group: fail/review compatibility rather than install incompatible OTA.
2. Remote `versionCode` changed or Build25 already reserved it: fail closed and re-attest, never reuse guessed 25.
3. Existing validator expects 1.0.0: update only application-version assertions, preserving library `versionName=1.0.0` and native contract checks.
4. User upgrades from earlier installed APK: signing/restore credentials/16 KB/ABI behavior must remain intact, proven on physical devices.
5. Release notes or Play metadata mismatch actual binary: generate exact Serbian text only from verified shipped changes; evidence-linked build report required.

---

### Task 1: Read-only version/identity matrix before source mutation

**Files:**
- Create during execution: numbered `docs/operations/<next>-ANDROID-V1_5-IDENTITY-PREFLIGHT.md` with source/version/gate evidence; no product code at this step.

**Interfaces:** source authority `main HEAD SHA`, observed exact EAS project UUID/remote versionCode/signer public certificate, Build25 existing journal status.

- [ ] **Step 1:** Reconstruct fresh GitHub main + latest reports; verify Build25 journal/active release authority, `app.config.js`, `package.json`, `package-lock.json`, `eas.json` and exact current output of the approved EAS remote version/status probes.
- [ ] **Step 2:** Audit targeted `1.0.0` references using scoped `git grep` with explicit nonzero/RC handling. Categorize **application versionName**, internal Android library metadata, Expo runtimeVersion and historical assertions in separate sets; protect unrelated constants.
- [ ] **Step 3:** Write a simple failing version convergence contract `apps/mobile/current/scripts/android-v15-version-contract.test.mjs` that checks `version: '1.5.0'`, package metadata consistency, expected application `versionName` guard and preserved internal module versionName plus unchanged native snapshot/versionCode policy. RED is expected before edits.
- [ ] **Step 4:** STOP if Build25 is in flight, EAS identity unavailable or requirements imply modifying a sealed AAB; do not bypass source/build audit.

### Task 2: Deliberate release-source `versionName` convergence

**Files (provisional; finalize exact allowlist from Task 1):**
- Modify: `apps/mobile/current/app.config.js` Expo app version.
- Modify: `apps/mobile/current/package.json` and top-level `apps/mobile/current/package-lock.json` package metadata only, if policy requires consistency.
- Modify: `apps/mobile/current/scripts/validate-project.mjs` application version assertion.
- Modify: `apps/mobile/current/scripts/batch528-native-contract.mjs` **application snapshot expected versionName only**, not library versionName regex.
- Modify only if actual release identity comparison proves necessary: separately scoped release25 expected identity constant(s), with exact owner-approved provenance and negative tests. Never weaken fail-closed checks.
- Test: `apps/mobile/current/scripts/android-v15-version-contract.test.mjs`, `scripts/batch528-native-contract.test.mjs`, existing release25 tests.

**Interfaces:**
- Expo effective config must report Android application `versionName=1.5.0`, `applicationId=com.ald1n.mobile`, other locked native baseline unchanged.
- Remote `versionCode` is an independent increasing integer managed by authorized EAS release procedure; it is **not** hardcoded to 15, 150 or 25.
- Preserve `runtimeVersion='1.0.0-build17'` pending separate compatibility decision; do not silently push an OTA with unreviewed native/runtime pairing.

- [ ] **Step 1: Verify RED** `node --test scripts/android-v15-version-contract.test.mjs` and save negative result.
- [ ] **Step 2: Change smallest exact source/version assertions** to v1.5.0, retaining source/default package and unrelated module pins. If the native/release controller's source allowlist blocks it, stop and prepare a separate gate change with evidence rather than disabling checks.
- [ ] **Step 3: GREEN** targeted version tests, native-source validator, `npm run typecheck`, Mobile full validator summary `Ukupno FAIL: 0`, `expo install --check`, Expo Doctor and `node --test scripts/release25/*.test.mjs` where execution environment allows. Do not claim unavailable JDK signature tests as passed.
- [ ] **Step 4:** Re-audit exact `git diff --check`, file allowlist, no Gradle/R8/AGP/NDK drift. Commit on isolated release-source branch; no EAS action.

### Task 3: Independently gate future Android build and Play submission

**Files:**
- New operation report/acceptance receipts only; no automatic product/source changes.

**Interfaces:** Must publish exact tuple `(appVersion=1.5.0,versionCode=freshly attested integer,runtimeVersion=verified exact value,sourceCommit=approved SHA,profile=production,channel=production,applicationId=com.ald1n.mobile)`, specific EAS build ID/hash and signed AAB SHA-256.

- [ ] **Step 1:** Recheck exact project/owner/slug/UUID, versionCode, remote signer public SHA-256 and build controller compatibility **freshly**, after all source approvals and before authorization of any EAS command. If existing Build25 controller cannot safely handle v1.5, design and review a new controller/versioned authority; do not use a direct CLI workaround.
- [ ] **Step 2:** Present the exact **authorized** build command, app version, current remote next versionCode, runtimeVersion, source commit, production profile/channel and owner build authorization for review. Before approval, the exact build command/number is **not yet established**, and no build is dispatched.
- [ ] **Step 3:** Upon separately requested production build (future), use owner-approved `build-only` path; retain unique run ID, SHA-256, signer, R8 mapping, ABI/ELF+ZIP alignment and 16KB evidence. Test on required physical API24–32 and API33+ devices plus any 16KB-specific device/emulator before upload.
- [ ] **Step 4:** Only after another independent explicit Play submit authorization, use exact `submit-only` sealed AAB. No unknown-status retries and no second dispatch.
- [ ] **Step 5:** Record `BUILD_CREATED`, `GOOGLE_PLAY_ACTION`, exact commands/results and source identity in final operations report. Output copy/paste Google Play release notes verified against the actual feature set. Draft template (DO NOT claim shipped until device acceptance):
  ```text
  Novosti u verziji 1.5.0
  • Pregledniji detalji porudžbina i kontakt podaci krajnjeg kupca na prvom mestu.
  • Brži pristup operativnim akcijama i modernizovana navigacija radnim prostorom.
  • Poboljšano otvaranje obaveštenja o porudžbinama i jasnije poruke kada zapis nije dostupan.
  • Poboljšanja stabilnosti i performansi.
  ```

**Stop condition:** Until all source, ABI/16KB, signing, runtime, physical-device, EAS identity, owner-build and separate Play-submit gates are met, status is **v1.5.0 PLANNED — NO PRODUCTION BUILD**.
