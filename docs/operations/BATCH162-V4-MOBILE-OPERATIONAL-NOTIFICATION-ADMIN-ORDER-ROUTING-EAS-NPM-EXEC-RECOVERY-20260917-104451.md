# Batch162 V4 - Operational notification admin order routing EAS npm-exec recovery
TIMESTAMP=20260917-104451
TERMINAL_OUTPUT_MODE=VERBOSE_LIVE_MIRROR_TO_TERMINAL_AND_REPORT
BUG=payment_overdue_notification_routes_superadmin_to_customer_order_404
RECOVERY_CAUSE=V3_HARDCODED_OR_PATH_DISCOVERED_EAS_BINARY_WAS_NOT_AVAILABLE
RECOVERY_POLICY=REUSE_VERIFIED_COMMITTED_SOURCE_AND_USE_PINNED_NPM_EXEC_EAS_CLI_23_2_0
SCOPE=release_only_recovery_no_source_reapply_no_build_no_google_play

============================================================
PREFLIGHT - BIND V3 FAILURE AND VERIFIED SOURCE
============================================================
REPORT_V3=/home/icaffeco/ald1n-project/docs/operations/BATCH162-V3-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-RECOVERY-20260917-103843.md
REPORT_V3_SHA=496f61b4a98bbce5dfe260e59a56e86b198a7353ec651e01670ff5c55708af0c
REPORT_V3_BINDING=PASS
SOURCE_GATES_REUSED=TDD_RED_GREEN_SOURCE_CONTRACT_TYPESCRIPT_VALIDATOR_CMS_983_0_DIFF_SCOPE_BUILD17_COMPAT

============================================================
GIT AUTHORITY - EXACT COMMITTED SOURCE, NO REAPPLY
============================================================
RUN=git_fetch
COMMAND=git fetch origin main
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
RC_git_fetch=0
BRANCH=main
LOCAL_HEAD=d73fde25892c984a23486125dd0f516008a782bd
REMOTE_HEAD=d73fde25892c984a23486125dd0f516008a782bd
EXPECTED_PARENT_ACTUAL=bfd6ea5db4f00ebf546a7d2da258b8a49d134762
SOURCE_COMMIT_MESSAGE=fix(notifications): open operational orders in admin detail
HTACCESS_FILE_SHA=d10ecf0d63f9a8e1bd978a440afcbadb34faf1d24eb899bf2582d1e5c0033cef
HTACCESS_DIFF_SHA=8ce9fa55a71768bf711203fcc50be8eec6de26886bbf4852ceb30a97b36958fe
KNOWN_HTACCESS_DRIFT=PASS
SOURCE_COMMIT_SCOPE=PASS_EXACT_THREE_MOBILE_ROUTING_FILES
NO_SOURCE_REAPPLY=PASS

============================================================
EAS AUTHORITY - PINNED NPM EXEC, NO HARDCODED GLOBAL BINARY
============================================================
RUN=eas_version
COMMAND=eas_cli --version
★ eas-cli@24.3.0 is now available.
To upgrade, run:
npm install -g eas-cli
Proceeding with outdated version.

eas-cli/23.2.0 linux-x64 node-v22.23.2
RC_eas_version=0
RUN=eas_whoami
COMMAND=eas_cli whoami
★ eas-cli@24.7.0 is now available.
To upgrade, run:
npm install -g eas-cli
Proceeding with outdated version.

ald1n
pruzljanin@gmail.com

Accounts:
• ald1n (Role: Owner)
• ald1ns-team (Role: Owner)
(node:1496768) [UnparsedCommand] Warning: Command account:view did not parse its arguments. Did you forget to call 'this.parse'?
(Use `node --trace-warnings ...` to show where the warning was created)
RC_eas_whoami=0
EAS_EXECUTION_PATH=PINNED_NPM_EXEC_PACKAGE_eas-cli@23.2.0
ROOT_CAUSE_CONFIRMED=V3_EXPECTED_GLOBAL_EAS_BINARY_INSTEAD_OF_PROVEN_PINNED_NPM_EXEC_PATH
EAS_AUTHORITY=PASS_23.2.0_ACCOUNT_ALD1N
RUN=json_helper_syntax
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node --check /home/icaffeco/.ald1n-batch162-v4-20260917-104451/eas-json-helper.mjs
RC_json_helper_syntax=0
JSON_GROUP_PARSER=PASS_SYNTHETIC

============================================================
CURRENT PRODUCTION AUTHORITY
============================================================
RUN=production_update_list
COMMAND=eas_mobile update:list --branch production --limit 1 --json --non-interactive
★ eas-cli@24.7.0 is now available.
To upgrade, run:
npm install -g eas-cli
Proceeding with outdated version.

{
  "name": "production",
  "id": "019fff34-708f-7613-99e7-c68e72f58ff1",
  "currentPage": [
    {
      "branch": "production",
      "message": "\"Promote Batch157 V2 verified Commission Build17 candidate 792fd7b 20260912-091352\" (5 days ago by ald1n)",
      "runtimeVersion": "1.0.0-build17",
      "isRollBackToEmbedded": false,
      "group": "98e2cb1c-2fb3-4a40-9e4c-c442a99f1eb5",
      "platforms": "android"
    }
  ]
}
RC_production_update_list=0
UPDATE_LIST_MODE=WORKING_FROM_MOBILE_ROOT
CURRENT_PRODUCTION_GROUP_ID=98e2cb1c-2fb3-4a40-9e4c-c442a99f1eb5
RUN=current_production_view
COMMAND=eas_mobile update:view 98e2cb1c-2fb3-4a40-9e4c-c442a99f1eb5 --json --non-interactive
★ eas-cli@24.7.0 is now available.
To upgrade, run:
npm install -g eas-cli
Proceeding with outdated version.

Nonexistent flag: --non-interactive
See more help with --help
    Error: update:view command failed.
RC_current_production_view=1
FAIL_STAGE=CURRENT_PRODUCTION_VIEW

============================================================
FINAL SUMMARY
============================================================
BATCH162_V4_RESULT=FAIL
BATCH162_RESULT=FAIL
FAILED_STAGE=CURRENT_PRODUCTION_VIEW
SOURCE_MUTATION=NO_NEW_SOURCE_MUTATION_V4
SOURCE_COMMIT=d73fde25892c984a23486125dd0f516008a782bd
PUSH_COMPLETED=ALREADY_PASS_FROM_V3
OTA_REQUIRED=YES_BUILD17_COMPATIBLE
CANDIDATE_PUBLISHED=0
CANDIDATE_GROUP_ID=NONE
PRODUCTION_PROMOTED=0
PRODUCTION_GROUP_ID=NONE
BUILD_CREATED=NO
GOOGLE_PLAY_ACTION=NO
NEXT_ACTION=AUTOMATIC_BATCH162_V5_RECOVERY_FROM_THIS_REPORT
REPORT_PATH=/home/icaffeco/ald1n-project/docs/operations/BATCH162-V4-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-EAS-NPM-EXEC-RECOVERY-20260917-104451.md
