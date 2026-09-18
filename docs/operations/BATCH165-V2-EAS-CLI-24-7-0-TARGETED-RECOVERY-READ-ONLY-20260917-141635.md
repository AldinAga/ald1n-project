
============================================================
BATCH165 V2 - EAS CLI 24.7.0 TARGETED RECOVERY - READ ONLY
============================================================
TIMESTAMP=20260917-141635
SCOPE=targeted_recovery_of_batch165_invalid_update_view_noninteractive_expectation
ROOT_CAUSE_HYPOTHESIS=update_view_does_not_support_explicit_non_interactive_flag_but_group_id_plus_json_is_non_prompting
APP_SOURCE_CHANGE=NO
OTA_ACTION=NO
BUILD_ACTION=NO
GOOGLE_PLAY_ACTION=NO
CANONICAL_PIN_CHANGE=NO_THIS_BATCH

============================================================
PREFLIGHT - BIND BATCH165 FAILURE REPORT
============================================================
SOURCE_REPORT=/home/icaffeco/ald1n-project/docs/operations/BATCH165-EAS-CLI-24-7-0-COMPATIBILITY-AUDIT-READ-ONLY-20260917-122610.md
SOURCE_REPORT_SHA_ACTUAL=2a17ed7fe93bb900f11d307ae703a364072c5ffaf441e8aa057dff4be493b92e
SOURCE_REPORT_SHA_EXPECTED=2a17ed7fe93bb900f11d307ae703a364072c5ffaf441e8aa057dff4be493b92e
BATCH165_FAIL_AUTHORITY=PASS_BOUND

============================================================
PREFLIGHT - GIT AND HOSTING AUTHORITY
============================================================
LOCAL_HEAD=08705c2487a86bca8bea748620f86d87d6aea1c7
REMOTE_HEAD=08705c2487a86bca8bea748620f86d87d6aea1c7
TRACKED_DIFF_BEFORE_SHA=8ce9fa55a71768bf711203fcc50be8eec6de26886bbf4852ceb30a97b36958fe
HTACCESS_FILE=/home/icaffeco/ald1n-project/apps/cms/current/public/.htaccess
HTACCESS_SHA_ACTUAL=d10ecf0d63f9a8e1bd978a440afcbadb34faf1d24eb899bf2582d1e5c0033cef

============================================================
PREFLIGHT - TOOLCHAIN
============================================================
RC_json_helper_syntax=0

============================================================
ROOT CAUSE - COMMAND SURFACE CONTRACT
============================================================

============================================================
RUN - eas24_update_view_help
============================================================
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas update:view --help 
update group details

USAGE
  $ eas update:view GROUPID [--insights] [--days <value> | --start
    <value> | --end <value>] [--json]

ARGUMENTS
  GROUPID  The ID of an update group, or the ID of a platform-specific update.

FLAGS
  --days=<value>   Show insights from the last N days (default 7). Only used
                   with --insights.
  --end=<value>    End of insights time range (ISO date). Only used with
                   --insights.
  --insights       Also show insights (launches, crash rate, unique users,
                   payload size) for the update group.
  --json           Enable JSON output, non-JSON messages will be printed to
                   stderr.
  --start=<value>  Start of insights time range (ISO date). Only used with
                   --insights.

DESCRIPTION
  update group details

RC_eas24_update_view_help=0
EAS24_UPDATE_VIEW_JSON_FLAG=PASS
EAS24_UPDATE_VIEW_EXPLICIT_NON_INTERACTIVE_FLAG=INTENTIONALLY_UNSUPPORTED
ROOT_CAUSE=INVALID_EXPECTATION_UPDATE_VIEW_NONINTERACTIVE_FLAG

============================================================
ACTIVE REPOSITORY USAGE SCAN
============================================================
ACTIVE_UPDATE_VIEW_NONINTERACTIVE_USAGE=NONE

============================================================
BASELINE - EAS CLI 23.2.0
============================================================

============================================================
RUN - eas23_version
============================================================
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@23.2.0 -- eas --version 
★ eas-cli@24.7.0 is now available.
To upgrade, run:
npm install -g eas-cli
Proceeding with outdated version.

eas-cli/23.2.0 linux-x64 node-v22.23.2
RC_eas23_version=0

============================================================
RUN - eas23_whoami
============================================================
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@23.2.0 -- eas whoami 
★ eas-cli@24.7.0 is now available.
To upgrade, run:
npm install -g eas-cli
Proceeding with outdated version.

(node:2293354) [UnparsedCommand] Warning: Command account:view did not parse its arguments. Did you forget to call 'this.parse'?
(Use `node --trace-warnings ...` to show where the warning was created)
ald1n
pruzljanin@gmail.com

