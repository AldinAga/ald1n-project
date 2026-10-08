# Batch528R3D - Worklets lint KaModule recovery

## Bound state

- Main authority remains `b5b942e645d28d3ed5f248eaae4180ebe9a54ee6`.
- Audit parent is `5626cf7fe37041d7cd7e513c9776d677011d1e61` from Batch528R3C.
- Immediate predecessor Report559 is bound by exact SHA-256 `45bff7e3ee3cf5b06d7c6cf6d3b496b5f8001defce6c0c884302e622765d079f`.
- Previous GitHub Actions run: `37694123417`, job `113041364791`.
- Previous evidence artifact: `11516357689`, SHA-256 `9e01c1e2d52ad8f914d089a06b4e099860bd737b3ba828b120ffd830a7c55ee4`.

## Proven progress from R3C

The runner disk recovery is now proven: root free space increased from about 10 GiB to 29 GiB before native compilation. The isolated native snapshot passed, the expected original Kotlin RED was reproduced, the corrected Kotlin source compiled, and the full release command completed successfully in 31m42s.

The release log proves an executed `:app:minifyReleaseWithR8`, followed by `:app:assembleRelease` and `:app:bundleRelease`, then `BUILD SUCCESSFUL`. The release-log contract passed. The evidence artifact contains the release APK, AAB and `outputs/mapping/release/mapping.txt`.

## Exact remaining failure

The run failed only in the separate full `:app:lintRelease` gate. Gradle reported:

- task: `:react-native-worklets:lintAnalyzeRelease`
- Android Lint internal failure: `Unexpected failure during lint analysis`
- K2/UAST message: `Cannot find a KaModule for the VirtualFile`
- final Batch528 stage: `lint-release`

This matches Android public bug `430991549`: AGP 8.11+ lint can crash while analyzing Kotlin Gradle scripts that apply other `.gradle.kts` scripts via `apply(from = ...)`. The audited dependency is `react-native-worklets` 0.10.1 and its Android build script contains both `apply(from = "./fix-prefab.gradle.kts")` and `apply(from = "./generate-stub-pch.gradle.kts")`, the trigger shape described by that Android bug.

## Recovery design

Do not upgrade AGP/Gradle/Expo/RN or dependency versions inside this recovery. Expo SDK57 compatibility checks are already green and the native release/R8 path is now proven on the current toolchain.

Immediately after the release/R8 log contract passes, and only for the generated CI `node_modules/react-native-worklets` copy, Batch528 applies a guarded module-local lint workaround. It requires Worklets exactly `0.10.1`, verifies the two `.gradle.kts` apply anchors, saves the original file and appends the same `tasks.configureEach { if (name.startsWith("lint")) enabled = false }` pattern currently used upstream by Worklets for a K2/UAST lint crash. The original dependency file is restored after lint or by the exit trap on failure.

The full app command `:app:lintRelease` remains mandatory. No `-x`, `ignoreFailures`, `continueOnError`, app-level warning suppression or release-gate weakening is allowed. Only the third-party Worklets module lint tasks that hit the known Android Lint engine bug are disabled. Application lint and all other dependency lint tasks remain active.

## Deferred clean toolchain alignment

After this native gate is proven, perform a separate bounded maintenance batch to align project Node to the canonical server `22.23.3`, update pinned GitHub Actions to Node-24-native current stable revisions, run Gradle with `--warning-mode all`, and address controllable deprecations/JVM tuning without suppressing warnings.

## Release boundary

This remains an audit candidate only. Build25 is not authorized. No EAS build/submit, OTA, Google Play action, production signing or database change is performed.
