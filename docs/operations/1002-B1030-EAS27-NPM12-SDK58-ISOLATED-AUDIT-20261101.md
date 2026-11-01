# Report1002 - B1030 EAS CLI 27.2 / npm12 / SDK58 dependency audit

**Date:** 2026-11-01 (Europe/Belgrade)

## Owner question and verified answer

The signed internal Android APK currently being tested was **not** built with every latest upstream tool and dependency. The finished EAS build `994c264d-a147-40a4-ba7b-51f8734b3422` used source `4af7b16c4f47c6aa09271ed03993f477c9be1bd8`, SDK58 (`~58.0.22`), React Native `0.88.1`, Expo Router `~58.0.29`, Nitro Google Sign-In `1.3.0`, and the explicitly pinned EAS CLI `25.0.0`/npm10 toolchain. Its artifact is the separately verified **internal preview** `com.ald1n.mobile.preview` version `1.0.0`, Android versionCode `11`, SHA-256 `d757c1f5f6320454611149d6fb810ab43bc3cbac4e4c505dd2562e1147ab202d`. It is **not** an update to the production package `com.ald1n.mobile` whose accepted versionCode is `28`. The earlier 59/59 64-bit ELF 16KiB static alignment PASS is preserved. Real physical `adb shell getconf PAGE_SIZE=16384` and full native Google OAuth UAT on the same device remain unverified.

## B1030 real work and outcome

