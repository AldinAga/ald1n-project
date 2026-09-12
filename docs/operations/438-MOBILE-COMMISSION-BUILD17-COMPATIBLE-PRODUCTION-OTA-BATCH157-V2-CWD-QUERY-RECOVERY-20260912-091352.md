
============================================================
438 - MOBILE COMMISSION BUILD17-COMPATIBLE PRODUCTION OTA - BATCH157 V2 CWD/QUERY RECOVERY
============================================================
DATE=Sat Sep 12 09:13:52 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
PURPOSE=RECOVER_REPORT437_EAS_AUTHORITY_FAILURE_WITH_PROJECT_CWD_DIAGNOSTIC_AND_EXACT_GROUP_FALLBACK
TERMINAL_OUTPUT_MODE=VERBOSE_LIVE_MIRROR_TO_TERMINAL_AND_REPORT
EXPECTED_HEAD=792fd7bb1ab07ec632406d528b42dc1598a92539
EXPECTED_REPORT437_SHA=535e96ed3a0e770d58ff1a670dd44faa5ef313f8f2a29bdb3e64537edc2c2115
BUILD17_RUNTIME=1.0.0-build17
DATABASE_WRITES=NO
MIGRATIONS_RUN=NO
DEPENDENCY_INSTALL_IN_PROJECT=NO
EAS_BUILD=NO
GOOGLE_PLAY_ACTION=NO
PRODUCT_VARIANTS_REINTRODUCED=NO

============================================================
0. REPORT437 FAIL AUTHORITY + SAFE-NO-MUTATION RECONCILIATION
============================================================
REPORT437_SHA256=535e96ed3a0e770d58ff1a670dd44faa5ef313f8f2a29bdb3e64537edc2c2115
REPORT437_AUTHORITY=PASS_EXACT_SHA_EAS_AUTHORITY_FAIL_NO_CANDIDATE_NO_PRODUCTION_MUTATION
REPORT436_SHA256=0a202098d409dce2afa3b76a0122c6d58a0d21106512cd5f9efa538cc933ed6b
REPORT436_AUTHORITY=PASS
REPORT418_SHA256=d622b5863bc26dc52e5f8da1a3d8ea393932eb7c1b43d6d732a328ce0265981f
REPORT418_OTA_BASELINE_AUTHORITY=PASS

============================================================
1. LIVE SOURCE + BUILD17 IMMUTABILITY
============================================================
RUN=GIT_FETCH
COMMAND=git fetch origin main
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
RC_GIT_FETCH=0
BRANCH=main
LOCAL_HEAD=792fd7bb1ab07ec632406d528b42dc1598a92539
REMOTE_HEAD=792fd7bb1ab07ec632406d528b42dc1598a92539
SOURCE_PARENT=21e7d4883bf9b2d69ff01e1dc7ce6bbaf3e91d37
TRACKED_WORKTREE_STATUS_BEGIN
 M apps/cms/current/public/.htaccess
TRACKED_WORKTREE_STATUS_END
HTACCESS_FILE_SHA256=d10ecf0d63f9a8e1bd978a440afcbadb34faf1d24eb899bf2582d1e5c0033cef
HTACCESS_DIFF_SHA256=8ce9fa55a71768bf711203fcc50be8eec6de26886bbf4852ceb30a97b36958fe
BUILD17_AAB=/home/icaffeco/backups/releases/Ald1n-CMS-v1.0.0-production-vc17-7f3b4381-a7f9-4d01-9312-2b7754b8cb01.aab
BUILD17_AAB_SHA256=13d25760807fd20b0faa839d89c589f331845e5b1c12c2926d6ac906845b1b60
BUILD17_AAB_SIZE_BYTES=83304530
BUILD17_COMPAT_UNCHANGED=apps/mobile/current/app.config.js
BUILD17_COMPAT_UNCHANGED=apps/mobile/current/eas.json
BUILD17_COMPAT_UNCHANGED=apps/mobile/current/package.json
BUILD17_COMPAT_UNCHANGED=apps/mobile/current/package-lock.json
SOURCE_AUTHORITY=PASS_EXACT_REPORT437_HEAD_WITH_KNOWN_HTACCESS_ONLY
BUILD17_BINARY_AUTHORITY=PASS_EXACT_SHA256
BUILD18_REQUIRED=NO_BUILD17_COMPATIBLE_JS_TS_ONLY

============================================================
2. REUSE REPORT437 SOURCE GATES - NO REDUNDANT 1600-LINE RERUN
============================================================
CMS_STATIC=REUSED_REPORT437_PASS_983_TOTAL_0_FAILED
MOBILE_TYPESCRIPT=REUSED_REPORT437_PASS
MOBILE_VALIDATOR=REUSED_REPORT437_PASS_ZERO_FAIL
COMMISSION_MOBILE_SOURCE=REUSED_REPORT437_PASS_SINGLE_PAGE_LIST_DETAIL_PAYOUT_CONTRACT
REUSE_SAFETY=PASS_EXACT_REPORT437_SHA_PLUS_UNCHANGED_LOCAL_REMOTE_HEAD

============================================================
3. EAS ROOT-CAUSE DIAGNOSTIC - PROJECT CWD FIRST
============================================================
RUN=EAS_VERSION
COMMAND=eas_cli --version
★ eas-cli@24.3.0 is now available.
To upgrade, run:
npm install -g eas-cli
Proceeding with outdated version.

eas-cli/23.2.0 linux-x64 node-v22.23.2
RC_EAS_VERSION=0
RUN=EAS_WHOAMI
COMMAND=eas_cli whoami
★ eas-cli@24.3.0 is now available.
To upgrade, run:
npm install -g eas-cli
Proceeding with outdated version.

ald1n
pruzljanin@gmail.com

