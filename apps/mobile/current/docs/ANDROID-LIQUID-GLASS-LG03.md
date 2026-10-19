# Ald1n Mobile / Android Liquid Glass — LG-03

Status: **Wave 1 isolated source candidate, not an OTA or production APK release**.

## Exact source authority (2026-10-19)

- Baseline GitHub main before this wave: 1a7f9c052df414510849f96a360cadec1fa0604e.
- Expo SDK 57.0.27, RN 0.86.3, native baseline Build25.
- Current production identity remains com.ald1n.mobile; app 1.0.0; versionCode 25; runtimeVersion 1.0.0-build17; EAS production.
- Navigation source: src/components/layout/app-bottom-nav.tsx. Exactly five role-aware slots with elevated centered Home and keyboard-hide behavior.
- Canonical brand colors derive from packages/design-tokens/ald1n-operator.json: light #C45116, dark #FF8A3D. Do not adopt legacy violet doc colors.

## Wave 1 — honest JS-only material/fallback

- Responsive bottom bar has a translucent neutral-gray backing surface, edge glints and an active-selection outline.
- The material is pure React Native View layers; **it does NOT blur or refract the background**.
- For Android API 33+ use translucent fallback chrome. For Android API 32 or older, unknown API, or reduced-transparency/high-contrast input, use opaque background.
- Listen to available OS reduced-transparency signal; do not change functionality if the signal cannot be read.
- Preserve primary orange centered Home, icon legibility, role routing, disabled state, 48dp minimum tap targets, unread badges, iOS/web legacy behavior and tablet rail.
- No package, package-lock, native plugin, app.config, version, runtime or build-profile changes.
- No Android OTA has been published and this wave does not authorize one; owner device UAT is pending.

## Native refraction next wave (strictly separate)

Draft PR #32 (https://github.com/AldinAga/ald1n-project/pull/32) already includes the isolated Kotlin compatibility PoC for react-native-android-liquid-glass@0.2.1. Exact CI run 39135587915 **PASS**: :react-native-android-liquid-glass:compileDebugKotlin compiled on RN0.86.3. This is **module compile evidence only**, not production integration or full APK compatibility.

Existing Firebase config does not contain the com.ald1n.mobile.development development client; original :app:processDebugGoogleServices prevented full debug APK. Never re-label a module-only PASS as full app PASS or manufacture Firebase clients.

Native glass would require a pinned dependency/lockfile, real Expo/Gradle app compile under an approved identity, new native release with runtime isolation, Android device optical/fallback/heat/scroll QA, and a separate owner's production build authorization. Do not distribute native-only JavaScript through current Build25 OTA.

## Gate for future waves

1. Isolated exact-head Node22 TypeScript, static validator, Release25 tests and Expo Android bundle export.
2. Screen-reader/reduced-transparency policy; real low-end device tests, 320/390dp, dark/light and orientation.
3. Native variant full app compile under a legitimate Firebase identity and qualified device performance, only if approved.
4. Explicit EAS group check and owner production release process separately. Never automatic OTA/build/Play.
