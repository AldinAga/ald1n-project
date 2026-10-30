# B989 — Dedicated EAS Android 16 KiB v1.5 production-channel AAB profile

This is a **source-only build configuration**, not approval to run a production build, change signing credentials, submit Google Play, publish OTA or update the CloudLinux production checkout.

## Why the raw production build is unsafe

`apps/mobile/current/app.config.js` has a native release gate. The ordinary `eas.json` `build.production.env` intentionally does **not** supply `ALD1N_V15_RELEASE_GATE=approved`; the default version remains `1.0.0` with runtime `1.0.0-build17`. A shell-exported variable is **not** by itself guaranteed to reach the EAS remote builder. See [Expo eas.json env documentation](https://docs.expo.dev/build/eas-json/) and [Expo remote environment FAQ](https://docs.expo.dev/eas/environment-variables/faq/).

## New source-only release profile (B989)

The added `production-v15-16kb` profile:
- **extends** `production` but carries explicit `channel=production`, `environment=production`, `autoIncrement=true` and Android `buildType=app-bundle`;
- includes public `EXPO_PUBLIC_APP_ENV=production` and `EXPO_PUBLIC_API_URL=https://cms.ald1n.com/api/v1`, plus the exact gate markers `ALD1N_V15_RELEASE_GATE=approved` and `ALD1N_V15_RELEASE_PROFILE=production-v15-16kb` in **profile env** (available locally and on the EAS builder);
- resolves `version=1.5.0`, `runtimeVersion=1.5.0-native2`, package `com.ald1n.mobile`, project `d43b3866-6838-4217-a23e-3dc7f2cc76cc`.
- leaves the original `production` profile unchanged. **Do not create a persistent EAS project-level release-gate variable** to bypass this profile; avoid affecting other production builds.
- does **not** add any auto-submit flag or change the Play submit track.

The app.config.js dynamic gate remains backwards compatible with a deliberately approved historical `production` profile, but the dedicated profile is required for future B989 one-time Android16KiB release candidate builds. The separate profile marker ensures the build resolves to v1.5 even when `EAS_BUILD_PROFILE` is unavailable in local config evaluation.

## Exact future build command — not authorized until independent gates

Run **only from a clean exact SHA isolated checkout** of the eventual merged source after successful CI, with authentic Expo account and separately authorized one-time build controller:

```bash
/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package=eas-cli@25.0.0 -- eas build --platform android --profile production-v15-16kb --non-interactive --wait --json --freeze-credentials
```

Command does not auto-submit. `--freeze-credentials` prevents unauthorized update of signing credentials. The user confirmed on **2026-10-30** that existing signing keys **have not changed**; record this as **owner testimony**, **not independent cryptographic matching**. Do not request private keys or repeat historical key verification solely to pass a documentation gate. Require real signed artifact signature verification after build.

- Profile / OTA channel: `production-v15-16kb` / `production`.
- Application version / runtimeVersion: `1.5.0` / `1.5.0-native2`.
- Android package: `com.ald1n.mobile`.
- Remote Android EAS versionCode previously authenticated **27** by owner Report967; real next assigned versionCode **UNVERIFIED** until build. Require operator-confirmed highest occupied Google Play versionCode across testing/production tracks and gate that new code cannot collide. Never guess 28 or call `build:version:set` without authorization.
- **Source commit:** `TBD` until GitHub PR is verified and merged. Do not reuse B984 pinned SHA as new B989 source.
- Physical Android16KiB / Liquid Glass UAT: `NOT_ATTESTED`; may require actual new AAB installation after build and **before** Play submission/rollout.
- Postbuild gate: inspect signed AAB SHA256, JAR signing, 16KiB ELF + bundle config `PAGE_ALIGNMENT_16K`, derived split/standalone APK zipalign and signatures via existing `signed-16kb-aab-release-gate.mjs`; EAS source/build provenance and independent Play acceptance remain separate.

## Google Play release notes, ready to copy/paste (not submitted)

Nova verzija Ald1n CMS aplikacije donosi unapređenu navigaciju i osvežen Android prikaz. Poboljšani su prečice na početnom ekranu, katalog, filteri i upravljanje porudžbinama. Unapređena je upotrebljivost pri unosu podataka i navigaciji uz tastaturu. Dodatne ispravke stabilnosti i pristupačnosti.

## Validation

`node --test apps/mobile/current/scripts/release25/b989-production-v15-eas-profile.test.mjs`: tests normal Build25 isolation, dedicated profile remote/local v1.5 resolution, missing/wrong marker rejection, controlled legacy behavior and no auto-submit. CI must PASS exact PR HEAD before merge. This is **not** signed EAS Build evidence.

Production EAS, Play, OTA, CMS deployment and SDK58 remain HOLD until actual release gates.
