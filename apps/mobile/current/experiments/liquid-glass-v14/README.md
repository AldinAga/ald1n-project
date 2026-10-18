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
