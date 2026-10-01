# Batch 497 — Android Native Modernization — GitHub Actions PASS

## Status

- RESULT: PASS
- DATE: 2026-10-01
- SOURCE_BASE_MAIN: 9eb530e19be32366bc7ebc2bfdbe8ffa01381a0f
- AUDIT_BRANCH: audit/android-native-497
- AUDIT_COMMITTED_SOURCE: dc4591eafbe9bd83290fc7f01632fc0f92b60042
- FINAL_AUDIT_HEAD: 3cf506204375c4f9ca7e345cac5e34abfd4de7ef
- GITHUB_ACTIONS_RUN_ID: 36858949597
- GITHUB_ACTIONS_RUN_NUMBER: 10
- GITHUB_ACTIONS_RESULT: success
- EAS_BUILD_STARTED: NO
- GOOGLE_PLAY_SUBMIT: NO
- PRODUCTION_RUNTIME_CHANGED: NO
- DATABASE_CHANGED: NO

## Scope

Batch 497 modernizes the Android native configuration and adaptive Mobile layout without changing the Laravel production runtime or business data.

Validated source changes:

1. apps/mobile/current/app.config.js
2. apps/mobile/current/plugins/with-android-native-modernization.js
3. apps/mobile/current/scripts/validate-project.mjs
4. apps/mobile/current/src/app/(app)/(tabs)/catalog.tsx
5. apps/mobile/current/src/app/(app)/(tabs)/notifications.tsx
6. apps/mobile/current/src/app/(app)/(tabs)/orders.tsx
7. apps/mobile/current/src/app/(app)/_layout.tsx
8. apps/mobile/current/src/app/(app)/admin/commissions/index.tsx
9. apps/mobile/current/src/app/(app)/after-sales/index.tsx
10. apps/mobile/current/src/app/(app)/assigned-orders/index.tsx
11. apps/mobile/current/src/app/(app)/commissions/index.tsx
12. apps/mobile/current/src/app/(app)/warranties/index.tsx
13. apps/mobile/current/src/app/(auth)/_layout.tsx
14. apps/mobile/current/src/components/layout/app-bottom-nav.tsx
15. apps/mobile/current/src/components/layout/screen.tsx

## Source changes

- Removed the forced portrait orientation lock.
- Added an idempotent Expo config plugin for Android native modernization.
- Switched release ProGuard defaults to proguard-android-optimize.txt.
- Enabled android.r8.optimizedResourceShrinking=true.
- Added adaptive shell behavior using navigation rail on wider viewports.
- Made the navigation rail vertically scrollable for low-height landscape and keyboard scenarios.
- Added lateral safe-area handling to the shared Screen component and the remaining standalone operational screens.
- Added responsive catalog columns and bounded content width.
- Updated the Mobile project validator so the adaptive navigation contract is validated structurally rather than by a stale literal <AppBottomNav /> check.
- Product Variants remain decommissioned.

## Final GitHub Actions gates

Final run:
https://github.com/AldinAga/ald1n-project/actions/runs/36858949597

All final run steps completed successfully.

### Source integrity

- COMMITTED_SOURCE_497=PASS
- Exact verified blob identities were checked for all 15 source files.
- OpenAPI 3-copy parity: PASS
  - packages/api-contract/openapi.yaml
  - apps/mobile/current/docs/openapi.yaml
  - apps/cms/current/docs/openapi.yaml

### CMS static gate

GitHub checkout initially reported exactly two missing runtime/ignored placeholders:

- apps/cms/current/.env.testing.mysql.example
- apps/cms/current/storage/framework/cache/data/.gitignore

No other CMS static failures were present.

The CI runner created those two placeholders only inside the ephemeral runner and reran the same static check:

- Ukupno: 983
- neuspešno: 0
- CMS_STATIC_CI_983_983=PASS

This is a CI static result, not a fresh production-server runtime check. No CMS source file was changed by Batch 497.

### Mobile gates

- npm ci: PASS
- Expo compatibility detector: executed
- Mobile TypeScript typecheck: PASS
- Mobile project validator: PASS, 0 FAIL
- Expo Android prebuild: PASS
- Generated native contract: PASS

The Expo compatibility detector returned RC=1 because newer SDK57 patch versions are now recommended. This was recorded as:

- EXPO_COMPATIBILITY_DRIFT=DETECTED_NONBLOCKING_FOR_NATIVE_BASELINE_AUDIT

No dependency upgrade was performed in Batch 497. The validated baseline remains the Build21 SDK57 dependency set.

## Native runtime certification

Actual GitHub-hosted Android/Gradle runtime was executed.

- Java: 17.0.20.1
- Gradle: 9.3.1
- Android Gradle Plugin: 8.12.0
- compileSdk: 36
- minSdk: 24
- targetSdk: 36
- applicationId: com.ald1n.mobile
- versionName in generated prebuild: 1.0.0
- minifyEnabled: true
- shrinkResources: true
- default ProGuard: proguard-android-optimize.txt-8.12.0
- android.r8.optimizedResourceShrinking: true
- R8 fullMode property: UNSET

The generated local prebuild showed versionCode=1. This is NOT the Android release versionCode authority. EAS remote versioning must be rechecked immediately before the production Build22 build.

### Release dependency graph

- unresolved dependencies: 0
- Material resolved: 1.13.0
- Fresco resolved: 3.6.0
- NATIVE_497_ASSERTIONS=PASS

Material was not force-upgraded because the resolved SDK57 graph is healthy and compatible.

## Residual warnings / non-blockers

- android.r8.optimizedResourceShrinking is reported by AGP 8.12.0 as experimental.
- Gradle reports deprecated features that will be incompatible with Gradle 10; Batch 497 does not migrate to Gradle 10.
- Expo install --check now recommends newer SDK57 patch versions; dependency alignment is intentionally not mixed into this native modernization batch.
- Android edge-to-edge / third-party residual warnings are not suppressed merely to make Play Console green.

## Release gate

Batch 497 source/native audit is PASS.

Before a production Build22:
1. integrate the exact verified source blobs into main;
2. synchronize the canonical hosting checkout;
3. run a fresh production-server CMS static check and require 983/983;
4. recheck Mobile typecheck/validator if source authority changes;
5. live-check EAS remote Android version allocation;
6. only then run one planned production Android AAB build;
7. upload to Internal Testing and recheck Play warnings.

No claim is made here that Build22 has been built, submitted, or accepted by Google Play.
