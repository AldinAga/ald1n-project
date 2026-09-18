
============================================================
BATCH165 - EAS CLI 24.7.0 COMPATIBILITY AUDIT - READ ONLY
============================================================
TIMESTAMP=20260917-122610
TERMINAL_OUTPUT_MODE=VERBOSE_LIVE_MIRROR_TO_TERMINAL_AND_REPORT
SCOPE=read_only_compare_eas_cli_23_2_0_to_24_7_0_on_current_production_authority
APP_SOURCE_CHANGE=NO
OTA_ACTION=NO
BUILD_ACTION=NO
GOOGLE_PLAY_ACTION=NO
CANONICAL_PIN_CHANGE=NO_THIS_BATCH

============================================================
PREFLIGHT - BIND BATCH164 PASS AUTHORITY
============================================================
REPORT164=/home/icaffeco/ald1n-project/docs/operations/BATCH164-POST-NOTIFICATION-FIX-CHECKPOINT-AND-GUARDRAIL-UPDATE-20260917-120957.md
REPORT164_SHA=6b25eea26769a5df7c4acfa938fa636ebafb7dd36402adf86809bff022cb6c6a
REPORT164_BINDING=PASS
BATCH164_PASS_AND_NEXT_ACTION=PASS_BOUND

============================================================
PREFLIGHT - GIT AND HOSTING AUTHORITY
============================================================
RUN=git_fetch
COMMAND=git -C /home/icaffeco/ald1n-project fetch origin main
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
RC_git_fetch=0
BRANCH=main
LOCAL_HEAD=08705c2487a86bca8bea748620f86d87d6aea1c7
REMOTE_HEAD=08705c2487a86bca8bea748620f86d87d6aea1c7
RC_PREEXISTING_STAGED_DIFF=0
HTACCESS_FILE_SHA=d10ecf0d63f9a8e1bd978a440afcbadb34faf1d24eb899bf2582d1e5c0033cef
HTACCESS_DIFF_SHA=8ce9fa55a71768bf711203fcc50be8eec6de26886bbf4852ceb30a97b36958fe
KNOWN_HTACCESS_DRIFT=PASS
NODE_BIN=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node
NPM_CLI=/opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js
CMS_TREE_BEFORE=703f24e38d294911b002268196869d606865abc1
MOBILE_TREE_BEFORE=1a537e41b67f607fbb5b3f23ea48ea8059c6b9c6
API_TREE_BEFORE=a30324349de94b9511e5341887940d2cbf2baa14

============================================================
PREFLIGHT - AGENTS.MD CURRENT EAS POLICY
============================================================
AGENTS_CURRENT_PIN=eas-cli@23.2.0
AGENTS_UPDATE_VIEW_FLAG_RULE=PASS
AGENTS_MAJOR_UPGRADE_POLICY=PASS
RUN=json_helper_syntax
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node --check /home/icaffeco/.ald1n-batch165-20260917-122610/eas-audit-helper.mjs
RC_json_helper_syntax=0

============================================================
BASELINE - CURRENT CANONICAL EAS CLI 23.2.0
============================================================
RUN=eas23_version
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@23.2.0 -- eas --version
★ eas-cli@24.7.0 is now available.
To upgrade, run:
npm install -g eas-cli
Proceeding with outdated version.

eas-cli/23.2.0 linux-x64 node-v22.23.2
RC_eas23_version=0
RUN=eas23_whoami
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
(node:1869109) [UnparsedCommand] Warning: Command account:view did not parse its arguments. Did you forget to call 'this.parse'?
(Use `node --trace-warnings ...` to show where the warning was created)
RC_eas23_whoami=0
RUN=eas23_update_list
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
EAS_PACKAGE=eas-cli@23.2.0
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
      "message": "\"Promote Batch162 V5 notification admin-order routing fix d73fde2 20260917-114858\" (35 minutes ago by ald1n)",
      "runtimeVersion": "1.0.0-build17",
      "isRollBackToEmbedded": false,
      "group": "9a3774f5-99fe-4008-8c85-2cad8b5c5a2e",
      "platforms": "android"
    }
  ]
}
RC_eas23_update_list=0
RC_eas23_update_list_stdout_tee=0
RC_eas23_update_list_stderr_tee=0
RUN=verify_eas23_list
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /home/icaffeco/.ald1n-batch165-20260917-122610/eas-audit-helper.mjs list /home/icaffeco/.ald1n-batch165-20260917-122610/eas23-list.json 9a3774f5-99fe-4008-8c85-2cad8b5c5a2e 1.0.0-build17 -
PARSED_GROUP=9a3774f5-99fe-4008-8c85-2cad8b5c5a2e
PARSED_RUNTIME=1.0.0-build17
PARSED_PLATFORM=android
RC_verify_eas23_list=0
RUN=eas23_update_view
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
EAS_PACKAGE=eas-cli@23.2.0
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
RC_eas23_update_view_stdout_tee=0
RC_eas23_update_view_stderr_tee=0
RUN=verify_eas23_view
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /home/icaffeco/.ald1n-batch165-20260917-122610/eas-audit-helper.mjs view /home/icaffeco/.ald1n-batch165-20260917-122610/eas23-view.json 9a3774f5-99fe-4008-8c85-2cad8b5c5a2e 1.0.0-build17 277118631d11828162e6d2992dd02d9c1eaddaf6
PARSED_GROUP=9a3774f5-99fe-4008-8c85-2cad8b5c5a2e
PARSED_RUNTIME=1.0.0-build17
PARSED_PLATFORM=android
PARSED_COMMIT=277118631d11828162e6d2992dd02d9c1eaddaf6
RC_verify_eas23_view=0
EAS23_BASELINE=PASS

