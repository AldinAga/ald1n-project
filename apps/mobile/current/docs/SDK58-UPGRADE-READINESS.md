# Expo SDK 58 — upgrade readiness (preparation only)

**Status: PLANNED / DEFERRED by owner on 2026-10-21.** This document does not authorize an Expo SDK upgrade, EAS build, OTA, Google Play submission, Android production mutation, CloudLinux deployment, or iOS release.

## Authority and currently observed state

- GitHub `main` at preparation time: `33aca8043c65987e38315fd9638430aeabbf3c38`. Always reconstruct exact `main`, active PRs, `AGENTS.md`, `docs/operations/000-LATEST.md`, newest host reports and canonical Google Drive `ALD1N-EXPO-CLOUDLINUX-UPDATE-RUNBOOK.md` before starting the real upgrade. This SHA is a historical snapshot, not a future build source.
- Production CMS: Laravel source PR #78 and CSS PR #81 are merged, but **actual CloudLinux deployment and live schema state remain unattested**. Do not modify a dirty fixed-DocumentRoot checkout, bypass backup/restore/metadata-lock rules or assume a source merge was deployed.
- Android v1.5: an authenticated, signed AAB versionCode 27 / runtime `1.5.0-native1` was created, but the actual AAB failed Google's 16 KB native memory-page requirement (one `arm64-v8a/libnativegauss.so` had ELF LOAD alignment `0x1000`; 54 other 64-bit libraries were aligned). Existing native fix candidate PR #82 pins the newer verified upstream vendor AAR and requires its own signed AAB validation and a different native runtime. **Do not confuse a passing upstream AAR check or a GitHub source CI with Play compliance.**
- On 2026-10-21 the owner **reported manual Google Play publication**. This is an owner-provided status, not independently verified Play track/version/rollout, and it **does not establish that AAB27 is 16 KB-compliant**. Verify the exact Play artifact before describing any specific version as eligible.
- The SDK 57 mobile release source is on a separate candidate/release branch; the GitHub `main` mobile app configuration can still describe the older v1.0.0 release baseline. **Resolve and freeze the approved mobile source at actual upgrade time** rather than changing an unintegrated old baseline.

## Verified SDK 58 compatibility baseline (official Expo release 2026-10-15)

| Dimension | SDK 57 baseline | SDK 58 target |
|---|---|---|
| Expo SDK | 57 | 58 (stable) |
| React Native | 0.86 | 0.88 |
| React | 19.2.3 | 19.3.0 |
| Android compileSdk | 36 | 37 |
| Android targetSdk | 36 | 36 |
| Android minimum OS | Android 7+ | Android 7+ |
| Node | >=22.13 | >=22.13 |

Official references: https://expo.dev/changelog/sdk-58 and https://docs.expo.dev/versions/v58.0.0/ . **Do not freeze or guess every Expo package patch version here**: resolve compatible package versions against the official SDK 58 template and a current `expo install --check` on the isolated candidate when execution is authorized.

## Required sequence when the owner resumes the SDK upgrade

1. **Close already planned tasks first:** actual Laravel CloudLinux deploy with host evidence and safe backup/rollback gates; resolve the Android 16 KB production binary issue, observe Play track/artifact status, and close PR #82/release chain as separately approved. The owner's physical UAT for the previous Android binary is already acknowledged; do not request a redundant identical 20-point UAT, but a new native SDK or vendor binary needs targeted regression UAT.
2. **Freeze exact mobile source:** resolve current GitHub main/PR status, production signing and EAS project ID, live remote versionCode, Play max code and runtime/channel separation. Preserve `com.ald1n.mobile`, Firebase OAuth signatures, production API base URL and existing keystore provenance.
3. **Upgrade in an isolated checkout/branch, not the dirty CloudLinux production workspace.** Use project-approved Node 22 / npm 10 and its native pin rules. Never execute `expo install --fix` as an installer on CloudLinux; use read-only `expo install --check` to calculate targets and audited direct npm to update `package.json` + `package-lock.json`. Preserve lockfile parity and validator source assertions; do not globally upgrade npm or EAS CLI.
4. **Review RN 0.88 / React 19.3 breaking API changes:** the strict TypeScript API becomes the default, with deep `react-native/Libraries/*` import and ref typing differences. Check removals of `InteractionManager`, `NativeMethods`, `Touchable` root export, old `StatusBar` props and Expo Router custom navigation surfaces. Treat compatibility with `react-native-android-liquid-glass`, `react-native-nitro-google-signin`, `react-native-nitro-modules`, Reanimated/Worklets, Tamagui, screens and the custom Android Expo config plugins as unverified until compiled.
5. **Preserve native behavior:** verify Liquid Glass at Android API 33+ and older supported Android versions, startup/splash, Google Sign-In/credential restore, product editor, keyboard/navigation, notifications, buyer/order API, offline recovery and runtime isolation. Expo SDK 58 prebuilt Android `expo-modules-core` libraries still require actual signed AAB ELF 16 KB, bundletool `PAGE_ALIGNMENT_16K`, generated APK zip alignment and device checks; do not infer compatibility from SDK number.
6. **Only after source QA and owner-authorized targeted device UAT:** issue an explicit distinct native `runtimeVersion` and, if building an actual production release, a fresh signed AAB with strictly increasing *attested* versionCode. Keep build authorization, Play submission, OTA, and Laravel deployment as independent gates.
7. **iOS remains deferred** under the existing production Firebase plist/EAS File identity and Apple signing gates unless the owner explicitly resumes it.

## Starting decision

**NO SDK58 CODE/LOCKFILE/MANIFEST MUTATION NOW.** The next SDK 58 batch starts only after the current Laravel deployment and Android binary compliance work are finished or the owner explicitly changes their priority. This readiness document is source guidance, not evidence of build success.
