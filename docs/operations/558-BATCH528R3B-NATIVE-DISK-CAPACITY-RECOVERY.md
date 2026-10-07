# Batch528R3B - GitHub runner disk-capacity recovery after missing predecessor report

## Bound state

- Immediate predecessor Report557 is bound by exact SHA-256 and proves Batch528R3A stopped at `evidence` because the exact Report556 file was no longer present at the expected `incoming` path.
- Report557 also proves R3A made no source/native semantics change, no commit, no push, no EAS/OTA/DB action, and did not authorize Build25.
- Main authority: `b5b942e645d28d3ed5f248eaae4180ebe9a54ee6`.
- Audit parent: `4747620e4357370579dee1aea842916498877815`.
- Previous native CI run: `37645196267`.
- Previous evidence artifact: `11495307869`, SHA-256 `a24dfd3df4cd0b46da4cf28372235b0242895b033eab6ced5c9228077eb92f1e`.

## Why Report556 is not a hard prerequisite

The canonical project rule requires a recovery batch to bind the exact immediate predecessor report. R3B therefore binds Report557, not an older missing file. Report557 itself records the expected Report556 SHA and the exact R3A stop condition (`report556_missing`) before authority inspection, clone, commit or push. The missing older evidence file is not recreated, guessed or silently substituted.

## Native recovery preserved

The previous GitHub native audit had already proven the intentional Kotlin RED and the corrected Kotlin candidate compile before later CMake failure. The fatal release error was runner storage exhaustion: `No space left on device` during `armeabi-v7a`. R8 and lint were not reached.

R3B does not change application/native build semantics. It adds only GitHub-hosted-runner disk telemetry and cleanup before the native Gradle stages. It explicitly preserves NDK `27.1.12297006`, CMake `3.22.1`, all configured ABIs, Gradle/AGP/R8 settings, signing, app/runtime versions and production channel.

## Release boundary

This commit is an audit candidate only. It does not authorize Build25, EAS build/submit, OTA publication, Google Play action or production signing. A full native PASS still requires release compile, executed R8, mapping output, lintRelease and artifact inventory on the exact candidate commit.