Accounts:
• ald1n (Role: Owner)
• ald1ns-team (Role: Owner)
(node:3894377) [UnparsedCommand] Warning: Command account:view did not parse its arguments. Did you forget to call 'this.parse'?
(Use `node --trace-warnings ...` to show where the warning was created)
RC_EAS_WHOAMI=0
EAS_CLI_SPEC=eas-cli@23.2.0
EAS_PROJECT_CWD_DIAGNOSTIC=RUN_UPDATE_LIST_FROM_MOBILE_ROOT
REPORT437_V1_CONTEXT=PROJECT_ROOT_BEFORE_PROJECT_SCOPED_UPDATE_LIST
DIAGNOSTIC_HYPOTHESIS=V1_PROJECT_SCOPED_EAS_QUERY_FAILED_BECAUSE_COMMAND_RAN_FROM_MONOREPO_ROOT
RECOVERY_JSON_PARSER_TDD=PASS_SYNTHETIC_GROUP_EXTRACTION
RUN=MOBILE_ROOT_PRODUCTION_UPDATE_LIST
COMMAND=eas_mobile update:list --branch production --limit 1 --json --non-interactive
★ eas-cli@24.3.0 is now available.
To upgrade, run:
npm install -g eas-cli
Proceeding with outdated version.

{
  "name": "production",
  "id": "019fff34-708f-7613-99e7-c68e72f58ff1",
  "currentPage": [
    {
      "branch": "production",
      "message": "\"Promote Batch152 verified Build17 candidate 4154e7f 20260911-120949\" (21 hours ago by ald1n)",
      "runtimeVersion": "1.0.0-build17",
      "isRollBackToEmbedded": false,
      "group": "cdd2aebd-6eb9-4ef9-9a05-713d0a3acc04",
      "platforms": "android"
    }
  ]
}
RC_MOBILE_ROOT_PRODUCTION_UPDATE_LIST=0
ROOT_CAUSE_CONFIRMED=V1_PROJECT_SCOPED_EAS_COMMAND_RAN_FROM_MONOREPO_ROOT
PREVIOUS_PRODUCTION_GROUP_ID=cdd2aebd-6eb9-4ef9-9a05-713d0a3acc04
RUN=PREVIOUS_PRODUCTION_EXACT_VIEW
COMMAND=eas_mobile update:view cdd2aebd-6eb9-4ef9-9a05-713d0a3acc04 --json
★ eas-cli@24.3.0 is now available.
To upgrade, run:
npm install -g eas-cli
Proceeding with outdated version.

[
  {
    "id": "01a08ff5-4e14-782a-b379-aa9718d6a4b8",
    "createdAt": "2026-09-11T10:13:33.332Z",
    "group": "cdd2aebd-6eb9-4ef9-9a05-713d0a3acc04",
    "branch": "production",
    "message": "Promote Batch152 verified Build17 candidate 4154e7f 20260911-120949",
    "runtimeVersion": "1.0.0-build17",
    "platform": "android",
    "manifestPermalink": "https://u.expo.dev/update/01a08ff5-4e14-782a-b379-aa9718d6a4b8",
    "isRollBackToEmbedded": false,
    "gitCommitHash": "4154e7f115386f10a7676879f83333b0d51d8b6f"
  }
]
RC_PREVIOUS_PRODUCTION_EXACT_VIEW=0
PREVIOUS_PRODUCTION_SUMMARY={"group":"cdd2aebd-6eb9-4ef9-9a05-713d0a3acc04","branch":"production","runtime":"1.0.0-build17","platform":"android","gitCommit":"4154e7f115386f10a7676879f83333b0d51d8b6f","rollback":"false"}
PREVIOUS_PRODUCTION_GIT_COMMIT=4154e7f115386f10a7676879f83333b0d51d8b6f
PREVIOUS_PRODUCTION_RUNTIME=1.0.0-build17
PREVIOUS_PRODUCTION_BRANCH=production
PREVIOUS_PRODUCTION_PLATFORM=android
PREVIOUS_PRODUCTION_EXACT_GROUP_AUTHORITY=PASS

============================================================
4. PUBLISH ISOLATED ANDROID CANDIDATE WITH DIRECT JSON GROUP CAPTURE
============================================================
CANDIDATE_BRANCH=batch157-v2-commission-build17-20260912-091352
CANDIDATE_MESSAGE=Batch157 V2 Commission Build17 candidate 792fd7b 20260912-091352
CANDIDATE_RESOLUTION_STRATEGY=DIRECT_EAS_UPDATE_JSON_NO_UPDATE_LIST_DEPENDENCY
RUN=CANDIDATE_PUBLISH
COMMAND=eas_mobile update --branch batch157-v2-commission-build17-20260912-091352 --platform android --message Batch157 V2 Commission Build17 candidate 792fd7b 20260912-091352 --environment production --json --non-interactive
Environment variables with visibility "Plain text" and "Sensitive" loaded from the "production" environment on EAS: EXPO_PUBLIC_API_URL, EXPO_PUBLIC_APP_ENV.

⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...[expo-cli] --non-interactive is not supported, use $CI=1 instead
⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...[expo-cli] Using src/app as the root directory for Expo Router.
⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...[expo-cli] Starting Metro Bundler
⠋ Exporting...[expo-cli] warning: Bundler cache is empty, rebuilding (this may take a minute)
⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...⠇ Exporting...⠏ Exporting...⠋ Exporting...[expo-cli] Android Bundled 58222ms node_modules/expo-router/entry.js (2726 modules)
⠋ Exporting...[expo-cli] Creating asset map
⠋ Exporting...⠙ Exporting...[expo-cli] 
⠋ Exporting...[expo-cli] › Assets (29):
⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...⠦ Exporting...⠧ Exporting...[expo-cli] assets/icon.png (63KB)
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
⠋ Exporting...[expo-cli] › android bundles (2):
⠋ Exporting...[expo-cli] _expo/static/js/android/entry-5bdb5e3362fa1fec62bf182f0ef477c6.hbc (6.3MB)
⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...⠴ Exporting...[expo-cli] _expo/static/js/android/entry-5bdb5e3362fa1fec62bf182f0ef477c6.hbc.map (14MB)
⠋ Exporting...[expo-cli] › Files (2):
⠋ Exporting...[expo-cli] assetmap.json (11KB)
⠋ Exporting...[expo-cli] metadata.json (2KB)
⠋ Exporting...⠙ Exporting...[expo-cli] 
⠋ Exporting...[expo-cli] Exported: dist
⠋ Exporting...⠙ Exporting...⠹ Exporting...✔ Exported bundle(s)
⠋ Uploading...⠙ Uploading...⠹ Uploading...⠋ Uploading assetmap.json (0 / 11.0 KB)⠸ Uploading...⠙ Uploading assetmap.json (11.0 KB / 11.0 KB)⠼ Uploading...⠹ Uploading assetmap.json (11.0 KB / 11.0 KB)⠴ Uploading...⠸ Uploading assetmap.json (11.0 KB / 11.0 KB)⠦ Uploading...⠼ Uploading assetmap.json (11.0 KB / 11.0 KB)⠧ Uploading...✔ Uploaded assetmap.json 
⠇ Uploading...⠏ Uploading...⠋ Uploading...⠙ Uploading (0/30)⠹ Uploading (0/30)⠸ Uploading (29/30)⠼ Uploading (29/30)⠴ Uploading (29/30)⠦ Uploading (29/30)⠧ Uploading (29/30)⠇ Uploading (29/30)⠏ Uploading (29/30)⠋ Uploading (29/30)⠙ Uploading (29/30)⠹ Uploading (29/30)⠸ Uploading (29/30)⠼ Uploading (29/30)⠴ Uploading (29/30)⠦ Uploading (29/30)⠧ Uploading (29/30)⠇ Uploading (29/30)⠏ Uploading (29/30)⠋ Uploading (29/30)⠙ Uploading (29/30)⠹ Uploading (29/30)⠸ Uploading (29/30)⠼ Uploading (29/30)⠴ Uploading (29/30)⠦ Uploading (29/30)⠧ Uploading (29/30)⠇ Uploading (29/30)⠏ Uploading (29/30)⠋ Uploading (29/30)⠙ Uploading (29/30)⠹ Uploading (29/30)⠸ Uploading (29/30)⠼ Uploading (29/30)⠴ Uploading (29/30)⠦ Uploading (29/30)⠧ Uploading (29/30)⠇ Uploading (29/30)⠏ Uploading (29/30)⠋ Uploading (29/30)⠙ Uploading (29/30)⠹ Uploading (29/30)⠸ Uploading (29/30)⠼ Uploading (29/30)⠴ Uploading (29/30)⠦ Uploading (29/30)⠧ Uploading (29/30)⠇ Uploading (29/30)⠏ Uploading (29/30)⠋ Uploading (29/30)⠙ Uploading (29/30)⠹ Uploading (29/30)⠸ Uploading (29/30)⠼ Uploading (29/30)⠴ Uploading (29/30)⠦ Uploading (29/30)⠧ Uploading (29/30)⠇ Uploading (29/30)⠏ Uploading (29/30)⠋ Uploading (29/30)⠙ Uploading (29/30)⠹ Uploading (29/30)⠸ Uploading (29/30)⠼ Uploading (29/30)⠴ Uploading (29/30)⠦ Uploading (29/30)⠧ Uploading (30/30)⠇ Uploading (30/30)⠏ Uploading (30/30)⠋ Uploading (30/30)⠙ Uploading (30/30)⠹ Uploading (30/30)⠸ Uploading (30/30)⠼ Uploading (30/30)⠴ Uploading (30/30)⠦ Uploading (30/30)⠧ Uploading (30/30)⠇ Uploading (30/30)⠏ Uploading (30/30)⠋ Uploading (30/30)⠙ Uploading (30/30)⠹ Uploading (30/30)⠸ Uploading (30/30)⠼ Uploading (30/30)⠴ Uploading (30/30)⠦ Uploading (30/30)⠧ Uploading (30/30)⠇ Uploading (30/30)⠏ Uploading (30/30)⠋ Uploading (30/30)⠙ Uploading (30/30)⠹ Uploading (30/30)⠸ Uploading (30/30)⠼ Uploading (30/30)⠴ Uploading (30/30)⠦ Uploading (30/30)⠧ Uploading (30/30)⠇ Uploading (30/30)⠏ Uploading (30/30)⠋ Uploading (30/30)⠙ Uploading (30/30)⠹ Uploading (30/30)⠸ Uploading (30/30)✔ Uploaded 1 app bundle
✔ Uploading assets skipped - no new assets found
ℹ 30 Android assets (maximum: 2000 total per update). Learn more about asset limits: https://expo.fyi/eas-update-asset-limits
⠋ Computing project fingerprints⠙ Computing project fingerprints⠹ Computing project fingerprints⠸ Computing project fingerprints⠼ Computing project fingerprints⠴ Computing project fingerprints⠦ Computing project fingerprints⠧ Computing project fingerprints⠇ Computing project fingerprints⠏ Computing project fingerprints⠋ Computing project fingerprints⠙ Computing project fingerprints⠹ Computing project fingerprints⠸ Computing project fingerprints⠼ Computing project fingerprints⠴ Computing project fingerprints⠦ Computing project fingerprints⠧ Computing project fingerprints⠇ Computing project fingerprints⠏ Computing project fingerprints⠋ Computing project fingerprints⠙ Computing project fingerprints⠹ Computing project fingerprints⠸ Computing project fingerprints⠼ Computing project fingerprints⠴ Computing project fingerprints⠦ Computing project fingerprints⠧ Computing project fingerprints⠇ Computing project fingerprints⠏ Computing project fingerprints⠋ Computing project fingerprints⠙ Computing project fingerprints⠹ Computing project fingerprints⠸ Computing project fingerprints⠼ Computing project fingerprints⠴ Computing project fingerprints⠦ Computing project fingerprints⠧ Computing project fingerprints⠇ Computing project fingerprints⠏ Computing project fingerprints⠋ Computing project fingerprints⠙ Computing project fingerprints⠹ Computing project fingerprints⠸ Computing project fingerprints⠼ Computing project fingerprints⠴ Computing project fingerprints⠦ Computing project fingerprints⠧ Computing project fingerprints⠇ Computing project fingerprints⠏ Computing project fingerprints⠋ Computing project fingerprints⠙ Computing project fingerprints⠹ Computing project fingerprints⠸ Computing project fingerprints⠼ Computing project fingerprints⠴ Computing project fingerprints⠦ Computing project fingerprints⠧ Computing project fingerprints⠇ Computing project fingerprints⠏ Computing project fingerprints⌛️ Computing the project fingerprints is taking longer than expected...
⠋ Computing project fingerprints⏩ To skip this step, set the environment variable: EAS_SKIP_AUTO_FINGERPRINT=1
⠋ Computing project fingerprints⠙ Computing project fingerprints⠹ Computing project fingerprints⠸ Computing project fingerprints⠼ Computing project fingerprints⠴ Computing project fingerprints⠦ Computing project fingerprints⠧ Computing project fingerprints⠇ Computing project fingerprints⠏ Computing project fingerprints⠋ Computing project fingerprints⠙ Computing project fingerprints⠹ Computing project fingerprints⠸ Computing project fingerprints⠼ Computing project fingerprints⠴ Computing project fingerprints✔ Computed project fingerprints
⠋ Publishing...⠙ Publishing...⠹ Publishing...⠸ Publishing...⠼ Publishing...⠴ Publishing...⠦ Publishing...⠧ Publishing...✔ Published!
[
  {
    "id": "01a09479-04d9-7933-9af8-10f74edf1645",
    "createdAt": "2026-09-12T07:15:54.201Z",
    "group": "62c5a9fd-279b-4321-bae7-a86853c71cae",
    "branch": "batch157-v2-commission-build17-20260912-091352",
    "message": "Batch157 V2 Commission Build17 candidate 792fd7b 20260912-091352",
    "runtimeVersion": "1.0.0-build17",
    "platform": "android",
    "manifestPermalink": "https://u.expo.dev/update/01a09479-04d9-7933-9af8-10f74edf1645",
    "isRollBackToEmbedded": false,
    "gitCommitHash": "792fd7bb1ab07ec632406d528b42dc1598a92539"
  }
]
RC_CANDIDATE_PUBLISH=0
CANDIDATE_GROUP_ID=62c5a9fd-279b-4321-bae7-a86853c71cae
RUN=CANDIDATE_EXACT_VIEW
COMMAND=eas_mobile update:view 62c5a9fd-279b-4321-bae7-a86853c71cae --json
★ eas-cli@24.3.0 is now available.
To upgrade, run:
npm install -g eas-cli
Proceeding with outdated version.

