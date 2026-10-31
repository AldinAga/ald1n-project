# Report991 - M17 Expo SDK58 / Nitro Google Sign-In Kotlin compatibility (2026-10-31)

## Authority and exact source scope

- M16 Android 16 KiB policy/device result remains owner-accepted PASS, Android production versionCode 28 unchanged. Do not repeat M16 remediation.
- SDK58 development remains in draft PR123, branch `m17-sdk58-isolated-upgrade-20261031`. None of these Kotlin probes is a production, EAS, OTA, Play, CloudLinux host or signed APK/AAB operation.
- Project main authority: `AGENTS.md`, `docs/operations/000-LATEST.md`, canonical Google Drive `ALD1N-EXPO-CLOUDLINUX-UPDATE-RUNBOOK.md`. The SDK58 source and native prebuild were already reported in Report990 and passed isolated source QA 309/309 and native prebuild.

## Observed Kotlin build blocker, not a guess

1. Initial native compile run https://github.com/AldinAga/ald1n-project/actions/runs/41198524254 failed `:app:processDebugGoogleServices` due to `com.ald1n.mobile.development` having no matching Google Services client in the existing configuration. This was a CI debug environment mismatch, not a production app change. The CI workflow was scoped to the already tracked `com.ald1n.mobile` client only for unsigned debug compilation.
2. With that gate corrected, real completed GitHub run https://github.com/AldinAga/ald1n-project/actions/runs/41198826999 failed native compilation at `:react-native-nitro-google-signin:compileDebugKotlin`: `GoogleSignInButtonShadowNode.kt:32:28 Function invocation 'getThemedContext()' expected`. Exact installed npm package: `react-native-nitro-google-signin@1.0.2`; Expo58/RN0.88.1. Protected release source hash checks remained PASS.
3. Maintainer tag `v1.0.2` contains `measureIntrinsicSize(themedContext)`, while tag `v1.3.0` removes that deprecated access and measures the Paper shadow node's fixed intrinsic size directly. Upgrading the library is a broader auth-native dependency change, so source tests and native compilation are required before promoting it to the SDK58 candidate.

## Isolated upstream 1.3.0 candidate evidence

- First candidate probe https://github.com/AldinAga/ald1n-project/actions/runs/41201206811 verified npm exact `1.3.0` + existing `react-native-nitro-modules@0.36.1`, Kotlin source no longer containing `themedContext`, strict TS, project validator 0 failures and Expo Doctor 20/20. Five release25 tests correctly failed because the iOS native-auth readiness contract still required `1.0.2`; none was suppressed or rewritten to ignore the native-auth gate.
- Second probe https://github.com/AldinAga/ald1n-project/actions/runs/41201321884 confirmed those 5 tests still failed after clearing the global test env. Precise cause was `IOS_APP_OR_NATIVE_AUTH_VERSION_DRIFT` inside `scripts/ios-release-readiness.mjs`, not just environment contamination. Preserve the gate and update its expected exact version only when the native dependency is really pinned to `1.3.0`.
- Third candidate probe https://github.com/AldinAga/ald1n-project/actions/runs/41201459691 passed exact npm/lock/validator/iOS gate, strict TypeScript, project validator, Expo Doctor and full Release25 source tests with no skipping; native Kotlin compilation was next. **Do not claim its Kotlin result until the original completed job is read.**

## Release constraints

- No EAS native build/submit, Play, OTA, signing, release-version bump, versionCode bump, `eas.json` mutation or CloudLinux production source changes authorized.
- Keep PR123 draft and M17 NOT ACCEPTED until native compilation and subsequent actual SDK58 Android artifact/device and native 16KiB checks pass under separate gates.
- M16 accepted progress: program 8/17 = 47.1%; CMS 7/15 = 46.7%; original UI 6/6 = 100%.

## Completed isolated candidate and exact-source promotion

