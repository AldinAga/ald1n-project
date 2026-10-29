# B985 — Report965 owner acceptance and isolated EAS metadata QA handoff (2026-10-29)

## Owner artifact receipt: PASS (reported)

Authentic owner [Report965](965-B984-ISOLATED-ANDROID-RELEASE-20261029-224744.md), UTC 2026-10-29T21:47:44+00:00, states:
- `PASS_ISOLATED_SOURCE_ONLY`, Exit code 0; fresh isolated checkout `/home/icaffeco/ald1n-release-b984-20261029`.
- Exact pinned source SHA `8049f22d208bac1ee47fd4133bb8e1a737cfe457`.
- B984 operator script SHA-256 `a64f29feb5123e0d7c0787ede7f4ebc0bf95f6cbb98897b34c1bfab3708a6c3d` matches the corresponding delivered script byte hash inspected in the authoring environment.
- Production application/source unchanged; no EAS/OTA/Play; Git fetch/reset on production NO.
- The 13-line report is intentionally compact and does not contain full clone terminal output; PASS is a script-enforced source checkout assertion, not a signed AAB attestation.
- Report965 archived unaltered to GitHub main in commit `0457398b2c30e5ee3450cf534b0d386ec09f9469` and Google Drive document `1we7G4b3BR4eg_irun21H_mQ_Pjb9LBsMXNr8E852jeQ`.

## Fresh source comparison

GitHub main before B985 report at `0457398b2c30e5ee3450cf534b0d386ec09f9469` differs from pinned clone `8049f22d208bac1ee47fd4133bb8e1a737cfe457` **only in two docs/operations report files**. No mobile application/source changes between these SHAs. Reverify current main and actual build source again at any build execution.

## B985 safe next action: EAS read-only account metadata (NOT BUILD)

Prepared and locally tested `B985-ISOLATED-EAS-RELEASE-READONLY-QA.sh`; complete-file SHA256: `9c5a21520f198de59ec61b2a6dacc71f1bb34f287e18276fc89b83f60a02d7f5`; script's embedded **normalized** integrity SHA differs intentionally and is verified via `--selftest`. Local bash syntax PASS; script integrity PASS; deliberate single-byte/source alteration is blocked `RC=2, HOLD_B985_SCRIPT_SHA_MISMATCH`. **No CloudLinux run has yet taken place**.

Owner must copy EXACT file to `/home/icaffeco/ald1n-project/incoming/B985-ISOLATED-EAS-RELEASE-READONLY-QA.sh`. Only intended invocation: `bash /home/icaffeco/ald1n-project/incoming/B985-ISOLATED-EAS-RELEASE-READONLY-QA.sh --run`. The script checks canonical path, self-integrity, prior report numbering (>=965), B984 isolated detached source SHA, clean source status, EAS25 + remote auto-increment + v1.5.0-native2 source config, authenticated EAS project, remote versionCode, sanitized prior Android production build list. It **does not call** EAS build/credentials/submit/OTA, does not print tokens or keystore data, does not edit app source, and only writes an auto-numbered canonical operations report. Read-only EAS command failures return HOLD, not PASS.

Google Drive complete source archives: B985 script `14milHX4QCdu7GBfSThPRTk-v5LG12PLT3_zDzU2YEGQ`, B984 script `1fDfG0kwm2yn2bcJYyVfCSfzh37vzP14l4JszUVe2Aio`; complete content was inserted and read back, with only the Docs container's required final newline added. The runnable Bash file is supplied separately, not executed from Google Docs.

## Production Android release gates after B985

- Baseline EAS remote Android versionCode `27`, last authenticated B983; next real code must be unique on Google Play and >27; `autoIncrement=true` does not prove eventual numeric value until the build.
- Source/CI 16KiB 7/7 PASS on merged PR116; source-only/disposable artifacts are not Play signed binaries.
- Upload signing certificate SHA256 must be compared to Google Play Console App Integrity upload certificate, without downloading/private-key disclosure; not attested by B985.
- Physical 16KiB device UAT, release authorization, real production signed AAB build ID, package/version/runtime/source provenance, post-build signed AAB ELF/page alignment and derived APK audit are still HOLD.
- `ALD1N_V15_RELEASE_GATE=approved` is reserved for a separately authorized production build controller. No EAS production build, Play, OTA, SDK58, CloudLinux deployment or migration performed in this batch.

B985 is a **read-only metadata stage**. Avoid claiming completed production AAB or Play release.
