# Ald1n LG-03 — Google Play Testing AAB

Purpose: build **one** Android App Bundle with EAS, then **owner manually** uploads it to the existing Play Console app's testing track (internal/closed/open selected by owner). This is NOT a Google Play production-track rollout.

## Exact candidate identity

- Source: Draft PR #47, to be pinned to the exact dispatched commit by GitHub Actions. Do not use the dirty/stale CloudLinux production checkout.
- Android package: `com.ald1n.mobile` (same existing Play application, not `com.ald1n.mobile.preview`).
- App version: `1.0.0`, unless explicitly changed in a separate gate.
- Expected versionCode: **EAS remote-assigned** because `cli.appVersionSource=remote` and `autoIncrement=true`; the number is NOT known until EAS build metadata is returned. Must be unique and greater than previous Play builds (25 is the documented baseline).
- EAS profile: `lg03-play-testing`.
- Channel: `lg03-play-testing` — never `production`.
- Isolated runtime: `1.0.0-lg03-play-testing1` — NOT Build25's `1.0.0-build17`.
- Artifact: **.aab**, `distribution=store`, `android.buildType=app-bundle`.
- Firebase config: existing registered production package entry from tracked `google-services.json`.
- Native feature: `react-native-android-liquid-glass@0.2.1` (exact-lock-pinned).
- Expo SDK 57 and React Native 0.86.3.
- Expo EAS CLI: `eas-cli@25.0.0`.

The dedicated EAS build does **not** auto-submit to Play Console. The owner handles testing-track upload. A shared app package means a tester's install may replace their existing Play production app; use a device/account appropriate for testing.

## Build command executed from the source of PR #47

From `apps/mobile/current` at the exact reviewed source:

```bash
EAS_BUILD_PROFILE=lg03-play-testing \
EXPO_PUBLIC_APP_ENV=production \
ALD1N_LG03_PLAY_TESTING=1 \
npm exec --yes --package eas-cli@25.0.0 -- eas build \
  --platform android \
  --profile lg03-play-testing \
  --non-interactive \
  --no-wait \
  --freeze-credentials \
  --json
```

The owner asked not to perform manual preliminary EAS account/signing checks. Expo EAS must still authenticate and use existing credentials. The command freezes credentials to avoid generating/changing signing identity silently. If Expo cannot authenticate or finds missing signing keys, report HOLD, not a successful build.

The GitHub Actions one-shot workflow, when authorized by the user and if `EXPO_TOKEN` is already configured, may dispatch the **same command** from the exact PR branch. Do not insert placeholder tokens or commit a secret.

## Play Console — copy/paste release notes (testing track)

<sr-RS>
Test verzija — Android Liquid Glass

• Novi staklasti izgled donje navigacije sa efektom zamućenja i refrakcije na podržanim Android uređajima.
• Poboljšan prikaz aktivne kartice u svetloj i tamnoj temi.
• Sačuvan brz pristup Početnoj, Katalogu, Porudžbinama, Obaveštenjima i Nalogu.
• Prilagođen prikaz za starije uređaje i podešavanja pristupačnosti.

Ova verzija je namenjena testiranju. Molimo prijavite nepravilnosti u prikazu ili radu navigacije.
</sr-RS>

## Release hold and follow-up

- No production Google Play track submission, no EAS Update/republish, no publishing to production channel.
- Actual `versionCode`, signing key reuse, EAS build ID/status, artifact URI and artifact digest must be read from actual build result when available. Do NOT guess them.
- User-visible physical acceptance still HOLD until tests for scrolling/refraction, 5 tabs, role-specific destinations, Google login, dark/light, reduced transparency, performance, heat and crashes. Do not conclude production-ready on a CI or EAS build PASS alone.
- Play app signing/upload certificate is controlled by Expo/Play; do not generate a replacement if the existing one is not available.
