# Batch528R - repair composite-build audit scope

## Evidence and scope

- Base main: `b5b942e645d28d3ed5f248eaae4180ebe9a54ee6`.
- Existing candidate: `8321aa38498359053278b3614922feae7940367d`.
- Existing branch: `audit/batch528-native-release-gate`.
- Report551 SHA-256: `e792a78926d83f212cb9b7343bf84fc1edad792c860e823d876df3a85fdb7696`.
- Failed Actions run: `37624508363`; job: `112802725228`; attempt 1.
- Preserved evidence artifact: `11483477393`.
- Artifact ZIP SHA-256: `39fa57f2f3499668baa89d6cfe1d1e8a2df1ae3d8551af30d07af3e2cc75bc5f`.

Report551 proved candidate dispatch and preservation of the production checkout,
not successful Android compilation. The run passed source contracts, 31 contract
unit tests, npm ci, typecheck, the Mobile validator, Expo compatibility/Doctor,
OpenAPI parity and prebuild. It failed at native-snapshot, before the actual
Kotlin RED/GREEN and full release/R8 tasks.

The exact exception is `Batch528: :app missing`, thrown by our globally injected
`batch528-native-snapshot.gradle` at line 6 during resolution of the included
React Native settings-plugin build. Included builds have independent project
hierarchies. Requiring :app inside every build was an audit harness defect.

## Correction

The existing corrected Kotlin candidate is reused unchanged. The audit task is
copied into generated `android/app/ald1n-audit-528.gradle` and applied only from
generated `android/app/build.gradle`. No Gradle init script is used for the real
native snapshot. The helper rejects application to a project other than :app.
The generated files are ephemeral CI files, not tracked application changes.

A real Gradle composite regression must run before the native snapshot:

1. Original global init reproduces the included-build :app-missing exception.
2. Corrected project application registers the main :app task without installing
   it in an unrelated included build that also has an :app project.
3. Missing actual main :app remains an error, never a silent skip.
4. Applying the helper to the root instead of :app remains an error.

This composite probe tests scope only. It does not replace the real locked Expo
Kotlin RED/GREEN, release assemble/bundle, R8 execution, mapping and lint gates.
All of those remain mandatory. A pending or skipped stage is NOT native PASS.

## Change and release limits

- Only audit helpers, their regression tests and this scope record change.
- No Kotlin re-patch; no app identity, runtime, dependency, R8, AGP, Gradle or NDK change.
- No main push, production checkout mutation, database write, EAS build,
  EAS submit, version allocation or OTA publication.
- The recovery dispatches one fast-forward child to the existing audit branch.
- Report549/550/551 and original staged/unstaged evidence remain unchanged.
- Audit APK/AAB files use only generated debug signing and must never go to Play.
- Build25 is not authorized. All other findings in the deep audit remain open.

## Verification boundary

Node wiring tests and local runner fixtures are not an Android build. The real
Gradle composite probe and full Android compile must be verified from the new
Actions run at its exact commit. No native PASS is claimed by this scope record.

Primary references:
- https://docs.gradle.org/current/userguide/init_scripts.html
- https://docs.gradle.org/current/userguide/composite_builds.html
- https://github.com/AldinAga/ald1n-project/actions/runs/37624508363

Documentation references explain build scoping; the observed Gradle 9.3.1
wrapper remains unchanged and must be exercised by CI. No upgrade is implied.