- Run https://github.com/AldinAga/ald1n-project/actions/runs/41201459691 job `124537925627` completed **SUCCESS**. Actual isolated checks: `npm ci` from original SDK58 source, npm10 `npm install --save-exact` of Nitro Google Sign-In `1.3.0` in disposable runner, strict TypeScript PASS, project validator `Ukupno FAIL: 0`, Expo Doctor `20/20`, Release25 tests `309/309 PASS`, JDK17 / SDK36 / NDK27 PASS, Android Expo58 native prebuild and Gradle `:app:compileDebugKotlin :react-native-android-liquid-glass:compileDebugKotlin` **BUILD SUCCESSFUL in 5m 40s**; `M17_NITRO130_SDK58_NATIVE_KOTLIN=PASS_CI_ONLY`. Exact source EAS/app.config/Google auth files unchanged by probe.
- CI-generated candidate artifact: https://github.com/AldinAga/ald1n-project/actions/runs/41201459691/artifacts/13042693262 and verified Google Drive archive https://drive.google.com/file/d/1d2-Y_9ZxVHPcyKqSLDXrhcsimdHHlTCW/view . Source manifest SHA256 efffc2d58b7c418fc371279d2a90c39db5d8b6026de0b35b6fe288f1997cc964; lock SHA256 b369c3eed9beaab5147c72ce0407692b6a1a576362911fc8abf1f078c8ff977e; validator SHA256 3445cc45b091d7a4c236ea81d755b2bfca06b241a4e0b1bfc04196c831099425. The npm10 lock additionally marks the same previously pinned `yaml@2.9.0` metadata as `dev:true` without changing version/integrity; no other transitive packages changed.
- Audit of original vs candidate manifest showed only one altered dependency name/spec: `react-native-nitro-google-signin` `1.0.2 -> 1.3.0`; Nitro core remains `0.36.1`. The full project validator changed only its one exact Google package assertion. iOS readiness preserved `IOS_APP_OR_NATIVE_AUTH_VERSION_DRIFT`, changing only expected native package version to `1.3.0`; all preview/production Firebase, OAuth, bundle identity and external-file protections remain. Added 3 regression tests asserting package-lock SHA512, strict iOS release/Google login source contracts and EAS/OTA/Play holds. The temporary probe-only workflow was deleted after promotion.
- Draft PR123 candidate now includes **actual pinned source fix** in commit `4fbb3afdc7e6f2237fea9bbd9158b22a26bc034f` (no production/main merge). New exact-source CI runs: source regression https://github.com/AldinAga/ald1n-project/actions/runs/41202278168, Android prebuild https://github.com/AldinAga/ald1n-project/actions/runs/41202278094, and Kotlin source https://github.com/AldinAga/ald1n-project/actions/runs/41202278105; statuses must be read live before claiming an exact committed-source PASS.
- **Boundary:** This is source and disposable debug Kotlin compilation only. No APK/AAB, no physical device test (including actual Google login, on-device 16KiB native alignment or notifications), no EAS build/submit, OTA, Google Play or CloudLinux changes. SDK58 M17 owner acceptance remains OPEN. M16 remains accepted PASS, program 8/17 (47.1%), CMS 7/15 (46.7%).

## Verification of promoted PR source after candidate update

- Actual immutable SDK58 PR123 source commit: `4fbb3afdc7e6f2237fea9bbd9158b22a26bc034f` (Git tree `591cee6ea9db26acb2c4e591ffae7aef1034135c`). Branch promotion used a stale-head guard and no forced update. Main and CloudLinux are not changed by this commit.
- Direct run https://github.com/AldinAga/ald1n-project/actions/runs/41202278168 job `124540272177` **SUCCESS**: real `npm ci`, Expo SDK58 dependency detection PASS, TypeScript PASS, project validator 0 FAIL, Expo Doctor 20/20 PASS, **312/312 Release25 tests PASS**, protected package-lock/eas.json/app.config.js unchanged during CI, and no EAS build/OTA/Play.
- Direct native prebuild run https://github.com/AldinAga/ald1n-project/actions/runs/41202278094 job `124540272125` **SUCCESS**. The generated SDK58 Android Gradle project retains R8, API33 splash, Restore Credential and v2.0.11 nativegauss pin; accepted only two known ephemeral package.json script rewrites during Expo prebuild.
- Direct **pinned-source Kotlin compiler** run https://github.com/AldinAga/ald1n-project/actions/runs/41202278105 job `124540271867` is the final source-build proof. Its result is distinct from successful temporary npm-upgrade probe and must be inspected before it can be labeled PASS.

## B1019 exact committed-source CI results after permanent source fix

- Exact PR123 head: `4fbb3afdc7e6f2237fea9bbd9158b22a26bc034f` (draft; not merged into `main`).
- Real GitHub Actions source run `41202278168`, job `124540272177`, **SUCCESS**: npm10 lock install, Expo58 dependency detector, strict TypeScript PASS, app validator **0 FAIL**, Expo Doctor **20/20 PASS**, full Release25 regressions **312/312 PASS** (three additional Nitro Google native regression cases), and release configuration hashes unchanged.
- Real native SDK58 Android prebuild run `41202278094`, job `124540272125`, **SUCCESS**, exact PR source and bounded prebuild/plugin checks. No APK, AAB, EAS build, OTA or Play action.
- Separate direct exact-commit Kotlin run `41202278105`, job `124540271867`, initially **IN_PROGRESS** during this report draft. Its terminal status must be inspected before labelling Kotlin native gate PASS; the earlier successful v1.3 ephemeral probe is not sufficient evidence for source-pinned compilation. Check https://github.com/AldinAga/ald1n-project/actions/runs/41202278105 .
- Native release/Google sign-in physical device UAT and Android 16KiB binary/ELF proof remain **NOT_RUN** for SDK58. Older released M16 versionCode28 remains user-accepted PASS. M17 acceptance remains OPEN; 8/17 = 47.1% accepted program scope, CMS 7/15 = 46.7%.
