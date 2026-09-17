
============================================================
BATCH162 V5 - UPDATE:VIEW FLAG RECOVERY
============================================================
TIMESTAMP=20260917-114858
TERMINAL_OUTPUT_MODE=VERBOSE_LIVE_MIRROR_TO_TERMINAL_AND_REPORT
BUG=payment_overdue_notification_routes_superadmin_to_customer_order_404
RECOVERY_CAUSE=EAS_CLI_23_2_0_UPDATE_VIEW_REJECTS_NON_INTERACTIVE_FLAG
RECOVERY_POLICY=REUSE_VERIFIED_SOURCE_REMOVE_NON_INTERACTIVE_ONLY_FROM_UPDATE_VIEW_AND_CONTINUE_OTA
SCOPE=release_only_recovery_no_source_reapply_no_build_no_google_play

============================================================
PREFLIGHT - BIND V4 FAILURE
============================================================
REPORT_V4=/home/icaffeco/ald1n-project/docs/operations/BATCH162-V4-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-EAS-NPM-EXEC-RECOVERY-20260917-104451.md
REPORT_V4_SHA=4513148cd423552f3f81cd6f8ef3bac812348649b2749948034db49720574827
REPORT_V4_BINDING=PASS
ROOT_CAUSE_CONFIRMED=UPDATE_VIEW_DOES_NOT_ACCEPT_NON_INTERACTIVE_IN_EAS_CLI_23_2_0
V4_RELEASE_STATE=NO_CANDIDATE_NO_PRODUCTION_MUTATION

============================================================
GIT AUTHORITY - SAFE DOCS-ONLY FAST-FORWARD AFTER DIRECT GITHUB AGENTS COMMIT
============================================================
RUN=git_fetch
COMMAND=git -C /home/icaffeco/ald1n-project fetch origin main
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
   d73fde2..2771186  main       -> origin/main
RC_git_fetch=0
BRANCH=main
LOCAL_HEAD=d73fde25892c984a23486125dd0f516008a782bd
REMOTE_HEAD=277118631d11828162e6d2992dd02d9c1eaddaf6
PRE_SYNC_STAGED_COUNT=0
RC_SOURCE_FIX_ANCESTOR_REMOTE=0
REMOTE_PARENT=d73fde25892c984a23486125dd0f516008a782bd
REMOTE_MESSAGE=docs: add Ald1n hosting and batch guardrails
AGENTS.md
RUN=fast_forward
COMMAND=git -C /home/icaffeco/ald1n-project merge --ff-only origin/main
Updating d73fde2..2771186
Fast-forward
 AGENTS.md | 797 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
 1 file changed, 797 insertions(+)
 create mode 100644 AGENTS.md
RC_fast_forward=0
MAIN_HEAD_AFTER_SYNC=277118631d11828162e6d2992dd02d9c1eaddaf6
RC_SOURCE_FIX_ANCESTOR_MAIN=0
RC_MOBILE_TREE_EQUAL_SOURCE_FIX=0
MOBILE_TREE_EQUAL_TO_FIX_SOURCE=PASS
SOURCE_FIX_MESSAGE=fix(notifications): open operational orders in admin detail
SOURCE_FIX_PARENT=bfd6ea5db4f00ebf546a7d2da258b8a49d134762
apps/mobile/current/src/app/(app)/(tabs)/notifications.tsx
apps/mobile/current/src/features/notifications/notification-routing.ts
apps/mobile/current/src/features/notifications/push-notification-bridge.tsx
SOURCE_FIX_SCOPE=PASS_EXACT_THREE_MOBILE_ROUTING_FILES
HTACCESS_FILE_SHA=d10ecf0d63f9a8e1bd978a440afcbadb34faf1d24eb899bf2582d1e5c0033cef
HTACCESS_DIFF_SHA=8ce9fa55a71768bf711203fcc50be8eec6de26886bbf4852ceb30a97b36958fe
KNOWN_HTACCESS_DRIFT=PASS

============================================================
EAS AUTHORITY - PINNED NPM EXEC
============================================================
RUN=eas_version
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@23.2.0 -- eas --version
★ eas-cli@24.7.0 is now available.
To upgrade, run:
npm install -g eas-cli
Proceeding with outdated version.

eas-cli/23.2.0 linux-x64 node-v22.23.2
RC_eas_version=0
RUN=eas_whoami
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@23.2.0 -- eas whoami
★ eas-cli@24.7.0 is now available.
To upgrade, run:
npm install -g eas-cli
Proceeding with outdated version.

ald1n
pruzljanin@gmail.com

Accounts:
• ald1n (Role: Owner)
• ald1ns-team (Role: Owner)
(node:1727506) [UnparsedCommand] Warning: Command account:view did not parse its arguments. Did you forget to call 'this.parse'?
(Use `node --trace-warnings ...` to show where the warning was created)
RC_eas_whoami=0
EAS_EXECUTION_PATH=PINNED_NPM_EXEC_PACKAGE_eas-cli@23.2.0
EAS_PROJECT_COMMAND_CWD=/home/icaffeco/ald1n-project/apps/mobile/current
RUN=json_helper_syntax
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node --check /home/icaffeco/.ald1n-batch162-v5-20260917-114858/eas-json-helper.mjs
RC_json_helper_syntax=0
RUN=json_helper_synthetic
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /home/icaffeco/.ald1n-batch162-v5-20260917-114858/eas-json-helper.mjs verify-view /home/icaffeco/.ald1n-batch162-v5-20260917-114858/synthetic.json test-group 1.0.0-build17 277118631d11828162e6d2992dd02d9c1eaddaf6 Batch162 V5
VIEW_GROUP=test-group
VIEW_RUNTIME=1.0.0-build17
VIEW_PLATFORM=android
VIEW_COMMITS=277118631d11828162e6d2992dd02d9c1eaddaf6
VIEW_MESSAGES=Batch162 V5 synthetic
RC_json_helper_synthetic=0
JSON_GROUP_PARSER=PASS_SYNTHETIC
UPDATE_VIEW_FLAG_POLICY=JSON_ONLY_NO_NON_INTERACTIVE_FOR_EAS_CLI_23_2_0

