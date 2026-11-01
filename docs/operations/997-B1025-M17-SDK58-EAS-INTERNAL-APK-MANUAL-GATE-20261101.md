# Report997 — B1025 M17 SDK58 one internal Android EAS APK manual-only request gate (2026-11-01)

## Purpose and exact authority
- Owner says a new month's build budget is available and authorizes proceeding to the next controlled step. This is **not** authorization for production AAB, OTA, Google Play submission or a merge.
- Read latest GitHub `AGENTS.md`, `docs/operations/000-LATEST.md`, reports 995/996, draft PR123 and Issue124, and the canonical Google Drive `ALD1N-EXPO-CLOUDLINUX-UPDATE-RUNBOOK.md` before work.
- Current GitHub `main` **before this report**: `8bbd0ce1f19b999261775e638eab98b093b6ddd3`, where the new **manual-only** GitHub Actions workflow was just added. App code in main remains Expo SDK57.
- Immutable SDK58 draft PR123 head: `4af7b16c4f47c6aa09271ed03993f477c9be1bd8`, open/DRAFT/unmerged. Never substitute the previous head's 10/10 CI pass for this head.

## Actual B1025 engineering action
- Added `.github/workflows/b1025-m17-sdk58-eas-preview-android.yml` to GitHub **main**, in commit `8bbd0ce1f19b999261775e638eab98b093b6ddd3`.
- Manual workflow URL: https://github.com/AldinAga/ald1n-project/actions/workflows/b1025-m17-sdk58-eas-preview-android.yml
- Exact workflow source SHA256 `07a7547b24d46e3e6bcc6c31f36df121f7269da6ed4a744bfd9ecf4296c95405`; **14,183 bytes**; GitHub readback matches the Google Drive archived original byte-for-byte. GitHub source blob `3569979ffd7672aa913a0e2bdfa1977cc7325b25`.
- **Only** `workflow_dispatch` can invoke it. No `push`, `pull_request` or scheduled build. The agent cannot dispatch a workflow with the available GitHub connector. The owner must deliberately select **Run workflow on main** to authorize this single internal APK request. This manual action is an approval of one internal preview build only, not a production release.
- The workflow first re-fetches exact PR123 head/draft state and **all 11** fixed GitHub Actions CI run IDs; if any is missing, incomplete, failed or tests a different SHA: HOLD **before touching EAS**. It checks SDK58 app config, package, Firebase preview package, exclusive OTA runtime, EAS25 pin and unchanged production profile; installs exact locked JS dependencies solely in temporary GitHub CI.
- A GitHub Actions `EXPO_TOKEN` secret is required; its existence and authorization **CANNOT be verified by the GitHub read-only connector**. The manual workflow fails closed if absent. It requires authenticated EAS project ID, preview versionCode readback and no prior build for the same source/profile. There is no OAuth/credential dump to logs.
- After all gates only, a single non-interactive remote internal Android APK **build request** is dispatched, without waiting for binary completion. The workflow captures exact EAS build ID and readback into a bounded sanitized CI receipt. The build receipt is not binary or device acceptance; if dispatch response is ambiguous, **do not retry** until EAS build list is reconciled.

## Exact version/source identity for the internal build

| Field | Exact declared value | Verified boundary |
|---|---|---|
| App version | `1.0.0` | Static app config, not an actual completed EAS artifact |
| Expo | `~58.0.22` | Draft source only |
| React Native | `0.88.1` | Draft source only |
| EAS CLI | `25.0.0` | GitHub CI exact pinned CLI; host upgrade not attempted |
| Android app ID | `com.ald1n.mobile.preview` | Preview app, **not** production `com.ald1n.mobile` |
| Profile | `m17-sdk58-uat` | Internal `distribution=internal`, Android `buildType=apk` |
| Channel | `m17-sdk58-uat` | No production channel writes |
| runtimeVersion | `1.0.0-m17-sdk58-uat1` | Explicit isolated runtime, not legacy `1.0.0-build17` or v1.5 `1.5.0-native2` |
| Source commit | `4af7b16c4f47c6aa09271ed03993f477c9be1bd8` | Workflow exact checkout and CI check |
| versionCode | **NOT_ATTESTED** | Source uses `appVersionSource=remote`; never invent future remote version |
| EAS build ID | **NOT_CREATED / NOT_DISPATCHED** | Manual action not performed by this report |

