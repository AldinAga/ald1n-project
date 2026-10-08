# Batch528R3F - Expo Android API33 splash resource correction

## Exact evidence

- Pinned main: `b5b942e645d28d3ed5f248eaae4180ebe9a54ee6`.
- Audit parent: `8a1b5449fe7894db46d1d07ef694d0e672787ef0`, Batch528R3E.
- Previous Report561 SHA-256: `c7084e7bb81a982814559c54e1073fa7f2673a26934b1aab6ccde48fcda5dbd1`.
- Previous Actions run `37744793824`, job `113203613351`, FAILED in early `lint-release`.
- Previous artifact `11536925629`, SHA-256 `c38d80dba1552edaa3c852cbc743a276601f48355f8c69895db87738fcb874fd`.
- Exact Android Lint: `:app:lintRelease FAILED`; `Lint found 1 error and 34 warnings`; `android:windowSplashScreenBehavior requires API level 33 (current min is 24) [NewApi]` in generated `res/values/styles.xml` line 13.
- Previous Worklets and Reanimated exception tasks were confirmed SKIPPED; this is a *real app Lint finding*, not a further dependency KaModule crash. The earlier full release/R8 was PASS in R3D; R3E properly aborted before another expensive R8 run.

## Root cause and correct resource semantics

Expo SDK57 `expo-splash-screen`'s `withAndroidSplashStyles.ts` appends `android:windowSplashScreenBehavior=icon_preferred` into the unqualified `Theme.App.SplashScreen` under `res/values/styles.xml`. Android introduced that framework attribute at API 33, but this app supports Android from API 24. Standard Android resource qualifiers require such new framework style attributes under `res/values-v33/` rather than in unqualified resources.

Add a narrowly-scoped Expo Android config plugin registered **before** `expo-splash-screen` in `app.config.js` (Expo `withMod` execution is reverse registration order). It takes the generated splash style as its source of truth, creates an identical API33-qualified `res/values-v33/ald1n_splash_api33.xml`, and removes only the API33-only item from the base `res/values/styles.xml`. The matching style parent and every other item are preserved on API 33+, while older API versions do not see the unsupported attribute. It is idempotent on subsequent prebuilds and fails closed on incompatible upstream changes, missing expected style elements or conflicting versioned resources. It performs no writes during Expo introspection.

A native resource verification is required **immediately after clean Expo prebuild**, before Gradle and C++ compilation. Existing `:app:lintRelease` remains fail-closed and early. No NewApi suppression, baseline, app minSdk bump, SDK/AGP/Gradle/Expo dependency change, ABI reduction, signing changes, or CI failure bypass are allowed. Confirm splash visually on devices at API 24-32 and 33+ before authorizing production.

## Audited source change and release boundary

This correction intentionally adds one **native resource semantics change** in the isolated audit branch; unlike R3E it is not a CI-only change. It must be included in any later EAS release source if certified. Android app version, runtime, package identity, Kotlin Restore fix, four ABI set, R8 behavior and dependency versions are unchanged.

Build25 remains unauthorized. No EAS build/submit, OTA, Google Play, production signing key, CMS/DB change or main push. Next stage depends on CI evidence at the exact new audit commit and remaining open audit findings. GitHub Actions Node20 and Gradle deprecation warnings are separate maintenance, not the trigger of this failure.