Accounts:
• ald1n (Role: Owner)
• ald1ns-team (Role: Owner)
RC_eas23_whoami=0

============================================================
RUN - eas23_update_list
============================================================
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
      "message": "\"Promote Batch162 V5 notification admin-order routing fix d73fde2 20260917-114858\" (2 hours ago by ald1n)",
      "runtimeVersion": "1.0.0-build17",
      "isRollBackToEmbedded": false,
      "group": "9a3774f5-99fe-4008-8c85-2cad8b5c5a2e",
      "platforms": "android"
    }
  ]
}
RC_eas23_update_list=0
EAS23_UPDATE_LIST=PASS

============================================================
RUN - eas23_update_view
============================================================
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
RC_eas23_update_view=0
EAS23_UPDATE_VIEW=PASS

============================================================
AUDIT - EAS CLI 24.7.0
============================================================

============================================================
RUN - eas24_version
============================================================
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas --version 
eas-cli/24.7.0 linux-x64 node-v22.23.2
RC_eas24_version=0
EAS24_VERSION_EXACT=PASS_24.7.0

============================================================
RUN - eas24_whoami
============================================================
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas whoami 
(node:2294933) [UnparsedCommand] Warning: Command account:view did not parse its arguments. Did you forget to call 'this.parse'?
(Use `node --trace-warnings ...` to show where the warning was created)
ald1n
pruzljanin@gmail.com

Accounts:
• ald1n (Role: Owner)
• ald1ns-team (Role: Owner)
RC_eas24_whoami=0
EAS24_AUTHORITY_ACCOUNT=PASS_ALD1N

============================================================
RUN - eas24_update_list
============================================================
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas update:list --branch production --limit 1 --json --non-interactive 
{
  "name": "production",
  "id": "019fff34-708f-7613-99e7-c68e72f58ff1",
  "currentPage": [
    {
      "branch": "production",
      "message": "\"Promote Batch162 V5 notification admin-order routing fix d73fde2 20260917-114858\" (2 hours ago by ald1n)",
      "runtimeVersion": "1.0.0-build17",
      "isRollBackToEmbedded": false,
      "group": "9a3774f5-99fe-4008-8c85-2cad8b5c5a2e",
      "platforms": "android"
    }
  ]
}
RC_eas24_update_list=0
EAS24_UPDATE_LIST=PASS

============================================================
RUN - eas24_update_view_json_ci
============================================================
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas update:view 9a3774f5-99fe-4008-8c85-2cad8b5c5a2e --json 
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
RC_eas24_update_view_json_ci=0
EAS24_UPDATE_VIEW_JSON_CI=PASS_WITHOUT_PROMPT_FLAG

============================================================
SEMANTIC PARITY - 23.2.0 VS 24.7.0
============================================================
EAS_UPDATE_LIST_SEMANTIC_PARITY=PASS
EAS_UPDATE_VIEW_SEMANTIC_PARITY=PASS

============================================================
COMMAND FLAG SURFACE - MUTATING COMMANDS HELP ONLY
============================================================

============================================================
RUN - eas24_update_help
============================================================
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas update --help 
publish an update group

USAGE
  $ eas update [--branch <value>] [--channel <value>] [-m <value>]
    [--input-dir <value>] [--skip-bundler] [--clear-cache] [--emit-metadata]
    [--rollout-percentage <value>] [-p android|ios|all] [--auto]
    [--private-key-path <value>] [--environment <value>] [--json]
    [--non-interactive]

FLAGS
  -m, --message=<value>
      A short message describing the update

  -p, --platform=<option>
      [default: all]
      <options: android|ios|all>

  --auto
      Use the current git branch and commit message for the EAS branch and update
      message

  --branch=<value>
      Branch to publish the update group on

  --channel=<value>
      Channel that the published update should affect

  --clear-cache
      Clear the bundler cache before publishing

  --emit-metadata
      Emit "eas-update-metadata.json" in the bundle folder with detailed
      information about the generated updates

  --environment=<value>
      Environment to use for the server-side defined EAS environment variables
      during command execution, e.g. "production", "preview", "development".
      Required for projects using Expo SDK 55 or greater.

  --input-dir=<value>
      [default: dist] Location of the bundle

  --json
      Enable JSON output, non-JSON messages will be printed to stderr. Implies
      --non-interactive.

  --non-interactive
      Run the command in non-interactive mode.

  --private-key-path=<value>
      File containing the PEM-encoded private key corresponding to the certificate
      in expo-updates' configuration. Defaults to a file named "private-key.pem"
      in the certificate's directory. Only relevant if you are using code signing:
      https://docs.expo.dev/eas-update/code-signing/

  --rollout-percentage=<value>
      Percentage of users this update should be immediately available to. Users
      not in the rollout will be served the previous latest update on the branch,
      even if that update is itself being rolled out. The specified number must be
      an integer between 1 and 100. When not specified, this defaults to 100.

  --skip-bundler
      Skip running Expo CLI to bundle the app before publishing

