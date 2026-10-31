# Report990 — M17 Expo SDK58 actual source migration, isolated CI and native prebuild (2026-10-31)

## Source authority and scope

- Owner-accepted M16 Android 16 KB remains PASS based on versionCode 28, real device confirmation and cleared Google Play policy warning (Reports987/988). No repeated M16 remediation, Android release or source rollback.
- Starting main source observed at `983266b7ff2808944e4dec31563be800f811a6e1` (Report989/B1017 already merged). Development is in draft PR123: https://github.com/AldinAga/ald1n-project/pull/123, branch `m17-sdk58-isolated-upgrade-20261031`.
- Actual SDK58 dependencies installed from Expo58 bundledNativeModules and checked with the real Expo detector; key versions: expo `~58.0.22`, React Native `0.88.1`, React `19.3.0`, expo-router `~58.0.29`, react-native-worklets `0.13.0`, and `@types/react ~19.3.0`. Expo/npm dependency and package-lock source edits are in the PR, not active CloudLinux production.
- SDK58 candidate fixes actual RN0.88/React19.3 compile issues in app: RN `InteractionManager` replacement with guarded requestIdleCallback/cancelIdleCallback; `ScrollViewInstance` refs; react-hook-form button callback adapters; FlatList header/footer types; nullable color-scheme; migration of strict package/lock validator matrix to the SDK58 exact versions; explicit YAML parser 2.9.0 dependency (present in historical SDK57 lock, lost as a transitive dependency on SDK58).
- The app still declares `version=1.0.0`, `packageManager=npm@10.9.2`, EAS CLI `25.0.0` with remote app-version authority; `eas.json` and `app.config.js` were not edited. Historical published runtime labels `1.0.0-build17` and gated `1.5.0-native2` are preserved; owner-reported Android versionCode 28 is untouched.

## Actual isolated source QA

- Initial real CI run 41197433210: installed 31 SDK58 packages but found missing `@types/react` target. Fixed without bypassing Expo install check.
- CI run 41197543765: exact dependency check PASS after `@types/react ~19.3.0`; discovered **16 authentic TypeScript regressions** from RN0.88. Source was repaired, never skipped with `skipLibCheck` or loosening TS strict settings.
- CI run 41197845942: strict TypeScript PASS; original project validator reported 2 false failures: Node YAML parser transitive dependency missing and a stale InteractionManager source assertion. Restored direct YAML dependency and updated the behavioral validator contract.
- CI run 41197967499: Expo detector/typecheck/validator PASS, but full historical Release25 regression suite 304/309 PASS and 5 FAIL (two SDK57-only expectations, one outdated ALT Node22 CLI location, two dated B963 index assertions). Each was reconciled to current verifiable requirements. No native/release permission was granted.
- **Final integrated source and Release25 CI PASS**: https://github.com/AldinAga/ald1n-project/actions/runs/41198102946 job 124528271136, Node22.23.4/npm10.9.9, `npm ci` exact lock, `expo install --check` PASS, TypeScript PASS, `node scripts/validate-project.mjs` **0 FAIL**, Expo Doctor **20/20 PASS**, real Release25 **309/309 PASS** with 0 failures. Release config and package/lock remain unchanged during this tested job.
- Additional Android keyboard-source workflow https://github.com/AldinAga/ald1n-project/actions/runs/41198102974 PASS. B1012 Node22/EAS25 source-only QA https://github.com/AldinAga/ald1n-project/actions/runs/41198102986 PASS.

## Actual native SDK58 generator QA

- A separate isolated GitHub Actions source-only prebuild was implemented in PR123 (`.github/workflows/m17-sdk58-native-prebuild-qa.yml`). It invoked the real Expo58 Android `prebuild --platform android --no-install --clean` on an ephemeral Ubuntu runner; NO EAS build/submit/update, no APK/AAB signing, no OTA and no Play.
- Run 41198405662 https://github.com/AldinAga/ald1n-project/actions/runs/41198405662, job124529142683 **SUCCESS**. Generated Gradle project `android/`, preserved approved Liquid Glass upstream v2.0.11 and 16KB-oriented plugin pin, R8 optimization property, default ProGuard optimization, split Android API33 splash resources and Restore Credentials DAL manifest; exact protected `package-lock.json`, `eas.json`, `app.config.js` SHAs unchanged.
- Root-cause of earlier native-prebuild HOLD (run41198242156 and diagnostic41198332483) was confirmed: Expo auto-rewrites ONLY `package.json` scripts `android: expo start --android` -> `expo run:android`, and `ios: expo start --ios` -> `expo run:ios` inside the disposable prebuild workspace. The final run strict-deep-compares every package.json field to the original with exactly these two expected ephemeral rewrites, with no other modification accepted. It does NOT commit these rewrites to the PR or host.
- The legacy Android LG-03/16KB CI workflow in the PR still insists on the *existing released SDK57/RN0.86 identity* and correctly FAILS that historical gate when pointed at an SDK58 source candidate (e.g. run 41198103051 RC31). This failure is not a new regression of production versionCode28, but it means **native SDK58 compiler/runtime and future release must be independently established**. No suppression/waiver or false all-green status.

## Native compiler / owner acceptance boundaries

- An additional **isolated, unsigned, no-APK Android Kotlin compile gate** has been prepared and started: https://github.com/AldinAga/ald1n-project/actions/runs/41198524254, workflow `.github/workflows/m17-sdk58-android-kotlin-compile-qa.yml`. It uses JDK17, Android SDK36, NDK27, npm10 exact lock, real Expo58 native prebuild and Gradle `:app:compileDebugKotlin :react-native-android-liquid-glass:compileDebugKotlin`. No release-signing keys, production EAS, Play or OTA.
- At the time this report is initialized, Kotlin compilation outcome = **PENDING / NOT YET ACCEPTED**. Do NOT promote to PASS without the original completed CI job log.
- M17 full milestone = **OPEN** until native Kotlin compilation, Android actual binary/device behavior and all separate release gates pass. M16 remains owner-accepted. Android production build / OTA / Play authorization = NOT GRANTED. Active CloudLinux npm10.9.9/EAS25 source/production checkout not changed by this work. No user-run SSH script is needed for source-only GitHub CI.
- Formal accepted progress remains **8/17=47.1%** program; CMS **7/15=46.7%**, original UI **6/6=100%**. Engineering work accomplished is not confused with an extra accepted functional milestone.

## Next safe batch

Continue only from fresh PR123 exact-head and GitHub run evidence. On Kotlin HOLD, diagnose compiler root cause and patch PR source with scoped tests, retaining upstream native/16KB guards. On Kotlin PASS, design an explicit separate unsigned Android 16KB binary/device QA gate before considering any native production build. Never repeat B1017 or M16 work. Keep all deliverables and this numbered report on owner Google Drive; update AGENTS.md only for recurrent new blockers, not for ordinary version migration errors.
