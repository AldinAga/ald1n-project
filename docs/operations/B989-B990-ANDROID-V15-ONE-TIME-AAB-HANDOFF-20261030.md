# B989 / B990 — 3/3 Android release source CI PASS; gated one-time AAB handoff (2026-10-30)

## B989 GitHub PR118 exact-head source merge — PASS

- [PR #118](https://github.com/AldinAga/ald1n-project/pull/118) **MERGED** into `main` by exact expected HEAD `52edf56227bb4ae7456a8af07ad75aec33853b17`. Merge commit **`08f8e09eeb74ba6b6e3264b7042b59f30e8538b3`**. GitHub compared PR head to merge commit and found **zero changed paths**; no unreviewed source changes.
- Exact-HEAD GitHub CI: v1.5 release candidate `41021655372` **SUCCESS**, MariaDB `41021655371` **SUCCESS**, native 16KiB `41021655370` **SUCCESS**. Earlier CI failure at the first PR head came from evaluating app config gate at module import time instead of **per invocation**; fixed at the PR exact head and 8 regression tests were added, final CI 3/3 PASS. No EAS/Play/OTA action from CI.
- The ordinary `production` profile retains historical Build25 version `1.0.0` / runtime `1.0.0-build17`; the new dedicated **`production-v15-16kb`** profile includes the native v1.5 release markers in profile env (local and remote), profile `production-v15-16kb`, OTA channel `production`, Android AAB, `autoIncrement=true`, app version `1.5.0`, runtime `1.5.0-native2`, Android package `com.ald1n.mobile`.
- Source is merged but **no new signed production AAB** has been built; historical EAS remote versionCode **27** last authenticated by owner Report967, previously rejected AAB27 remains invalid.

## B990 prepared one-time operator controller (not executed)

- Runnable `B990-M16-ONE-TIME-AAB-CONTROLLED.sh`; full byte SHA256 **`5e2e123ddc9c968d662641b3756dbf6c36ed88e741f9b4ad7791cfc63d4ace4c`**, embedded normalized self SHA `daea54d588adea48994cbbfd3f8af5b3d2ce2fe3b14581898b3c837246c29eab`.
- Preserved full script on [GitHub source archive](B990-M16-ONE-TIME-AAB-CONTROLLED.sh) and [Google Drive native source archive](https://docs.google.com/document/d/1_F3yf_3E3GcQYHj7iVz_pMkoEo6U9v1hCdJV9ge1HVs/edit). Imported Drive content vs GitHub content verified exact after excluding Docs' single additional final newline (17,739 script bytes).
- Local `bash -n` **PASS**; `--selftest` self-integrity **PASS**; tampered source **RC2/HOLD**, wrong invocation **RC2/HOLD**, path outside canonical incoming **RC2/HOLD**, static banned shell constructs (process substitution and `/dev/fd`) **none**. Real CloudLinux B990 execution **NOT RUN**.
- Owner must download the exact byte file to canonical `/home/icaffeco/ald1n-project/incoming/B990-M16-ONE-TIME-AAB-CONTROLLED.sh`, then use **one SSH command**:

```bash
bash /home/icaffeco/ald1n-project/incoming/B990-M16-ONE-TIME-AAB-CONTROLLED.sh --run
```

- The controller does **not** touch old dirty production Git source. It creates a separate fresh immutable exact-`08f8...` detached checkout at `/home/icaffeco/ald1n-release-b990-20261030`. It requires origin/main ancestry and allows only subsequent AGENTS/docs/operations changes; any new application code causes HOLD, never stale-source build.
- Installs locked Node22/npm10 dependencies only in isolated release checkout, verifies dynamic v1.5 config, EAS CLI25 account/project, reads current remote Android versionCode. Prompts the owner for the **highest Google Play Console occupied versionCode** across testing and production; the number is **OWNER_ATTESTED**, not an authenticated Play API value. If next remote code would collide, HOLD; never auto-adjust `versionCode` or pretend remote 28 is guaranteed.
- Requires human TTY typed authorization `ALD1N_M16_BUILD_ONCE 08f8e09eeb74` before any quota-spending action. Re-reads remote versionCode and checks authenticated EAS build history for same-source build on dedicated profile; persists external one-shot dispatch intent before build. If EAS outcome is ambiguous, no automatic retry.
- After typed authorization alone, runs one **pinned EAS CLI25 build** from isolated app root:
```bash
/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package=eas-cli@25.0.0 -- eas build --platform android --profile production-v15-16kb --non-interactive --wait --json --freeze-credentials
```
- EAS build is NOT run by the agent; no auto-submit. If genuine build completes, controller checks authoritative EAS `build:view` UUID, source SHA, versionName, assigned versionCode and FINISHED status, and saves auto-numbered canonical `docs/operations` report. Any failure remains HOLD with no retry.

## Owner authorization and remaining Play/physical binary gates

- On 2026-10-30 owner explicitly stated Android signing keys have **not changed** and asked to skip repeating that historical check. This is testimony, **not independent certificate SHA verification**. Controller uses `--freeze-credentials`; post-build signed AAB signature verification and continuity remain mandatory before Play.
- Physical 16KiB Android/Liquid Glass UAT and actual signed AAB 16KiB ELF / Bundletool `PAGE_ALIGNMENT_16K` / signed APK zipalign audit must occur **after** artifact availability, and **before** any Play submit/rollout.
- No Play highest occupied versionCode value has been given yet; the **operator must provide** it in the script prompt from the Play Console. Do NOT assume 27/28 is Play-eligible.
- **No EAS native build, Google Play upload, OTA, SDK58 or CloudLinux app/CMS production deployment was performed by B989/B990 preparation.**

Future production metadata after real signed build must include: exact build command, `1.5.0`, **actual assigned** `versionCode` (not guessed), `1.5.0-native2`, exact source commit `08f8e09eeb74ba6b6e3264b7042b59f30e8538b3`, profile `production-v15-16kb`, channel `production`, and Serbian Google Play copy/paste notes:

> Nova verzija Ald1n CMS aplikacije donosi unapređenu navigaciju i osvežen Android prikaz. Poboljšani su prečice na početnom ekranu, katalog, filteri i upravljanje porudžbinama. Unapređena je upotrebljivost pri unosu podataka i navigaciji uz tastaturu. Dodatne ispravke stabilnosti i pristupačnosti.