============================================================
AUDIT - EAS CLI 24.7.0 VERSION AND AUTHENTICATION
============================================================
RUN=eas24_version
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas --version
npm warn deprecated inflight@1.0.6: This module is not supported, and leaks memory. Do not use it. Check out lru-cache if you want a good and tested way to coalesce async requests by a key value, which is much more comprehensive and powerful.
npm warn deprecated rimraf@2.4.5: Rimraf versions prior to v4 are no longer supported
npm warn deprecated glob@6.0.4: Old versions of glob are not supported, and contain widely publicized security vulnerabilities, which have been fixed in the current version. Please update. Support for old versions may be purchased (at exorbitant rates) by contacting i@izs.me
npm warn deprecated uuid@7.0.3: uuid@10 and below is no longer supported.  For ESM codebases, update to uuid@latest.  For CommonJS codebases, use uuid@11 (but be aware this version will likely be deprecated in 2028).
npm warn deprecated uuid@8.3.2: uuid@10 and below is no longer supported.  For ESM codebases, update to uuid@latest.  For CommonJS codebases, use uuid@11 (but be aware this version will likely be deprecated in 2028).
npm warn deprecated uuid@8.3.2: uuid@10 and below is no longer supported.  For ESM codebases, update to uuid@latest.  For CommonJS codebases, use uuid@11 (but be aware this version will likely be deprecated in 2028).
npm warn deprecated glob@10.5.0: Old versions of glob are not supported, and contain widely publicized security vulnerabilities, which have been fixed in the current version. Please update. Support for old versions may be purchased (at exorbitant rates) by contacting i@izs.me
npm warn deprecated glob@10.5.0: Old versions of glob are not supported, and contain widely publicized security vulnerabilities, which have been fixed in the current version. Please update. Support for old versions may be purchased (at exorbitant rates) by contacting i@izs.me
eas-cli/24.7.0 linux-x64 node-v22.23.2
RC_eas24_version=0
EAS24_VERSION_EXACT=PASS_24.7.0
RUN=eas24_whoami
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas whoami
ald1n
pruzljanin@gmail.com

Accounts:
• ald1n (Role: Owner)
• ald1ns-team (Role: Owner)
(node:1873467) [UnparsedCommand] Warning: Command account:view did not parse its arguments. Did you forget to call 'this.parse'?
(Use `node --trace-warnings ...` to show where the warning was created)
RC_eas24_whoami=0
EAS24_AUTHORITY_ACCOUNT=PASS_ALD1N

