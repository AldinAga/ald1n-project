# Report 634 - Batch632 CLI compatibility lab

Script: `/home/icaffeco/ald1n-project/incoming/ald1n-b632-cli-compat-lab-v2.sh`

Script SHA-256: `ff88d25a3cdc178c8e05ae026399225eee2f5cd4391428a042a05daefce879be`

Source main at preflight: `7789f273cb52909ebe7e6d20823c85561886becc`

Execution: 2026-10-12T11:23:48+02:00

## Full terminal output

```text
=== NODE / NPM / PROJECT VERSIONS ===
v22.23.3
10.9.9
{
  "nodeMinimum": ">=22.13.0",
  "expo": "~57.0.27",
  "reactNative": "0.86.3",
  "easPinned": "24.8.0",
  "appVersion": "1.0.0",
  "productionChannel": "production"
}
=== LATEST EAS FROM NPM REGISTRY (NO PROJECT INSTALL) ===
npm notice
npm notice New major version of npm available! 10.9.9 -> 12.2.0
npm notice Changelog: https://github.com/npm/cli/releases/tag/v12.2.0
npm notice To update run: npm install -g npm@12.2.0
npm notice
EAS_CLI_REGISTRY_LATEST=24.12.1
{
  "node": "^20.18.3 || >=22.0.0"
}
=== CANDIDATE BINARY / HELP IN ISOLATED NPM CACHE ===
npm warn deprecated inflight@1.0.6: This module is not supported, and leaks memory. Do not use it. Check out lru-cache if you want a good and tested way to coalesce async requests by a key value, which is much more comprehensive and powerful.
npm warn deprecated rimraf@2.4.5: Rimraf versions prior to v4 are no longer supported
npm warn deprecated glob@6.0.4: Old versions of glob are not supported, and contain widely publicized security vulnerabilities, which have been fixed in the current version. Please update. Support for old versions may be purchased (at exorbitant rates) by contacting i@izs.me
npm warn deprecated uuid@7.0.3: uuid@10 and below is no longer supported.  For ESM codebases, update to uuid@latest.  For CommonJS codebases, use uuid@11 (but be aware this version will likely be deprecated in 2028).
npm warn deprecated uuid@8.3.2: uuid@10 and below is no longer supported.  For ESM codebases, update to uuid@latest.  For CommonJS codebases, use uuid@11 (but be aware this version will likely be deprecated in 2028).
npm warn deprecated uuid@8.3.2: uuid@10 and below is no longer supported.  For ESM codebases, update to uuid@latest.  For CommonJS codebases, use uuid@11 (but be aware this version will likely be deprecated in 2028).
npm warn deprecated glob@10.5.0: Old versions of glob are not supported, and contain widely publicized security vulnerabilities, which have been fixed in the current version. Please update. Support for old versions may be purchased (at exorbitant rates) by contacting i@izs.me
npm warn deprecated glob@10.5.0: Old versions of glob are not supported, and contain widely publicized security vulnerabilities, which have been fixed in the current version. Please update. Support for old versions may be purchased (at exorbitant rates) by contacting i@izs.me
eas-cli/24.12.1 linux-x64 node-v22.23.3
PASS: LATEST_CANDIDATE_CLI_HELP
=== SEPARATED BUILD25 RELEASE CONTROLLER TESTS (NO RELEASE ACTIONS) ===
RELEASE25_TEST_FILES=15
TAP version 13
# Subtest: rejects_conflicting_pins
ok 1 - rejects_conflicting_pins
  ---
  duration_ms: 3.778327
  type: 'test'
  ...
# Subtest: rejects_wrong_production_identity
ok 2 - rejects_wrong_production_identity
  ---
  duration_ms: 0.627276
  type: 'test'
  ...
# Subtest: accepts_consistent_pin
ok 3 - accepts_consistent_pin
  ---
  duration_ms: 0.331504
  type: 'test'
  ...
# Subtest: rejects_missing_version_source
ok 4 - rejects_missing_version_source
  ---
  duration_ms: 0.249121
  type: 'test'
  ...
# Subtest: P1: real signature crypto regressions run after JDK17 before Android SDK
ok 5 - P1: real signature crypto regressions run after JDK17 before Android SDK
  ---
  duration_ms: 6.550008
  type: 'test'
  ...
# Subtest: P2: current pointer records exact audit source without pretending remote versionCode is known
ok 6 - P2: current pointer records exact audit source without pretending remote versionCode is known
  ---
  duration_ms: 0.56328
  type: 'test'
  ...
# Subtest: P2: old Build23 instructions are visibly archived, not live build authority
ok 7 - P2: old Build23 instructions are visibly archived, not live build authority
  ---
  duration_ms: 0.242924
  type: 'test'
  ...
# Subtest: P1: CI remains manual-branch-only, artifact upload off and no direct production writes
ok 8 - P1: CI remains manual-branch-only, artifact upload off and no direct production writes
  ---
  duration_ms: 0.500695
  type: 'test'
  ...
# Subtest: pinned eas-cli 24.7.0 emits a string versionCode; it is strictly normalized
ok 9 - pinned eas-cli 24.7.0 emits a string versionCode; it is strictly normalized
  ---
  duration_ms: 9.86089
  type: 'test'
  ...
# Subtest: numeric fixture remains backwards compatible
ok 10 - numeric fixture remains backwards compatible
  ---
  duration_ms: 1.012713
  type: 'test'
  ...
# Subtest: rejects empty, malformed, float, negative, oversized and noncanonical version codes
ok 11 - rejects empty, malformed, float, negative, oversized and noncanonical version codes
  ---
  duration_ms: 12.759938
  type: 'test'
  ...
# Subtest: read-only remote operation never contains build dispatch or mutation flags
ok 12 - read-only remote operation never contains build dispatch or mutation flags
  ---
  duration_ms: 1.325125
  type: 'test'
  ...
# Subtest: EAS project:info is a single read-only pinned call from exact Mobile root
ok 13 - EAS project:info is a single read-only pinned call from exact Mobile root
  ---
  duration_ms: 7.510343
  type: 'test'
  ...
# Subtest: project:info tolerates ANSI formatting only and does not invent an Android package
ok 14 - project:info tolerates ANSI formatting only and does not invent an Android package
  ---
  duration_ms: 1.274075
  type: 'test'
  ...
# Subtest: project:info rejects missing, extra and malformed field data
ok 15 - project:info rejects missing, extra and malformed field data
  ---
  duration_ms: 2.776288
  type: 'test'
  ...
# Subtest: project:info failed process blocks independently of successful version endpoint
ok 16 - project:info failed process blocks independently of successful version endpoint
  ---
  duration_ms: 2.462471
  type: 'test'
  ...
# Subtest: preflight accepts real pinned version-only JSON only with separate project identity
ok 17 - preflight accepts real pinned version-only JSON only with separate project identity
  ---
  duration_ms: 1.307896
  type: 'test'
  ...
# Subtest: preflight rejects missing separate identity even when version JSON contains forged metadata
ok 18 - preflight rejects missing separate identity even when version JSON contains forged metadata
  ---
  duration_ms: 0.646875
  type: 'test'
  ...
# Subtest: preflight rejects wrong owner, slug and uuid separately
ok 19 - preflight rejects wrong owner, slug and uuid separately
  ---
  duration_ms: 1.086638
  type: 'test'
  ...
# Subtest: preflight rejects changed version, wrong local package and missing A03-A14/device proofs
ok 20 - preflight rejects changed version, wrong local package and missing A03-A14/device proofs
  ---
  duration_ms: 1.86675
  type: 'test'
  ...
# Subtest: preflight blocks project method returning null without throwing
ok 21 - preflight blocks project method returning null without throwing
  ---
  duration_ms: 0.941012
  type: 'test'
  ...
# Subtest: build preflight separates postbuild A03-device-JDK evidence without asserting it passed
ok 22 - build preflight separates postbuild A03-device-JDK evidence without asserting it passed
  ---
  duration_ms: 6.282112
  type: 'test'
  ...
# Subtest: submit preflight still requires all A03-A14 devices and JDK tools
ok 23 - submit preflight still requires all A03-A14 devices and JDK tools
  ---
  duration_ms: 1.198064
  type: 'test'
  ...
# Subtest: unknown preflight phase fails closed
ok 24 - unknown preflight phase fails closed
  ---
  duration_ms: 0.470112
  type: 'test'
  ...
# Subtest: build-only persists one remote build and never submits
ok 25 - build-only persists one remote build and never submits
  ---
  duration_ms: 31.693011
  type: 'test'
  ...
# Subtest: build-only denies missing or fake owner authorization
ok 26 - build-only denies missing or fake owner authorization
  ---
  duration_ms: 5.152335
  type: 'test'
  ...
# Subtest: build-only refuses Play approval piggyback and does not dispatch
ok 27 - build-only refuses Play approval piggyback and does not dispatch
  ---
  duration_ms: 2.159972
  type: 'test'
  ...
# Subtest: build dispatch unknown never retries from existing journal
ok 28 - build dispatch unknown never retries from existing journal
  ---
  duration_ms: 6.23907
  type: 'test'
  ...
# Subtest: submit-only resumes same verified build, does not dispatch another build
ok 29 - submit-only resumes same verified build, does not dispatch another build
  ---
  duration_ms: 16.454532
  type: 'test'
  ...
# Subtest: submit-only refuses absent distinct Play owner approval
ok 30 - submit-only refuses absent distinct Play owner approval
  ---
  duration_ms: 8.625001
  type: 'test'
  ...
# Subtest: submit-only refuses missing device A03-A14 acceptance before downloading
ok 31 - submit-only refuses missing device A03-A14 acceptance before downloading
  ---
  duration_ms: 8.36711
  type: 'test'
  ...
# Subtest: submit-only refuses altered version code without downloading or submitting
ok 32 - submit-only refuses altered version code without downloading or submitting
  ---
  duration_ms: 7.497141
  type: 'test'
  ...
# Subtest: submit-only refuses unsigned or wrong certificate AAB
ok 33 - submit-only refuses unsigned or wrong certificate AAB
  ---
  duration_ms: 9.655679
  type: 'test'
  ...
# Subtest: submit-only refuses device/runtime absence even after cryptographic signer
ok 34 - submit-only refuses device/runtime absence even after cryptographic signer
  ---
  duration_ms: 7.849246
  type: 'test'
  ...
# Subtest: submit-only cannot retry existing successfully submitted build
ok 35 - submit-only cannot retry existing successfully submitted build
  ---
  duration_ms: 12.452356
  type: 'test'
  ...
# Subtest: submit-only rejects changed sealed AAB without re-downloading
ok 36 - submit-only rejects changed sealed AAB without re-downloading
  ---
  duration_ms: 8.569043
  type: 'test'
  ...
# Subtest: build-only never redispatches after artifact transfer fails
ok 37 - build-only never redispatches after artifact transfer fails
  ---
  duration_ms: 6.801585
  type: 'test'
  ...
# Subtest: legacy one-shot execute stays permanently blocked despite existing authorization
ok 38 - legacy one-shot execute stays permanently blocked despite existing authorization
  ---
  duration_ms: 4.342469
  type: 'test'
  ...
# Subtest: CI trigger restricted to future explicit branch only, no accidental execution
ok 39 - CI trigger restricted to future explicit branch only, no accidental execution
  ---
  duration_ms: 3.217007
  type: 'test'
  ...
# Subtest: large artifact uploader is hard disabled, not controlled by implicit variable
ok 40 - large artifact uploader is hard disabled, not controlled by implicit variable
  ---
  duration_ms: 0.404446
  type: 'test'
  ...
# Subtest: source SHA and release evidence digests survive in job logs without artifact storage
ok 41 - source SHA and release evidence digests survive in job logs without artifact storage
  ---
  duration_ms: 0.258473
  type: 'test'
  ...
# Subtest: cheap offline regressions run before downloading Android SDK components
ok 42 - cheap offline regressions run before downloading Android SDK components
  ---
  duration_ms: 0.254958
  type: 'test'
  ...
# Subtest: existing native real compiler R8 and lint run untouched
ok 43 - existing native real compiler R8 and lint run untouched
  ---
  duration_ms: 0.437567
  type: 'test'
  ...
# Subtest: previous deep validator regression follows new CI branch and keeps direct release blocked
ok 44 - previous deep validator regression follows new CI branch and keeps direct release blocked
  ---
  duration_ms: 0.541561
  type: 'test'
  ...
# Subtest: preflight_zero_remote_mutations_and_no_journal
ok 45 - preflight_zero_remote_mutations_and_no_journal
  ---
  duration_ms: 15.876947
  type: 'test'
  ...
# Subtest: no_execute_without_exact_authorization
ok 46 - no_execute_without_exact_authorization
  ---
  duration_ms: 4.980621
  type: 'test'
  ...
# Subtest: one_build_then_one_submit
ok 47 - one_build_then_one_submit
  ---
  duration_ms: 26.663992
  type: 'test'
  ...
# Subtest: build_error_blocks_submit
ok 48 - build_error_blocks_submit
  ---
  duration_ms: 15.237258
  type: 'test'
  ...
# Subtest: orphan_intent_blocks_dispatch
ok 49 - orphan_intent_blocks_dispatch
  ---
  duration_ms: 8.426515
  type: 'test'
  ...
# Subtest: wrong_artifact_blocks_submit
ok 50 - wrong_artifact_blocks_submit
  ---
  duration_ms: 13.42182
  type: 'test'
  ...
# Subtest: final_sha_or_version_change_blocks_submit
ok 51 - final_sha_or_version_change_blocks_submit
  ---
  duration_ms: 8.000606
  type: 'test'
  ...
# Subtest: submission_unknown_blocks_retry
ok 52 - submission_unknown_blocks_retry
  ---
  duration_ms: 11.002866
  type: 'test'
  ...
# Subtest: redacts_credentials_from_report
ok 53 - redacts_credentials_from_report
  ---
  duration_ms: 9.754693
  type: 'test'
  ...
# Subtest: parallel_simulation_one_dispatch
ok 54 - parallel_simulation_one_dispatch
  ---
  duration_ms: 11.881024
  type: 'test'
  ...
# Subtest: simulate_requires_fake_transport
ok 55 - simulate_requires_fake_transport
  ---
  duration_ms: 2.090533
  type: 'test'
  ...
# Subtest: native_verifier_must_prove_device_and_runtime_receipts
ok 56 - native_verifier_must_prove_device_and_runtime_receipts
  ---
  duration_ms: 7.110094
  type: 'test'
  ...
# Subtest: cannot_assume_validated_authority_for_submit
ok 57 - cannot_assume_validated_authority_for_submit
  ---
  duration_ms: 1.76049
  type: 'test'
  ...
# Subtest: missing_expected_upload_signer_blocks_before_build
ok 58 - missing_expected_upload_signer_blocks_before_build
  ---
  duration_ms: 1.634486
  type: 'test'
  ...
# Subtest: validator is cwd independent, no inherited TypeScript error
ok 59 - validator is cwd independent, no inherited TypeScript error
  ---
  duration_ms: 5.403778
  type: 'test'
  ...
# Subtest: all late validators execute before exactly one terminal summary
ok 60 - all late validators execute before exactly one terminal summary
  ---
  duration_ms: 1.851959
  type: 'test'
  ...
# Subtest: late product image checks match current shared manager, not old local image state
ok 61 - late product image checks match current shared manager, not old local image state
  ---
  duration_ms: 0.779086
  type: 'test'
  ...
# Subtest: five optional EAS build scripts are pinned to canonical node/npm, no latest
not ok 62 - five optional EAS build scripts are pinned to canonical node/npm, no latest
  ---
  duration_ms: 4.574199
  type: 'test'
  location: '/home/icaffeco/ald1n-workspace-v2-audit-M0VH9I/repo/apps/mobile/current/scripts/release25/deep-offline-contract.test.mjs:46:1'
  failureType: 'testCodeFailure'
  error: |-
    Expected values to be strictly equal:
    
    '24.8.0' !== '24.7.0'
    
  code: 'ERR_ASSERTION'
  name: 'AssertionError'
  expected: '24.7.0'
  actual: '24.8.0'
  operator: 'strictEqual'
  stack: |-
    TestContext.<anonymous> (file:///home/icaffeco/ald1n-workspace-v2-audit-M0VH9I/repo/apps/mobile/current/scripts/release25/deep-offline-contract.test.mjs:60:9)
    Test.runInAsyncScope (node:async_hooks:214:14)
    Test.run (node:internal/test_runner/test:1047:25)
    Test.processPendingSubtests (node:internal/test_runner/test:744:18)
    Test.postRun (node:internal/test_runner/test:1173:19)
    Test.run (node:internal/test_runner/test:1101:12)
    async Test.processPendingSubtests (node:internal/test_runner/test:744:7)
  ...
# Subtest: production build and legacy submit remain explicitly fail closed
ok 63 - production build and legacy submit remain explicitly fail closed
  ---
  duration_ms: 0.437132
  type: 'test'
  ...
# Subtest: production Android identity and API remain unchanged
ok 64 - production Android identity and API remain unchanged
  ---
  duration_ms: 0.445103
  type: 'test'
  ...
# Subtest: remote_version_is_read_only
ok 65 - remote_version_is_read_only
  ---
  duration_ms: 4.611743
  type: 'test'
  ...
# Subtest: rejects_latest_and_unpinned_cli
ok 66 - rejects_latest_and_unpinned_cli
  ---
  duration_ms: 1.865467
  type: 'test'
  ...
# Subtest: rejects_mutation_without_authorization
ok 67 - rejects_mutation_without_authorization
  ---
  duration_ms: 0.573343
  type: 'test'
  ...
# Subtest: rejects_zero_rc_bad_json
ok 68 - rejects_zero_rc_bad_json
  ---
  duration_ms: 0.707465
  type: 'test'
  ...
# Subtest: reconciles_unknown_build_without_redispatch
ok 69 - reconciles_unknown_build_without_redispatch
  ---
  duration_ms: 0.923153
  type: 'test'
  ...
# Subtest: build_errored_cannot_submit
ok 70 - build_errored_cannot_submit
  ---
  duration_ms: 1.018304
  type: 'test'
  ...
# Subtest: rejects_foreign_build_id_or_wrong_git_sha
ok 71 - rejects_foreign_build_id_or_wrong_git_sha
  ---
  duration_ms: 0.8169
  type: 'test'
  ...
# Subtest: build_dispatch_has_no_auto_submit_and_exact_intent
ok 72 - build_dispatch_has_no_auto_submit_and_exact_intent
  ---
  duration_ms: 0.755081
  type: 'test'
  ...
# Subtest: artifact_download_refuses_existing_target
ok 73 - artifact_download_refuses_existing_target
  ---
  duration_ms: 7.494237
  type: 'test'
  ...
# Subtest: artifact_download_does_not_delete_foreign_temp_symlink
ok 74 - artifact_download_does_not_delete_foreign_temp_symlink
  ---
  duration_ms: 5.608395
  type: 'test'
  ...
# Subtest: journal_created_private
ok 75 - journal_created_private
  ---
  duration_ms: 22.925237
  type: 'test'
  ...
# Subtest: cannot_overwrite_existing_release
ok 76 - cannot_overwrite_existing_release
  ---
  duration_ms: 6.272546
  type: 'test'
  ...
# Subtest: journal_enforces_expected_old_stage
ok 77 - journal_enforces_expected_old_stage
  ---
  duration_ms: 6.892235
  type: 'test'
  ...
# Subtest: orphan_intent_does_not_retry
ok 78 - orphan_intent_does_not_retry
  ---
  duration_ms: 7.466263
  type: 'test'
  ...
# Subtest: invalid_transition_rejected
ok 79 - invalid_transition_rejected
  ---
  duration_ms: 5.666766
  type: 'test'
  ...
# Subtest: same_state_after_invalid_patch
ok 80 - same_state_after_invalid_patch
  ---
  duration_ms: 4.64802
  type: 'test'
  ...
# Subtest: denies_symlink_state_dir
ok 81 - denies_symlink_state_dir
  ---
  duration_ms: 3.784153
  type: 'test'
  ...
# Subtest: flock_second_process_fails_and_does_not_delete_lock
ok 82 - flock_second_process_fails_and_does_not_delete_lock
  ---
  duration_ms: 38.853367
  type: 'test'
  ...
# Subtest: accepts_exact_read_only_preflight_fixture
ok 83 - accepts_exact_read_only_preflight_fixture
  ---
  duration_ms: 5.551345
  type: 'test'
  ...
# Subtest: blocks_dirty_or_divergent_canonical_index
ok 84 - blocks_dirty_or_divergent_canonical_index
  ---
  duration_ms: 2.331281
  type: 'test'
  ...
# Subtest: allows_only_exact_known_htaccess_drift
ok 85 - allows_only_exact_known_htaccess_drift
  ---
  duration_ms: 0.982578
  type: 'test'
  ...
# Subtest: blocks_missing_native_patch_or_open_release_finding
ok 86 - blocks_missing_native_patch_or_open_release_finding
  ---
  duration_ms: 1.214602
  type: 'test'
  ...
# Subtest: blocks_missing_signing_or_bundletool
ok 87 - blocks_missing_signing_or_bundletool
  ---
  duration_ms: 0.966954
  type: 'test'
  ...
# Subtest: blocks_remote_version_change
ok 88 - blocks_remote_version_change
  ---
  duration_ms: 0.744322
  type: 'test'
  ...
# Subtest: rejects_wrong_project_id_or_api
ok 89 - rejects_wrong_project_id_or_api
  ---
  duration_ms: 0.810691
  type: 'test'
  ...
# Subtest: source_changed_since_preflight_blocks
ok 90 - source_changed_since_preflight_blocks
  ---
  duration_ms: 0.514112
  type: 'test'
  ...
# Subtest: blocking_refuses_unverifiable_device_gate
ok 91 - blocking_refuses_unverifiable_device_gate
  ---
  duration_ms: 1.244011
  type: 'test'
  ...
# Subtest: old_execute_cannot_bypass_acceptance
ok 92 - old_execute_cannot_bypass_acceptance
  ---
  duration_ms: 6.666528
  type: 'test'
  ...
# Subtest: positive_submission_exact_verified_path_once
ok 93 - positive_submission_exact_verified_path_once
  ---
  duration_ms: 13.625989
  type: 'test'
  ...
# Subtest: rejects_build_id_or_sha_mismatch
ok 94 - rejects_build_id_or_sha_mismatch
  ---
  duration_ms: 5.118989
  type: 'test'
  ...
# Subtest: rejects_modified_aab_after_acceptance
ok 95 - rejects_modified_aab_after_acceptance
  ---
  duration_ms: 4.865478
  type: 'test'
  ...
# Subtest: rejects_changed_main_or_play_profile
ok 96 - rejects_changed_main_or_play_profile
  ---
  duration_ms: 7.980793
  type: 'test'
  ...
# Subtest: rejects_submission_without_device_gate
ok 97 - rejects_submission_without_device_gate
  ---
  duration_ms: 3.785052
  type: 'test'
  ...
# Subtest: submit_timeout_never_autoretries
ok 98 - submit_timeout_never_autoretries
  ---
  duration_ms: 4.675891
  type: 'test'
  ...
# Subtest: rejects_file_symlink_or_hardlink
ok 99 - rejects_file_symlink_or_hardlink
  ---
  duration_ms: 2.818908
  type: 'test'
  ...
# Subtest: validator helper and eas pin all match canonical AGENTS authority
not ok 100 - validator helper and eas pin all match canonical AGENTS authority
  ---
  duration_ms: 5.318393
  type: 'test'
  location: '/home/icaffeco/ald1n-workspace-v2-audit-M0VH9I/repo/apps/mobile/current/scripts/release25/validator-pin-recovery.test.mjs:12:1'
  failureType: 'testCodeFailure'
  error: |-
    Expected values to be strictly equal:
    
    '24.8.0' !== '24.7.0'
    
  code: 'ERR_ASSERTION'
  name: 'AssertionError'
  expected: '24.7.0'
  actual: '24.8.0'
  operator: 'strictEqual'
  stack: |-
    TestContext.<anonymous> (file:///home/icaffeco/ald1n-workspace-v2-audit-M0VH9I/repo/apps/mobile/current/scripts/release25/validator-pin-recovery.test.mjs:13:10)
    Test.runInAsyncScope (node:async_hooks:214:14)
    Test.run (node:internal/test_runner/test:1047:25)
    Test.start (node:internal/test_runner/test:944:17)
    startSubtestAfterBootstrap (node:internal/test_runner/harness:296:17)
  ...
# Subtest: legacy direct submit remains disabled and guarded
ok 101 - legacy direct submit remains disabled and guarded
  ---
  duration_ms: 0.453617
  type: 'test'
  ...
# Subtest: TypeScript status only tests syntax failures, no inherited release assertion failure
ok 102 - TypeScript status only tests syntax failures, no inherited release assertion failure
  ---
  duration_ms: 0.30278
  type: 'test'
  ...
# Subtest: passes_complete_fake_tool_fixture
ok 103 - passes_complete_fake_tool_fixture
  ---
  duration_ms: 5.019749
  type: 'test'
  ...
# Subtest: rejects_wrong_manifest_identity_version
ok 104 - rejects_wrong_manifest_identity_version
  ---
  duration_ms: 0.735038
  type: 'test'
  ...
# Subtest: rejects_missing_fourth_abi
ok 105 - rejects_missing_fourth_abi
  ---
  duration_ms: 0.487426
  type: 'test'
  ...
# Subtest: rejects_missing_16k_zip_alignment
ok 106 - rejects_missing_16k_zip_alignment
  ---
  duration_ms: 0.675805
  type: 'test'
  ...
# Subtest: rejects_elf_load_below_16k
ok 107 - rejects_elf_load_below_16k
  ---
  duration_ms: 0.592872
  type: 'test'
  ...
# Subtest: rejects_missing_mapping
ok 108 - rejects_missing_mapping
  ---
  duration_ms: 0.491737
  type: 'test'
  ...
# Subtest: rejects_missing_runtime_evidence
ok 109 - rejects_missing_runtime_evidence
  ---
  duration_ms: 0.569029
  type: 'test'
  ...
# Subtest: rejects_device_receipt_absence
ok 110 - rejects_device_receipt_absence
  ---
  duration_ms: 0.510758
  type: 'test'
  ...
# Subtest: rejects_unknown_build_provenance
ok 111 - rejects_unknown_build_provenance
  ---
  duration_ms: 0.830091
  type: 'test'
  ...
# Subtest: build25_accepts_4k_32bit_ELF_while_keeping_16k_64bit_requirement
ok 112 - build25_accepts_4k_32bit_ELF_while_keeping_16k_64bit_requirement
  ---
  duration_ms: 3.048997
  type: 'test'
  ...
# Subtest: build25_rejects_4k_arm64_even_if_other_ABIs_are_valid
ok 113 - build25_rejects_4k_arm64_even_if_other_ABIs_are_valid
  ---
  duration_ms: 0.834328
  type: 'test'
  ...
# Subtest: build25_rejects_4k_x86_64_even_if_32bit_is_valid
ok 114 - build25_rejects_4k_x86_64_even_if_32bit_is_valid
  ---
  duration_ms: 0.572716
  type: 'test'
  ...
# Subtest: build25_rejects_32bit_ELF_below_4k
ok 115 - build25_rejects_32bit_ELF_below_4k
  ---
  duration_ms: 0.402569
  type: 'test'
  ...
# Subtest: build25_rejects_non_power_of_two_ELF_alignment_in_either_ABI_family
ok 116 - build25_rejects_non_power_of_two_ELF_alignment_in_either_ABI_family
  ---
  duration_ms: 0.48456
  type: 'test'
  ...
# Subtest: build25_mixed_alignment_still_rejects_missing_device_acceptance
ok 117 - build25_mixed_alignment_still_rejects_missing_device_acceptance
  ---
  duration_ms: 0.355965
  type: 'test'
  ...
# Subtest: build25_mixed_alignment_still_rejects_4k_bundle_zip_policy
ok 118 - build25_mixed_alignment_still_rejects_4k_bundle_zip_policy
  ---
  duration_ms: 0.266236
  type: 'test'
  ...
# Subtest: accepts_valid_signed_fixture
not ok 119 - accepts_valid_signed_fixture
  ---
  duration_ms: 2.396477
  type: 'test'
  location: '/home/icaffeco/ald1n-workspace-v2-audit-M0VH9I/repo/apps/mobile/current/scripts/release25/verify-signature.test.mjs:29:1'
  failureType: 'hookFailed'
  error: 'jar failed: undefined'
  code: 'ERR_TEST_FAILURE'
  stack: |-
    sh (file:///home/icaffeco/ald1n-workspace-v2-audit-M0VH9I/repo/apps/mobile/current/scripts/release25/verify-signature.test.mjs:10:107)
    TestContext.<anonymous> (file:///home/icaffeco/ald1n-workspace-v2-audit-M0VH9I/repo/apps/mobile/current/scripts/release25/verify-signature.test.mjs:20:2)
    TestHook.runInAsyncScope (node:async_hooks:214:14)
    TestHook.run (node:internal/test_runner/test:1047:25)
    TestHook.run (node:internal/test_runner/test:1354:18)
    TestHook.run (node:internal/util:580:20)
    Test.createHook (node:internal/test_runner/test:872:33)
    node:internal/test_runner/harness:335:12
    file:///home/icaffeco/ald1n-workspace-v2-audit-M0VH9I/repo/apps/mobile/current/scripts/release25/verify-signature.test.mjs:12:1
    ModuleJob.run (node:internal/modules/esm/module_job:343:25)
  ...
# Subtest: tampered_signed_payload_rejected_even_with_valid_zip_crc
not ok 120 - tampered_signed_payload_rejected_even_with_valid_zip_crc
  ---
  duration_ms: 0.224334
  type: 'test'
  location: '/home/icaffeco/ald1n-workspace-v2-audit-M0VH9I/repo/apps/mobile/current/scripts/release25/verify-signature.test.mjs:33:1'
  failureType: 'hookFailed'
  error: 'jar failed: undefined'
  code: 'ERR_TEST_FAILURE'
  stack: |-
    sh (file:///home/icaffeco/ald1n-workspace-v2-audit-M0VH9I/repo/apps/mobile/current/scripts/release25/verify-signature.test.mjs:10:107)
    TestContext.<anonymous> (file:///home/icaffeco/ald1n-workspace-v2-audit-M0VH9I/repo/apps/mobile/current/scripts/release25/verify-signature.test.mjs:20:2)
    TestHook.runInAsyncScope (node:async_hooks:214:14)
    TestHook.run (node:internal/test_runner/test:1047:25)
    TestHook.run (node:internal/test_runner/test:1354:18)
    TestHook.run (node:internal/util:580:20)
    Test.createHook (node:internal/test_runner/test:872:33)
    node:internal/test_runner/harness:335:12
    file:///home/icaffeco/ald1n-workspace-v2-audit-M0VH9I/repo/apps/mobile/current/scripts/release25/verify-signature.test.mjs:12:1
    ModuleJob.run (node:internal/modules/esm/module_job:343:25)
  ...
# Subtest: rejects_wrong_upload_certificate
not ok 121 - rejects_wrong_upload_certificate
  ---
  duration_ms: 0.423624
  type: 'test'
  location: '/home/icaffeco/ald1n-workspace-v2-audit-M0VH9I/repo/apps/mobile/current/scripts/release25/verify-signature.test.mjs:41:1'
  failureType: 'hookFailed'
  error: 'jar failed: undefined'
  code: 'ERR_TEST_FAILURE'
  stack: |-
    sh (file:///home/icaffeco/ald1n-workspace-v2-audit-M0VH9I/repo/apps/mobile/current/scripts/release25/verify-signature.test.mjs:10:107)
    TestContext.<anonymous> (file:///home/icaffeco/ald1n-workspace-v2-audit-M0VH9I/repo/apps/mobile/current/scripts/release25/verify-signature.test.mjs:20:2)
    TestHook.runInAsyncScope (node:async_hooks:214:14)
    TestHook.run (node:internal/test_runner/test:1047:25)
    TestHook.run (node:internal/test_runner/test:1354:18)
    TestHook.run (node:internal/util:580:20)
    Test.createHook (node:internal/test_runner/test:872:33)
    node:internal/test_runner/harness:335:12
    file:///home/icaffeco/ald1n-workspace-v2-audit-M0VH9I/repo/apps/mobile/current/scripts/release25/verify-signature.test.mjs:12:1
    ModuleJob.run (node:internal/modules/esm/module_job:343:25)
  ...
# Subtest: rejects_unsigned_entries_or_signature_error
not ok 122 - rejects_unsigned_entries_or_signature_error
  ---
  duration_ms: 0.134416
  type: 'test'
  location: '/home/icaffeco/ald1n-workspace-v2-audit-M0VH9I/repo/apps/mobile/current/scripts/release25/verify-signature.test.mjs:45:1'
  failureType: 'hookFailed'
  error: 'jar failed: undefined'
  code: 'ERR_TEST_FAILURE'
  stack: |-
    sh (file:///home/icaffeco/ald1n-workspace-v2-audit-M0VH9I/repo/apps/mobile/current/scripts/release25/verify-signature.test.mjs:10:107)
    TestContext.<anonymous> (file:///home/icaffeco/ald1n-workspace-v2-audit-M0VH9I/repo/apps/mobile/current/scripts/release25/verify-signature.test.mjs:20:2)
    TestHook.runInAsyncScope (node:async_hooks:214:14)
    TestHook.run (node:internal/test_runner/test:1047:25)
    TestHook.run (node:internal/test_runner/test:1354:18)
    TestHook.run (node:internal/util:580:20)
    Test.createHook (node:internal/test_runner/test:872:33)
    node:internal/test_runner/harness:335:12
    file:///home/icaffeco/ald1n-workspace-v2-audit-M0VH9I/repo/apps/mobile/current/scripts/release25/verify-signature.test.mjs:12:1
    ModuleJob.run (node:internal/modules/esm/module_job:343:25)
  ...
# Subtest: rejects_apk_or_empty_bundle
not ok 123 - rejects_apk_or_empty_bundle
  ---
  duration_ms: 0.241438
  type: 'test'
  location: '/home/icaffeco/ald1n-workspace-v2-audit-M0VH9I/repo/apps/mobile/current/scripts/release25/verify-signature.test.mjs:51:1'
  failureType: 'hookFailed'
  error: 'jar failed: undefined'
  code: 'ERR_TEST_FAILURE'
  stack: |-
    sh (file:///home/icaffeco/ald1n-workspace-v2-audit-M0VH9I/repo/apps/mobile/current/scripts/release25/verify-signature.test.mjs:10:107)
    TestContext.<anonymous> (file:///home/icaffeco/ald1n-workspace-v2-audit-M0VH9I/repo/apps/mobile/current/scripts/release25/verify-signature.test.mjs:20:2)
    TestHook.runInAsyncScope (node:async_hooks:214:14)
    TestHook.run (node:internal/test_runner/test:1047:25)
    TestHook.run (node:internal/test_runner/test:1354:18)
    TestHook.run (node:internal/util:580:20)
    Test.createHook (node:internal/test_runner/test:872:33)
    node:internal/test_runner/harness:335:12
    file:///home/icaffeco/ald1n-workspace-v2-audit-M0VH9I/repo/apps/mobile/current/scripts/release25/verify-signature.test.mjs:12:1
    ModuleJob.run (node:internal/modules/esm/module_job:343:25)
  ...
# Subtest: rejects_symlink_and_nonregular_file
not ok 124 - rejects_symlink_and_nonregular_file
  ---
  duration_ms: 0.169167
  type: 'test'
  location: '/home/icaffeco/ald1n-workspace-v2-audit-M0VH9I/repo/apps/mobile/current/scripts/release25/verify-signature.test.mjs:57:1'
  failureType: 'hookFailed'
  error: 'jar failed: undefined'
  code: 'ERR_TEST_FAILURE'
  stack: |-
    sh (file:///home/icaffeco/ald1n-workspace-v2-audit-M0VH9I/repo/apps/mobile/current/scripts/release25/verify-signature.test.mjs:10:107)
    TestContext.<anonymous> (file:///home/icaffeco/ald1n-workspace-v2-audit-M0VH9I/repo/apps/mobile/current/scripts/release25/verify-signature.test.mjs:20:2)
    TestHook.runInAsyncScope (node:async_hooks:214:14)
    TestHook.run (node:internal/test_runner/test:1047:25)
    TestHook.run (node:internal/test_runner/test:1354:18)
    TestHook.run (node:internal/util:580:20)
    Test.createHook (node:internal/test_runner/test:872:33)
    node:internal/test_runner/harness:335:12
    file:///home/icaffeco/ald1n-workspace-v2-audit-M0VH9I/repo/apps/mobile/current/scripts/release25/verify-signature.test.mjs:12:1
    ModuleJob.run (node:internal/modules/esm/module_job:343:25)
  ...
# Subtest: rejects_precomputed_hash_mismatch
not ok 125 - rejects_precomputed_hash_mismatch
  ---
  duration_ms: 0.143005
  type: 'test'
  location: '/home/icaffeco/ald1n-workspace-v2-audit-M0VH9I/repo/apps/mobile/current/scripts/release25/verify-signature.test.mjs:62:1'
  failureType: 'hookFailed'
  error: 'jar failed: undefined'
  code: 'ERR_TEST_FAILURE'
  stack: |-
    sh (file:///home/icaffeco/ald1n-workspace-v2-audit-M0VH9I/repo/apps/mobile/current/scripts/release25/verify-signature.test.mjs:10:107)
    TestContext.<anonymous> (file:///home/icaffeco/ald1n-workspace-v2-audit-M0VH9I/repo/apps/mobile/current/scripts/release25/verify-signature.test.mjs:20:2)
    TestHook.runInAsyncScope (node:async_hooks:214:14)
    TestHook.run (node:internal/test_runner/test:1047:25)
    TestHook.run (node:internal/test_runner/test:1354:18)
    TestHook.run (node:internal/util:580:20)
    Test.createHook (node:internal/test_runner/test:872:33)
    node:internal/test_runner/harness:335:12
    file:///home/icaffeco/ald1n-workspace-v2-audit-M0VH9I/repo/apps/mobile/current/scripts/release25/verify-signature.test.mjs:12:1
    ModuleJob.run (node:internal/modules/esm/module_job:343:25)
  ...
1..125
# tests 125
# suites 0
# pass 116
# fail 9
# cancelled 0
# skipped 0
# todo 0
# duration_ms 611.832895

```

## Final status

BATCH632_CLI_LOCAL_COMPATIBILITY=FAIL

AUDITED_COMMAND_RC=1

TEE_RC=0

EAS_PIN_CHANGED=NO
PRODUCTION_MUTATIONS=NONE
