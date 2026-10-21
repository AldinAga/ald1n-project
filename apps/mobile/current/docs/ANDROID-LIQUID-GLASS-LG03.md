# Ald1n Mobile / Android Liquid Glass — LG-03

Status: **Wave 1 merged PR #45 (source only); Wave 2 candidate PR #47 remains DRAFT / NOT OTA SAFE**.

## Wave 2 native integration status (2026-10-19)

- Prior exact-head PR #46 merged as a workflow-only compatibility receipt. CI run 39327323204 PASS: pinned Android Liquid Glass v0.2.1 Kotlin **and** complete Android app debug assemble (real com.ald1n.mobile package). No artifact was distributed, and no native module reached main.
- Draft PR #47 pins react-native-android-liquid-glass@0.2.1 and actual npm SHA-512 in package-lock v3. **This dependency requires a new Android binary and new compatible runtimeVersion. It is NOT compatible with existing Build25 OTA.**
- App shell adds a noncollapsible native parent shared by Stack and the LiquidGlassAndroidView. The native glass is returned directly from the Android bottom-navigation component and refracts the scrolling Stack sibling backdrop; wrapping it inside the old bar would capture a flat surface instead.
- Five role-aware buttons render as children of the native glass, preserving labels, selected and disabled state, unread badges, orange raised Home and router.replace destinations. Tablet >=600dp remains the original rail; iOS remains the existing bottom nav.
- Native refraction is only attempted on Android API33+ after reduced-transparency preferences have loaded. Older/unknown APIs, accessibility restrictions, keyboard focus, fatal errors and degraded pipeline use an opaque fallback. Android high-contrast system preferences use native accessibility auto behavior.
- Dynamic backdrop capture is active only while the app is in the foreground, with native paused background state, downsample 0.5, disabled high-quality blur/dispersion/sensor highlights and disabled frame diagnostics.
- Physical optical quality, frame time, heat, battery, scrolling contrast, 320dp and dark/light tests are NOT certified by a successful compile.

### Mandatory release HOLD for PR #47

1. Exact candidate Node22 npm ci, TypeScript, Release25 regressions, canonical validator, Android JS export, and complete JDK17/Android36 assembleDebug.
2. Owner-authorized physical native preview build under legitimate Firebase identity, device screenshots and moving-content refraction proof, accessibility, keyboard, orientation and tablet rail.
3. Performance acceptance with genuine frame-time samples and device thermal observations, especially repeated scrolling/navigation.
4. A new versionCode and isolated runtimeVersion, exact source and EAS profile/channel, only with explicit owner production build authorization. Supply the production build command, app version, versionCode, runtimeVersion, source commit, profile/channel and Google Play release notes then.
5. **NEVER** merge a live native import into the Build25-compatible main source before the compatible binary release is safely planned. No production build, OTA, Play, iOS or database mutation in this Draft phase.



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
