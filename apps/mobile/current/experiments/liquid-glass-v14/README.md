# Android v1.4 Liquid Glass native integration spike (isolated)

Upstream: https://github.com/sssajjad007/react-native-liquid-glass-android
Pinned candidate: `react-native-android-liquid-glass@0.2.1`.

Source under `LiquidGlassV14NavigationPrototype.tsx.example` is intentionally **not** imported from app routes. This avoids breaking the currently deployed Expo 57 / RN 0.86 source tree before the native dependency and lockfile are vetted. Rename to `.tsx` only in an isolated integration worktree after a version-pinned installation with lockfile.

## Necessary compatibility gate, not yet executed

- Current Expo SDK 57; React Native 0.86.3.
- Candidate explicitly documents React Native 0.85, Fabric/new architecture, compileSdk 36, minSdk 24, arm64-v8a/armeabi-v7a; Android API 33+ for AGSL.
- Inspect Gradle repository mode and JitPack dependency before any native build.
- Install in isolated worktree through npm while retaining npm lock, then run TypeScript, standard validation and one disposable Android development/preview build on configured infrastructure.
- Verify actual refraction over a detailed backdrop and fallback on older Android; measure low-end device frame time and accessibility.
- No app version, versionCode, runtimeVersion, OTA, Play, or production build changes are part of this spike.

This is an implementation prototype and compatibility investigation, **not** a passed native compile or production-ready integration.

## Actual isolated CI failure (run 39130859433)

- Module autolinking was observed: `react-native-android-liquid-glass` Gradle codegen/resources/manifest tasks executed.
- The full app debug APK stopped at `:app:processDebugGoogleServices` **before** module Kotlin compilation, because the existing tracked Google Services JSON does not contain the development client `com.ald1n.mobile.development`.
- Do **not** change app identity, reuse a different Firebase client, manufacture credentials or bypass the check to claim a usable APK.
- Next isolated CI compiles only `:react-native-android-liquid-glass:compileDebugKotlin` under actual Expo57/RN0.86 toolchain. `NATIVE_LIQUID_GLASS_MODULE_COMPILE=PASS` is *module compatibility evidence only*. `APP_DEBUG_APK=HOLD_FIREBASE_DEVELOPMENT_CLIENT_MISSING` persists until an authorized development Firebase client exists and full application QA is run.
- No version/versionCode/runtimeVersion updates, EAS, APK distribution, OTA or Play changes.
