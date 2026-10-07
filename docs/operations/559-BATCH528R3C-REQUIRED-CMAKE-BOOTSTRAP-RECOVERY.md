# Batch528R3C - required CMake bootstrap recovery

## Bound state

- Main authority remains `b5b942e645d28d3ed5f248eaae4180ebe9a54ee6`.
- Audit parent is `0a3e5f3d49600f75a54acfa578396d75a40d08a0` from successful Batch528R3B dispatch.
- Immediate predecessor Report558 is bound by exact SHA-256.
- Previous GitHub Actions run: `37691337966`.
- Previous evidence artifact: `11512749559`, SHA-256 `2bb8aeed55a1aebd291ce84767ca692de994f46dd5874febf338866d51e48faf`.

## Root cause

The R3B native run did not fail because of the GitHub Node 20 deprecation warning. It failed inside the new runner disk-reclaim stage after cleanup with `Required CMake ninja missing after cleanup`.

The cleanup contract intentionally preserves Android SDK CMake `3.22.1` and verifies `$ANDROID_SDK_ROOT/cmake/3.22.1/bin/ninja`. The workflow bootstrap installed platform 36, build-tools 36.0.0 and NDK `27.1.12297006`, but did not install CMake `3.22.1` before cleanup. The hosted image had other CMake versions, which the cleanup correctly removed as unused, leaving the required version absent before Gradle had a chance to auto-provision it.

## Minimal correction

The GitHub workflow now explicitly installs `cmake;3.22.1` through the same Android `sdkmanager` bootstrap step before the disk-reclaim function runs, and verifies the exact Ninja binary exists. Existing disk cleanup, CMake preservation, NDK preservation, ABI configuration, Kotlin candidate, Gradle, AGP, R8, signing and application source remain unchanged.

This follows Android's documented CI pattern of explicitly installing the required side-by-side CMake/NDK versions for reproducible native builds.

## Separate maintenance deliberately deferred

The workflow currently selects Node `22.16.0` for project commands while canonical CloudLinux uses Node `22.23.3`. Separately, several pinned GitHub Actions revisions declare the deprecated Node 20 action runtime and are being forced by GitHub to Node 24. Those warnings are not the cause of this failure and are intentionally not changed in this root-cause recovery so the native retry changes one variable only.

After this CMake recovery is proven, a separate bounded workflow-maintenance batch may align project Node to `22.23.3` and update the pinned GitHub Actions to reviewed Node-24-native revisions without touching application/native semantics.

## Release boundary

This is an audit candidate only. Build25 remains unauthorized. No EAS build/submit, OTA, Google Play action, production signing or database change is performed. Full native release readiness still requires release compile, executed R8, mapping, lintRelease and artifact inventory on the exact candidate commit.
