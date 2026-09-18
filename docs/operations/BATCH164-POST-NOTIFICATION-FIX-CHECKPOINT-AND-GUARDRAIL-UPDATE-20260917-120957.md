# Batch164 - Post notification fix checkpoint and guardrail update
TIMESTAMP=20260917-120957
TERMINAL_OUTPUT_MODE=VERBOSE_LIVE_MIRROR_TO_TERMINAL_AND_REPORT
SCOPE=checkpoint_pending_operation_evidence_and_update_agents_eas_23_2_0_flag_guardrail
APP_SOURCE_CHANGE=NO
OTA_ACTION=NO
BUILD_ACTION=NO
GOOGLE_PLAY_ACTION=NO

============================================================
PREFLIGHT - BIND BATCH163 PASS AUTHORITY
============================================================
REPORT163=/home/icaffeco/ald1n-project/docs/operations/BATCH163-MOBILE-PHYSICAL-NOTIFICATION-ROUTING-ACCEPTANCE-20260917-115856.md
REPORT163_SHA=9bc6b3346a3b2c8000f0ff0db63961cff77f9697c338fd4b190e2cc13c408022
REPORT163_BINDING=PASS
PHYSICAL_NOTIFICATION_ROUTING_ACCEPTANCE=PASS_BOUND

============================================================
PREFLIGHT - REPOSITORY AUTHORITY
============================================================
RUN=git_fetch
COMMAND=git -C /home/icaffeco/ald1n-project fetch origin main
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
RC_git_fetch=0
BRANCH=main
LOCAL_HEAD=277118631d11828162e6d2992dd02d9c1eaddaf6
REMOTE_HEAD=277118631d11828162e6d2992dd02d9c1eaddaf6
RC_PREEXISTING_STAGED_DIFF=0
RC_SOURCE_FIX_ANCESTOR_MAIN=0
HTACCESS_FILE_SHA=d10ecf0d63f9a8e1bd978a440afcbadb34faf1d24eb899bf2582d1e5c0033cef
HTACCESS_DIFF_SHA=8ce9fa55a71768bf711203fcc50be8eec6de26886bbf4852ceb30a97b36958fe
KNOWN_HTACCESS_DRIFT=PASS
CMS_TREE_BEFORE=703f24e38d294911b002268196869d606865abc1
MOBILE_TREE_BEFORE=1a537e41b67f607fbb5b3f23ea48ea8059c6b9c6
API_TREE_BEFORE=a30324349de94b9511e5341887940d2cbf2baa14

============================================================
PREFLIGHT - EXACT PENDING EVIDENCE INVENTORY
============================================================
EVIDENCE=444-MOBILE-DEEP-CLEAN-FINAL-BACKUP-BATCH161-V2-EXPECTED-NONZERO-ERR-TRAP-RECOVERY-20260912-125040.md SHA256=29c72576996d929d389842a339d77d9449db03e25534f0ad8c621ea96e27850c
EVIDENCE=BATCH162-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-20260917-102601.md SHA256=a83179f69e051bb43402c57cf70b23624f65d964ee55988d5be861bbe99d3df6
EVIDENCE=BATCH162-V2-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-RECOVERY-20260917-103311.md SHA256=57ef8440100ade68b564af7c44ae99b8d984125079040e2b0e967498e82e75a9
EVIDENCE=BATCH162-V3-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-RECOVERY-20260917-103843.md SHA256=496f61b4a98bbce5dfe260e59a56e86b198a7353ec651e01670ff5c55708af0c
EVIDENCE=BATCH162-V4-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-EAS-NPM-EXEC-RECOVERY-20260917-104451.md SHA256=4513148cd423552f3f81cd6f8ef3bac812348649b2749948034db49720574827
EVIDENCE=BATCH162-V5-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-UPDATE-VIEW-FLAG-RECOVERY-20260917-114858.md SHA256=19e5163431e4b590005feb690b7ce56e61ad6282d3271b83956418a855f65758
EVIDENCE=BATCH163-MOBILE-PHYSICAL-NOTIFICATION-ROUTING-ACCEPTANCE-20260917-115856.md SHA256=9bc6b3346a3b2c8000f0ff0db63961cff77f9697c338fd4b190e2cc13c408022
RUN=git_status
COMMAND=git -C /home/icaffeco/ald1n-project status --porcelain=v1
 M apps/cms/current/public/.htaccess