============================================================
CURRENT PRODUCTION AUTHORITY
============================================================
RUN=production_update_list
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@23.2.0 -- eas update:list --branch production --limit 1 --json --non-interactive
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
RC_production_update_list_stdout_tee=0
RC_production_update_list_stderr_tee=0
CURRENT_PRODUCTION_GROUP_ID=98e2cb1c-2fb3-4a40-9e4c-c442a99f1eb5
RC_current_group_parse=0
RUN=current_production_view
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@23.2.0 -- eas update:view 98e2cb1c-2fb3-4a40-9e4c-c442a99f1eb5 --json
★ eas-cli@24.7.0 is now available.
To upgrade, run:
npm install -g eas-cli
Proceeding with outdated version.

[
  {
    "id": "01a09479-5ffe-7a3f-ad7b-b6d0e995a840",
    "createdAt": "2026-09-12T07:16:17.534Z",
    "group": "98e2cb1c-2fb3-4a40-9e4c-c442a99f1eb5",
    "branch": "production",
    "message": "Promote Batch157 V2 verified Commission Build17 candidate 792fd7b 20260912-091352",
    "runtimeVersion": "1.0.0-build17",
    "platform": "android",
    "manifestPermalink": "https://u.expo.dev/update/01a09479-5ffe-7a3f-ad7b-b6d0e995a840",
    "isRollBackToEmbedded": false,
    "gitCommitHash": "792fd7bb1ab07ec632406d528b42dc1598a92539"
  }
]
RC_current_production_view=0
RC_current_production_view_stdout_tee=0
RC_current_production_view_stderr_tee=0
RUN=current_production_view_verify
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /home/icaffeco/.ald1n-batch162-v5-20260917-114858/eas-json-helper.mjs verify-view /home/icaffeco/.ald1n-batch162-v5-20260917-114858/current-production-view.json 98e2cb1c-2fb3-4a40-9e4c-c442a99f1eb5 1.0.0-build17 - -
VIEW_GROUP=98e2cb1c-2fb3-4a40-9e4c-c442a99f1eb5
VIEW_RUNTIME=1.0.0-build17
VIEW_PLATFORM=android
VIEW_COMMITS=792fd7bb1ab07ec632406d528b42dc1598a92539
VIEW_MESSAGES=Promote Batch157 V2 verified Commission Build17 candidate 792fd7b 20260912-091352
RC_current_production_view_verify=0
CURRENT_PRODUCTION_VIEW=PASS_WITHOUT_NON_INTERACTIVE

============================================================
PUBLISH BUILD17-COMPATIBLE CANDIDATE
============================================================
RUN=candidate_publish
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@23.2.0 -- eas update --branch batch162-notification-admin-order-routing-d73fde2 --platform android --environment production --message Batch162 V5 notification admin-order routing candidate fix d73fde2 main 2771186 20260917-114858 --json --non-interactive
Environment variables with visibility "Plain text" and "Sensitive" loaded from the "production" environment on EAS: EXPO_PUBLIC_API_URL, EXPO_PUBLIC_APP_ENV.

⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...[expo-cli] --non-interactive is not supported, use $CI=1 instead
⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...[expo-cli] Using src/app as the root directory for Expo Router.
⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...[expo-cli] Starting Metro Bundler
⠋ Exporting...[expo-cli] warning: Bundler cache is empty, rebuilding (this may take a minute)
⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...[expo-cli] Android Bundled 55880ms node_modules/expo-router/entry.js (2726 modules)
⠋ Exporting...[expo-cli] Creating asset map
⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...[expo-cli] 
⠋ Exporting...[expo-cli] › Assets (29):
⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...[expo-cli] assets/icon.png (63KB)
⠋ Exporting...[expo-cli] node_modules/@expo-google-fonts/material-symbols/400Regular/MaterialSymbols_400Regular.ttf (962KB)
⠋ Exporting...[expo-cli] node_modules/@expo-google-fonts/material-symbols/500Medium/MaterialSymbols_500Medium.ttf (963KB)
⠋ Exporting...[expo-cli] node_modules/expo-router/assets/arrow_down.png (9.5KB)
⠋ Exporting...[expo-cli] node_modules/expo-router/assets/arrow_right.xml (307B)
⠋ Exporting...[expo-cli] node_modules/expo-router/assets/checkmark.xml (312B)
⠋ Exporting...[expo-cli] node_modules/expo-router/assets/error.png (469B)
⠋ Exporting...[expo-cli] node_modules/expo-router/assets/file.png (138B)
⠋ Exporting...[expo-cli] node_modules/expo-router/assets/forward.png (188B)
⠋ Exporting...[expo-cli] node_modules/expo-router/assets/pkg.png (364B)
⠋ Exporting...[expo-cli] node_modules/expo-router/assets/react-navigation/elements/back-icon-mask.png (653B)
⠋ Exporting...[expo-cli] node_modules/expo-router/assets/react-navigation/elements/back-icon.png (4 variations | 152B)
⠋ Exporting...[expo-cli] node_modules/expo-router/assets/react-navigation/elements/clear-icon.png (4 variations | 425B)
⠋ Exporting...[expo-cli] node_modules/expo-router/assets/react-navigation/elements/close-icon.png (4 variations | 235B)
⠋ Exporting...[expo-cli] node_modules/expo-router/assets/react-navigation/elements/search-icon.png (4 variations | 599B)
⠋ Exporting...[expo-cli] node_modules/expo-router/assets/sitemap.png (465B)
⠋ Exporting...[expo-cli] node_modules/expo-router/assets/unmatched.png (4.8KB)
⠋ Exporting...[expo-cli] 
⠋ Exporting...[expo-cli] › android bundles (2):
⠋ Exporting...[expo-cli] _expo/static/js/android/entry-4975e6736b20231e4ae6fdc40f28bce0.hbc (6.3MB)
⠋ Exporting...[expo-cli] _expo/static/js/android/entry-4975e6736b20231e4ae6fdc40f28bce0.hbc.map (14MB)
⠋ Exporting...[expo-cli] › Files (2):
⠋ Exporting...[expo-cli] assetmap.json (11KB)
⠋ Exporting...[expo-cli] metadata.json (2KB)
⠋ Exporting...⠙ Exporting...[expo-cli] 
⠋ Exporting...[expo-cli] Exported: dist
⠋ Exporting...⠙ Exporting...⠹ Exporting...✔ Exported bundle(s)
⠋ Uploading...⠙ Uploading...⠹ Uploading...⠋ Uploading assetmap.json (0 / 11.0 KB)⠸ Uploading...⠙ Uploading assetmap.json (11.0 KB / 11.0 KB)⠼ Uploading...⠹ Uploading assetmap.json (11.0 KB / 11.0 KB)⠴ Uploading...⠸ Uploading assetmap.json (11.0 KB / 11.0 KB)⠦ Uploading...⠼ Uploading assetmap.json (11.0 KB / 11.0 KB)⠧ Uploading...✔ Uploaded assetmap.json 
⠇ Uploading...⠏ Uploading...⠋ Uploading...⠙ Uploading (0/30)⠹ Uploading (0/30)⠸ Uploading (29/30)⠼ Uploading (29/30)⠴ Uploading (29/30)⠦ Uploading (29/30)⠧ Uploading (29/30)⠇ Uploading (29/30)⠏ Uploading (29/30)⠋ Uploading (29/30)⠙ Uploading (29/30)⠹ Uploading (29/30)⠸ Uploading (29/30)⠼ Uploading (29/30)⠴ Uploading (29/30)⠦ Uploading (29/30)⠧ Uploading (29/30)⠇ Uploading (29/30)⠏ Uploading (29/30)⠋ Uploading (29/30)⠙ Uploading (29/30)⠹ Uploading (29/30)⠸ Uploading (29/30)⠼ Uploading (29/30)⠴ Uploading (29/30)⠦ Uploading (29/30)⠧ Uploading (29/30)⠇ Uploading (29/30)⠏ Uploading (29/30)⠋ Uploading (29/30)⠙ Uploading (29/30)⠹ Uploading (29/30)⠸ Uploading (29/30)⠼ Uploading (29/30)⠴ Uploading (29/30)⠦ Uploading (29/30)⠧ Uploading (29/30)⠇ Uploading (29/30)⠏ Uploading (29/30)⠋ Uploading (29/30)⠙ Uploading (29/30)⠹ Uploading (29/30)⠸ Uploading (29/30)⠼ Uploading (29/30)⠴ Uploading (29/30)⠦ Uploading (29/30)⠧ Uploading (29/30)⠇ Uploading (29/30)⠏ Uploading (29/30)⠋ Uploading (29/30)⠙ Uploading (29/30)⠹ Uploading (29/30)⠸ Uploading (29/30)⠼ Uploading (29/30)⠴ Uploading (29/30)⠦ Uploading (29/30)⠧ Uploading (29/30)⠇ Uploading (29/30)⠏ Uploading (29/30)⠋ Uploading (29/30)⠙ Uploading (29/30)⠹ Uploading (29/30)⠸ Uploading (30/30)⠼ Uploading (30/30)⠴ Uploading (30/30)⠦ Uploading (30/30)⠧ Uploading (30/30)⠇ Uploading (30/30)⠏ Uploading (30/30)⠋ Uploading (30/30)⠙ Uploading (30/30)⠹ Uploading (30/30)⠸ Uploading (30/30)⠼ Uploading (30/30)⠴ Uploading (30/30)⠦ Uploading (30/30)⠧ Uploading (30/30)⠇ Uploading (30/30)⠏ Uploading (30/30)⠋ Uploading (30/30)⠙ Uploading (30/30)⠹ Uploading (30/30)⠸ Uploading (30/30)⠼ Uploading (30/30)⠴ Uploading (30/30)⠦ Uploading (30/30)⠧ Uploading (30/30)⠇ Uploading (30/30)⠏ Uploading (30/30)⠋ Uploading (30/30)⠙ Uploading (30/30)⠹ Uploading (30/30)⠸ Uploading (30/30)⠼ Uploading (30/30)⠴ Uploading (30/30)⠦ Uploading (30/30)⠧ Uploading (30/30)⠇ Uploading (30/30)⠏ Uploading (30/30)✔ Uploaded 1 app bundle
✔ Uploading assets skipped - no new assets found
ℹ 30 Android assets (maximum: 2000 total per update). Learn more about asset limits: https://expo.fyi/eas-update-asset-limits
⠋ Computing project fingerprints⠙ Computing project fingerprints⠹ Computing project fingerprints⠸ Computing project fingerprints⠼ Computing project fingerprints⠴ Computing project fingerprints⠦ Computing project fingerprints⠧ Computing project fingerprints⠇ Computing project fingerprints⠏ Computing project fingerprints⠋ Computing project fingerprints⠙ Computing project fingerprints⠹ Computing project fingerprints⠸ Computing project fingerprints⠼ Computing project fingerprints⠴ Computing project fingerprints⠦ Computing project fingerprints⠧ Computing project fingerprints⠇ Computing project fingerprints⠏ Computing project fingerprints⠋ Computing project fingerprints⠙ Computing project fingerprints⠹ Computing project fingerprints⠸ Computing project fingerprints⠼ Computing project fingerprints⠴ Computing project fingerprints⠦ Computing project fingerprints⠧ Computing project fingerprints⠇ Computing project fingerprints⠏ Computing project fingerprints⠋ Computing project fingerprints⠙ Computing project fingerprints⠹ Computing project fingerprints⠸ Computing project fingerprints⠼ Computing project fingerprints⠴ Computing project fingerprints⠦ Computing project fingerprints⠧ Computing project fingerprints⠇ Computing project fingerprints⠏ Computing project fingerprints⠋ Computing project fingerprints⠙ Computing project fingerprints⠹ Computing project fingerprints⠸ Computing project fingerprints⠼ Computing project fingerprints⠴ Computing project fingerprints⠦ Computing project fingerprints⠧ Computing project fingerprints⠇ Computing project fingerprints⠏ Computing project fingerprints⠋ Computing project fingerprints⠙ Computing project fingerprints⠹ Computing project fingerprints⠸ Computing project fingerprints⠼ Computing project fingerprints⠴ Computing project fingerprints⠦ Computing project fingerprints⠧ Computing project fingerprints⠇ Computing project fingerprints⠏ Computing project fingerprints⠋ Computing project fingerprints⌛️ Computing the project fingerprints is taking longer than expected...
⠋ Computing project fingerprints⏩ To skip this step, set the environment variable: EAS_SKIP_AUTO_FINGERPRINT=1
⠋ Computing project fingerprints⠙ Computing project fingerprints⠹ Computing project fingerprints⠸ Computing project fingerprints⠼ Computing project fingerprints⠴ Computing project fingerprints⠦ Computing project fingerprints⠧ Computing project fingerprints⠇ Computing project fingerprints⠏ Computing project fingerprints⠋ Computing project fingerprints⠙ Computing project fingerprints⠹ Computing project fingerprints⠸ Computing project fingerprints⠼ Computing project fingerprints⠴ Computing project fingerprints⠦ Computing project fingerprints⠧ Computing project fingerprints⠇ Computing project fingerprints⠏ Computing project fingerprints⠋ Computing project fingerprints⠙ Computing project fingerprints⠹ Computing project fingerprints⠸ Computing project fingerprints⠼ Computing project fingerprints⠴ Computing project fingerprints⠦ Computing project fingerprints✔ Computed project fingerprints
⠋ Publishing...⠙ Publishing...⠹ Publishing...⠸ Publishing...⠼ Publishing...⠴ Publishing...⠦ Publishing...⠧ Publishing...⠇ Publishing...✔ Published!
[
  {
    "id": "01a0aec6-bfb8-71d7-926e-2aa572c028a6",
    "createdAt": "2026-09-17T09:50:55.928Z",
    "group": "2d27620e-875c-4215-95f8-5744aac39ca8",
    "branch": "batch162-notification-admin-order-routing-d73fde2",
    "message": "Batch162 V5 notification admin-order routing candidate fix d73fde2 main 2771186 20260917-114858",
    "runtimeVersion": "1.0.0-build17",
    "platform": "android",
    "manifestPermalink": "https://u.expo.dev/update/01a0aec6-bfb8-71d7-926e-2aa572c028a6",
    "isRollBackToEmbedded": false,
    "gitCommitHash": "277118631d11828162e6d2992dd02d9c1eaddaf6"
  }
]
RC_candidate_publish=0
RC_candidate_publish_stdout_tee=0
RC_candidate_publish_stderr_tee=0
CANDIDATE_GROUP_ID=2d27620e-875c-4215-95f8-5744aac39ca8
RC_candidate_group_parse=0
RUN=candidate_view
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@23.2.0 -- eas update:view 2d27620e-875c-4215-95f8-5744aac39ca8 --json
★ eas-cli@24.7.0 is now available.
To upgrade, run:
npm install -g eas-cli
Proceeding with outdated version.

