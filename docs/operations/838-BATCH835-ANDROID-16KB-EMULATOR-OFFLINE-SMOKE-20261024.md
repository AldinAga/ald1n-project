# B835 - Android 15 16 KiB emulator offline QA

Date: 2026-10-24. SOURCE/CI-ONLY. GitHub main prior: `36845f12f7fa22d29d0a4bc357f06969ac8088db` (before B835). Canonical `AGENTS.md` and Drive update runbook reviewed. No CloudLinux/DB/EAS/OTA/Play mutation, no SSH.

## Delivered source

PR #82 `fix/android-v15-16kb-nativegauss-20261021` exact candidate `5601aa77cc2c7bc9cde1c2e4225aae3e3564b687`, against `release/android-v15-production-aab-one-20261021`. Added:
- `apps/mobile/current/scripts/release25/verify-android16kb-emulator.mjs`: require exactly one x86_64 Android emulator, API >=35, real `adb shell getconf PAGE_SIZE` == 16384, flight mode before install/start, valid APK install and `com.ald1n.mobile/.MainActivity` surviving 7 seconds without network, no app-specific AndroidRuntime fatal. Report only CI debug emulation PASS, not physical or Play.
- `apps/mobile/current/scripts/release25/verify-android16kb-emulator.test.mjs`: 11 positive/negative, offline, page-size, ABI, install/start/crash and CLI HOLD regression cases.
- `.github/workflows/android-v15-signed-aab-gate-source-qa.yml`: 11 tests wired alongside existing 47 tests.
- `.github/workflows/android-v15-nativegauss-16kb-qa.yml`: pinned `ReactiveCircus/android-emulator-runner@a421e43855164a8197daf9d8d40fe71c6996bb0d` (v2.38.0), `google_apis_ps16k` Android 15 x86_64, KVM, offline app smoke AFTER native APK/AAB artifact gates.

## Actual proof / unknowns

- Local Node22: **58/58 PASS**, zero failures, source archive SHA256 `1ebb861bb1c98708d19188dda68fa79001f8ff3eb087e727290e1d886d12dbd7`.
- Exact-head [GitHub source CI #40135270379](https://github.com/AldinAga/ald1n-project/actions/runs/40135270379): **58/58 PASS**, zero failures in decoded job logs.
- [GitHub full native/emulator workflow #40135270378](https://github.com/AldinAga/ald1n-project/actions/runs/40135270378) is **not attested complete** as of this report. It must explicitly show `ANDROID_15_16KB_EMULATOR_OFFLINE_LAUNCH=PASS_CI_DEBUG_ONLY`, actual 16384 page-size JSON and a completed success job before awarding real emulator evidence. Failure = HOLD; no forced skip.
- Historical B831 actual debug APK [#40131233437](https://github.com/AldinAga/ald1n-project/actions/runs/40131233437) **PASS** 57/57 64-bit ELF, ZIP 16K. Historical B832 [#40132378163](https://github.com/AldinAga/ald1n-project/actions/runs/40132378163) **FAIL** due unsigned bundletool-derived CI APK (not new 4KB ELF failure); B834 supplies isolated throwaway-key fix; B835 does not assume it has completed in a full native CI run.
- Full source/runtime release M16 **HOLD** until a separately authorized new signed production EAS AAB (remote `versionCode` >27 and attested SHA/source/runtime/signer), signed artifact AAB+derived universal/default APK 16K audit, appropriate physical/Play evidence. Old rejected AAB27 cannot be reused.

## Progress accounting

Original acceptance ledger still **7/17=41.2%** overall, **7/15=46.7%** CMS, original UI **6/6=100%**. B835 adds actual committed source and verified source CI, not an accepted milestone. CMS production B798 unchanged, storefront OFF. PR82 remains open/unmerged; PR93/98 stay Draft; SDK58 deferred. No production-build command because no production build occurred.

B833 attempt to update `docs/operations/000-LATEST.md` was denied by tool safety; B835 did not bypass that denial. Its canonical index may still point at B832: use this numbered report and current GitHub main/PR head for reconstruction.
