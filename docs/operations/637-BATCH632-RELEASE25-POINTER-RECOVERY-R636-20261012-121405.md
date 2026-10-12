# Report 637 - Batch632 Report636 current authority / archive regression recovery and real JDK gate

Script: `/home/icaffeco/ald1n-project/incoming/ald1n-b632-release25-pointer-recovery-v5.sh`

Script SHA-256: `f8d3f085dd1101454397b2c1ce3508e3a85d84fa21c1fc6cfd8621e8a9dc487a`

GitHub main SHA at report reservation: `184f2bfff190dcdbd64d0e88184bc16fd61ce996`

Candidate branch: `audit/batch632-release25-pin-parity-20261012`

Expected candidate SHA: `0efb11e91a557dd478d8501158076dd82871c2b8`

Previous report: `/home/icaffeco/ald1n-project/docs/operations/636-BATCH632-RELEASE25-PIN-RECOVERY-R635-20261012-120300.md`

Previous SHA-256: `17af104f3f445f734a2c018750f47c151a92f4b3b0ddbf3d9edab14adb55cf51`

Execution: 2026-10-12T12:14:05+02:00

## Full terminal output

```text
=== FETCH PIN RECOVERY PR AT EXACT SHA ===
RECOVERY_BRANCH_SHA=0efb11e91a557dd478d8501158076dd82871c2b8
=== EXACT SIX-FILE SOURCE SCOPE ===
PASS: SIX-FILE_DIFF_SCOPE_AND_WHITESPACE
=== NODE / SOURCE ATTESTATION ===
v22.23.3
SOURCE_COMMIT=0efb11e91a557dd478d8501158076dd82871c2b8
=== TARGETED EAS PIN CONTRACT: EXACT SOURCE ===
PASS eas.json canonical 24.8.0
PASS build:android:preview
PASS build:ios:preview
PASS build:ios:production
PASS build:android:development
PASS build:ios:development
PASS submission helper canonical
PASS validator helper assertion canonical
PASS AGENTS authority
PASS Build25 protected production controller
PASS Play submit protected controller
PASS retired pin absent from actual CLI definitions
PASS production channel intact
PIN_PARITY=13/13
=== SOURCE FILE SYNTAX ===
PASS: NODE_SYNTAX
=== SOURCE IMMUTABILITY BASELINE ===
=== TARGETED RELEASE25 PIN PLUS CURRENT/HISTORICAL POINTER REGRESSIONS ===
TAP version 13
# Subtest: P1: real signature crypto regressions run after JDK17 before Android SDK
ok 1 - P1: real signature crypto regressions run after JDK17 before Android SDK
  ---
  duration_ms: 5.464369
  type: 'test'
  ...
# Subtest: P2: current Workspace V2 pointer is authoritative; old Build25 audit source remains historical
ok 2 - P2: current Workspace V2 pointer is authoritative; old Build25 audit source remains historical
  ---
  duration_ms: 0.457098
  type: 'test'
  ...
# Subtest: P2: old Build23 instructions are visibly archived, not live build authority
ok 3 - P2: old Build23 instructions are visibly archived, not live build authority
  ---
  duration_ms: 0.245911
  type: 'test'
  ...
# Subtest: P1: CI remains manual-branch-only, artifact upload off and no direct production writes
ok 4 - P1: CI remains manual-branch-only, artifact upload off and no direct production writes
  ---
  duration_ms: 0.453805
  type: 'test'
  ...
# Subtest: validator is cwd independent, no inherited TypeScript error
ok 5 - validator is cwd independent, no inherited TypeScript error
  ---
  duration_ms: 3.06375
  type: 'test'
  ...
# Subtest: all late validators execute before exactly one terminal summary
ok 6 - all late validators execute before exactly one terminal summary
  ---
  duration_ms: 1.36264
  type: 'test'
  ...
# Subtest: late product image checks match current shared manager, not old local image state
ok 7 - late product image checks match current shared manager, not old local image state
  ---
  duration_ms: 0.590489
  type: 'test'
  ...
# Subtest: five optional EAS build scripts are pinned to canonical node/npm, no latest
ok 8 - five optional EAS build scripts are pinned to canonical node/npm, no latest
  ---
  duration_ms: 0.55611
  type: 'test'
  ...
# Subtest: production build and legacy submit remain explicitly fail closed
ok 9 - production build and legacy submit remain explicitly fail closed
  ---
  duration_ms: 0.277355
  type: 'test'
  ...
# Subtest: production Android identity and API remain unchanged
ok 10 - production Android identity and API remain unchanged
  ---
  duration_ms: 0.236114
  type: 'test'
  ...
# Subtest: validator helper and eas pin all match canonical AGENTS authority
ok 11 - validator helper and eas pin all match canonical AGENTS authority
  ---
  duration_ms: 3.080527
  type: 'test'
  ...
# Subtest: legacy direct submit remains disabled and guarded
ok 12 - legacy direct submit remains disabled and guarded
  ---
  duration_ms: 0.259385
  type: 'test'
  ...
# Subtest: TypeScript status only tests syntax failures, no inherited release assertion failure
ok 13 - TypeScript status only tests syntax failures, no inherited release assertion failure
  ---
  duration_ms: 0.373612
  type: 'test'
  ...
1..13
# tests 13
# suites 0
# pass 13
# fail 0
# cancelled 0
# skipped 0
# todo 0
# duration_ms 291.411012
PASS: TARGETED_POINTER_AND_PIN_TESTS
=== REAL JAVA SIGNATURE TEST PREREQUISITES ===
=== JAVA AND ZIP TOOL AVAILABILITY ===
MISSING java
MISSING jar
MISSING keytool
MISSING jarsigner
FOUND zip /bin/zip
FOUND unzip /bin/unzip
HOLD: real JDK17 jar/keytool/jarsigner or zip/unzip not available; no mock signing allowed
=== NON-CRYPTO RELEASE25 SUITE (SIGNATURE TEST SEPARATELY HELD) ===
NONCRYPTO_TEST_FILES=14
TAP version 13
# Subtest: rejects_conflicting_pins
ok 1 - rejects_conflicting_pins
  ---
  duration_ms: 4.162593
  type: 'test'
  ...
# Subtest: rejects_wrong_production_identity
ok 2 - rejects_wrong_production_identity
  ---
  duration_ms: 0.587177
  type: 'test'
  ...
# Subtest: accepts_consistent_pin
ok 3 - accepts_consistent_pin
  ---
  duration_ms: 0.293264
  type: 'test'
  ...
# Subtest: rejects_missing_version_source
ok 4 - rejects_missing_version_source
  ---
  duration_ms: 0.246682
  type: 'test'
  ...
# Subtest: P1: real signature crypto regressions run after JDK17 before Android SDK
ok 5 - P1: real signature crypto regressions run after JDK17 before Android SDK
  ---
  duration_ms: 5.711178
  type: 'test'
  ...
# Subtest: P2: current Workspace V2 pointer is authoritative; old Build25 audit source remains historical
ok 6 - P2: current Workspace V2 pointer is authoritative; old Build25 audit source remains historical
  ---
  duration_ms: 0.491148
  type: 'test'
  ...
# Subtest: P2: old Build23 instructions are visibly archived, not live build authority
ok 7 - P2: old Build23 instructions are visibly archived, not live build authority
  ---
  duration_ms: 0.244717
  type: 'test'
  ...
# Subtest: P1: CI remains manual-branch-only, artifact upload off and no direct production writes
ok 8 - P1: CI remains manual-branch-only, artifact upload off and no direct production writes
  ---
  duration_ms: 0.461207
  type: 'test'
  ...
# Subtest: pinned eas-cli 24.7.0 emits a string versionCode; it is strictly normalized
ok 9 - pinned eas-cli 24.7.0 emits a string versionCode; it is strictly normalized
  ---
  duration_ms: 13.255324
  type: 'test'
  ...
# Subtest: numeric fixture remains backwards compatible
ok 10 - numeric fixture remains backwards compatible
  ---
  duration_ms: 0.955558
  type: 'test'
  ...
# Subtest: rejects empty, malformed, float, negative, oversized and noncanonical version codes
ok 11 - rejects empty, malformed, float, negative, oversized and noncanonical version codes
  ---
  duration_ms: 10.203962
  type: 'test'
  ...
# Subtest: read-only remote operation never contains build dispatch or mutation flags
ok 12 - read-only remote operation never contains build dispatch or mutation flags
  ---
  duration_ms: 1.225499
  type: 'test'
  ...
# Subtest: EAS project:info is a single read-only pinned call from exact Mobile root
ok 13 - EAS project:info is a single read-only pinned call from exact Mobile root
  ---
  duration_ms: 6.455591
  type: 'test'
  ...
# Subtest: project:info tolerates ANSI formatting only and does not invent an Android package
ok 14 - project:info tolerates ANSI formatting only and does not invent an Android package
  ---
  duration_ms: 1.009108
  type: 'test'
  ...
# Subtest: project:info rejects missing, extra and malformed field data
ok 15 - project:info rejects missing, extra and malformed field data
  ---
  duration_ms: 2.732876
  type: 'test'
  ...
# Subtest: project:info failed process blocks independently of successful version endpoint
ok 16 - project:info failed process blocks independently of successful version endpoint
  ---
  duration_ms: 2.243147
  type: 'test'
  ...
# Subtest: preflight accepts real pinned version-only JSON only with separate project identity
ok 17 - preflight accepts real pinned version-only JSON only with separate project identity
  ---
  duration_ms: 1.259738
  type: 'test'
  ...
# Subtest: preflight rejects missing separate identity even when version JSON contains forged metadata
ok 18 - preflight rejects missing separate identity even when version JSON contains forged metadata
  ---
  duration_ms: 0.618887
  type: 'test'
  ...
# Subtest: preflight rejects wrong owner, slug and uuid separately
ok 19 - preflight rejects wrong owner, slug and uuid separately
  ---
  duration_ms: 1.081572
  type: 'test'
  ...
# Subtest: preflight rejects changed version, wrong local package and missing A03-A14/device proofs
ok 20 - preflight rejects changed version, wrong local package and missing A03-A14/device proofs
  ---
  duration_ms: 1.704066
  type: 'test'
  ...
# Subtest: preflight blocks project method returning null without throwing
ok 21 - preflight blocks project method returning null without throwing
  ---
  duration_ms: 0.780957
  type: 'test'
  ...
# Subtest: build preflight separates postbuild A03-device-JDK evidence without asserting it passed
ok 22 - build preflight separates postbuild A03-device-JDK evidence without asserting it passed
  ---
  duration_ms: 4.048355
  type: 'test'
  ...
# Subtest: submit preflight still requires all A03-A14 devices and JDK tools
ok 23 - submit preflight still requires all A03-A14 devices and JDK tools
  ---
  duration_ms: 0.875093
  type: 'test'
  ...
# Subtest: unknown preflight phase fails closed
ok 24 - unknown preflight phase fails closed
  ---
  duration_ms: 0.33344
  type: 'test'
  ...
# Subtest: build-only persists one remote build and never submits
ok 25 - build-only persists one remote build and never submits
  ---
  duration_ms: 29.235042
  type: 'test'
  ...
# Subtest: build-only denies missing or fake owner authorization
ok 26 - build-only denies missing or fake owner authorization
  ---
  duration_ms: 4.435798
  type: 'test'
  ...
# Subtest: build-only refuses Play approval piggyback and does not dispatch
ok 27 - build-only refuses Play approval piggyback and does not dispatch
  ---
  duration_ms: 3.115661
  type: 'test'
  ...
# Subtest: build dispatch unknown never retries from existing journal
ok 28 - build dispatch unknown never retries from existing journal
  ---
  duration_ms: 8.114035
  type: 'test'
  ...
# Subtest: submit-only resumes same verified build, does not dispatch another build
ok 29 - submit-only resumes same verified build, does not dispatch another build
  ---
  duration_ms: 22.272683
  type: 'test'
  ...
# Subtest: submit-only refuses absent distinct Play owner approval
ok 30 - submit-only refuses absent distinct Play owner approval
  ---
  duration_ms: 8.40442
  type: 'test'
  ...
# Subtest: submit-only refuses missing device A03-A14 acceptance before downloading
ok 31 - submit-only refuses missing device A03-A14 acceptance before downloading
  ---
  duration_ms: 7.362591
  type: 'test'
  ...
# Subtest: submit-only refuses altered version code without downloading or submitting
ok 32 - submit-only refuses altered version code without downloading or submitting
  ---
  duration_ms: 6.905659
  type: 'test'
  ...
# Subtest: submit-only refuses unsigned or wrong certificate AAB
ok 33 - submit-only refuses unsigned or wrong certificate AAB
  ---
  duration_ms: 8.679364
  type: 'test'
  ...
# Subtest: submit-only refuses device/runtime absence even after cryptographic signer
ok 34 - submit-only refuses device/runtime absence even after cryptographic signer
  ---
  duration_ms: 6.509779
  type: 'test'
  ...
# Subtest: submit-only cannot retry existing successfully submitted build
ok 35 - submit-only cannot retry existing successfully submitted build
  ---
  duration_ms: 12.245989
  type: 'test'
  ...
# Subtest: submit-only rejects changed sealed AAB without re-downloading
ok 36 - submit-only rejects changed sealed AAB without re-downloading
  ---
  duration_ms: 8.130977
  type: 'test'
  ...
# Subtest: build-only never redispatches after artifact transfer fails
ok 37 - build-only never redispatches after artifact transfer fails
  ---
  duration_ms: 5.338072
  type: 'test'
  ...
# Subtest: legacy one-shot execute stays permanently blocked despite existing authorization
ok 38 - legacy one-shot execute stays permanently blocked despite existing authorization
  ---
  duration_ms: 1.893476
  type: 'test'
  ...
# Subtest: CI trigger restricted to future explicit branch only, no accidental execution
ok 39 - CI trigger restricted to future explicit branch only, no accidental execution
  ---
  duration_ms: 4.618034
  type: 'test'
  ...
# Subtest: large artifact uploader is hard disabled, not controlled by implicit variable
ok 40 - large artifact uploader is hard disabled, not controlled by implicit variable
  ---
  duration_ms: 0.613353
  type: 'test'
  ...
# Subtest: source SHA and release evidence digests survive in job logs without artifact storage
ok 41 - source SHA and release evidence digests survive in job logs without artifact storage
  ---
  duration_ms: 0.331292
  type: 'test'
  ...
# Subtest: cheap offline regressions run before downloading Android SDK components
ok 42 - cheap offline regressions run before downloading Android SDK components
  ---
  duration_ms: 0.313855
  type: 'test'
  ...
# Subtest: existing native real compiler R8 and lint run untouched
ok 43 - existing native real compiler R8 and lint run untouched
  ---
  duration_ms: 0.52504
  type: 'test'
  ...
# Subtest: previous deep validator regression follows new CI branch and keeps direct release blocked
ok 44 - previous deep validator regression follows new CI branch and keeps direct release blocked
  ---
  duration_ms: 0.598907
  type: 'test'
  ...
# Subtest: preflight_zero_remote_mutations_and_no_journal
ok 45 - preflight_zero_remote_mutations_and_no_journal
  ---
  duration_ms: 10.010023
  type: 'test'
  ...
# Subtest: no_execute_without_exact_authorization
ok 46 - no_execute_without_exact_authorization
  ---
  duration_ms: 3.57859
  type: 'test'
  ...
# Subtest: one_build_then_one_submit
ok 47 - one_build_then_one_submit
  ---
  duration_ms: 18.966595
  type: 'test'
  ...
# Subtest: build_error_blocks_submit
ok 48 - build_error_blocks_submit
  ---
  duration_ms: 7.574834
  type: 'test'
  ...
# Subtest: orphan_intent_blocks_dispatch
ok 49 - orphan_intent_blocks_dispatch
  ---
  duration_ms: 5.980813
  type: 'test'
  ...
# Subtest: wrong_artifact_blocks_submit
ok 50 - wrong_artifact_blocks_submit
  ---
  duration_ms: 13.015056
  type: 'test'
  ...
# Subtest: final_sha_or_version_change_blocks_submit
ok 51 - final_sha_or_version_change_blocks_submit
  ---
  duration_ms: 9.168192
  type: 'test'
  ...
# Subtest: submission_unknown_blocks_retry
ok 52 - submission_unknown_blocks_retry
  ---
  duration_ms: 10.121298
  type: 'test'
  ...
# Subtest: redacts_credentials_from_report
ok 53 - redacts_credentials_from_report
  ---
  duration_ms: 9.643008
  type: 'test'
  ...
# Subtest: parallel_simulation_one_dispatch
ok 54 - parallel_simulation_one_dispatch
  ---
  duration_ms: 9.687139
  type: 'test'
  ...
# Subtest: simulate_requires_fake_transport
ok 55 - simulate_requires_fake_transport
  ---
  duration_ms: 1.786607
  type: 'test'
  ...
# Subtest: native_verifier_must_prove_device_and_runtime_receipts
ok 56 - native_verifier_must_prove_device_and_runtime_receipts
  ---
  duration_ms: 7.357433
  type: 'test'
  ...
# Subtest: cannot_assume_validated_authority_for_submit
ok 57 - cannot_assume_validated_authority_for_submit
  ---
  duration_ms: 1.633694
  type: 'test'
  ...
# Subtest: missing_expected_upload_signer_blocks_before_build
ok 58 - missing_expected_upload_signer_blocks_before_build
  ---
  duration_ms: 1.367006
  type: 'test'
  ...
# Subtest: validator is cwd independent, no inherited TypeScript error
ok 59 - validator is cwd independent, no inherited TypeScript error
  ---
  duration_ms: 3.799788
  type: 'test'
  ...
# Subtest: all late validators execute before exactly one terminal summary
ok 60 - all late validators execute before exactly one terminal summary
  ---
  duration_ms: 1.618449
  type: 'test'
  ...
# Subtest: late product image checks match current shared manager, not old local image state
ok 61 - late product image checks match current shared manager, not old local image state
  ---
  duration_ms: 0.693156
  type: 'test'
  ...
# Subtest: five optional EAS build scripts are pinned to canonical node/npm, no latest
ok 62 - five optional EAS build scripts are pinned to canonical node/npm, no latest
  ---
  duration_ms: 0.765808
  type: 'test'
  ...
# Subtest: production build and legacy submit remain explicitly fail closed
ok 63 - production build and legacy submit remain explicitly fail closed
  ---
  duration_ms: 0.373313
  type: 'test'
  ...
# Subtest: production Android identity and API remain unchanged
ok 64 - production Android identity and API remain unchanged
  ---
  duration_ms: 0.358485
  type: 'test'
  ...
# Subtest: remote_version_is_read_only
ok 65 - remote_version_is_read_only
  ---
  duration_ms: 4.53648
  type: 'test'
  ...
# Subtest: rejects_latest_and_unpinned_cli
ok 66 - rejects_latest_and_unpinned_cli
  ---
  duration_ms: 1.911386
  type: 'test'
  ...
# Subtest: rejects_mutation_without_authorization
ok 67 - rejects_mutation_without_authorization
  ---
  duration_ms: 0.730005
  type: 'test'
  ...
# Subtest: rejects_zero_rc_bad_json
ok 68 - rejects_zero_rc_bad_json
  ---
  duration_ms: 0.761515
  type: 'test'
  ...
# Subtest: reconciles_unknown_build_without_redispatch
ok 69 - reconciles_unknown_build_without_redispatch
  ---
  duration_ms: 0.95902
  type: 'test'
  ...
# Subtest: build_errored_cannot_submit
ok 70 - build_errored_cannot_submit
  ---
  duration_ms: 1.083883
  type: 'test'
  ...
# Subtest: rejects_foreign_build_id_or_wrong_git_sha
ok 71 - rejects_foreign_build_id_or_wrong_git_sha
  ---
  duration_ms: 1.658768
  type: 'test'
  ...
# Subtest: build_dispatch_has_no_auto_submit_and_exact_intent
ok 72 - build_dispatch_has_no_auto_submit_and_exact_intent
  ---
  duration_ms: 1.244407
  type: 'test'
  ...
# Subtest: artifact_download_refuses_existing_target
ok 73 - artifact_download_refuses_existing_target
  ---
  duration_ms: 7.482878
  type: 'test'
  ...
# Subtest: artifact_download_does_not_delete_foreign_temp_symlink
ok 74 - artifact_download_does_not_delete_foreign_temp_symlink
  ---
  duration_ms: 4.079662
  type: 'test'
  ...
# Subtest: journal_created_private
ok 75 - journal_created_private
  ---
  duration_ms: 14.73288
  type: 'test'
  ...
# Subtest: cannot_overwrite_existing_release
ok 76 - cannot_overwrite_existing_release
  ---
  duration_ms: 5.915024
  type: 'test'
  ...
# Subtest: journal_enforces_expected_old_stage
ok 77 - journal_enforces_expected_old_stage
  ---
  duration_ms: 6.177651
  type: 'test'
  ...
# Subtest: orphan_intent_does_not_retry
ok 78 - orphan_intent_does_not_retry
  ---
  duration_ms: 5.116646
  type: 'test'
  ...
# Subtest: invalid_transition_rejected
ok 79 - invalid_transition_rejected
  ---
  duration_ms: 7.307885
  type: 'test'
  ...
# Subtest: same_state_after_invalid_patch
ok 80 - same_state_after_invalid_patch
  ---
  duration_ms: 3.349051
  type: 'test'
  ...
# Subtest: denies_symlink_state_dir
ok 81 - denies_symlink_state_dir
  ---
  duration_ms: 3.279745
  type: 'test'
  ...
# Subtest: flock_second_process_fails_and_does_not_delete_lock
ok 82 - flock_second_process_fails_and_does_not_delete_lock
  ---
  duration_ms: 23.942723
  type: 'test'
  ...
# Subtest: accepts_exact_read_only_preflight_fixture
ok 83 - accepts_exact_read_only_preflight_fixture
  ---
  duration_ms: 7.348135
  type: 'test'
  ...
# Subtest: blocks_dirty_or_divergent_canonical_index
ok 84 - blocks_dirty_or_divergent_canonical_index
  ---
  duration_ms: 1.977442
  type: 'test'
  ...
# Subtest: allows_only_exact_known_htaccess_drift
ok 85 - allows_only_exact_known_htaccess_drift
  ---
  duration_ms: 1.081166
  type: 'test'
  ...
# Subtest: blocks_missing_native_patch_or_open_release_finding
ok 86 - blocks_missing_native_patch_or_open_release_finding
  ---
  duration_ms: 1.36028
  type: 'test'
  ...
# Subtest: blocks_missing_signing_or_bundletool
ok 87 - blocks_missing_signing_or_bundletool
  ---
  duration_ms: 1.008105
  type: 'test'
  ...
# Subtest: blocks_remote_version_change
ok 88 - blocks_remote_version_change
  ---
  duration_ms: 0.777911
  type: 'test'
  ...
# Subtest: rejects_wrong_project_id_or_api
ok 89 - rejects_wrong_project_id_or_api
  ---
  duration_ms: 0.807468
  type: 'test'
  ...
# Subtest: source_changed_since_preflight_blocks
ok 90 - source_changed_since_preflight_blocks
  ---
  duration_ms: 0.482097
  type: 'test'
  ...
# Subtest: blocking_refuses_unverifiable_device_gate
ok 91 - blocking_refuses_unverifiable_device_gate
  ---
  duration_ms: 1.365178
  type: 'test'
  ...
# Subtest: old_execute_cannot_bypass_acceptance
ok 92 - old_execute_cannot_bypass_acceptance
  ---
  duration_ms: 6.664461
  type: 'test'
  ...
# Subtest: positive_submission_exact_verified_path_once
ok 93 - positive_submission_exact_verified_path_once
  ---
  duration_ms: 14.049309
  type: 'test'
  ...
# Subtest: rejects_build_id_or_sha_mismatch
ok 94 - rejects_build_id_or_sha_mismatch
  ---
  duration_ms: 4.721308
  type: 'test'
  ...
# Subtest: rejects_modified_aab_after_acceptance
ok 95 - rejects_modified_aab_after_acceptance
  ---
  duration_ms: 4.674591
  type: 'test'
  ...
# Subtest: rejects_changed_main_or_play_profile
ok 96 - rejects_changed_main_or_play_profile
  ---
  duration_ms: 7.796758
  type: 'test'
  ...
# Subtest: rejects_submission_without_device_gate
ok 97 - rejects_submission_without_device_gate
  ---
  duration_ms: 3.386283
  type: 'test'
  ...
# Subtest: submit_timeout_never_autoretries
ok 98 - submit_timeout_never_autoretries
  ---
  duration_ms: 3.915437
  type: 'test'
  ...
# Subtest: rejects_file_symlink_or_hardlink
ok 99 - rejects_file_symlink_or_hardlink
  ---
  duration_ms: 3.428976
  type: 'test'
  ...
# Subtest: validator helper and eas pin all match canonical AGENTS authority
ok 100 - validator helper and eas pin all match canonical AGENTS authority
  ---
  duration_ms: 4.193751
  type: 'test'
  ...
# Subtest: legacy direct submit remains disabled and guarded
ok 101 - legacy direct submit remains disabled and guarded
  ---
  duration_ms: 0.31038
  type: 'test'
  ...
# Subtest: TypeScript status only tests syntax failures, no inherited release assertion failure
ok 102 - TypeScript status only tests syntax failures, no inherited release assertion failure
  ---
  duration_ms: 0.419424
  type: 'test'
  ...
# Subtest: passes_complete_fake_tool_fixture
ok 103 - passes_complete_fake_tool_fixture
  ---
  duration_ms: 8.332771
  type: 'test'
  ...
# Subtest: rejects_wrong_manifest_identity_version
ok 104 - rejects_wrong_manifest_identity_version
  ---
  duration_ms: 0.867047
  type: 'test'
  ...
# Subtest: rejects_missing_fourth_abi
ok 105 - rejects_missing_fourth_abi
  ---
  duration_ms: 0.511751
  type: 'test'
  ...
# Subtest: rejects_missing_16k_zip_alignment
ok 106 - rejects_missing_16k_zip_alignment
  ---
  duration_ms: 0.772013
  type: 'test'
  ...
# Subtest: rejects_elf_load_below_16k
ok 107 - rejects_elf_load_below_16k
  ---
  duration_ms: 0.666047
  type: 'test'
  ...
# Subtest: rejects_missing_mapping
ok 108 - rejects_missing_mapping
  ---
  duration_ms: 0.52788
  type: 'test'
  ...
# Subtest: rejects_missing_runtime_evidence
ok 109 - rejects_missing_runtime_evidence
  ---
  duration_ms: 0.670597
  type: 'test'
  ...
# Subtest: rejects_device_receipt_absence
ok 110 - rejects_device_receipt_absence
  ---
  duration_ms: 0.702964
  type: 'test'
  ...
# Subtest: rejects_unknown_build_provenance
ok 111 - rejects_unknown_build_provenance
  ---
  duration_ms: 1.390186
  type: 'test'
  ...
# Subtest: build25_accepts_4k_32bit_ELF_while_keeping_16k_64bit_requirement
ok 112 - build25_accepts_4k_32bit_ELF_while_keeping_16k_64bit_requirement
  ---
  duration_ms: 5.535976
  type: 'test'
  ...
# Subtest: build25_rejects_4k_arm64_even_if_other_ABIs_are_valid
ok 113 - build25_rejects_4k_arm64_even_if_other_ABIs_are_valid
  ---
  duration_ms: 0.879707
  type: 'test'
  ...
# Subtest: build25_rejects_4k_x86_64_even_if_32bit_is_valid
ok 114 - build25_rejects_4k_x86_64_even_if_32bit_is_valid
  ---
  duration_ms: 0.508343
  type: 'test'
  ...
# Subtest: build25_rejects_32bit_ELF_below_4k
ok 115 - build25_rejects_32bit_ELF_below_4k
  ---
  duration_ms: 0.403754
  type: 'test'
  ...
# Subtest: build25_rejects_non_power_of_two_ELF_alignment_in_either_ABI_family
ok 116 - build25_rejects_non_power_of_two_ELF_alignment_in_either_ABI_family
  ---
  duration_ms: 0.490726
  type: 'test'
  ...
# Subtest: build25_mixed_alignment_still_rejects_missing_device_acceptance
ok 117 - build25_mixed_alignment_still_rejects_missing_device_acceptance
  ---
  duration_ms: 0.370011
  type: 'test'
  ...
# Subtest: build25_mixed_alignment_still_rejects_4k_bundle_zip_policy
ok 118 - build25_mixed_alignment_still_rejects_4k_bundle_zip_policy
  ---
  duration_ms: 0.267114
  type: 'test'
  ...
1..118
# tests 118
# suites 0
# pass 118
# fail 0
# cancelled 0
# skipped 0
# todo 0
# duration_ms 509.861615
PASS: NONCRYPTO_RELEASE25_SUITE
HOLD: FULL_RELEASE25_SIGNATURE_SUITE_NOT_RUN
PASS: CANDIDATE_SOURCE_IMMUTABLE
PRODUCTION_MUTATIONS=NONE
EAS_PIN_UPGRADE=NO

```

## Final status

BATCH632_RELEASE25_PIN_RECOVERY=HOLD

AUDITED_COMMAND_RC=2

TEE_RC=0

RECOVERY_PR=5_DRAFT
PREVIOUS_REPORT_SHA256=17af104f3f445f734a2c018750f47c151a92f4b3b0ddbf3d9edab14adb55cf51
EAS_PIN_UPGRADE=NO
PRODUCTION_MUTATIONS=NONE
