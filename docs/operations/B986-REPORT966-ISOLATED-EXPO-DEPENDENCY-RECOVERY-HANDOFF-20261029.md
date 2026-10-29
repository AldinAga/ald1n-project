# B986 — Report966 root-cause recovery: isolated Expo dependencies before EAS remote reads (2026-10-29)

## Original owner evidence and gate

Owner-supplied original [Report966](966-B985-ISOLATED-EAS-RELEASE-READONLY-20261029-230001.md), UTC `2026-10-29T22:00:01Z`, B985 script full SHA256 `9c5a21520f198de59ec61b2a6dacc71f1bb34f287e18276fc89b83f60a02d7f5`, source `8049f22d208bac1ee47fd4133bb8e1a737cfe457`, isolated root `/home/icaffeco/ald1n-release-b984-20261029`.
- **PASS** source SHA and `eas.json`/app.config.js CLI25 remote-autoIncrement v1.5.0-native2 contract, recorded file hashes in Report966.
- `HOLD_B985_EAS_READONLY_PREFLIGHT`, exit code **2**, occurred at `eas project:info`; no authenticated 25.0.0 remote code/list evidence.
- Report966 does **not** include stderr; exact CLI failure is **UNDETERMINED**.
- No app/CMS source, signing, EAS build, OTA, Play mutation in Report966. Do not re-run B984.

## Existing canonical rule explaining likely failure

Fresh [GitHub AGENTS.md](../../AGENTS.md) explicitly documents **2026-10-21 Report784**: a fresh isolated Expo clone can pass `eas whoami` while `eas project:info` fails to resolve `expo-router` config plugin until required dependencies have been installed using locked `npm ci`. B985 did **not** install dependencies before project:info. This is the likely repeated blocker but **not proven** absent original raw B985 stderr. Do not claim auth failure or production crash.

## B986 single follow-up operator batch prepared (not run)

Script: `B986-ISOLATED-EXPO-DEPS-EAS-READONLY-RECOVERY.sh` to be delivered byte-exact in canonical `/home/icaffeco/ald1n-project/incoming/`.
- **Exact file SHA256:** `866f2e2527cb614ca2352feb0717b1dfd36be820cb1eab6feddb18ba610358dc`; embedded normalized self-integrity SHA `e89dd0a8a9704b101382430bb7783348377621d6416ff792884729edcd24e224`.
- Local verification: Bash syntax PASS; `--selftest` PASS; tampered script RC2 `HOLD_B986_SCRIPT_SHA_MISMATCH`; unrecognized invocation RC2; static no process substitution, `/dev/fd`, or forbidden Bash redirects PASS. These are **authoring tests only**, not CloudLinux execution.
- Preflight enforces unique numbered operations report with preceding `>=966`, exact isolated source `8049f22d...`, clean tracked source, and immutable Git blob pins for `package.json` `71cc5eca73ca9a4f67e7af3c66e5acae7ba6deee` and `package-lock.json` `2c7d942a4de30f81dd83d84da1a0d7a19a9fae4e`. GitHub verified main vs pinned source changes since B984 are only `docs/operations`, no Android source drift.
- If Expo config dependencies already resolvable, skips install. Otherwise requires >5GiB and >=50k free inodes, executes `npm ci --ignore-scripts --no-audit --no-fund --no-progress --prefer-offline` using canonical Node22/npm10 **only in the isolated** `apps/mobile/current`. No install in production, no `expo install --fix`, global CLI update, git pull/reset/clean.
- Revalidates clean tracked source; performs pinned EAS25 authenticated `whoami`, `project:info`, `build:version:get`, `build:list` from isolated mobile root. Parses only allowlisted project name/ID, remote versionCode and sanitized build IDs/statuses/source commits; captures sensitive stdout/stderr in external scratch and emits only error classifications. No token/key/cert material in report.
- Host auto-numbered `docs/operations` report uses genuine return codes and no secrets; scratch files removed. Successful **metadata-only PASS** does NOT authorize EAS native build.
- **One operator invocation** after uploading file to canonical incoming: `bash /home/icaffeco/ald1n-project/incoming/B986-ISOLATED-EXPO-DEPS-EAS-READONLY-RECOVERY.sh --run`.
- **CloudLinux B986 run status: NOT RUN.** Actual new report number unknown until host execution.

## Deliverable archives

- Authentic Report966 preserved in GitHub main and Drive readback [owner report](https://docs.google.com/document/d/14jMa3BLHd5YgDkD_svUwbP4cmopsjr_PJIORTDwdqRE/edit).
- Full B986 runnable source preserved byte-for-byte in user's ChatGPT Library and a verified exact-content (plus required Docs final newline) [Drive source archive](https://docs.google.com/document/d/19jrVS02WLR8-If8fFNDsHXiox324pwxB34m7AlUtQmA/edit), which is **not** a substitute for the byte-exact Bash download.

## Release gates remain HOLD

Last authenticated remote Android versionCode `27` (B983). No new AAB build/versionCode or real signed production cert proven. Independent Google Play occupied code/signing certificate fingerprint comparison, physical Android 16KB UAT, source/provenance/signed binary ELF/zip package validation, and explicit release authorization remain missing. No EAS build, OTA, Google Play, CloudLinux production deployment or SDK58 upgrade authorized/executed.