?? docs/operations/444-MOBILE-DEEP-CLEAN-FINAL-BACKUP-BATCH161-V2-EXPECTED-NONZERO-ERR-TRAP-RECOVERY-20260912-125040.md
?? docs/operations/BATCH162-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-20260917-102601.md
?? docs/operations/BATCH162-V2-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-RECOVERY-20260917-103311.md
?? docs/operations/BATCH162-V3-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-RECOVERY-20260917-103843.md
?? docs/operations/BATCH162-V4-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-EAS-NPM-EXEC-RECOVERY-20260917-104451.md
?? docs/operations/BATCH162-V5-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-UPDATE-VIEW-FLAG-RECOVERY-20260917-114858.md
?? docs/operations/BATCH163-MOBILE-PHYSICAL-NOTIFICATION-ROUTING-ACCEPTANCE-20260917-115856.md
?? docs/operations/BATCH164-POST-NOTIFICATION-FIX-CHECKPOINT-AND-GUARDRAIL-UPDATE-20260917-120957.md
RC_git_status=0
WORKTREE_ALLOWLIST=PASS

============================================================
UPDATE AGENTS.MD - PROVEN EAS 23.2.0 FLAG POLICY
============================================================
RUN=patch_agents
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /home/icaffeco/.ald1n-batch164-20260917-120957/patch-agents.mjs /home/icaffeco/ald1n-project/AGENTS.md
AGENTS_EAS_FLAG_POLICY=PATCHED
RC_patch_agents=0
RC_AGENTS_POLICY_MARKER=0
RC_AGENTS_UPDATE_VIEW_RULE=0
RC_AGENTS_UPGRADE_AUDIT_RULE=0
RC_AGENTS_PIN_PRESERVED=0
RUN=agents_diff
COMMAND=git -C /home/icaffeco/ald1n-project diff -- AGENTS.md
diff --git a/AGENTS.md b/AGENTS.md
index 924fa48..b1b6a9e 100644
--- a/AGENTS.md
+++ b/AGENTS.md
@@ -153,6 +153,15 @@ Do not execute project-scoped EAS commands from the monorepo root.
 
 Known historical failure prevented by this rule: `eas update:list` failed when launched from the monorepo root even though authentication worked.
 
+### Command-specific flag policy for eas-cli@23.2.0
+
+The flags are not uniform across EAS subcommands. Use the behavior proven on this hosting, not a blanket flag template.
+
+- `eas update:view GROUP_ID --json` is the canonical exact-group read. **Do not add `--non-interactive`**: eas-cli 23.2.0 rejects that flag for `update:view`.
+- `eas update:list ... --json --non-interactive` is proven working from the Mobile root.
+- `eas update ... --environment production --json --non-interactive` and `eas update:republish ... --json --non-interactive` are proven working in the Build17 OTA flow. Expo export may print a warning recommending `CI=1`; a warning alone is not a failed EAS publish. Judge the command by its real return code and returned update JSON.
+- Do not globally install a newer EAS CLI merely because the CLI prints an upgrade notice. Test a new major version through the same pinned npm-exec mechanism in a dedicated read-only compatibility audit first; only then change the canonical pin in this file.
+
 ---
 
 ## 6. OTA / Build policy
@@ -758,6 +767,7 @@ These failures have already happened and are now encoded as permanent guards:
 8. **Assuming a single OpenAPI location** -> topology failure. Active topology is exactly three tracked copies.
 9. **Using `php bin/php-lint.php` as full CMS authority** -> wrong gate. Canonical full gate is `php bin/static-check.php`.
 10. **Restarting recovery from scratch** -> risks duplicate commits/candidates/promotions. Recover from recorded state markers.
+11. **`eas update:view ... --non-interactive` on eas-cli 23.2.0** -> hard CLI failure. Use exact-group `update:view GROUP_ID --json` without `--non-interactive`.
 
 Any new repeated infrastructure failure should be added to this section after its root cause is proven.
 
RC_agents_diff=0
RUN=agents_diff_check
COMMAND=git -C /home/icaffeco/ald1n-project diff --check -- AGENTS.md
RC_agents_diff_check=0
AGENTS_GUARDRAIL_UPDATE=PASS