[
  {
    "id": "01a09479-04d9-7933-9af8-10f74edf1645",
    "createdAt": "2026-09-12T07:15:54.201Z",
    "group": "62c5a9fd-279b-4321-bae7-a86853c71cae",
    "branch": "batch157-v2-commission-build17-20260912-091352",
    "message": "Batch157 V2 Commission Build17 candidate 792fd7b 20260912-091352",
    "runtimeVersion": "1.0.0-build17",
    "platform": "android",
    "manifestPermalink": "https://u.expo.dev/update/01a09479-04d9-7933-9af8-10f74edf1645",
    "isRollBackToEmbedded": false,
    "gitCommitHash": "792fd7bb1ab07ec632406d528b42dc1598a92539"
  }
]
RC_CANDIDATE_EXACT_VIEW=0
CANDIDATE_SUMMARY={"group":"62c5a9fd-279b-4321-bae7-a86853c71cae","branch":"batch157-v2-commission-build17-20260912-091352","runtime":"1.0.0-build17","platform":"android","gitCommit":"792fd7bb1ab07ec632406d528b42dc1598a92539","rollback":"false"}
CANDIDATE_RUNTIME=PASS_1.0.0-build17
CANDIDATE_PLATFORM=PASS_ANDROID_ONLY
CANDIDATE_GIT_COMMIT=PASS_792fd7bb1ab07ec632406d528b42dc1598a92539
CANDIDATE_ISOLATION=PASS_UNLINKED_BRANCH

============================================================
5. PRE-PROMOTION PRODUCTION RECHECK
============================================================
RUN=PRODUCTION_RECHECK
COMMAND=eas_mobile update:list --branch production --limit 1 --json --non-interactive
★ eas-cli@24.3.0 is now available.
To upgrade, run:
npm install -g eas-cli
Proceeding with outdated version.

{
  "name": "production",
  "id": "019fff34-708f-7613-99e7-c68e72f58ff1",
  "currentPage": [
    {
      "branch": "production",
      "message": "\"Promote Batch152 verified Build17 candidate 4154e7f 20260911-120949\" (21 hours ago by ald1n)",
      "runtimeVersion": "1.0.0-build17",
      "isRollBackToEmbedded": false,
      "group": "cdd2aebd-6eb9-4ef9-9a05-713d0a3acc04",
      "platforms": "android"
    }
  ]
}
RC_PRODUCTION_RECHECK=0
PRODUCTION_UNCHANGED_BEFORE_PROMOTION=PASS_cdd2aebd-6eb9-4ef9-9a05-713d0a3acc04

