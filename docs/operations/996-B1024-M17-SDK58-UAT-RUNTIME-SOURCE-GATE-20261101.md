# Report996 — B1024 M17 SDK58 isolated UAT runtime source gate (2026-11-01)

## Reconstructed authority
- GitHub main before this documentation commit: `61d22b7063162753842a8f7ada3152f634bcb5e8`, preserving the existing SDK57 production baseline. Reviewed `AGENTS.md`, `000-LATEST.md`, Report995 and the owner's canonical Google Drive runbook.
- Previous [Report995](995-B1023-M17-SDK58-ANDROID15-16KB-FULL-APP-EMULATOR-CI-20261101.md): former draft SDK58 PR123 head `4c75055f8c900ce7413d41f4fcde8deddf119ae1` passed **10/10 completed CI runs**, including [Android15 full app 16KiB emulator 41244705358](https://github.com/AldinAga/ald1n-project/actions/runs/41244705358), `PAGE_SIZE=16384`, debug-signed embedded-JS APK offline start and seven-second process stability. This is **not** signed production AAB or physical-device attestation.
- Draft [PR123](https://github.com/AldinAga/ald1n-project/pull/123) moved to exact new **B1024** source `4af7b16c4f47c6aa09271ed03993f477c9be1bd8`. Still DRAFT/NOT ACCEPTED, main unchanged by the PR.

## Actual engineering, four files in one commit
1. `apps/mobile/current/eas.json`: Dedicated `m17-sdk58-uat` **internal preview** build profile, channel `m17-sdk58-uat`, `environment=preview`, `autoIncrement=false`, Android APK and iOS simulator-only fallback. Existing production, ordinary preview and v1.5 profile contents remain unchanged; EAS CLI25 remote-version pin remains.
2. `apps/mobile/current/app.config.js`: Correct Expo58 `~58.0.22`, RN `0.88.1`, Nitro Google Sign-In `1.3.0` plus exact preview/profile markers resolve **`1.0.0-m17-sdk58-uat1`** and Android package `com.ald1n.mobile.preview`. Missing/incorrect/cross-profile marker combinations fail closed with `HOLD_M17_SDK58_UAT_PROFILE_OR_SOURCE`; normal Build25 `1.0.0-build17` and v1.5 `1.5.0-native2` behavior are unchanged.
3. `scripts/release25/m17-sdk58-uat-runtime.test.mjs`: Five new regression cases for source pin/profile identity, local/EAS parity, legacy preservation, invalid marker rejection and no-build/no-release semantics.
4. `.github/workflows/m17-sdk58-isolated-upgrade-candidate.yml`: Adds precise new path triggers and early source-only Node22 test step; no EAS build, OTA, signing or Play commands.

## Actual CI status and limitation
- [Exact new-head workflow 41312573250](https://github.com/AldinAga/ald1n-project/actions/runs/41312573250), job `124865850831`: **focused B1024 Node source test step COMPLETED SUCCESS**; subsequent npm install/typecheck/Doctor/complete source regressions were still in progress at the inspected checkpoint. Other triggered CI runs were in progress. **New commit is not yet full-suite accepted**; do not transfer the prior source's 10/10 result to the new source.
- Internal APK profile is **not** signed SDK58 AAB/Google Play evidence; no EAS build ID, remote versionCode, signer/AAB checksum, physical Android 16KiB UAT or native Google OAuth UAT. [Issue124](https://github.com/AldinAga/ald1n-project/issues/124) remains the acceptance tracker. Do not use existing **v1.5-only** signed AAB gate for SDK58.
- B1017 isolated npm12.2.1/EAS CLI26.0.0 CI [run41196744399](https://github.com/AldinAga/ald1n-project/actions/runs/41196744399) PASS does **not** mean server tooling upgraded. CloudLinux Node22.23.4/npm10.9.9/EAS CLI25.0.0 remain the approved host tools. Expo58/RN0.88.1 remains PR source only, not merged or deployed.

## Next gate
Read the terminal *new-head* CI results. If PASS, separately authorize a **preview-only native test build** after authenticated project/profile/Firebase checks; then perform physical Android16KiB and Google Sign-In UAT. Signed SDK58 AAB/signer/remote app-version source requires a separate approved candidate and verified artifacts; only then contemplate any production release. M16 owner-accepted Android versionCode28 remains closed and untouched. Formal milestone ledger stays **8/17 overall, 7/15 CMS, 6/6 original UI**.

**Mutations:** only PR123 source+tests and GitHub/Drive documentation. No CloudLinux/CMS/DB, EAS native build, OTA, Google Play, release versionCode change, source merge or production deployment.