============================================================
STAGE EXACT CHECKPOINT SCOPE
============================================================
RUN=stage_exact
COMMAND=git -C /home/icaffeco/ald1n-project add -- AGENTS.md docs/operations/444-MOBILE-DEEP-CLEAN-FINAL-BACKUP-BATCH161-V2-EXPECTED-NONZERO-ERR-TRAP-RECOVERY-20260912-125040.md docs/operations/BATCH162-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-20260917-102601.md docs/operations/BATCH162-V2-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-RECOVERY-20260917-103311.md docs/operations/BATCH162-V3-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-RECOVERY-20260917-103843.md docs/operations/BATCH162-V4-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-EAS-NPM-EXEC-RECOVERY-20260917-104451.md docs/operations/BATCH162-V5-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-UPDATE-VIEW-FLAG-RECOVERY-20260917-114858.md docs/operations/BATCH163-MOBILE-PHYSICAL-NOTIFICATION-ROUTING-ACCEPTANCE-20260917-115856.md
RC_stage_exact=0
EXPECTED_STAGED_FILES=8
ACTUAL_STAGED_FILES=8
AGENTS.md
docs/operations/444-MOBILE-DEEP-CLEAN-FINAL-BACKUP-BATCH161-V2-EXPECTED-NONZERO-ERR-TRAP-RECOVERY-20260912-125040.md
docs/operations/BATCH162-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-20260917-102601.md
docs/operations/BATCH162-V2-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-RECOVERY-20260917-103311.md
docs/operations/BATCH162-V3-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-RECOVERY-20260917-103843.md
docs/operations/BATCH162-V4-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-EAS-NPM-EXEC-RECOVERY-20260917-104451.md
docs/operations/BATCH162-V5-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-UPDATE-VIEW-FLAG-RECOVERY-20260917-114858.md
docs/operations/BATCH163-MOBILE-PHYSICAL-NOTIFICATION-ROUTING-ACCEPTANCE-20260917-115856.md
RC_STAGED_SCOPE_COMPARE=0
STAGED_SCOPE=PASS_EXACT_AGENTS_PLUS_SEVEN_REPORTS
RUN=agents_cached_diff_check
COMMAND=git -C /home/icaffeco/ald1n-project diff --cached --check -- AGENTS.md
RC_agents_cached_diff_check=0
docs/operations/444-MOBILE-DEEP-CLEAN-FINAL-BACKUP-BATCH161-V2-EXPECTED-NONZERO-ERR-TRAP-RECOVERY-20260912-125040.md:334: trailing whitespace.
+   INFO  Compiled views cleared successfully.  
docs/operations/444-MOBILE-DEEP-CLEAN-FINAL-BACKUP-BATCH161-V2-EXPECTED-NONZERO-ERR-TRAP-RECOVERY-20260912-125040.md:340: trailing whitespace.
+   INFO  Blade templates cached successfully.  
docs/operations/444-MOBILE-DEEP-CLEAN-FINAL-BACKUP-BATCH161-V2-EXPECTED-NONZERO-ERR-TRAP-RECOVERY-20260912-125040.md:1977: trailing whitespace.
+++⠋ Exporting...⠙ Exporting...[expo-cli] 
docs/operations/444-MOBILE-DEEP-CLEAN-FINAL-BACKUP-BATCH161-V2-EXPECTED-NONZERO-ERR-TRAP-RECOVERY-20260912-125040.md:1979: trailing whitespace.
+++⠋ Exporting...⠙ Exporting...[expo-cli] 
docs/operations/444-MOBILE-DEEP-CLEAN-FINAL-BACKUP-BATCH161-V2-EXPECTED-NONZERO-ERR-TRAP-RECOVERY-20260912-125040.md:1981: trailing whitespace.
+++⠋ Uploading...⠙ Uploading...⠹ Uploading...⠋ Uploading assetmap.json (0 / 11.0 KB)⠸ Uploading...⠙ Uploading assetmap.json (11.0 KB / 11.0 KB)⠼ Uploading...⠹ Uploading assetmap.json (11.0 KB / 11.0 KB)⠴ Uploading...⠸ Uploading assetmap.json (11.0 KB / 11.0 KB)⠦ Uploading...⠼ Uploading assetmap.json (11.0 KB / 11.0 KB)⠧ Uploading...✔ Uploaded assetmap.json 
docs/operations/444-MOBILE-DEEP-CLEAN-FINAL-BACKUP-BATCH161-V2-EXPECTED-NONZERO-ERR-TRAP-RECOVERY-20260912-125040.md:1983: trailing whitespace.
++   INFO  Compiled views cleared successfully.  
docs/operations/444-MOBILE-DEEP-CLEAN-FINAL-BACKUP-BATCH161-V2-EXPECTED-NONZERO-ERR-TRAP-RECOVERY-20260912-125040.md:1985: trailing whitespace.
++   INFO  Blade templates cached successfully.  
docs/operations/BATCH162-V3-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-RECOVERY-20260917-103843.md:1693: trailing whitespace.
+ 
docs/operations/BATCH162-V3-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-RECOVERY-20260917-103843.md:1720: trailing whitespace.
+ 
docs/operations/BATCH162-V3-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-RECOVERY-20260917-103843.md:1746: trailing whitespace.
+ 
docs/operations/BATCH162-V3-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-RECOVERY-20260917-103843.md:1800: trailing whitespace.
+ 
docs/operations/BATCH162-V5-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-UPDATE-VIEW-FLAG-RECOVERY-20260917-114858.md:185: trailing whitespace.
+⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...[expo-cli] 
docs/operations/BATCH162-V5-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-UPDATE-VIEW-FLAG-RECOVERY-20260917-114858.md:204: trailing whitespace.
+⠋ Exporting...[expo-cli] 
docs/operations/BATCH162-V5-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-UPDATE-VIEW-FLAG-RECOVERY-20260917-114858.md:211: trailing whitespace.
+⠋ Exporting...⠙ Exporting...[expo-cli] 
docs/operations/BATCH162-V5-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-UPDATE-VIEW-FLAG-RECOVERY-20260917-114858.md:214: trailing whitespace.
+⠋ Uploading...⠙ Uploading...⠹ Uploading...⠋ Uploading assetmap.json (0 / 11.0 KB)⠸ Uploading...⠙ Uploading assetmap.json (11.0 KB / 11.0 KB)⠼ Uploading...⠹ Uploading assetmap.json (11.0 KB / 11.0 KB)⠴ Uploading...⠸ Uploading assetmap.json (11.0 KB / 11.0 KB)⠦ Uploading...⠼ Uploading assetmap.json (11.0 KB / 11.0 KB)⠧ Uploading...✔ Uploaded assetmap.json 
RC_FULL_STAGED_DIFF_CHECK=2
FULL_STAGED_DIFF_CHECK=NONZERO_ALLOWED_ONLY_FOR_IMMUTABLE_OPERATION_REPORT_EVIDENCE
MUTABLE_AGENTS_DIFF_CHECK=PASS