============================================================
6. PROMOTE EXACT VERIFIED CANDIDATE TO PRODUCTION
============================================================
RUN=PRODUCTION_REPUBLISH
COMMAND=eas_mobile update:republish --group 62c5a9fd-279b-4321-bae7-a86853c71cae --destination-channel production --platform android --message Promote Batch157 V2 verified Commission Build17 candidate 792fd7b 20260912-091352 --json --non-interactive
★ eas-cli@24.3.0 is now available.
To upgrade, run:
npm install -g eas-cli
Proceeding with outdated version.

✔ The republished update group will appear only on: android
⠋ Republishing...⠙ Republishing...⠹ Republishing...⠸ Republishing...⠼ Republishing...⠴ Republishing...⠦ Republishing...✔ Republished update group
[
  {
    "id": "01a09479-5ffe-7a3f-ad7b-b6d0e995a840",
    "group": "98e2cb1c-2fb3-4a40-9e4c-c442a99f1eb5",
    "message": "Promote Batch157 V2 verified Commission Build17 candidate 792fd7b 20260912-091352",
    "createdAt": "2026-09-12T07:16:17.534Z",
    "runtime": {
      "id": "01a08277-bde9-759f-b365-c6083fd7793a",
      "version": "1.0.0-build17"
    },
    "platform": "android",
    "manifestFragment": "{\"extra\":{\"expoClient\":{\"ios\":{\"icon\":\"./assets/icon-ios.png\",\"infoPlist\":{\"NSFaceIDUsageDescription\":\"Ald1n CMS koristi Face ID samo za zaštitu lokalne prijave.\"},\"buildNumber\":\"1\",\"supportsTablet\":true,\"bundleIdentifier\":\"com.ald1n.mobile\",\"googleServicesFile\":\"./GoogleService-Info.plist\"},\"web\":{\"output\":\"static\",\"bundler\":\"metro\",\"favicon\":\"./assets/favicon.png\"},\"icon\":\"./assets/icon.png\",\"name\":\"Ald1n CMS\",\"slug\":\"ald1n-mobile\",\"extra\":{\"eas\":{\"projectId\":\"d43b3866-6838-4217-a23e-3dc7f2cc76cc\"},\"apiUrl\":\"https://cms.ald1n.com/api/v1\",\"appEnv\":\"production\",\"router\":{},\"googleAuthConfigured\":true},\"owner\":\"ald1n\",\"scheme\":\"ald1n\",\"android\":{\"package\":\"com.ald1n.mobile\",\"adaptiveIcon\":{\"backgroundColor\":\"#101214\",\"foregroundImage\":\"./assets/adaptive-icon.png\"},\"googleServicesFile\":\"./google-services.json\",\"softwareKeyboardLayoutMode\":\"resize\",\"predictiveBackGestureEnabled\":true},\"plugins\":[\"expo-router\",[\"expo-splash-screen\",{\"image\":\"./assets/splash-icon.png\",\"imageWidth\":200,\"resizeMode\":\"contain\",\"backgroundColor\":\"#101214\"}],[\"expo-secure-store\",{\"faceIDPermission\":\"Dozvoli aplikaciji Ald1n CMS korišćenje Face ID zaštite.\",\"configureAndroidBackup\":true}],\"expo-localization\",[\"expo-notifications\",{\"color\":\"#C45116\",\"defaultChannel\":\"business-updates\"}],[\"react-native-nitro-google-signin\",{\"iosGoogleServicesFile\":\"./GoogleService-Info.plist\",\"androidGoogleServicesFile\":\"./google-services.json\"}]],\"updates\":{\"url\":\"https://u.expo.dev/d43b3866-6838-4217-a23e-3dc7f2cc76cc\"},\"version\":\"1.0.0\",\"platforms\":[\"ios\",\"android\",\"web\"],\"sdkVersion\":\"57.0.0\",\"experiments\":{\"typedRoutes\":true},\"orientation\":\"portrait\",\"runtimeVersion\":\"1.0.0-build17\",\"backgroundColor\":\"#F6F7F8\",\"userInterfaceStyle\":\"automatic\"}},\"assets\":[{\"bundleKey\":\"425d0975e04ac69f126787a729c8e701\",\"fileSHA256\":\"lxA2TpFlLqSFZ-cKIHDZXFztcBW8XVkH0VQ0CF5Afj8\",\"storageKey\":\"9at6eLc6BDmDx-7K5S6JHVOOFamBIOMibPyXX4Kugdo\",\"contentType\":\"font/ttf\",\"fileExtension\":\".ttf\"},{\"bundleKey\":\"0a328cd9c1afd0afe8e3b1ec5165b1b4\",\"fileSHA256\":\"mtuMXvypMuK3MrKT2oE70uMZUiYwVrzp4lF_7A8LanY\",\"storageKey\":\"LM4SxB4vhDe2iN4tYT0dAlPP_dsY-Zme_H-JpULtHa8\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"778ffc9fe8773a878e9c30a6304784de\",\"fileSHA256\":\"i2Gkx-9w3JJ1PwSUl2SC9m_UFQ7CPfx3KrZeEDc6-lU\",\"storageKey\":\"idnX8z03q4vGLzhYMPaQGBADjhqvWe67_afPqJ1vGzM\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"c79c3606a1cf168006ad3979763c7e0c\",\"fileSHA256\":\"kGZm3WiTRq2iMs42ia3vkHtCV_obWT8DY40rlJf2SIQ\",\"storageKey\":\"k-3xHp3vP8mR36WdccUZVPTRJKpP-zlyboWdkXCIQpQ\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"02bc1fa7c0313217bde2d65ccbff40c9\",\"fileSHA256\":\"_6fuRbdkBbpzkhSVAI99aMneY5X0tpQdsGNGX244IyA\",\"storageKey\":\"lfqeUjiWFXwowhxVDBEzxsbTOEzYxMLebljy5Bh1mpU\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"35ba0eaec5a4f5ed12ca16fabeae451d\",\"fileSHA256\":\"hM9es7ICUPaeDkczvrTaX0F_BoKJKlrRteYlcHU8IbE\",\"storageKey\":\"fMoMrsUeB5xWw_Ugn8FqGJf-M2pld2hkXKR704z0MM8\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"3cd68ccdb8938e3711da2e8831b85493\",\"fileSHA256\":\"yGu0he7mh-BsUA_EI09RpnhLRTDCrhHQx2SBEQvOMQU\",\"storageKey\":\"A08F4i_QzJW79C3BCIZYmmpH_p2FkQbdTQZ_2hVWDeg\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"61ca7e64b7d605716c57706cef640b9a\",\"fileSHA256\":\"0Dpc16Zu8QxRR5FvnVh4stCgpa1G9AWjHNmWfvqlWv0\",\"storageKey\":\"04lOdmTXc1loPyqacYwao1j0xlM34n0vGxtIFY7ETTs\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"aff2c65b39a296d4f7e96d0f58169170\",\"fileSHA256\":\"q1YT0s5w-mfFkm6DE1NmSUUZn8GV-m_nbF_jzsRS4xo\",\"storageKey\":\"4s0taS6e9_zozzhgsa6FogUB4b0LQc1LMNbBLkxlIz0\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"d8e7601e3df962f83c62371ac14964d8\",\"fileSHA256\":\"xOVOagp95jaqFTN4-8YupKQWlb_t-Af5Dfexj_1zP2c\",\"storageKey\":\"g6PisuFJa8j79kTi3ZKboB3DcerIBZXujlFBVhXkh1s\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"0747a1317bbe9c6fc340b889ef8ab3ae\",\"fileSHA256\":\"IyXFOnPlTEOMu8YN_92LUT1HG6QuL1ZxPvQEuV4Mofw\",\"storageKey\":\"DP7IegcRXP-7kIHiKmplB43A2HIM9nT01_O4JsXZKXI\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"d84e297c3b3e49a614248143d53e40ca\",\"fileSHA256\":\"sbwYEMoj4sOM12lxxDWR1yYWEGEkDYt5KwV1cZbdtSg\",\"storageKey\":\"aY8TciqT0pLONlgDC1iSLgQd6TyRf7NkBhUrJkfuqD0\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"1190ab078c57159f4245a328118fcd9a\",\"fileSHA256\":\"y9-SE-VfZcSNB2rNhCO3ya7k8bgRAf9slaOMTNioZGE\",\"storageKey\":\"mJsf9KoBQiKLAHO11XdSt59xshQZeDJmGEa9jlA_GY4\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"78c625386b4d0690b421eb0fc78f7b9c\",\"fileSHA256\":\"0FydMNefRaeOVYWi5-xnxqBqiD3ZE34dUFnoyHszznY\",\"storageKey\":\"yag9GLD2iZjnBBRuIa9kgMTdG2sabQ7v5K3JoDYQcqY\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"4403c6117ec30c859bc95d70ce4a71d3\",\"fileSHA256\":\"unIvskv_CkxS5lbN8gXerFxd-F5briv20wcTvVDG1fc\",\"storageKey\":\"uG5CihBixNlPMeYN0V5MRwARA2Ovn0seNtMvhelV3kE\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"069d99eb1fa6712c0b9034a58c6b57dd\",\"fileSHA256\":\"UwD1C-NlnRfYzuho7E5sDT9OiZRP1YmdPCD2p2egRfU\",\"storageKey\":\"dV5iuhAavksqtDhoNxjh3ZJ3mjhWaRm_k_QMsxTkJRg\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"c3273c9e5321f20d1e42c2efae2578c4\",\"fileSHA256\":\"bI8Rv9HH4idp3BNcFuzQ4FO7VVVNqIuWEdnPcdEhNvE\",\"storageKey\":\"r81JaSP6EG6dxkyPe8l7CAVizX4cmddQVze71LebmUo\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"286d67d3f74808a60a78d3ebf1a5fb57\",\"fileSHA256\":\"XQdeDCh8T0RBtbXI_H6lzSfBG7YX7OoJdVlVvLGIkIM\",\"storageKey\":\"m9yS4VLFfdQtO3uDS8O8dWqyH7JfIBfBd3VkPnFGw6c\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"d1ea1496f9057eb392d5bbf3732a61b7\",\"fileSHA256\":\"wV7HKnTROVchGAgxPVw13rf3YGxtItvdzrAADU4FR60\",\"storageKey\":\"iiX0QENtWnFHr-ql_G75Cv2aoG8T9jbnoTrrLiduBMs\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"19eeb73b9593a38f8e9f418337fc7d10\",\"fileSHA256\":\"ppou21tsYLhDPVttI8afUgzNimAvP2DnmW5Ilxi8F1w\",\"storageKey\":\"0OEDV0a6nXu3m-NCyMZCejvRaLMgUJoN5kZZr6WGO9o\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"ab19f4cbc543357183a20571f68380a3\",\"fileSHA256\":\"J_TSKdRAklv3IS1d__pF1Zayx7O7KQXJa4ObIIGh2-s\",\"storageKey\":\"1QHSPNPuPJBWsSu-NeGsZJ65lgP9Nn9hUrFpSvorJ3Y\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"d8b800c443b8972542883e0b9de2bdc6\",\"fileSHA256\":\"cIp5z1YbiipIUM4CVFhNOlIxBGvgj7f630tu39sxF4s\",\"storageKey\":\"F0VhhOdSAZAGWHzCBT8frAxWVDzVgNYGLlSTnInMLk0\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"412dd9275b6b48ad28f5e3d81bb1f626\",\"fileSHA256\":\"rPaRlXo7zOfRlYVtzpilzxTc4QvqKKpcELNVDAeOI7U\",\"storageKey\":\"YbOaNgsSGzVwiIMg8au7XcXF5nhp0mKG64zufThcPZQ\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"017bc6ba3fc25503e5eb5e53826d48a8\",\"fileSHA256\":\"beyjK1a5f6H6h_rNaZa0bMRRkKVTQA5_698dIJMJm1c\",\"storageKey\":\"92813hASY0kS8OnTdsF2wf_z0FEi_ZDIIaK-dzQ6BRU\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"20e71bdf79e3a97bf55fd9e164041578\",\"fileSHA256\":\"78edo8TJffsVcb_2IClYqwUpxaox6DkmAFeLPjWdFEc\",\"storageKey\":\"Ij5rVfQl_Hbg8xU1LXrdNRR__q6WTEE-FIneYIROiLQ\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"},{\"bundleKey\":\"98a4e3d00dbf5d65da99a69b38a7def2\",\"fileSHA256\":\"5T8eafhi5ZX10RnAQoY_8qRE_kKZbSK7q4drMAc9D7o\",\"storageKey\":\"Yb5h2M9HkuLPfXqyzIilFgPQ5wG_hioQ9IvHMQqHwt4\",\"contentType\":\"application/xml\",\"fileExtension\":\".xml\"},{\"bundleKey\":\"9b5cb09b4e669ed0f28f3b3f2871a0a5\",\"fileSHA256\":\"_aPxg66F5jvLznCnQnbqIgMyroQfdGw3CwsqtANVo1c\",\"storageKey\":\"TvYQWVORer08BYLO00Y1PcJDE1-Z-ZluvGfqmYd8gCs\",\"contentType\":\"application/xml\",\"fileExtension\":\".xml\"},{\"bundleKey\":\"0091d85c828ed5501c3e14af870b6cc3\",\"fileSHA256\":\"OYvRRqYuNptRd0cBPZvxHDi4XQzNGYc8WlSs4E9jQc0\",\"storageKey\":\"i8jndpqwsKKau5ggj5IVWiWRSGvkjX8O8rC9bolG2NY\",\"contentType\":\"font/ttf\",\"fileExtension\":\".ttf\"},{\"bundleKey\":\"9517d71c04d0e210173e851b26154dc6\",\"fileSHA256\":\"rHezdm1kqjQzyD3Qe_pS9TdYO2Bj3HHe8DckWRMGoMk\",\"storageKey\":\"-YnPaF8-eWzGqyhllli__46orJxkZo076oeaEiXdLdY\",\"contentType\":\"image/png\",\"fileExtension\":\".png\"}],\"launchAsset\":{\"bundleKey\":\"ee35a567f7baa61aac2f0fda1df0faf6\",\"fileSHA256\":\"pyQ1ctWZiYP3RHpaOQ3b3rn3APezTiOSdFAx0EkQrmk\",\"storageKey\":\"WctOYkqrqfdyrlTbk-P6k2RX-PlZKPj2Xsdet4Yh5Qg\",\"contentType\":\"application/javascript\",\"fileExtension\":\".hbc\"}}",
    "isRollBackToEmbedded": false,
    "manifestPermalink": "https://u.expo.dev/update/01a09479-5ffe-7a3f-ad7b-b6d0e995a840",
    "gitCommitHash": "792fd7bb1ab07ec632406d528b42dc1598a92539",
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
      "debugInfoUrl": "https://storage.googleapis.com/updates-runtime-fingerprints-production/production/da308684-07a9-4dfe-8cbb-aae0af6479b0/d78e7c67-6fbe-4fa1-b3b3-c0296cc54a56?X-Goog-Algorithm=GOOG4-RSA-SHA256&X-Goog-Credential=www-production%40exponentjs.iam.gserviceaccount.com%2F20260912%2Fauto%2Fstorage%2Fgoog4_request&X-Goog-Date=20260912T071559Z&X-Goog-Expires=900&X-Goog-SignedHeaders=host&X-Goog-Signature=a0920abd550fa6880bd40e40d43d7a65cba90e97000f47e7ffc5a4d3e3bdd1930ac33a9b675a37784c56273853ce6267f81e36559de04bf1e4adfa4865b1e1368ef716a8b26149e5e159e26e17c6e67985208a33971678750b6aa232cffd1e678859f9c04925f756a5040b6764e76d29b502dd38061a40a72fdc16b9bf62fdfc171c656ad3e97f411f2e5a1a1fa096eda909b56549890f8b148b127c300fdd52fa8ea6c794708fcec55da8fb8c9bf693c0df993bb12f86e2d6b04bb9b424efeb84d06ed288a7fcf3dc306a6aa906b721c82a7effcf0fe55f5c0dbd2275813e15d9e498dfec17fd4faf6027682d4bc20cbba94ce716bc087b87c81bb6d3add949",
      "source": {
        "type": "GCS",
        "bucketKey": "production/da308684-07a9-4dfe-8cbb-aae0af6479b0/d78e7c67-6fbe-4fa1-b3b3-c0296cc54a56"
      }
    }
  }
]
RC_PRODUCTION_REPUBLISH=0
PRODUCTION_GROUP_ID=98e2cb1c-2fb3-4a40-9e4c-c442a99f1eb5
REPUBLISH_STRATEGY=EXACT_CANDIDATE_GROUP_DESTINATION_CHANNEL_NO_ROLLOUT_PERCENTAGE

