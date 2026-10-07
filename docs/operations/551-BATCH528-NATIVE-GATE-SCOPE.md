# Batch528 - isolated Kotlin candidate and real native release gate

Status at commit: CANDIDATE_ONLY_NATIVE_CI_NOT_YET_VERIFIED.
Base main: b5b942e645d28d3ed5f248eaae4180ebe9a54ee6.
Previous application source: 7066a7aee04a7bdb2c37e862905f74306737e78b.
Report550 SHA256: 5a6e1246dce5c4022936ffaf077703d7dbb9a24b4c317b7c02b471fe3532fb20.
Report549 SHA256: 55ac9f4f2d47369ab5322ec26e493804d6cfb95d5075f7b81ea7d1ce911dc2df.

This audit branch is not production source authority. Do not merge or create an EAS production build based on the existence of this document or a green source-string validator.

## Change

Only application behavior change: explicit zero-argument Coroutine for clearRestoreCredential, ending in Unit instead of null. Existing Gradle version metadata, create/get flows, dependencies, app version, runtime and channels are unchanged.

## Actual CI contract

The push-triggered workflow checks out github.sha. It runs locked npm installation, source guards, TypeScript, project validator, Expo compatibility and Doctor, clean prebuild, a read-only effective Gradle/dependency snapshot, real failing Kotlin compilation of the base source, then the corrected release Kotlin/Java/native code path through assembleRelease and bundleRelease with R8, plus lintRelease. Compiler diagnostics, R8 mapping, generated config, local audit APK/AAB hashes and outputs are retained for seven days.

Artifacts use the generated debug signing configuration, never the production upload key. They are AUDIT ARTIFACTS, not a Build25 release, and MUST NOT be uploaded to Google Play. Snapshot local versionCode is not EAS remote authority.

Hosted runner/JDK patch and EAS builder parity, final production signature validation, ABI/16KB/device acceptance are still separate gates. R8 configuration is checked, not turned off or replaced with blanket keep/dontwarn rules.

## Scope and open findings

A01: source correction proposed; CLOSED only after actual compiler evidence is reviewed.
A02: real native pipeline added; execution result required, not inferred.
A03: new runner uses owned lock; old runners remain unsafe and must not be rerun.
A10: original hosting index/residue preserved; canonical forensic reconciliation remains pending.
A04, A05, A06, A07, A08, A09, A11, A12, A13, A14: remain open according to the attached audit. This package intentionally does not mix lifecycle/backend fixes into compiler recovery.

## Release block

BUILD25_AUTHORIZED=NO
PRODUCTION_SOURCE_CHANGED=NO
PRODUCTION_INDEX_CHANGED=NO
EAS_BUILD_STARTED=NO
EAS_SUBMIT_STARTED=NO
OTA_PUBLISHED=NO
DATABASE_CHANGED=NO
RELEASE_READINESS=BLOCKED_OPEN_AUDIT_FINDINGS

After the exact candidate's CI logs are reviewed, continue Restore lifecycle/reauthentication and the remaining audited fixes in separate testable changes. Repeat the real native gate on the final cumulative candidate before any production release authorization.