DESCRIPTION
  publish an update group

TOPICS
  update:embedded  manage embedded updates registered with EAS Update

COMMANDS
  update:configure              configure the project to support EAS Update
  update:delete                 delete all the updates in an update group
  update:edit                   edit all the updates in an update group
  update:insights               display launch, crash, unique-user, and size
                                insights for an update group
  update:list                   view the recent updates
  update:republish              roll back to an existing update
  update:revert-update-rollout  revert a rollout update for a project
  update:roll-back-to-embedded  roll back to the embedded update
  update:rollback               roll back to an embedded update or an existing
                                update
  update:view                   update group details

RC_eas24_update_help=0
EAS24_UPDATE_REQUIRED_FLAGS=PASS

============================================================
RUN - eas24_republish_help
============================================================
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas update:republish --help 
roll back to an existing update

USAGE
  $ eas update:republish [--channel <value> | --branch <value> | --group
    <value>] [--destination-channel <value> | --destination-branch <value>] [-m
    <value>] [-p android|ios|all] [--private-key-path <value>]
    [--rollout-percentage <value>] [--json] [--non-interactive]

FLAGS
  -m, --message=<value>
      Short message describing the republished update group

  -p, --platform=<option>
      [default: all]
      <options: android|ios|all>

  --branch=<value>
      Branch name to select an update group to republish from

  --channel=<value>
      Channel name to select an update group to republish from

  --destination-branch=<value>
      Branch name to republish to if republishing to a different branch

  --destination-channel=<value>
      Channel name to select a branch to republish to if republishing to a
      different branch

  --group=<value>
      Update group ID to republish

  --json
      Enable JSON output, non-JSON messages will be printed to stderr. Implies
      --non-interactive.

  --non-interactive
      Run the command in non-interactive mode.

  --private-key-path=<value>
      File containing the PEM-encoded private key corresponding to the certificate
      in expo-updates' configuration. Defaults to a file named "private-key.pem"
      in the certificate's directory. Only relevant if you are using code signing:
      https://docs.expo.dev/eas-update/code-signing/

  --rollout-percentage=<value>
      Percentage of users this update should be immediately available to. Users
      not in the rollout will be served the previous latest update on the branch,
      even if that update is itself being rolled out. The specified number must be
      an integer between 1 and 100. When not specified, this defaults to 100.

DESCRIPTION
  roll back to an existing update

RC_eas24_republish_help=0
EAS24_REPUBLISH_REQUIRED_FLAGS=PASS

============================================================
POSTFLIGHT - NO PROJECT MUTATION
============================================================
TRACKED_DIFF_BEFORE_SHA=8ce9fa55a71768bf711203fcc50be8eec6de26886bbf4852ceb30a97b36958fe
TRACKED_DIFF_AFTER_SHA=8ce9fa55a71768bf711203fcc50be8eec6de26886bbf4852ceb30a97b36958fe
SOURCE_MUTATION=NO
GIT_AUTHORITY_STABLE=PASS
KNOWN_HTACCESS_DRIFT_STABLE=PASS

============================================================
FINAL SUMMARY
============================================================
BATCH165_V2_RESULT=PASS_EAS_CLI_24_7_0_COMPATIBILITY_AUDIT_READ_ONLY
ROOT_CAUSE=INVALID_EXPECTATION_UPDATE_VIEW_NONINTERACTIVE_FLAG
EAS24_UPDATE_VIEW_CONTRACT=GROUP_ID_PLUS_JSON_SUPPORTED_EXPLICIT_NON_INTERACTIVE_FLAG_UNSUPPORTED
EAS23_24_PRODUCTION_READ_ONLY_PARITY=PASS
SOURCE_MUTATION=NO
COMMIT_CREATED=NO
PUSH_COMPLETED=NO
OTA_MUTATION=NO
BUILD_CREATED=NO
GOOGLE_PLAY_ACTION=NO
CANONICAL_EAS_PIN_CHANGED=NO
RECOMMEND_PIN_UPGRADE=YES_NEXT_BATCH
RECOMMENDED_PIN=eas-cli@24.7.0
PIN_UPGRADE_REQUIRES_SEPARATE_MUTATING_BATCH=YES
NEXT_ACTION=PREPARE_BATCH166_EAS_CLI_24_7_0_CANONICAL_PIN_UPGRADE
REPORT_PATH=/home/icaffeco/ald1n-project/docs/operations/BATCH165-V2-EAS-CLI-24-7-0-TARGETED-RECOVERY-READ-ONLY-20260917-141635.md