============================================================
PRECOMMIT REMOTE RACE GUARD
============================================================
RUN=precommit_fetch
COMMAND=git -C /home/icaffeco/ald1n-project fetch origin main
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
RC_precommit_fetch=0
PRECOMMIT_LOCAL_HEAD=277118631d11828162e6d2992dd02d9c1eaddaf6
PRECOMMIT_REMOTE_HEAD=277118631d11828162e6d2992dd02d9c1eaddaf6
REMOTE_RACE_GUARD=PASS

============================================================
COMMIT CHECKPOINT AND GUARDRAIL UPDATE
============================================================
RUN=commit
COMMAND=git -C /home/icaffeco/ald1n-project commit -m docs: checkpoint notification routing fix evidence and EAS guardrails
[main 08705c2] docs: checkpoint notification routing fix evidence and EAS guardrails
 8 files changed, 4838 insertions(+)
 create mode 100644 docs/operations/444-MOBILE-DEEP-CLEAN-FINAL-BACKUP-BATCH161-V2-EXPECTED-NONZERO-ERR-TRAP-RECOVERY-20260912-125040.md
 create mode 100644 docs/operations/BATCH162-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-20260917-102601.md
 create mode 100644 docs/operations/BATCH162-V2-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-RECOVERY-20260917-103311.md
 create mode 100644 docs/operations/BATCH162-V3-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-RECOVERY-20260917-103843.md
 create mode 100644 docs/operations/BATCH162-V4-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-EAS-NPM-EXEC-RECOVERY-20260917-104451.md
 create mode 100644 docs/operations/BATCH162-V5-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-UPDATE-VIEW-FLAG-RECOVERY-20260917-114858.md
 create mode 100644 docs/operations/BATCH163-MOBILE-PHYSICAL-NOTIFICATION-ROUTING-ACCEPTANCE-20260917-115856.md
