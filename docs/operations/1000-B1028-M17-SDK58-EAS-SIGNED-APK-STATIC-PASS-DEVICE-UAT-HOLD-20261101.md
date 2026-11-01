# Report1000 — B1028 Expo SDK58 EAS-signed internal APK: static verification PASS, physical UAT OPEN

**Date:** 2026-11-01 (Europe/Belgrade). **Exact source:** `4af7b16c4f47c6aa09271ed03993f477c9be1bd8` (draft PR123, unmerged). **Initial main reference before this report:** `497a1ccb0c29fa515ca9d9ff5eefc1fdecdda2d5`. Canonical authority: `AGENTS.md`, `docs/operations/000-LATEST.md`, and Google Drive `ALD1N-EXPO-CLOUDLINUX-UPDATE-RUNBOOK.md`.

## B1025: one authenticated, intentionally dispatched EAS preview APK

- [GitHub Actions B1025 manual run41318158284](https://github.com/AldinAga/ald1n-project/actions/runs/41318158284) **COMPLETED SUCCESS**, 2026-11-01 14:51:27Z. All 11/11 exact PR123 source CI checks, locked SDK58 project, EAS account/project ID and duplicate-version preflight succeeded, followed by **ONE** `eas build --platform android --profile m17-sdk58-uat --no-wait --json` request. No Play submit, OTA publish, production EAS build or CloudLinux modification.
- [EAS build 994c264d-a147-40a4-ba7b-51f8734b3422](https://expo.dev/accounts/ald1n/projects/ald1n-mobile/builds/994c264d-a147-40a4-ba7b-51f8734b3422), authenticated original GitHub artifact **13088067621** downloaded and inspected independently. Observed `sourceSha=4af7b16c4f47c6aa09271ed03993f477c9be1bd8`, `profile=m17-sdk58-uat`, `package=com.ald1n.mobile.preview`, `appVersion=1.0.0`, `runtimeVersion=1.0.0-m17-sdk58-uat1` and **EAS-observed preview versionCode `11`**. At 14:51Z receipt the status was `NEW`; not a claim of APK completion then.
- **Exact original EAS build command**, executed inside isolated GitHub runner with `EXPO_TOKEN` secret (do NOT paste on CloudLinux SSH):

```bash
npm exec --yes --package=eas-cli@25.0.0 -- eas build --platform android --profile m17-sdk58-uat --non-interactive --no-wait --json
```

**SDK58 app identity:** Expo `~58.0.22`, RN `0.88.1`, Nitro Google Sign-In `1.3.0`, `appVersion=1.0.0`, Android package `com.ald1n.mobile.preview`, EAS profile/channel `m17-sdk58-uat`, declared source runtime `1.0.0-m17-sdk58-uat1`, EAS CLI `25.0.0`, exact draft source SHA above. The authenticated EAS `build:view` response independently exposed source/profile/appVersion/versionCode, but **did not expose `channel` or `runtimeVersion`**; these two remain pinned in immutable app config/CI and are not mislabeled as independent EAS response fields.

## B1028: actual signed APK static verification

- New [read-only verifier](https://github.com/AldinAga/ald1n-project/actions/workflows/b1028-m17-sdk58-eas-signed-apk-readonly.yml) and its 6 isolated Node regression tests added to GitHub main commit `3771abcf77bad0dcdc4b33a2e7eaa95d694dbc32`; tests 6/6 PASS. Workflow only uses authenticated `eas build:view` and optional APK download/inspect; **contains no EAS build request** and cannot submit or publish.
- Initial [run41320556816](https://github.com/AldinAga/ald1n-project/actions/runs/41320556816) proved **EAS status `FINISHED`, exact source/profile, versionCode `11` and actual APK download URL present**. The inspect step failed at `command -v sdkmanager` (binary was not on runner PATH), **before downloading/inspecting APK**; this was CI runner tooling only, not an EAS build failure or 16 KiB defect.
- Scoped repair in GitHub main commit `497a1ccb0c29fa515ca9d9ff5eefc1fdecdda2d5` uses the already-proven project Android SDK discovery `find "$ANDROID_HOME/cmdline-tools" -name sdkmanager`, JDK17 and Build Tools 36.0.0; no new native EAS build or app code changes. The [rechecked run41320715953](https://github.com/AldinAga/ald1n-project/actions/runs/41320715953) **COMPLETED SUCCESS** at 15:21:18Z; signed APK inspection and bounded receipt artifact **13088654632** both SUCCESS.
- Actual authenticated artifact from **the same EAS build ID** downloaded only into disposable GitHub runner scratch. `aapt` read actual APK `com.ald1n.mobile.preview`, versionName `1.0.0`, versionCode **11** (matches EAS view); `apksigner verify --verbose --print-certs` passed with a modern APK signature scheme, and certificate fingerprint was read; `zipalign -c -P 16 -v 4` succeeded; `readelf -W -l` checked **59/59** 64-bit `.so` libraries, **176/176** ELF `LOAD` segments with alignments >=16384 and 16 KiB offset/virtual-address congruence. Only sanitized metadata was archived; neither signed APK nor its transient artifact download URL was copied into public GitHub artifacts.

**Verified signed internal APK SHA-256:**

`d757c1f5f6320454611149d6fb810ab43bc3cbac4e4c505dd2562e1147ab202d`

**Verified APK signing certificate SHA-256:**

`6cc3c8407423d6d4aa15d9ec7e1d3197d8f18bc0a671732320c7e0dc4d2dc3f2`

**Status:** `SIGNED_INTERNAL_APK_STATIC_PROVENANCE=PASS_SCOPED`, `B1028_SIGNED_APK_NATIVE_ELF_16KIB=PASS_SCOPED`, `PHYSICAL_DEVICE_16KIB=NOT_RUN`, `NATIVE_GOOGLE_SIGN_IN=NOT_RUN`, `M17_PRODUCTION_RELEASE=HOLD`.

## Next practical step: device UAT, no second EAS build

Owner can install **this same completed internal preview APK** from the above EAS build link (not a random debug artifact). Do **not** uninstall or replace the existing `com.ald1n.mobile` production app: new preview package is `com.ald1n.mobile.preview`.

- **Device identity / 16 KiB kernel:** use an applicable physical Android device with debugging enabled and run `adb shell getconf PAGE_SIZE` from a trusted local computer (not CloudLinux); only exactly `16384` closes the physical 16-KiB gate. Record OS release/model without publishing serial/device IDs or private logs. A result of `4096` allows general functionality testing but **does not** pass physical 16-KiB UAT.
- **Installed app identity:** confirm package `com.ald1n.mobile.preview`, app version `1.0.0`, `versionCode=11`, and expected signing certificate fingerprint. If device shows another build, HOLD.
- **Google native Sign-In:** test sign-in success with an authorized synthetic/test account, cancellation, a controlled error, logout and session restoration on app restart; compare preview package plus signer to Firebase/Google OAuth app configuration. Do not paste access tokens, actual user identifiers, OAuth secrets, or private logcat. Source google-services.json contains a preview package entry, but that alone does NOT prove that the actual EAS signing fingerprint is registered with the OAuth client.
- **Core navigation:** verify splash/launch, dashboard/catalog access, item details, product editor keyboard and image UI with synthetic/test data only; evaluate background/resume and offline-to-online transition without creating real customer orders, payments, stock movements or outbound messages.
- **Acceptance record:** for each item explicitly report PASS/FAIL/SKIP, model/OS, page size value, installed app version/code, signer hash (if independently verifiable), and narrowly scoped sanitized failure symptom. Missing evidence stays NOT_RUN. M17 remains DRAFT until owner acceptance and any separate release signing/AAB/Play requirements are independently met.

## Preserved release boundaries

- **M16 remains accepted** on production Android `versionCode=28`; this preview `versionCode=11` is a different application ID. Do not rerun/replace production M16 merely because a preview APK exists.
- **No SDK58 production AAB** has been requested or signed here, and this signed **APK** is NOT an AAB. No production OTA/Play, PR merge, CMS/database change, CloudLinux global EAS/npm upgrade or unrelated migration.
- Before an SDK58 production rollout, independently approve and validate a new production `runtimeVersion` different from the preview and from SDK57/v1.5, a production signed AAB plus signer and 16KiB bundletool delivery checks, Google Play gate, physical-device UAT and owner release decision. No Google Play "Napomene o verziji" for this internal APK; production release notes are not applicable.
- Formal acceptance ledger remains **8/17 = 47.1% program**, **7/15 = 46.7% CMS**, **6/6 = 100% original UI**: this signed internal APK evidence advances engineering but does not formally close M17.

**Next tracking:** [Issue124](https://github.com/AldinAga/ald1n-project/issues/124), PR123 draft, no new expensive build authorized by this report.