[
  {
    "id": "01a0aec6-bfb8-71d7-926e-2aa572c028a6",
    "createdAt": "2026-09-17T09:50:55.928Z",
    "group": "2d27620e-875c-4215-95f8-5744aac39ca8",
    "branch": "batch162-notification-admin-order-routing-d73fde2",
    "message": "Batch162 V5 notification admin-order routing candidate fix d73fde2 main 2771186 20260917-114858",
    "runtimeVersion": "1.0.0-build17",
    "platform": "android",
    "manifestPermalink": "https://u.expo.dev/update/01a0aec6-bfb8-71d7-926e-2aa572c028a6",
    "isRollBackToEmbedded": false,
    "gitCommitHash": "277118631d11828162e6d2992dd02d9c1eaddaf6"
  }
]
RC_candidate_view=0
RC_candidate_view_stdout_tee=0
RC_candidate_view_stderr_tee=0
RUN=candidate_view_verify
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /home/icaffeco/.ald1n-batch162-v5-20260917-114858/eas-json-helper.mjs verify-view /home/icaffeco/.ald1n-batch162-v5-20260917-114858/candidate-view.json 2d27620e-875c-4215-95f8-5744aac39ca8 1.0.0-build17 277118631d11828162e6d2992dd02d9c1eaddaf6,d73fde25892c984a23486125dd0f516008a782bd Batch162 V5
VIEW_GROUP=2d27620e-875c-4215-95f8-5744aac39ca8
VIEW_RUNTIME=1.0.0-build17
VIEW_PLATFORM=android
VIEW_COMMITS=277118631d11828162e6d2992dd02d9c1eaddaf6
VIEW_MESSAGES=Batch162 V5 notification admin-order routing candidate fix d73fde2 main 2771186 20260917-114858
RC_candidate_view_verify=0
CANDIDATE_EXACT_GROUP_VERIFIED=PASS

