# B979 — first production Android 16 KiB AAB release authority preflight (2026-10-29)

## Outcome: HOLD — NO EAS BUILD EXECUTED

Verified latest main source `a4dc10d8838cb3febd3d69903c8360a7785c255b` (B978 docs after merge PR116 `814256a7e3ed5779ea8bb5b1e1c36f75111bcbbf`). Android 16KB GitHub PR source CI 7/7 PASS; source-only tests and disposable, locally debug-signed APK/AAB are **not** production signed AAB attestation.

Current main `apps/mobile/current/app.config.js`: gated app version `1.5.0` and runtimeVersion `1.5.0-native2` when EAS_BUILD_PROFILE=production AND ALD1N_V15_RELEASE_GATE=approved; default 1.0.0 / 1.0.0-build17. Android package `com.ald1n.mobile`, Expo project `d43b3866-6838-4217-a23e-3dc7f2cc76cc`. `eas.json`: EAS CLI `25.0.0`, remote appVersionSource, production Android app-bundle, autoIncrement true, channel production, production env API cms.ald1n.com/api/v1. Expected new EAS versionCode **UNVERIFIED**; must be > rejected AAB27 and above latest Google Play before release. Existing signed-16kb gate requires AAB SHA, signer cert fingerprint, actual remote build ID/source SHA, checked versionCode, checked 16KiB ELF and bundletool default delivered APKs. The prior rejected AAB27 is not eligible.

No connected Expo/EAS account read/build action is currently available to fetch authenticated remote versionCode and signing provenance; no authenticated production EAS build was started. No owner-supplied external physical 16KB Android UAT acceptance or Play signing proof is in evidence. Canonical release document remains HOLD on such gates. Production release environment switch `ALD1N_V15_RELEASE_GATE=approved` is NOT itself authorization to bypass them.

After independent gates are attested, canonical EAS CLI25 command from checked isolated exact-SHA checkout: `/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package=eas-cli@25.0.0 -- eas build --platform android --profile production --non-interactive --wait --json`. Run ONLY from authorized release controller with explicitly approved gate and environment; no auto-submit. Exact latest main source SHA at execution must be recorded anew; versionCode from authenticated remote EAS, never guessed.

Google Play release notes for later review (not submitted): Nova verzija Ald1n CMS aplikacije donosi unapređenu navigaciju i osvežen Android prikaz. Poboljšani su prečice na početnom ekranu, katalog, filteri i upravljanje porudžbinama. Unapređena je upotrebljivost pri unosu podataka i navigaciji uz tastaturu. Dodatne ispravke stabilnosti i pristupačnosti.

No EAS, OTA, Play upload, SDK58, CloudLinux deployment or production mutations. Next required: authenticated Expo/EAS remote versionCode + signing credentials provenance and independent physical/UAT release authorization; then one controlled build and signed binary gate. Preserve rollback and Build25 OTA isolation.