The actual future dispatch command, **only inside the authenticated gated GitHub workflow**, is:

```bash
npm exec --yes --package=eas-cli@25.0.0 -- eas build --platform android --profile m17-sdk58-uat --non-interactive --no-wait --json
```

No EAS native build has been launched by this report. No production app version, versionCode, runtime, store notes, release artifact or certificate was changed. Google Play release notes are **not applicable** to an internal-only preview APK.

## Verified local workflow QA and new-head CI status
- Offline YAML parse PASS (only workflow_dispatch), **6/6** embedded Bash scripts `bash -n` PASS, **5/5** embedded Node snippets `node --check` PASS, paid EAS build invocation count exactly one, static absence of `--auto-submit`, OTA and Play commands PASS. No CloudLinux forbidden shell substitutions are used.
- Exact source CI from the previous turn showed `9/11` successes and two active; **fresh this-turn** GitHub API/job readback now shows **10/11 completed SUCCESS**, including Kotlin compile retry [41312573140](https://github.com/AldinAga/ald1n-project/actions/runs/41312573140) attempt 2 SUCCESS. The first Kotlin attempt failed while downloading a corrupt GitHub-runner NDK ZIP **before compilation**; its targeted retry passed. No source modification/redundant rerun was necessary.
- The final expensive [Android15 16KiB full-app run41312573164](https://github.com/AldinAga/ald1n-project/actions/runs/41312573164) was **still running** at latest check, after native Fabric compile and while assembling the debug APK. Do not claim new head 11/11 or build authorization before its final SUCCESS.
- [Main source run41312573250](https://github.com/AldinAga/ald1n-project/actions/runs/41312573250) SUCCESS (5/5 B1024 runtime tests, **321/321** Release25 source tests, TypeScript, Expo Doctor, validator and dependency gate).

## Outstanding blockers, planned follow-through and risk boundary
1. **Finish all new-head CI 11/11**. If the large run fails, inspect the specific failing step; no paid EAS build automatically, no broad reruns.
2. Manual GitHub Actions `Run workflow` for B1025 on **main**, requiring an authorized `EXPO_TOKEN` repository secret. If missing or authenticated EAS remote version/profile credentials are absent: HOLD and correct only that prerequisite; no duplicate charge.
3. When actual EAS build ID and **FINISHED** artifact are independently available, verify profile/channel, source commit, actual versionCode, signer/certificate and APK SHA/16KiB ELF+ZIP. Then run physical Android device Google native Sign-In UAT with permitted synthetic account, including success/cancel/session restore. Do not treat a debug emulator or mere EAS queue receipt as physical UAT.
4. Keep separate *production* SDK58 AAB runtime isolation, signing, versionCode and Google Play release gates OPEN in [Issue124](https://github.com/AldinAga/ald1n-project/issues/124). Existing accepted M16 Android 16KiB versionCode28 remains untouched and not repeated. Avoid CLI26/npm12 host upgrade during this build gate: isolated CI was previously PASS but on-host EAS25/npm10 remain pinned.

## State and formal acceptance
- **No EAS native build, no remote versionCode write, no APK artifact, no Play/OTA, no PR123 merge, no CloudLinux/DB/CMS mutation** during B1025 manual gate setup.
- New source work: manually gated workflow in main + this report; SDK58 draft PR123 unchanged from B1024.
- Formal accepted milestones remain **8/17 = 47.1% overall**, **7/15 = 46.7% CMS**, **6/6 = 100% original UI**. SDK58 M17 is NOT ACCEPTED.
