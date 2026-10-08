# Batch528R3E - Reanimated KaModule repeat, early lint architecture recovery

## Bound state

- Main authority stays at `b5b942e645d28d3ed5f248eaae4180ebe9a54ee6`.
- Audit parent is `627f5cbe322487c730a7c2f77b0467bf6bb3cbc3` (Batch528R3D).
- Immediate predecessor Report560 is SHA-256 `f24714bf83e90b3b589210548f6e3094b539ececf8c0d59770e28ff997af6ff7`.
- Previous GitHub Actions: run `37736012921`, job `113175581607`; artifact `11534111039`, SHA-256 `db761bc07b38fe4b742e97fcea07d701af76cdbb92913458e16957f0cd1e84e2`.

## Exact CI evidence and root cause

Batch528R3D applied its locked Worklets lint exception correctly: Gradle reported `:react-native-worklets:lintAnalyzeRelease SKIPPED`, proving the prior crash was bypassed. The same AGP 8.12/K2 Lint KaModule crash then occurred in `:react-native-reanimated:lintAnalyzeRelease`: `Cannot find a KaModule for the VirtualFile`. Reanimated 4.5.1 applies the external Kotlin build script `./generate-stub-pch.gradle.kts`, matching Android public bug 430991549. The full release/R8/Kotlin/assemble/bundle had already passed (`BUILD SUCCESSFUL in 37m 42s`, release-log contract PASS). Deprecation warnings were not the failing cause.

## Structural improvement: fail fast

Previous audit order performed Kotlin RED and 30-40 minute native/R8 release *before* Lint, leading to repeated ~50 minute CI failures. This recovery moves the **same complete `:app:lintRelease` command** to immediately after clean prebuild and the read-only native snapshot, before the intentional Kotlin RED and the expensive release/R8 path. Lint still operates on the restored/corrected candidate, with unchanged Gradle/Expo/AGP/NDK/CMake and four-ABI configuration. The actual `:app:lintRelease` task must be executed, not SKIPPED/UP-TO-DATE/FROM-CACHE; a true failing finding must fail the CI immediately. There is no global Lint bypass, `continueOnError`, `ignoreFailures`, or `-x :app:lintRelease`.

## Narrow third-party exceptions, not zero-warning certification

Only the transient CI node_modules Gradle scripts for **Worklets 0.10.1** and **Reanimated 4.5.1** are eligible for the bug-specific exception. Both package versions and Kotlin `apply(from = ...)` anchors are checked before patching. Reanimated's upstream `lintVital` workaround is required to match the pinned source. The original Gradle files are restored after Lint or on an error via the EXIT trap, *before* Kotlin RED and native/R8 release begin. Their module-local Lint tasks are skipped deliberately due to upstream Lint engine failure, so we **must not claim that full third-party dependency Lint coverage was certified**. App Lint and all non-exempt modules remain active.

## Why not AGP9 now?

Google lists issue 430991549 as fixed in AGP 9; upgrading AGP/Expo/RN/native plugins here would be a separate compatibility change and invalidate the audited toolchain. Do not solve a third-party Lint crash via an unreviewed major upgrade.

## Deprecated-warning technical debt

Node 20 actions and Gradle 10 deprecation warnings remain a **separate** toolchain alignment batch, not a demonstrated cause of this Lint crash. Do not suppress warnings with configuration flags. Prior to Build25 authorization, run `--warning-mode all`, source-attribution, pinned Node 22.23.3 / Node24-native Actions upgrade, and appropriate verified compatibility gates.

## Release boundary

Audit branch only; no source application changes, no production signing, no Build25 authorization, EAS/OTA/Google Play/DB actions. Production release still requires final native evidence, Lint exception review and all open audit findings closed on the final exact commit.
