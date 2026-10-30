# B991 — Owner reports B990 EAS Android AAB IN PROGRESS; conditional EAS Submit command, no Play action

**Date 2026-10-30, Europe/Belgrade.** Owner reports that one Expo EAS build is actively executing after B990 handoff. This is **OWNER-REPORTED**, not independently confirmed via authenticated EAS `build:view`, which is not available to this agent. Do not assume successful completion, a new versionCode, artifact URL or Build UUID. Never dispatch a second build, reset remote versionCode, or submit prior rejected AAB27.

## Critical submit behavior

Current checked-in `apps/mobile/current/eas.json` has `submit.production.android.track="production"` and `releaseStatus="completed"`. Expo documentation states `completed` may immediately release on the selected track. Therefore ***do not run a production submit command simply because EAS Build reports FINISHED***: actual signed AAB 16KiB checks, upload signing certificate and Play code uniqueness, physical Android/UAT and explicit Play rollout authority remain independent release gates. The owner previously attested that signing credentials were not changed; retain that historical statement but independently test the signed artifact after build.

The already-started EAS Build cannot retroactively receive the `--auto-submit` flag. Re-running `eas build --auto-submit` can consume a second native build quota and is prohibited. Once all postbuild and Play release gates PASS, EAS CLI25 accepts `eas submit` with the **exact** finished build UUID instead of `--latest` (which can target a different binary):

```bash
cd /home/icaffeco/ald1n-release-b990-20261030/apps/mobile/current &&
/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node \
 /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js \
 exec --yes --package=eas-cli@25.0.0 -- \
 eas submit --platform android --profile production \
 --id "<VERIFIED_EAS_BUILD_UUID>" --non-interactive --wait
```

This is **reference syntax only**, **NOT a user-approved execution instruction or a request to bypass canonical incoming batch**. In the actual owner environment, any production Play submit action must be initiated via a separately guarded canonical `incoming/` operator script after the postbuild signed AAB gate + physical release/UAT and explicit Play authority. If owner only wants Play Console **draft upload without rollout**, **do not use** the current `submit.production` profile; instead create an explicit verified `releaseStatus: "draft"` submission profile or manual upload process, preserving signed artifact and release approval boundaries.

## Current artifact provenance awaiting B990 receipt

- Expected Android app version `1.5.0`, runtime `1.5.0-native2`, package `com.ald1n.mobile`.
- Source SHA `08f8e09eeb74ba6b6e3264b7042b59f30e8538b3`, EAS build profile `production-v15-16kb`, channel `production`.
- Latest previously authenticated EAS remote versionCode `27`. **Actual new assigned versionCode UNKNOWN** until new authenticated build metadata.
- EAS Build UUID and signed AAB hash UNKNOWN until actual completed B990 report.
- Signed native ELF 16KiB / AAB bundle PAGE_ALIGNMENT_16K / default delivered APK zipalign gate **NOT YET RUN**; run existing `apps/mobile/current/scripts/release25/signed-16kb-aab-release-gate.mjs` against genuine new signed AAB with expected signer SHA256.
- Physical Android 16KiB/Liquid Glass UAT and Google Play acceptance **NOT ATTESTED**.
- Google Play release notes (Serbian):
  `Nova verzija Ald1n CMS aplikacije donosi unapređenu navigaciju i osvežen Android prikaz. Poboljšani su prečice na početnom ekranu, katalog, filteri i upravljanje porudžbinama. Unapređena je upotrebljivost pri unosu podataka i navigaciji uz tastaturu. Dodatne ispravke stabilnosti i pristupačnosti.`

Sources:
- Official [EAS Submit CLI flags](https://docs.expo.dev/eas/cli/) `--id`, `--profile`, `--non-interactive`, `--wait`.
- Official [automate submissions](https://docs.expo.dev/build/automate-submissions/) `--auto-submit` at build start and releaseStatus completed immediate behavior.
- Official [submit to Android](https://docs.expo.dev/submit/android/).
- [B989/B990 release handoff](B989-B990-ANDROID-V15-ONE-TIME-AAB-HANDOFF-20261030.md).

**No EAS Submit executed, no new build triggered by B991, no Google Play, OTA, CloudLinux production or SDK58 mutations.**