============================================================
7. AUTHORITATIVE POST-PUBLISH EXACT-GROUP VALIDATION
============================================================
RUN=PRODUCTION_EXACT_VIEW
COMMAND=eas_mobile update:view 98e2cb1c-2fb3-4a40-9e4c-c442a99f1eb5 --json
★ eas-cli@24.3.0 is now available.
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
RC_PRODUCTION_EXACT_VIEW=0
PRODUCTION_SUMMARY={"group":"98e2cb1c-2fb3-4a40-9e4c-c442a99f1eb5","branch":"production","runtime":"1.0.0-build17","platform":"android","gitCommit":"792fd7bb1ab07ec632406d528b42dc1598a92539","rollback":"false"}
PRODUCTION_UPDATE_VIEW=PASS_GROUP_BRANCH_RUNTIME_PLATFORM_COMMIT_NOT_ROLLBACK
RUN=LATEST_PRODUCTION_LIST
COMMAND=eas_mobile update:list --branch production --limit 1 --json --non-interactive
★ eas-cli@24.3.0 is now available.
To upgrade, run:
npm install -g eas-cli
Proceeding with outdated version.

{
  "name": "production",
  "id": "019fff34-708f-7613-99e7-c68e72f58ff1",
  "currentPage": [
    {
      "branch": "production",
      "message": "\"Promote Batch157 V2 verified Commission Build17 candidate 792fd7b 20260912-091352\" (15 seconds ago by ald1n)",
      "runtimeVersion": "1.0.0-build17",
      "isRollBackToEmbedded": false,
      "group": "98e2cb1c-2fb3-4a40-9e4c-c442a99f1eb5",
      "platforms": "android"
    }
  ]
}
RC_LATEST_PRODUCTION_LIST=0
PRODUCTION_BRANCH_LATEST=PASS_EXACT_REPUBLISHED_GROUP

