# Report999 — B1027 CloudLinux npm PATH failure is NOT an EAS build (2026-11-01)

## Source-of-truth reconstruction
- Current verified GitHub `main`: `8fdcfe418eda348ce1d8d9e4f9dc9d9b9c3c0122` at checkpoint before this documentation-only change.
- Read current `AGENTS.md`, `docs/operations/000-LATEST.md`, Report998, B1025 workflow and canonical Google Drive `ALD1N-EXPO-CLOUDLINUX-UPDATE-RUNBOOK.md`.
- SDK58 [draft PR123](https://github.com/AldinAga/ald1n-project/pull/123) head: `4af7b16c4f47c6aa09271ed03993f477c9be1bd8`, open/draft, unmerged; exact source has **11/11 GitHub Actions completed SUCCESS**, including [Android15 full-app 16KiB emulator run41312573164](https://github.com/AldinAga/ald1n-project/actions/runs/41312573164). CI results are source/debug-emulator evidence only.
- GitHub Actions `actions/runs?event=workflow_dispatch` returned **0** runs at current inspection. This does not constitute an authenticated inventory of all possible Expo EAS builds outside GitHub, but no B1025 manual build was launched.

## Owner SSH terminal observation / root cause
Owner attempted the following command both from SSH `~` and from the Git repo root:

```text
npm exec --yes --package=eas-cli@25.0.0 -- eas build --platform android --profile m17-sdk58-uat --non-interactive --no-wait --json
bash: npm: command not found
```

- This is a **shell PATH/command-resolution failure before npm or EAS executes**, not a failed SDK58 build or source CI.
- CloudLinux on this project deliberately uses an audited Node22/npm10 path rather than a globally available `npm` binary; the canonical runbook identifies `/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node` and `/opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js` as the explicitly audited host launch path **for permitted host operations only**. The missing default PATH command is expected; this was previously known CloudLinux behavior, not a reason to install npm globally.
- Crucially, the B1025 `npm exec ... eas build ...` command was defined **inside GitHub Actions on an isolated runner after exact source, CI, profile, Expo credential, remote versionCode and duplicate gates**; it was never an authorized CloudLinux SSH operator command. Running it manually bypasses those gates even if npm is made available. Do not run that command on the hosting machine, from either repository root or mobile directory, and do not alter global npm/npm12/EAS26, symlinks, Node selector, production checkout, backend, database or signing state to fix it.
- The shown SSH attempt performed **zero npm, EAS, APK, AAB, OTA, Play or source mutation** because shell command resolution failed. No new versionCode, EAS build ID, signer or binary is established.

## Correct single next action, and only one
Use the preexisting [B1025 manually gated workflow](https://github.com/AldinAga/ald1n-project/actions/workflows/b1025-m17-sdk58-eas-preview-android.yml) in GitHub web UI: select `Run workflow` **on branch main exactly once**. GitHub must have the repository `EXPO_TOKEN` secret; do **not** send tokens in chat or logs. The workflow is `workflow_dispatch` only; it revalidates 11/11 and exact draft commit, then authenticates Expo project, reads remote preview versionCode and checks duplicates before dispatching **one** internal preview Android APK. If any gate fails or the dispatch outcome is ambiguous, do not click a second time before reconciling actual remote build receipts.

**Fixed candidate identity (source configuration only):** Expo `~58.0.22`, React Native `0.88.1`, app version `1.0.0`, Android `com.ald1n.mobile.preview`, EAS CLI `25.0.0`, profile/channel `m17-sdk58-uat`, runtimeVersion `1.0.0-m17-sdk58-uat1`, source `4af7b16c4f47c6aa09271ed03993f477c9be1bd8`. Actual remote `versionCode` remains **UNKNOWN** until an authenticated EAS readback. Exact build command exists in the guarded GitHub runner only, not an SSH instruction. Google Play release notes: **not applicable**, internal APK only.

## Production / accepted milestone preservation
- Existing production SDK57 and M16 owner-accepted Google Play Android `versionCode=28` remain untouched.
- SDK58 M17 remains **DRAFT / NOT ACCEPTED**, pending actual EAS build identity, signed artifact verification, physical Android 16KiB page-size device, Google native sign-in and owner UAT. No production AAB, OTA, Play submission, CloudLinux mutation, CMS/DB changes or PR merge authorized or observed.
- Formal accepted milestones unchanged: **8/17 (47.1%) project, 7/15 (46.7%) CMS, initial UI 6/6 (100%)**.
- This is a known user/workflow-boundary confusion. The remedy is a clearer **UI-only handoff**, not another CloudLinux toolchain recovery or another native CI run.
