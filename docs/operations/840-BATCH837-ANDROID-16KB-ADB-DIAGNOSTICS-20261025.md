# 840 — B837 16 KB emulator ADB diagnostics and isolated offline infrastructure QA

Date: 2026-10-25 (Europe/Belgrade). **Source/CI only; no production build or deployment.**

## Starting authority and ground truth

Repository `AldinAga/ald1n-project`; reconstructed `main` at `745737282b96af7c656a94a3cd6ce7cdc065cb6e`, GitHub `AGENTS.md`, `docs/operations/000-LATEST.md` (stale header B832, newer numbered reports supersede it), canonical Google Drive `ALD1N-EXPO-CLOUDLINUX-UPDATE-RUNBOOK.md`, PR82/PR93/PR98, and actual GitHub Actions run logs. PR93 remains DRAFT; no repeat of blocked B809/B815 fixture changes. Storefront remains OFF, CMS production B798 unchanged.

## Regression traced to its source

[Previous full-native CI 40137218246](https://github.com/AldinAga/ald1n-project/actions/runs/40137218246) **failed only at the real 16 KB emulator ADB step**. Kotlin/Fabric native compile, Gradle disposable debug APK and debug AAB, ELF checks and 16KB bundletool derived APK passed. The emulator booted (Google APIs ps16k x86_64), the shell callback ran, but it returned `HOLD_ADB_COMMAND_FAILED` without the failing command's identity. Do not infer Android compatibility failed or passed from the opaque code. No production EAS AAB was built.

## Delivered in B837

In open PR82 head **`b9f569280d99a9f1e6b0cb24f2427f9d6bc69b9f`**:

1. `apps/mobile/current/scripts/release25/verify-android16kb-emulator.mjs`: fixed allowlist of ADB stage names; safe categorized exit/timeouts/offline/unauthorized/install parser result without raw stdout/stderr, APK file paths, customer data or bearer links in logs. Emits ordered run-stage receipts and a strict HOLD at the failed step. Keeps page-size 16384, API35+, ABI x86_64, airplane-mode OFFLINE before install, actual APK install/start/PID/runtime checks.
2. `apps/mobile/current/scripts/release25/verify-android16kb-emulator.test.mjs`: 9 additional regressions, now **20/20 local PASS**, including safe diagnostic classification, real subprocess missing-executable failure, strict 4KB HOLD, ordered stages, preflight-only no-APK guarantee and offline-radio failure.
3. `.github/workflows/android-v15-16kb-emulator-infra-qa.yml`: pinned Android 15 `google_apis_ps16k` emulator, KVM and independent **infrastructure-only** PAGE_SIZE/ABI/flight-mode audit. Does **not** compile, install, launch or sign an application; its PASS must never count as signed AAB, Play approval or physical-device UAT.

Node v22.16 local evidence: `20 tests; 20 pass; 0 fail`; source/test/workflow ZIP SHA available in archived package. [Canonical source PR82](https://github.com/AldinAga/ald1n-project/pull/82).

## CI receipts (honest pending/fail distinctions)

- Exact new-source [infra smoke 40180986597](https://github.com/AldinAga/ald1n-project/actions/runs/40180986597): **PASS — actual Android 15 emulator, PAGE_SIZE=16384, API35, x86_64, airplane/offline proved; 20/20 source regressions PASS; NO APP INSTALLED**.
- Exact new-source [signed-AAB source gate 40180986589](https://github.com/AldinAga/ald1n-project/actions/runs/40180986589): **PASS**, source-only.
- Exact new-source [full native 40180986542](https://github.com/AldinAga/ald1n-project/actions/runs/40180986542): **IN PROGRESS as of report checkpoint (bridge/native build; APK installation and launch NOT YET ATTESTED)**.
- Source ZIP [Google Drive](https://drive.google.com/file/d/1QBFs4PwQUyaSeeme4_Ba9NQj3brafdgX/view?usp=drivesdk), containing validator, regressions, infra workflow, test log.
- Current `docs/operations/000-LATEST.md` still starts B832. Earlier provider safety denied updating this canonical index; this batch does not bypass or retry that denied edit. This numbered report plus latest main/PR head are the current reconstruction facts.

## Release and acceptance boundaries

- **Accepted program: 7/17 = 41.2%; CMS 7/15 = 46.7%; original UI 6/6 = 100%.** This is the fixed acceptance ledger, not coding-percentage and not a timeline. B837 CI-only work is not a new accepted milestone.
- M16 Android 16 KB **HOLD** until real emulator debug app succeeds and separately authorized signed production EAS AAB with fresh remote versionCode, source/identity/signer/ELF/split APK delivery and device/Play approval passes. Then M17 SDK58 work may start.
- PR93 registered-order receipt security 4/4 tests and 25 assertions passed in B836, but full CMS/source/security/merchant/SMTP/restore/UAT/host gates remain HOLD. Do not enable public storefront or deploy based on scoped tests.
- **No EAS, OTA, Play, SSH, CloudLinux, Laravel DB, customer mail, or production state mutation. No operator SSH step required in B837.**
