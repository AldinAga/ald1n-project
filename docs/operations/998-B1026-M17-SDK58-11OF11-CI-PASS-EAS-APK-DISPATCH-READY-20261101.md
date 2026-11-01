# Report998 - B1026 SDK58 exact-head 11/11 CI PASS and one internal APK dispatch READY (2026-11-01)

## Reconstructed authority
- GitHub `AGENTS.md`, `docs/operations/000-LATEST.md`, Report997, issue124, PR123 and the canonical Google Drive `ALD1N-EXPO-CLOUDLINUX-UPDATE-RUNBOOK.md` were read on 2026-11-01.
- Actual draft PR123 exact head: `4af7b16c4f47c6aa09271ed03993f477c9be1bd8` (open, DRAFT, unmerged). Production SDK57 remains on GitHub main and CloudLinux production was not touched.
- Current CI inspection used GitHub Actions head_sha-filtered API and exact workflow/job logs; no historical source's results were substituted.

## Newly verified CI gate
All eleven distinct workflows for exact source `4af7b16c4f47c6aa09271ed03993f477c9be1bd8` are `completed`/`success`.

| GitHub Actions run | Result |
|---|---|
| 41312573168 - B1012 Node/EAS25 source-only | SUCCESS |
| 41312573143 - v1.5 signed AAB gate source-only | SUCCESS |
| 41312573131 - M17 SDK58 native prebuild | SUCCESS |
| 41312573183 - Mobile Product Editor Android keyboard QA | SUCCESS |
| 41312573210 - v1.5 release candidate source QA | SUCCESS |
| 41312573126 - Batch721 integrated MariaDB candidate QA | SUCCESS |
| 41312573112 - Android LG-03 source QA | SUCCESS |
| 41312573250 - M17 SDK58 migration source, new profile 5/5 and Release25 321/321 | SUCCESS |
| 41312573116 - Android 16KiB emulator infrastructure smoke | SUCCESS |
| 41312573140 - M17 SDK58 Kotlin native compilation, attempt 2 | SUCCESS, 2026-11-01 13:51:51Z |
| 41312573164 - complete Android15 16KiB APK/AAB and offline emulator | SUCCESS, 2026-11-01 14:22:12Z |

The final full-app job 124865851189 shows all steps SUCCESS, including native bridge Kotlin, compiled native ELF 16KiB alignments, all 64-bit APK ELF and ZIP alignments, debug AAB bundletool-derived APK alignment, embedded-JS locally debug-signed APK and offline install/launch in Android15 emulator with a 16384-byte kernel page. This establishes **CI/debug compatibility only**; the EAS-signed SDK58 APK and real physical-device UAT are NOT yet independently attested.

## Actual existing next-step implementation
- One manual-only workflow is in GitHub main: `.github/workflows/b1025-m17-sdk58-eas-preview-android.yml`, initially committed as `8bbd0ce1f19b999261775e638eab98b093b6ddd3`.
- Workflow: https://github.com/AldinAga/ald1n-project/actions/workflows/b1025-m17-sdk58-eas-preview-android.yml
- It runs only with GitHub `workflow_dispatch`, not on push/PR/schedule. GitHub API `actions/runs?event=workflow_dispatch&per_page=100` returned **0** runs at this inspection. Therefore **NO EAS BUILD WAS DISPATCHED** by the connected tools or recorded GitHub Actions manual history.
- Connected GitHub actions support source updates and CI readback but do not expose workflow dispatch or an authenticated Expo/EAS remote build action. Do not create another workflow, change triggers, or bypass the deliberate manual approval. Owner single action: open link above, select branch `main`, click **Run workflow** once. No CloudLinux SSH is required.
- The manual workflow verifies exact PR123 head and all eleven successful workflows again, immutable source checkout, Expo/RN/Firebase-preview/app runtime profile, project ownership, EAS token presence, actual remote preview versionCode and absence of prior same-source build before submitting one paid **internal APK**. Failure to attest any requirement => HOLD without build. Do not blindly retry an ambiguous EAS dispatch.

## Internal build identity (declared, not remote artifact evidence)
- Expo: `~58.0.22`; React Native: `0.88.1`; Nitro Google: `1.3.0`.
- App version: `1.0.0`.
- Android package: `com.ald1n.mobile.preview` (not production package).
- EAS profile and channel: `m17-sdk58-uat`; `distribution=internal`, Android `buildType=apk`, autoIncrement=false.
- Runtime version: `1.0.0-m17-sdk58-uat1`.
- EAS CLI: `25.0.0`; actual CloudLinux/npm/EAS26 host migration NOT RUN.
- Exact source commit: `4af7b16c4f47c6aa09271ed03993f477c9be1bd8`.
- Exact command run ONLY by the authenticated workflow: `npm exec --yes --package=eas-cli@25.0.0 -- eas build --platform android --profile m17-sdk58-uat --non-interactive --no-wait --json`.
- Actual remote `versionCode`: **UNKNOWN**, will be checked live by the workflow; EAS build ID: **NOT CREATED**, signed APK SHA/certificate: **NOT VERIFIED**.
- Play release notes: **not applicable to an internal-only preview APK**; no Google Play submission.

## Remaining independent gates / safety
- Following EAS build dispatch: read exact EAS build ID and completion, validate APK file identity/hash and signing certificate, physical device page size 16384, and real Google native Sign-In success/cancel/session restore with no private token logs. Issue124 stays OPEN; SDK58 production AAB, versionCode, signer, dedicated production runtime, OTA and Google Play still require separate release gate.
- No source change on PR123, no PR merge, no EAS/OTA/Play action, no CloudLinux/CMS/DB mutation and no v1.5/M16 redo in this batch.
- M16 Android production `versionCode=28` remains owner-accepted PASS.
- Formal milestones: **8/17=47.1% program; 7/15=46.7% CMS; 6/6 original UI**. The 11/11 is a CI gate closure, not M17 owner acceptance.

## Evidence references
- Exact full 16KiB job: https://github.com/AldinAga/ald1n-project/actions/runs/41312573164
- Kotlin retry: https://github.com/AldinAga/ald1n-project/actions/runs/41312573140
- Source QA: https://github.com/AldinAga/ald1n-project/actions/runs/41312573250
- Draft PR123: https://github.com/AldinAga/ald1n-project/pull/123
- M17 release tracker: https://github.com/AldinAga/ald1n-project/issues/124
- Manual B1025 build workflow: https://github.com/AldinAga/ald1n-project/actions/workflows/b1025-m17-sdk58-eas-preview-android.yml