RC_commit=0
BATCH164_COMMIT=08705c2487a86bca8bea748620f86d87d6aea1c7
BATCH164_PARENT=277118631d11828162e6d2992dd02d9c1eaddaf6
CMS_TREE_AFTER=703f24e38d294911b002268196869d606865abc1
MOBILE_TREE_AFTER=1a537e41b67f607fbb5b3f23ea48ea8059c6b9c6
API_TREE_AFTER=a30324349de94b9511e5341887940d2cbf2baa14
APPLICATION_SOURCE_TREES_IMMUTABLE=PASS

============================================================
PUSH AND VERIFY REMOTE
============================================================
RUN=push
COMMAND=git -C /home/icaffeco/ald1n-project push origin main
To github.com:AldinAga/ald1n-project.git
   2771186..08705c2  main -> main
RC_push=0
RUN=post_push_fetch
COMMAND=git -C /home/icaffeco/ald1n-project fetch origin main
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
RC_post_push_fetch=0
FINAL_LOCAL_HEAD=08705c2487a86bca8bea748620f86d87d6aea1c7
FINAL_REMOTE_HEAD=08705c2487a86bca8bea748620f86d87d6aea1c7
PUSH_REMOTE_SYNC=PASS

============================================================
FINAL COMMIT SCOPE VERIFICATION
============================================================
AGENTS.md
docs/operations/444-MOBILE-DEEP-CLEAN-FINAL-BACKUP-BATCH161-V2-EXPECTED-NONZERO-ERR-TRAP-RECOVERY-20260912-125040.md
docs/operations/BATCH162-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-20260917-102601.md
docs/operations/BATCH162-V2-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-RECOVERY-20260917-103311.md
docs/operations/BATCH162-V3-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-RECOVERY-20260917-103843.md
docs/operations/BATCH162-V4-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-EAS-NPM-EXEC-RECOVERY-20260917-104451.md
docs/operations/BATCH162-V5-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-UPDATE-VIEW-FLAG-RECOVERY-20260917-114858.md
docs/operations/BATCH163-MOBILE-PHYSICAL-NOTIFICATION-ROUTING-ACCEPTANCE-20260917-115856.md
RC_COMMIT_SCOPE_COMPARE=0
FINAL_COMMIT_SCOPE=PASS_EXACT_AGENTS_PLUS_SEVEN_REPORTS

============================================================
FINAL WORKTREE RECHECK
============================================================
RUN=final_status
COMMAND=git -C /home/icaffeco/ald1n-project status --porcelain=v1
 M apps/cms/current/public/.htaccess
?? docs/operations/BATCH164-POST-NOTIFICATION-FIX-CHECKPOINT-AND-GUARDRAIL-UPDATE-20260917-120957.md
RC_final_status=0
FINAL_WORKTREE=PASS_ONLY_KNOWN_HTACCESS_AND_CURRENT_REPORT

============================================================
FINAL SUMMARY
============================================================
BATCH164_RESULT=PASS_POST_NOTIFICATION_FIX_CHECKPOINT_AND_GUARDRAIL_UPDATE
FAILED_STAGE=NONE
SOURCE_MUTATION=NO_APP_SOURCE_MUTATION
DOCUMENTATION_MUTATION=YES_AGENTS_AND_OPERATION_EVIDENCE
COMMIT_CREATED=1
BATCH164_COMMIT=08705c2487a86bca8bea748620f86d87d6aea1c7
PUSH_COMPLETED=1
SOURCE_FIX_COMMIT=d73fde25892c984a23486125dd0f516008a782bd
PHYSICAL_ACCEPTANCE=PASS_BATCH163_BOUND
PRODUCTION_OTA_GROUP=9a3774f5-99fe-4008-8c85-2cad8b5c5a2e
OTA_RUNTIME=1.0.0-build17
OTA_MUTATION=NO
BUILD_CREATED=NO
GOOGLE_PLAY_ACTION=NO
AGENTS_EAS_23_2_0_UPDATE_VIEW_RULE=PASS_JSON_ONLY_NO_NON_INTERACTIVE
AGENTS_EAS_MAJOR_UPGRADE_POLICY=PASS_READ_ONLY_AUDIT_BEFORE_PIN_CHANGE
APPLICATION_SOURCE_TREES_IMMUTABLE=PASS
NEXT_ACTION=RUN_BATCH165_EAS_CLI_24_7_0_COMPATIBILITY_AUDIT_READ_ONLY
REPORT_PATH=/home/icaffeco/ald1n-project/docs/operations/BATCH164-POST-NOTIFICATION-FIX-CHECKPOINT-AND-GUARDRAIL-UPDATE-20260917-120957.md
