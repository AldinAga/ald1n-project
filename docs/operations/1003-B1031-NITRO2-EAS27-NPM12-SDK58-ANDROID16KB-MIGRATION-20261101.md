# 1003 - B1031 Nitro Google 2.3.1, npm12, EAS27 and strict signed Android 16KiB release gates

Date: 2026-11-01 Europe/Belgrade. Scope: **source and isolated CI only; no paid EAS native build, release, OTA or Google Play submission**.

## Owner decision and exact baseline

Owner explicitly orders latest compatible Google Nitro / EAS CLI / npm12, then a new Android release, and **EVERY Android native build must prove 16KiB memory-page compatibility**. Production app on Google Play is `com.ald1n.mobile`, last owner-accepted Android versionCode **28** (M16). Existing working internal SDK58 preview APK build `994c264d-a147-40a4-ba7b-51f8734b3422`, source `4af7b16c4f47c6aa09271ed03993f477c9be1bd8`, used Nitro Google1.3.0 / EAS CLI25 and is **NOT** the production Play app (different `com.ald1n.mobile.preview` package, versionCode11). Its earlier actual signed APK static ELF/ZIP 16KiB gate passed 59/59, but does not attest the NEW upgraded release.

## New actual source

[Draft PR127](https://github.com/AldinAga/ald1n-project/pull/127) is stacked on Draft PR126 -> PR125 -> PR123 and remains **OPEN, DRAFT, UNMERGED**. Its newest source head at this report is `6c27cd9f5387a3a12fb8381a98e0fac32eb6c3d5`.

Updated and source-committed `apps/mobile/current/package.json`, `package-lock.json`, `eas.json` and `app.config.js`:

- Expo `~58.0.22`, React Native `0.88.1`, Expo Router `~58.0.29` unchanged.
- `react-native-nitro-google-signin` **2.3.1**, Nitro core **0.37.1** with real npm12-regenerated SHA512-tarball-integrity lock.
- Package manager **npm@12.2.1**, configured EAS CLI **27.2.0**, Node CI **22.23.4**. These are **staged source pins and isolated GitHub CI installation**: they DO NOT represent a global CloudLinux npm upgrade. Host dirty checkout, Node selector, installed npm10 and old protected Build25 controller were not mutated.
- M17 preview runtime `1.0.0-m17-sdk58-uat2` on internal package `com.ald1n.mobile.preview`; M17 Play source-only runtime `1.0.0-m17-sdk58-play2`, production package `com.ald1n.mobile`, channel `production`, Android AAB. App version declared **1.0.0**; actual new Play versionCode NOT ATTESTED. No auto-submit.
- Updated strict Android/iOS Google native source sentinels, UAT/Play runtime negative tests, SDK58 native prebuild and Kotlin CI to use npm12 isolated while preserving old v1.5/Build25 identity and blocking its direct submit.

Real read-only source generation [CI run41324937873](https://github.com/AldinAga/ald1n-project/actions/runs/41324937873) **COMPLETED SUCCESS**: actual isolated npm12.2.1 installation, Nitro2.3.1/Core0.37.1 npm lock, `npm ci`, Expo SDK58 dependency check PASS, TypeScript PASS, Expo Doctor 20/20, new config-runtime checks PASS. Exact generated manifest+lock artifact `13090338129` archived to owner Drive at [source ZIP](https://drive.google.com/file/d/1MXdVTLssTfcc0zHfHaiETWYq6kRDdh5s/view), plus separately archived four manifest/lock files.

## Current exact head QA

Exact latest source `6c27cd9f5387a3a12fb8381a98e0fac32eb6c3d5`:

- [M17 SDK58 source CI 41326273507](https://github.com/AldinAga/ald1n-project/actions/runs/41326273507) **COMPLETED SUCCESS**; **327/327 release regression tests PASS**, Expo Doctor 20/20, typecheck, validator, npm12 lock and SDK58 dependency check PASS.
- [Ald1n v1.5 regression 41326273423](https://github.com/AldinAga/ald1n-project/actions/runs/41326273423) **COMPLETED SUCCESS**, **327/327 PASS**; original v1.5 release identity remains protected. Earlier source head test was 325/327 due to two out-of-date project EAS25 assertions; fixed only those assertions to distinguish historical Build25 CLI wrappers from new SDK58 EAS27, not by bypassing release protection.
- [Native prebuild 41326273435](https://github.com/AldinAga/ald1n-project/actions/runs/41326273435) **COMPLETED SUCCESS**, no signed artifact.
- [Source-only Play profile 41326273469](https://github.com/AldinAga/ald1n-project/actions/runs/41326273469) **COMPLETED SUCCESS**.
- [B1031 npm12/EAS27 exact committed-source CI 41326273448](https://github.com/AldinAga/ald1n-project/actions/runs/41326273448) **COMPLETED SUCCESS**.
- [B1031 strict SDK58 16KiB signed AAB verifier source CI 41326273403](https://github.com/AldinAga/ald1n-project/actions/runs/41326273403) **COMPLETED SUCCESS** with existing original v1.5 signed AAB and APK/ELF regression cases. Newly added verifier source/test/workflow archived to owner Drive [ZIP](https://drive.google.com/file/d/1W9NdWwggQT8MXNvXCFJUi5C07B50hNPg/view).
- Kotlin native compile [new-head 41326273388](https://github.com/AldinAga/ald1n-project/actions/runs/41326273388) **IN_PROGRESS at report creation; NOT PASS until terminal readback**. Previous head Kotlin [41325842611](https://github.com/AldinAga/ald1n-project/actions/runs/41325842611) subsequently **COMPLETED SUCCESS** on the same unchanged upgraded app manifests and native source as latest head; the newer commit only changes two tests plus the source-only 16KiB CI workflow. Independent exact-new-head Kotlin run41326273388 still requires terminal readback. No signed SDK58 AAB or physical 16KiB proof is inferred from debug Kotlin compilation.

## Mandatory EVERY-BUILD 16KiB / Play HOLD

New **source-only postbuild** `apps/mobile/current/scripts/release27/signed-16kb-sdk58-aab-gate.mjs` is completely separate from historical v1.5 verifier. For an authenticated actual signed SDK58 Play AAB it requires actual AAB SHA256, single signed certificate, EAS build ID/source identity declaration, runtime `1.0.0-m17-sdk58-play2`, production package `com.ald1n.mobile`, appVersion `1.0.0`, actual production remote versionCode **strictly greater than 28**, every packaged 64-bit native ELF LOAD segment aligned >=16384 and congruent, bundletool `PAGE_ALIGNMENT_16K`, every bundletool-derived default split/standalone APK verified with `zipalign -c -P 16` and `apksigner`. Outputs only `PASS_SIGNED_AAB_BINARY_GATE_ONLY`; never EAS build, submit or Play authorization. Actual identity/signer/remote-version/device credentials must be separately verified, not inferred from source arguments or debug CI.

**OPEN blockers before native EAS production build / Google Play submit:** current exact-source Kotlin terminal CI, complete signed SDK58 production AAB (does not exist yet), authenticated EAS project/profile/channel/runtime, production signing/upload certificate and Google Play App Signing mapping, independently confirmed remote production versionCode >28, actual bundletool-delivered signed artifact 16KiB proof, physical applicable Android `adb shell getconf PAGE_SIZE` == 16384 and real native Google login success/cancel/error/restart/logout with synthetic authorized test account, full owner/UAT and explicit separate authorized paid build/submit phases. A positive owner report that older preview APK works is not a Nitro2.3.1 signed AAB acceptance.

Permanent 16KiB rule was written to GitHub `AGENTS.md` section 0E. Preserve the old Build25 production controller (EAS25) as a protected legacy path; do not run its previous v1.5 binary gate on this SDK58 candidate. Do not run EAS from CloudLinux SSH or globally install npm12 in the dirty protected checkout. Do not retry ambiguous native build requests or auto-increment production versionCode without audited preflight.

**No EAS paid builds, OTA, Google Play submits, production app/DB/CMS changes or production global npm updates were made by B1031.**

## Formal project acceptance ledger

Formal accepted milestones remain **11/17 = 64.7% overall**, **10/15 = 66.7% CMS**, **6/6 = 100% initial UI** from owner Reports871/872 and M16 Report988. SDK58/M17 NOT ACCEPTED; public storefront M13-M15 OFF until separate security/provider release gates; do not count code/CI checks as production acceptance.

## Next closing action

Read the terminal Kotlin result. If FAIL, fix the exact Kotlin source compatibility without weakening checks. If PASS, prepare authorized separate EAS27 production signed AAB with mandatory full 16KiB artifact inspection and physical Google UAT. Only after all passes and owner-authorized Google Play track action may versionCode>28 be submitted. Source-only success does not authorize a paid build or an upload.