============================================================
8. FINAL SOURCE IMMUTABILITY + HANDOFF
============================================================
RUN=FINAL_GIT_FETCH
COMMAND=git fetch origin main
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
RC_FINAL_GIT_FETCH=0
FINAL_LOCAL_HEAD=792fd7bb1ab07ec632406d528b42dc1598a92539
FINAL_REMOTE_HEAD=792fd7bb1ab07ec632406d528b42dc1598a92539
SOURCE_MUTATION=NO
SOURCE_AUTHORITY=PASS_UNCHANGED_HEAD_WITH_KNOWN_HTACCESS_ONLY
BUILD_CREATED=NO
GOOGLE_PLAY_ACTION=NO
PRODUCTION_BUSINESS_WRITES=NONE_BY_BATCH
PRODUCT_VARIANTS_REINTRODUCED=NO

============================================================
FINAL CERTIFICATION
============================================================
BATCH157_RESULT=PASS_CONTROLLED_BUILD17_COMPATIBLE_COMMISSION_PRODUCTION_OTA
BATCH157_V2_RESULT=PASS_CWD_QUERY_PATH_RECOVERY_AND_COMMISSION_PRODUCTION_OTA
REPORT438_RESULT=PASS
REPORT437_AUTHORITY=PASS_EXACT_SHA_FAIL_EAS_AUTHORITY_NO_MUTATION_AND_V2_NEXT_ACTION
ROOT_CAUSE_CLASSIFICATION=V1_WRONG_EAS_PROJECT_CWD
UPDATE_LIST_MODE=WORKING_FROM_MOBILE_ROOT
TERMINAL_OUTPUT_MODE=PASS_VERBOSE_LIVE_MIRROR_TO_TERMINAL_AND_REPORT
BUILD17_RUNTIME=1.0.0-build17
BUILD18_REQUIRED=NO_BUILD17_COMPATIBLE_JS_TS_ONLY
BUILD17_BINARY_AUTHORITY=PASS_EXACT_SHA256
CMS_STATIC=REUSED_REPORT437_PASS_983_TOTAL_0_FAILED
MOBILE_TYPESCRIPT=REUSED_REPORT437_PASS
MOBILE_VALIDATOR=REUSED_REPORT437_PASS_ZERO_FAIL
COMMISSION_SINGLE_PAGE_SOURCE=PASS
CANDIDATE_PUBLISHED=YES
CANDIDATE_GROUP_ID=62c5a9fd-279b-4321-bae7-a86853c71cae
PREVIOUS_PRODUCTION_GROUP_ID=cdd2aebd-6eb9-4ef9-9a05-713d0a3acc04
PRODUCTION_GROUP_ID=98e2cb1c-2fb3-4a40-9e4c-c442a99f1eb5
PRODUCTION_OTA_PUBLISHED=YES_ANDROID_PRODUCTION_BUILD17_RUNTIME
PRODUCTION_OTA_ROLLBACK=NOT_REQUIRED
EAS_BUILD=NO
GOOGLE_PLAY_ACTION=NO
SOURCE_MUTATION=NO
PHYSICAL_DEVICE_ACCEPTANCE=PENDING_COMMISSION_SINGLE_PAGE_WORKFLOW
DEVICE_ACCEPTANCE_BOOT_SEQUENCE=FORCE_CLOSE_OPEN_WAIT_FOR_UPDATE_FORCE_CLOSE_REOPEN
DEVICE_ACCEPTANCE_SCOPE=ADMIN_PROVIZIJE_DIRECT_LIST_ORDER_VALUE_SINGLE_PAGE_BREAKDOWN_APPROVE_PAYOUT_PAID_HISTORY_PLUS_EXISTING_BUILD17_REGRESSION
NEXT_ACTION=RUN_BATCH158_COMMISSION_PHYSICAL_DEVICE_ACCEPTANCE
REPORT_PATH=/home/icaffeco/ald1n-project/docs/operations/438-MOBILE-COMMISSION-BUILD17-COMPATIBLE-PRODUCTION-OTA-BATCH157-V2-CWD-QUERY-RECOVERY-20260912-091352.md
FINAL_REPORT_GIT_STATE=INTENTIONALLY_UNTRACKED_REPORT438_PRE_BATCH158
PASS: BATCH157 V2 CWD/QUERY RECOVERY AND COMMISSION BUILD17-COMPATIBLE PRODUCTION OTA COMPLETE