============================================================
RECHECK PRODUCTION BEFORE PROMOTION
============================================================
RUN=prepromote_update_list
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@23.2.0 -- eas update:list --branch production --limit 1 --json --non-interactive
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
RC_prepromote_update_list=0
RC_prepromote_update_list_stdout_tee=0
RC_prepromote_update_list_stderr_tee=0
PREPROMOTE_PRODUCTION_GROUP_ID=98e2cb1c-2fb3-4a40-9e4c-c442a99f1eb5
RC_prepromote_group_parse=0
PRODUCTION_UNCHANGED_BEFORE_PROMOTION=PASS

============================================================
PROMOTE EXACT VERIFIED CANDIDATE TO PRODUCTION
============================================================
RUN=production_republish
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@23.2.0 -- eas update:republish --group 2d27620e-875c-4215-95f8-5744aac39ca8 --destination-channel production --platform android --message Promote Batch162 V5 notification admin-order routing fix d73fde2 20260917-114858 --json --non-interactive
★ eas-cli@24.7.0 is now available.
To upgrade, run:
npm install -g eas-cli
Proceeding with outdated version.

✔ The republished update group will appear only on: android
⠋ Republishing...⠙ Republishing...⠹ Republishing...⠸ Republishing...⠼ Republishing...⠴ Republishing...⠦ Republishing...⠧ Republishing...⠇ Republishing...⠏ Republishing...✔ Republished update group
[
  {
    "id": "01a0aec7-188f-78d1-8ad5-ea9a03fc1884",
    "group": "9a3774f5-99fe-4008-8c85-2cad8b5c5a2e",
    "message": "Promote Batch162 V5 notification admin-order routing fix d73fde2 20260917-114858",
    "createdAt": "2026-09-17T09:51:18.671Z",
    "runtime": {
      "id": "01a08277-bde9-759f-b365-c6083fd7793a",
      "version": "1.0.0-build17"
    },
    "platform": "android",
    "manifestFragment": "{\"extra\":{\"expoClient\":{\"ios\":{\"icon\":\"./assets/icon-ios.png\",\"infoPlist\":{\"NSFaceIDUsageDescription\":\"Ald1n CMS koristi Face ID samo za zaštitu lokalne prijave.\"},\"buildNumber\":\"1\",\"supportsTablet\":true,\"bundleIdentifier\":\"com.ald1n.mobile\",\"googleServicesFile\":\"./GoogleService-Info.plist\"},\"web\":{\"output\":\"static\",\"bundler\":\"metro\",\"favicon\":\"./assets/favicon.png\"},\"icon\":\"./assets/icon.png\",\"name\":\"Ald1n CMS\",\"slug\":\"ald1n-mobile\",\"extra\":{\"eas\":{\"projectId\":\"d43b3866-6838-4217-a23e-3dc7f2cc76cc\"},\"apiUrl\":\"https://cms.ald1n.com/api/v1\",\"appEnv\":\"production\",\"router\":{},\"googleAuthConfigured\":true},\"owner\":\"ald1n\",\"scheme\":\"ald1n\",\"android\":{\"package\":\"com.ald1n.mobile\",\"adaptiveIcon\":{\"backgroundColor\":\"#101214\",\"foregroundImage\":\"./assets/adaptive-icon.png\"},\"googleServicesFile\":\"./google-services.json\",\"softwareKeyboardLayoutMode\":\"resize\",\"predictiveBackGestureEnabled\":true},\"plugins\":[\"expo-router\",[\"expo-splash-screen\",{\"image\":\"./assets/splash-icon.png\",\"imageWidth\":200,\"resizeMode\":\"contain\",\"backgroundColor\":\"#101214\"}],[\"expo-secure-store\",{\"faceIDPermission\":\"Dozvoli aplikaciji Ald1n CMS korišćenje Face ID zaštite.\",\"configureAndroidBackup\":true}],\"expo-localization\",[\"expo-notifications\",{\"color\":\"#C45116\",\"defaultChannel\":\"business-updates\"}],[\"react-native-nitro-google-signin\",{\"iosGoogleServicesFile\":\"./GoogleService-Info.plist\",\"androidGoogleServicesFile\":\"./google-services.json\"}]],\"updates\":{\"url\":\"https://u.expo.dev/d43b3866-6838-4217-a23e-3dc7f2cc76cc\"},\"version\":\"1.0.0\",\"platforms\":[\"ios\",\"android\",\"web\"],\"sdkVersion\":\"57.0.0\",\"experiments\":{\"typedRoutes\":true},\"orientation\":\"portrait\",\"runtimeVersion\":\"1.0.0-build17\",\"backgroundColor\":\"#F6F7F8\",\"userInterfaceStyle\":\"automatic\"}},\"assets\":[{\"bundleKey\":\"425d0975e04ac69f126787a729c8e701\",\"fileSHA256\":\"lxA2TpFlLqSFZ-cKIHDZXFztcBW8XVkH0VQ0CF5Afj8\",\"storageKey\":\"9at6eLc6BDmDx-7K5S6JHVOOFamBIOMibPyXX4Kugdo\",\"contentType\":\"font/ttf\",\"fileExtension\":\".ttf\"},{\"bundleKey\":\"0a328cd9c1afd0afe8e3b1ec5165b1b4\",\"fileSHA256\":\"mtuMXvypMuK3MrKT2oE70uMZUiYwVrzp4lF_7A8LanY\",\"storageKey\":\"LM4SxB4vhDe2iN4tYT0dAlPP_dsY-Zme_H-JpULtHa8\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"778ffc9fe8773a878e9c30a6304784de\",\"fileSHA256\":\"i2Gkx-9w3JJ1PwSUl2SC9m_UFQ7CPfx3KrZeEDc6-lU\",\"storageKey\":\"idnX8z03q4vGLzhYMPaQGBADjhqvWe67_afPqJ1vGzM\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"c79c3606a1cf168006ad3979763c7e0c\",\"fileSHA256\":\"kGZm3WiTRq2iMs42ia3vkHtCV_obWT8DY40rlJf2SIQ\",\"storageKey\":\"k-3xHp3vP8mR36WdccUZVPTRJKpP-zlyboWdkXCIQpQ\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"02bc1fa7c0313217bde2d65ccbff40c9\",\"fileSHA256\":\"_6fuRbdkBbpzkhSVAI99aMneY5X0tpQdsGNGX244IyA\",\"storageKey\":\"lfqeUjiWFXwowhxVDBEzxsbTOEzYxMLebljy5Bh1mpU\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"35ba0eaec5a4f5ed12ca16fabeae451d\",\"fileSHA256\":\"hM9es7ICUPaeDkczvrTaX0F_BoKJKlrRteYlcHU8IbE\",\"storageKey\":\"fMoMrsUeB5xWw_Ugn8FqGJf-M2pld2hkXKR704z0MM8\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"3cd68ccdb8938e3711da2e8831b85493\",\"fileSHA256\":\"yGu0he7mh-BsUA_EI09RpnhLRTDCrhHQx2SBEQvOMQU\",\"storageKey\":\"A08F4i_QzJW79C3BCIZYmmpH_p2FkQbdTQZ_2hVWDeg\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"61ca7e64b7d605716c57706cef640b9a\",\"fileSHA256\":\"0Dpc16Zu8QxRR5FvnVh4stCgpa1G9AWjHNmWfvqlWv0\",\"storageKey\":\"04lOdmTXc1loPyqacYwao1j0xlM34n0vGxtIFY7ETTs\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"aff2c65b39a296d4f7e96d0f58169170\",\"fileSHA256\":\"q1YT0s5w-mfFkm6DE1NmSUUZn8GV-m_nbF_jzsRS4xo\",\"storageKey\":\"4s0taS6e9_zozzhgsa6FogUB4b0LQc1LMNbBLkxlIz0\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"d8e7601e3df962f83c62371ac14964d8\",\"fileSHA256\":\"xOVOagp95jaqFTN4-8YupKQWlb_t-Af5Dfexj_1zP2c\",\"storageKey\":\"g6PisuFJa8j79kTi3ZKboB3DcerIBZXujlFBVhXkh1s\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"0747a1317bbe9c6fc340b889ef8ab3ae\",\"fileSHA256\":\"IyXFOnPlTEOMu8YN_92LUT1HG6QuL1ZxPvQEuV4Mofw\",\"storageKey\":\"DP7IegcRXP-7kIHiKmplB43A2HIM9nT01_O4JsXZKXI\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"d84e297c3b3e49a614248143d53e40ca\",\"fileSHA256\":\"sbwYEMoj4sOM12lxxDWR1yYWEGEkDYt5KwV1cZbdtSg\",\"storageKey\":\"aY8TciqT0pLONlgDC1iSLgQd6TyRf7NkBhUrJkfuqD0\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"1190ab078c57159f4245a328118fcd9a\",\"fileSHA256\":\"y9-SE-VfZcSNB2rNhCO3ya7k8bgRAf9slaOMTNioZGE\",\"storageKey\":\"mJsf9KoBQiKLAHO11XdSt59xshQZeDJmGEa9jlA_GY4\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"78c625386b4d0690b421eb0fc78f7b9c\",\"fileSHA256\":\"0FydMNefRaeOVYWi5-xnxqBqiD3ZE34dUFnoyHszznY\",\"storageKey\":\"yag9GLD2iZjnBBRuIa9kgMTdG2sabQ7v5K3JoDYQcqY\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"4403c6117ec30c859bc95d70ce4a71d3\",\"fileSHA256\":\"unIvskv_CkxS5lbN8gXerFxd-F5briv20wcTvVDG1fc\",\"storageKey\":\"uG5CihBixNlPMeYN0V5MRwARA2Ovn0seNtMvhelV3kE\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"069d99eb1fa6712c0b9034a58c6b57dd\",\"fileSHA256\":\"UwD1C-NlnRfYzuho7E5sDT9OiZRP1YmdPCD2p2egRfU\",\"storageKey\":\"dV5iuhAavksqtDhoNxjh3ZJ3mjhWaRm_k_QMsxTkJRg\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"c3273c9e5321f20d1e42c2efae2578c4\",\"fileSHA256\":\"bI8Rv9HH4idp3BNcFuzQ4FO7VVVNqIuWEdnPcdEhNvE\",\"storageKey\":\"r81JaSP6EG6dxkyPe8l7CAVizX4cmddQVze71LebmUo\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"286d67d3f74808a60a78d3ebf1a5fb57\",\"fileSHA256\":\"XQdeDCh8T0RBtbXI_H6lzSfBG7YX7OoJdVlVvLGIkIM\",\"storageKey\":\"m9yS4VLFfdQtO3uDS8O8dWqyH7JfIBfBd3VkPnFGw6c\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"d1ea1496f9057eb392d5bbf3732a61b7\",\"fileSHA256\":\"wV7HKnTROVchGAgxPVw13rf3YGxtItvdzrAADU4FR60\",\"storageKey\":\"iiX0QENtWnFHr-ql_G75Cv2aoG8T9jbnoTrrLiduBMs\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"19eeb73b9593a38f8e9f418337fc7d10\",\"fileSHA256\":\"ppou21tsYLhDPVttI8afUgzNimAvP2DnmW5Ilxi8F1w\",\"storageKey\":\"0OEDV0a6nXu3m-NCyMZCejvRaLMgUJoN5kZZr6WGO9o\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"ab19f4cbc543357183a20571f68380a3\",\"fileSHA256\":\"J_TSKdRAklv3IS1d__pF1Zayx7O7KQXJa4ObIIGh2-s\",\"storageKey\":\"1QHSPNPuPJBWsSu-NeGsZJ65lgP9Nn9hUrFpSvorJ3Y\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"d8b800c443b8972542883e0b9de2bdc6\",\"fileSHA256\":\"cIp5z1YbiipIUM4CVFhNOlIxBGvgj7f630tu39sxF4s\",\"storageKey\":\"F0VhhOdSAZAGWHzCBT8frAxWVDzVgNYGLlSTnInMLk0\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"412dd9275b6b48ad28f5e3d81bb1f626\",\"fileSHA256\":\"rPaRlXo7zOfRlYVtzpilzxTc4QvqKKpcELNVDAeOI7U\",\"storageKey\":\"YbOaNgsSGzVwiIMg8au7XcXF5nhp0mKG64zufThcPZQ\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"017bc6ba3fc25503e5eb5e53826d48a8\",\"fileSHA256\":\"beyjK1a5f6H6h_rNaZa0bMRRkKVTQA5_698dIJMJm1c\",\"storageKey\":\"92813hASY0kS8OnTdsF2wf_z0FEi_ZDIIaK-dzQ6BRU\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"20e71bdf79e3a97bf55fd9e164041578\",\"fileSHA256\":\"78edo8TJffsVcb_2IClYqwUpxaox6DkmAFeLPjWdFEc\",\"storageKey\":\"Ij5rVfQl_Hbg8xU1LXrdNRR__q6WTEE-FIneYIROiLQ\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"98a4e3d00dbf5d65da99a69b38a7def2\",\"fileSHA256\":\"5T8eafhi5ZX10RnAQoY_8qRE_kKZbSK7q4drMAc9D7o\",\"storageKey\":\"Yb5h2M9HkuLPfXqyzIilFgPQ5wG_hioQ9IvHMQqHwt4\",\"contentType\":\"application/xml\",\"fileExtension\":\".xml\"},{\"bundleKey\":\"9b5cb09b4e669ed0f28f3b3f2871a0a5\",\"fileSHA256\":\"_aPxg66F5jvLznCnQnbqIgMyroQfdGw3CwsqtANVo1c\",\"storageKey\":\"TvYQWVORer08BYLO00Y1PcJDE1-Z-ZluvGfqmYd8gCs\",\"contentType\":\"application/xml\",\"fileExtension\":\".xml\"},{\"bundleKey\":\"0091d85c828ed5501c3e14af870b6cc3\",\"fileSHA256\":\"OYvRRqYuNptRd0cBPZvxHDi4XQzNGYc8WlSs4E9jQc0\",\"storageKey\":\"i8jndpqwsKKau5ggj5IVWiWRSGvkjX8O8rC9bolG2NY\",\"contentType\":\"font/ttf\",\"fileExtension\":\".ttf\"},{\"bundleKey\":\"9517d71c04d0e210173e851b26154dc6\",\"fileSHA256\":\"rHezdm1kqjQzyD3Qe_pS9TdYO2Bj3HHe8DckWRMGoMk\",\"storageKey\":\"-YnPaF8-eWzGqyhllli__46orJxkZo076oeaEiXdLdY\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"}],\"launchAsset\":{\"bundleKey\":\"8758fd4babc052e8051e747420c377a2\",\"fileSHA256\":\"KtSLl_O5fJHFNB08BNFWJ3W9A4sFWItg2EvR1gzKMDA\",\"storageKey\":\"axixYs2GWLDuI6kCGNPfPXQAeKONqvipg5K-b8NmJWQ\",\"contentType\":\"application/javascript\",\"fileExtension\":\".hbc\"}}",
    "isRollBackToEmbedded": false,
    "manifestPermalink": "https://u.expo.dev/update/01a0aec7-188f-78d1-8ad5-ea9a03fc1884",
    "gitCommitHash": "277118631d11828162e6d2992dd02d9c1eaddaf6",
    "isGitWorkingTreeDirty": true,
    "environment": "production",
    "actor": {
      "id": "da308684-07a9-4dfe-8cbb-aae0af6479b0",
      "username": "ald1n"
    },
    "branch": {
      "id": "019fff34-708f-7613-99e7-c68e72f58ff1",
      "name": "production"
    },
    "fingerprint": {
      "id": "01a087a8-ea5f-70b6-a654-c255afffd841",
      "hash": "ed8e027164cf6d0e0061b1ed0437b4df7b948bea",
      "debugInfoUrl": "https://storage.googleapis.com/updates-runtime-fingerprints-production/production/da308684-07a9-4dfe-8cbb-aae0af6479b0/d78e7c67-6fbe-4fa1-b3b3-c0296cc54a56?X-Goog-Algorithm=GOOG4-RSA-SHA256&X-Goog-Credential=www-production%40exponentjs.iam.gserviceaccount.com%2F20260917%2Fauto%2Fstorage%2Fgoog4_request&X-Goog-Date=20260917T095119Z&X-Goog-Expires=900&X-Goog-SignedHeaders=host&X-Goog-Signature=080aa9e4ab52327cc88c3cf35d9e089f7275d15348584a49f5c171861dbbfa6067046f9207395b110481b5c32e6a0d444d6589a80aab47cf4818d4aa14d47352be5b800d893926934524a0a7dbf1b2c492cea9a37bd0e92984733517c83da563d7556588e40f44ced53a45b2c9321fcdc0c363d8bde6d4f16e61036f90c4c679c3e0aad3b04a470665c8d7e0734cbc2181784a0ca9086d25c5a7615e0bceb822827565e2221ae340cc1536d85be3ab14ec242a9b27dae3870e2ca45cd4cc2a218d4c8a3a6eaeeef7cc6ab1b222ef94615d41f967a889a740c335aa15b1483509fa756ada3322c8a0469a306df92b3194d523085822b398a24da6a98e2c56d18b",
      "source": {
        "type": "GCS",
        "bucketKey": "production/da308684-07a9-4dfe-8cbb-aae0af6479b0/d78e7c67-6fbe-4fa1-b3b3-c0296cc54a56"
      }
    }
  }
]
RC_production_republish=0
RC_production_republish_stdout_tee=0
RC_production_republish_stderr_tee=0
PRODUCTION_GROUP_ID=9a3774f5-99fe-4008-8c85-2cad8b5c5a2e
RC_production_group_parse=0
RUN=production_view
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@23.2.0 -- eas update:view 9a3774f5-99fe-4008-8c85-2cad8b5c5a2e --json
★ eas-cli@24.7.0 is now available.
To upgrade, run:
npm install -g eas-cli
Proceeding with outdated version.