- Current `main` reconstructed; `AGENTS.md`, `docs/operations/000-LATEST.md`, Report1001 and canonical Drive runbook consulted. Original M16/M08/M09/M12 owner acceptance retained.
- Stacked **draft [PR126](https://github.com/AldinAga/ald1n-project/pull/126)**, exact head `8bff5f2819f36f2dde187406db178680470f0c2a` on immutable **draft PR125** `1e91973330df30fdcb626b88736910aa24c4d7b9`, which itself is stacked on PR123.
- Only one changed path: `.github/workflows/b1030-eas27-npm12-sdk58-audit.yml`, Git blob `c35885704befad6237d82c244cc052240238160e`. No app, release, dependency lockfile or production config changed.
- **Actual [GitHub CI run41323611831](https://github.com/AldinAga/ald1n-project/actions/runs/41323611831) COMPLETED SUCCESS**, job `124898838935`. Four pinned source blobs PASS: `package.json` `234f0f6fdfb78fea45c13d3a3ccc44e50282d5d2`; `package-lock.json` `c25320e0a0ec97c392dad338c2a616d1c209e9cb`; `eas.json` `2131c32e34793811a9998fb5289fe06b8801d97d`; `app.config.js` `32ceaf3346680e856fb031be6f9f7bd4c4df0b6e`.
- On exact **Node 22.23.4**, `npm@12.2.1` was installed into isolated temporary prefix (real package version, engine strict), and **eas-cli@27.2.0** installed independently into another temporary prefix (527 packages). CLI output: `eas-cli/27.2.0 linux-x64 node-v22.23.4`; actual `--help`, `build --help`, `submit --help`, `update --help`, `build:version --help` succeeded. No authenticated/publishing EAS command invoked. EAS27 package engine `^20.18.3 || >=22.0.0`, npm12.2.1 engine `^22.22.2 || ^24.15.0 || >=26.0.0` read from registry during CI. Node22.23.4 satisfies both.
- CI messages confirmed `B1030_NPM12_NODE22_ISOLATED=PASS`, `B1030_EAS27_NODE22_NPM12_SAFE_HELP=PASS`, `B1030_REMOTE_EAS_ACTIONS=NONE`, `B1030_NO_DEPENDENCY_AUTO_UPGRADE=PASS`, `B1030_PROTECTED_APP_SOURCE_UNCHANGED=PASS` and `B1030_NO_BUILD_NO_SUBMIT_NO_OTA_NO_PLAY=PASS`.
- npm printed some transitive deprecation notices for `inflight`, old `glob`, `rimraf`, `uuid` and `http-call`; these are upstream packages in the isolated CLI installation, **not** an observed failure or proof of a production vulnerability.

## Actual npm dist-tag snapshot from this same CI run

| Dependency | Project declaration | Latest published npm tag at run time |
| --- | --- | --- |
| Expo | `~58.0.22` | `58.0.22` |
| Expo Router | `~58.0.29` | `58.0.29` |
| React Native | `0.88.1` | `0.88.1` |
| React | `19.3.0` | `19.3.1` |
| Nitro Google Sign-In | `1.3.0` | `2.3.1` |
| Nitro Modules | `0.36.1` | `0.37.1` |
| Worklets | `0.13.0` | `0.13.1` |
| Reanimated | `4.7.1` | `4.7.1` |
| RN Screens | `~4.30.1` | `4.30.1` |
| Expo Notifications | `~58.1.7` | `58.1.7` |
| RN Gesture Handler | `~3.3.0` | `3.4.0` |
| Tamagui | `2.6.0` | `3.1.10` |
| npm | packageManager `10.9.2` | `12.2.1` |
| EAS CLI | `25.0.0` | `27.2.0` |

**Interpretation:** Latest npm tag is NOT a compatibility target. Exact Expo SDK58 bundled dependencies and actual native Google OAuth/Android/iOS behavior govern whether each bump is acceptable. Nitro Sign-In 1.x -> 2.x, Tamagui 2.x -> 3.x and React 19.3.0 -> 19.3.1 are NOT approved upgrades. Worklets patch 0.13.1 is NOT automatically aligned with Expo SDK58 until independently checked. EAS CLI 27.2.0 **help-only/isolate PASS**, not an authenticated actual EAS production AAB build or proof that the existing release controller is compatible with this CLI generation.

## Next gating and authorization

- Keep production CloudLinux Node22/npm10/EAS25, SDK57 currently installed production and M16 versionCode28 unchanged. Preview EAS APK versionCode11 does not become the Play bundle. No request for another paid EAS build is justified only to update CLI.
- Before CLI27 adoption: exact full EAS API option contract/source protection, authenticated read-only project/version/signer checks and separated release-authority review; do not modify `eas.json.cli.version` or hard-pinned Build25 controllers as a shortcut.
- Before SDK58 production build/Play: physical `PAGE_SIZE=16384` device, native Google Sign-In positive/cancel/error/restart/logout UAT, exact source and signed AAB provenance/signer, new effective runtime/OTA compatibility, package `com.ald1n.mobile`, remotely validated versionCode **greater than 28**, 16KiB bundletool/ELF/ZIP, and independent owner release approval. No production build command is authorized by B1030.
- Public Laravel Storefront remains OFF; unrelated M10/M11/M13-M15 security and owner gates stay open. Correct formal acceptance: **CMS 10/15=66.7%, program 11/17=64.7%, first six UI 6/6=100%**. New B1030 CI is engineering progress, not a newly accepted milestone.

## Source and archives

- [PR126](https://github.com/AldinAga/ald1n-project/pull/126); [exact head](https://github.com/AldinAga/ald1n-project/commit/8bff5f2819f36f2dde187406db178680470f0c2a); [read-only CI](https://github.com/AldinAga/ald1n-project/actions/runs/41323611831); [source workflow](https://github.com/AldinAga/ald1n-project/blob/8bff5f2819f36f2dde187406db178680470f0c2a/.github/workflows/b1030-eas27-npm12-sdk58-audit.yml); [B1028 signed APK report](https://github.com/AldinAga/ald1n-project/blob/main/docs/operations/1000-B1028-M17-SDK58-EAS-SIGNED-APK-STATIC-PASS-DEVICE-UAT-HOLD-20261101.md).
- No direct CloudLinux execution, Git merge, EAS native paid build, OTA or Play submit, production source/schema/inode cleanup.
