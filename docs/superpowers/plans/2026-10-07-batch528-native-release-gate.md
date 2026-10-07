# Batch528 Native Release Gate Implementation Plan

> For agentic workers: execute with superpowers:executing-plans. This is the first bounded remediation package, not audit closure or production release authorization.

**Goal:** Repair A01 in an isolated candidate and obtain real Android release/R8 evidence without EAS allocation or production writes.
**Architecture:** Exact-base external git worktree; complete Kotlin replacement after a RED contract; audit-only branch and exact-commit CI. Do not repair the dirty production index in this package. A03 ownership is corrected for the new runner, not retroactively for old runners.
**Tech Stack:** Bash, Node built-ins, Kotlin/Expo, existing Gradle wrapper, GitHub Actions.
**Spec:** docs/superpowers/specs/2026-10-07-build22-24-deep-audit.md (A01, A02, A03, A10), Report550.

## Global Constraints
- Expected main b5b942e645d28d3ed5f248eaae4180ebe9a54ee6; source 7066a7aee04a7bdb2c37e862905f74306737e78b.
- App 1.0.0; runtime 1.0.0-build17; production profile/channel unchanged.
- No EAS build/submit/update/version write; no DB write; no main push; no cleanup of production residue.
- Remote VC is read-only, expected 24. A missing compiler is NOT_READY.
- Canonical hosting Node/npm paths and eas-cli@24.8.0; no dependency installation on hosting.
- External workspace. Owned lock only. Source scope exact. Evidence sealed before staging.

## Review Focus
- Second invocation must not remove the first lock or create a second branch/workspace.
- Root index, tracked source and known report bytes must survive success and failure unchanged.
- Compiler failure without the known Kotlin diagnostic must not count as a valid RED result.
- A later pipeline failure must not be masked by tee, artifact upload, or a successful summary step.
- CI success covers native compile/R8 only; auth, backend, signature, 16KB and device issues remain open.

## Task 1: Exact Kotlin regression and minimal fix
Files: local module; scripts/batch528-native-contract.mjs; scripts/batch528-native-contract.test.mjs.
Interface: validateSource(kotlin, gradle) returns array of failures; CLI validates real source files.
- [ ] Write contract tests and run the CLI against exact old source: fail for ambiguous zero-argument coroutine/null.
- [ ] Replace only the clear block with explicit zero arguments and Unit return. No dependency/version change.
- [ ] Repeat tests and minimal language probe. Never label the language probe an Android compile.

## Task 2: Native CI pipeline
Files: .github/workflows/ald1n-native-528.yml; scripts/batch528-native-snapshot.gradle; scripts/batch528-ci.sh.
Interface: exact github.sha; pinned action SHAs; source gate -> locked npm -> typecheck/validator/doctor -> clean prebuild -> actual compiler RED/GREEN -> full release/R8/lint -> evidence.
- [ ] Test snapshot validator and negative fixtures for absent/disabled shrinking, wrong SDK, absent optimized ProGuard, missing compile artifacts.
- [ ] CI compiles original source as RED with actual dependencies, restores candidate, then runs compileReleaseKotlin + assembleRelease + bundleRelease + lintRelease.
- [ ] Check real R8 task execution and mapping; archive only selected outputs, never keys or credentials.
- [ ] Record native success separately from BLOCKED release status. No submission path exists.

## Task 3: Isolated candidate runner
Files: downloadable ald1n-batch528-isolated-native-release-gate.sh; test harness; candidate status document.
- [ ] Test lock ownership, dirty index survival, stale main, rerun, report mismatch and push failure via isolated fake remotes/tool adapters.
- [ ] Bind reports and source, snapshot original index and tracked bytes, create external worktree, patch complete files and stage exact scope only in candidate.
- [ ] Push only audit/batch528-native-release-gate after local source tests. CI starts on that branch push.
- [ ] Leave immutable report551; CI status is NOT_YET_VERIFIED, not PASS. Preserve workspace after failure.

## Remaining work, not silently closed
A04/A05 release ordering/signature; A06/A07 Restore lifecycle and server revocation; A08 legacy API; A09 publication race; A10 canonical evidence integration; A11 OTA compatibility; A12 16KB/device acceptance; A13 behavioral transactions; A14 builder parity.