[
  {
    "id": "01a0aec7-188f-78d1-8ad5-ea9a03fc1884",
    "createdAt": "2026-09-17T09:51:18.671Z",
    "group": "9a3774f5-99fe-4008-8c85-2cad8b5c5a2e",
    "branch": "production",
    "message": "Promote Batch162 V5 notification admin-order routing fix d73fde2 20260917-114858",
    "runtimeVersion": "1.0.0-build17",
    "platform": "android",
    "manifestPermalink": "https://u.expo.dev/update/01a0aec7-188f-78d1-8ad5-ea9a03fc1884",
    "isRollBackToEmbedded": false,
    "gitCommitHash": "277118631d11828162e6d2992dd02d9c1eaddaf6"
  }
]
RC_production_view=0
RC_production_view_stdout_tee=0
RC_production_view_stderr_tee=0
RUN=production_view_verify
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /home/icaffeco/.ald1n-batch162-v5-20260917-114858/eas-json-helper.mjs verify-view /home/icaffeco/.ald1n-batch162-v5-20260917-114858/production-view.json 9a3774f5-99fe-4008-8c85-2cad8b5c5a2e 1.0.0-build17 277118631d11828162e6d2992dd02d9c1eaddaf6,d73fde25892c984a23486125dd0f516008a782bd Promote Batch162 V5
VIEW_GROUP=9a3774f5-99fe-4008-8c85-2cad8b5c5a2e
VIEW_RUNTIME=1.0.0-build17
VIEW_PLATFORM=android
VIEW_COMMITS=277118631d11828162e6d2992dd02d9c1eaddaf6
VIEW_MESSAGES=Promote Batch162 V5 notification admin-order routing fix d73fde2 20260917-114858
RC_production_view_verify=0
PRODUCTION_EXACT_GROUP_VERIFIED=PASS
RUN=final_production_update_list
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@23.2.0 -- eas update:list --branch production --limit 1 --json --non-interactive
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
      "message": "\"Promote Batch162 V5 notification admin-order routing fix d73fde2 20260917-114858\" (16 seconds ago by ald1n)",
      "runtimeVersion": "1.0.0-build17",
      "isRollBackToEmbedded": false,
      "group": "9a3774f5-99fe-4008-8c85-2cad8b5c5a2e",
      "platforms": "android"
    }
  ]
}
RC_final_production_update_list=0
RC_final_production_update_list_stdout_tee=0
RC_final_production_update_list_stderr_tee=0
FINAL_PRODUCTION_LIST_GROUP_ID=9a3774f5-99fe-4008-8c85-2cad8b5c5a2e
RC_final_group_parse=0
FINAL_PRODUCTION_AUTHORITY=PASS