============================================================
AUDIT - EAS CLI 24.7.0 READ-ONLY PRODUCTION COMMANDS
============================================================
RUN=eas24_update_list
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
EAS_PACKAGE=eas-cli@24.7.0
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas update:list --branch production --limit 1 --json --non-interactive
{
  "name": "production",
  "id": "019fff34-708f-7613-99e7-c68e72f58ff1",
  "currentPage": [
    {
      "branch": "production",
      "message": "\"Promote Batch162 V5 notification admin-order routing fix d73fde2 20260917-114858\" (36 minutes ago by ald1n)",
      "runtimeVersion": "1.0.0-build17",
      "isRollBackToEmbedded": false,
      "group": "9a3774f5-99fe-4008-8c85-2cad8b5c5a2e",
      "platforms": "android"
    }
  ]
}
RC_eas24_update_list=0
RC_eas24_update_list_stdout_tee=0
RC_eas24_update_list_stderr_tee=0
RUN=verify_eas24_list
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /home/icaffeco/.ald1n-batch165-20260917-122610/eas-audit-helper.mjs list /home/icaffeco/.ald1n-batch165-20260917-122610/eas24-list.json 9a3774f5-99fe-4008-8c85-2cad8b5c5a2e 1.0.0-build17 -
PARSED_GROUP=9a3774f5-99fe-4008-8c85-2cad8b5c5a2e
PARSED_RUNTIME=1.0.0-build17
PARSED_PLATFORM=android
RC_verify_eas24_list=0
EAS24_UPDATE_LIST_COMPATIBILITY=PASS
RUN=eas24_update_view
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
EAS_PACKAGE=eas-cli@24.7.0
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
RC_eas24_update_view=0
RC_eas24_update_view_stdout_tee=0
RC_eas24_update_view_stderr_tee=0
RUN=verify_eas24_view
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /home/icaffeco/.ald1n-batch165-20260917-122610/eas-audit-helper.mjs view /home/icaffeco/.ald1n-batch165-20260917-122610/eas24-view.json 9a3774f5-99fe-4008-8c85-2cad8b5c5a2e 1.0.0-build17 277118631d11828162e6d2992dd02d9c1eaddaf6
PARSED_GROUP=9a3774f5-99fe-4008-8c85-2cad8b5c5a2e
PARSED_RUNTIME=1.0.0-build17
PARSED_PLATFORM=android
PARSED_COMMIT=277118631d11828162e6d2992dd02d9c1eaddaf6
RC_verify_eas24_view=0
EAS24_UPDATE_VIEW_JSON_COMPATIBILITY=PASS

============================================================
AUDIT - EAS CLI 24.7.0 COMMAND FLAG SURFACE
============================================================
RUN=eas24_update_list_help
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas update:list --help
view the recent updates

USAGE
  $ eas update:list [--branch <value> | --all] [-p android|ios|all]
    [--runtime-version <value>] [--offset <value>] [--limit <value>] [--json]
    [--non-interactive]

FLAGS
  -p, --platform=<option>        Filter updates by platform
                                 <options: android|ios|all>
      --all                      List updates on all branches
      --branch=<value>           List updates only on this branch
      --json                     Enable JSON output, non-JSON messages will be
                                 printed to stderr. Implies --non-interactive.
      --limit=<value>            The number of items to fetch each query.
                                 Defaults to 25 and is capped at 50.
      --non-interactive          Run the command in non-interactive mode.
      --offset=<value>           Start queries from specified index. Use for
                                 paginating results. Defaults to 0.
      --runtime-version=<value>  Filter updates by runtime version

DESCRIPTION
  view the recent updates

RC_eas24_update_list_help=0
EAS24_UPDATE_LIST_REQUIRED_FLAGS=PASS
RUN=eas24_update_view_help
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
EAS24_UPDATE_VIEW_HELP_NON_INTERACTIVE=NOT_ADVERTISED
RUN=eas24_update_help
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
RUN=eas24_republish_help
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
AUDIT - UPDATE:VIEW NON-INTERACTIVE BEHAVIOR MAP FOR 24.7.0
============================================================
RUN=eas24_update_view_noninteractive_probe
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
EAS_PACKAGE=eas-cli@24.7.0
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas update:view 9a3774f5-99fe-4008-8c85-2cad8b5c5a2e --json --non-interactive
Nonexistent flag: --non-interactive
See more help with --help
    Error: update:view command failed.
RC_eas24_update_view_noninteractive_probe=1
RC_eas24_update_view_noninteractive_probe_stdout_tee=0
RC_eas24_update_view_noninteractive_probe_stderr_tee=0
RC_EAS24_UPDATE_VIEW_NONINTERACTIVE_PROBE=1
EAS24_UPDATE_VIEW_NONINTERACTIVE_PROBE=UNEXPECTED_FAILURE

============================================================
FINAL SUMMARY
============================================================
BATCH165_RESULT=FAIL_EAS_CLI_24_7_0_COMPATIBILITY_AUDIT_READ_ONLY
FAILED_STAGE=EAS24_UPDATE_VIEW_NONINTERACTIVE_UNEXPECTED_FAILURE
SOURCE_MUTATION=NO
COMMIT_CREATED=NO
PUSH_COMPLETED=NO
OTA_MUTATION=NO
BUILD_CREATED=NO
GOOGLE_PLAY_ACTION=NO
CANONICAL_EAS_PIN_CHANGED=NO
RECOMMEND_PIN_UPGRADE=NO
NEXT_ACTION=RUN_BATCH165_V2_TARGETED_RECOVERY_FROM_THIS_REPORT
REPORT_PATH=/home/icaffeco/ald1n-project/docs/operations/BATCH165-EAS-CLI-24-7-0-COMPATIBILITY-AUDIT-READ-ONLY-20260917-122610.md
