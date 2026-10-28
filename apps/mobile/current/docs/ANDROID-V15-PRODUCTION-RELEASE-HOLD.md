# Android v1.5.0 native release candidate — HOLD

Source parent: PR #76 head `b5e887c5070d4032ec8cd3d64e3e82d0e4d41380` based on PR #75 exact Build26 integration. New source commit must be taken from latest exact candidate HEAD, never use an older SHA for production builds.

- App version: **1.5.0**; Android package: **com.ald1n.mobile**; Expo project ID: `d43b3866-6838-4217-a23e-3dc7f2cc76cc`.
- Production runtime when release gate approved: **1.5.0-native2**, isolated from Build25's **1.0.0-build17**.
- EAS build profile/channel: **production/production**; artifact: Android **AAB**.
- Android `versionCode`: **UNVERIFIED** (remote EAS appVersionSource=remote, autoIncrement=true); verify against installed Build26/testing and Play Store latest before release. Never claim a specific code without querying EAS.
- Build command after all gates: `/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package=eas-cli@25.0.0 -- eas build --platform android --profile production --non-interactive --wait --json`. This command MUST be run from a checked, isolated exact-SHA checkout under a production-authorized release controller (no auto-submit until Play gate). It spends Android quota: DO NOT RUN while HOLD.
- Runtime activation `ALD1N_V15_RELEASE_GATE=approved` is an implementation switch, **NOT release authorization**. Never set this environment variable before every external gate below is attested.

**16KB Play rejection correction (2026-10-21):** signed AAB27 used `1.5.0-native1` and was rejected due to upstream nativegauss 4KB ELF. The new upstream 16KB-aligned candidate is `1.5.0-native2` and requires fresh build, post-build ELF/zip check and separate Play release decision. This is NOT the already signed AAB27.

## Blocking gates

1. Backend warranty-expiry preference migration and strict customer owner scope from draft PR #67: separate MariaDB 10.11 integration test and production backup/rehearsal/explicit deployment; currently **HOLD**. UI alone is insufficient for source safety.
2. Final v1.5 release source: exact HEAD, full Node22 TypeScript/validator/Release25 + Android JS export + native compatibility gates **PASS** on candidate, no unreviewed diff from PR #75/#76.
3. Expo authenticated read-only account/project/remote versionCode verification; production EAS CLI25 profile and signing provenance; Play upload signing/track; **HOLD** pending host evidence.
4. Physical native Liquid Glass Android UAT (accessibility, API24–32 solid fallback, Android 13+, 48dp targets, order/cart/focus routes, keyboard fix) **HOLD**.
5. No production checkout pull/reset/clean: host current `/home/icaffeco/ald1n-project` has historical tracked/untracked CMS drift. Use **separate isolated checkout** and report to canonical `docs/operations`.
6. Exact Google Play release notes for review (Serbian, copy/paste):
   `Nova verzija Ald1n CMS aplikacije donosi unapređenu navigaciju i osvežen Android prikaz. Poboljšani su prečice na početnom ekranu, katalog, filteri i upravljanje porudžbinama. Unapređena je upotrebljivost pri unosu podataka i navigaciji uz tastaturu. Dodatne ispravke stabilnosti i pristupačnosti.`

Do not launch EAS Build, OTA, auto-submit, deploy or migration before independent gate evidence. Producing source code or source-only CI does not count as physical Android UAT. No secrets in reports.