============================================================
FINAL GIT AND RELEASE STATE
============================================================
RUN=final_git_fetch
COMMAND=git -C /home/icaffeco/ald1n-project fetch origin main
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
RC_final_git_fetch=0
FINAL_LOCAL_HEAD=277118631d11828162e6d2992dd02d9c1eaddaf6
FINAL_REMOTE_HEAD=277118631d11828162e6d2992dd02d9c1eaddaf6
SOURCE_FIX_COMMIT=d73fde25892c984a23486125dd0f516008a782bd
OTA_MAIN_HEAD=277118631d11828162e6d2992dd02d9c1eaddaf6
OTA_RUNTIME=1.0.0-build17
BUILD18=NO
GOOGLE_PLAY_ACTION=NO
BUGFIX_RELEASE_STATE=SOURCE_PUSHED_CANDIDATE_VERIFIED_PRODUCTION_PROMOTED

============================================================
FINAL SUMMARY
============================================================
BATCH162_V5_RESULT=PASS
BATCH162_RESULT=PASS
FAILED_STAGE=NONE
SOURCE_MUTATION=NO_APP_SOURCE_MUTATION_V5
SOURCE_FIX_COMMIT=d73fde25892c984a23486125dd0f516008a782bd
MAIN_HEAD_FOR_OTA=277118631d11828162e6d2992dd02d9c1eaddaf6
DOCUMENTATION_SYNC=FAST_FORWARDED_AGENTS_ONLY
PUSH_COMPLETED=ALREADY_PASS_FROM_V3
OTA_REQUIRED=YES_BUILD17_COMPATIBLE
CANDIDATE_PUBLISHED=1
CANDIDATE_GROUP_ID=2d27620e-875c-4215-95f8-5744aac39ca8
PRODUCTION_PROMOTED=1
PRODUCTION_GROUP_ID=9a3774f5-99fe-4008-8c85-2cad8b5c5a2e
BUILD_CREATED=NO
GOOGLE_PLAY_ACTION=NO
NEXT_ACTION=RUN_BATCH163_PHYSICAL_NOTIFICATION_ROUTING_ACCEPTANCE
REPORT_PATH=/home/icaffeco/ald1n-project/docs/operations/BATCH162-V5-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-UPDATE-VIEW-FLAG-RECOVERY-20260917-114858.md
