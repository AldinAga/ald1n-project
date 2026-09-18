
============================================================
BATCH166 - EAS CLI 24.7.0 CANONICAL PIN UPGRADE
============================================================
TIMESTAMP=20260918-120132
SCOPE=canonical_eas_cli_authority_upgrade_23_2_0_to_24_7_0
APP_SOURCE_CHANGE=NO
APP_DOCUMENTATION_CHANGE=YES_AGENTS_MD_ONLY
OTA_ACTION=NO
BUILD_ACTION=NO
GOOGLE_PLAY_ACTION=NO

============================================================
PREFLIGHT - BIND BATCH165 V2 PASS AUTHORITY
============================================================
SOURCE_REPORT=/home/icaffeco/ald1n-project/docs/operations/BATCH165-V2-EAS-CLI-24-7-0-TARGETED-RECOVERY-READ-ONLY-20260917-141635.md
SOURCE_REPORT_SHA_ACTUAL=4c87ea13dee27a7813da6675a6d2e0e5c769242d687eef97fb5e6c885c3664f2
SOURCE_REPORT_SHA_EXPECTED=4c87ea13dee27a7813da6675a6d2e0e5c769242d687eef97fb5e6c885c3664f2
BATCH165_V2_PASS_AUTHORITY=PASS_BOUND

============================================================
PREFLIGHT - GIT AND HOSTING AUTHORITY
============================================================

============================================================
RUN - git_fetch_preflight
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=git fetch origin main
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
RC_git_fetch_preflight=0
BRANCH=main
LOCAL_HEAD=08705c2487a86bca8bea748620f86d87d6aea1c7
REMOTE_HEAD=08705c2487a86bca8bea748620f86d87d6aea1c7
EXPECTED_START_HEAD=08705c2487a86bca8bea748620f86d87d6aea1c7
RC_PREEXISTING_STAGED_DIFF=0
HTACCESS_SHA_ACTUAL=d10ecf0d63f9a8e1bd978a440afcbadb34faf1d24eb899bf2582d1e5c0033cef
HTACCESS_SHA_EXPECTED=d10ecf0d63f9a8e1bd978a440afcbadb34faf1d24eb899bf2582d1e5c0033cef
HTACCESS_DIFF_SHA_ACTUAL=8ce9fa55a71768bf711203fcc50be8eec6de26886bbf4852ceb30a97b36958fe
HTACCESS_DIFF_SHA_EXPECTED=8ce9fa55a71768bf711203fcc50be8eec6de26886bbf4852ceb30a97b36958fe
TRACKED_DIFF_NAMES_BEGIN
apps/cms/current/public/.htaccess
TRACKED_DIFF_NAMES_END
KNOWN_HTACCESS_DRIFT=PASS

============================================================
PREFLIGHT - CANONICAL AGENTS CONTRACT
============================================================
OLD_SPEC_COUNT=5
OLD_PLAIN_COUNT=2
NEW_SPEC_COUNT_BEFORE=0
NEW_PLAIN_COUNT_BEFORE=0

============================================================
PREFLIGHT - TOOLCHAIN
============================================================

============================================================
RUN - patch_helper_syntax
============================================================
CWD=/home/icaffeco/.ald1n-batch166-20260918-120132
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node --check /home/icaffeco/.ald1n-batch166-20260918-120132/patch-agents.mjs
RC_patch_helper_syntax=0

============================================================
RUN - json_helper_syntax
============================================================
CWD=/home/icaffeco/.ald1n-batch166-20260918-120132
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node --check /home/icaffeco/.ald1n-batch166-20260918-120132/verify-eas-json.mjs
RC_json_helper_syntax=0

============================================================
MUTATION - UPGRADE CANONICAL EAS PIN
============================================================

============================================================
RUN - patch_agents
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /home/icaffeco/.ald1n-batch166-20260918-120132/patch-agents.mjs /home/icaffeco/ald1n-project/AGENTS.md
BEFORE_OLD_SPEC_COUNT=5
BEFORE_OLD_PLAIN_COUNT=2
BEFORE_NEW_SPEC_COUNT=0
BEFORE_NEW_PLAIN_COUNT=0
AFTER_OLD_SPEC_COUNT=0
AFTER_OLD_PLAIN_COUNT=0
AFTER_NEW_SPEC_COUNT=5
AFTER_NEW_PLAIN_COUNT=2
PATCH_AGENTS_RESULT=PASS
RC_patch_agents=0
OLD_SPEC_COUNT_AFTER=0
OLD_PLAIN_COUNT_AFTER=0
NEW_SPEC_COUNT_AFTER=5
NEW_PLAIN_COUNT_AFTER=2

============================================================
VERIFY - SOURCE DIFF SCOPE
============================================================

============================================================
RUN - git_diff_agents
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=git diff -- AGENTS.md
diff --git a/AGENTS.md b/AGENTS.md
index b1b6a9e..fbf0df3 100644
--- a/AGENTS.md
+++ b/AGENTS.md
@@ -120,13 +120,13 @@ Do **not** require or probe for a physical/global `eas` binary as a hard prerequ
 Canonical EAS CLI version:
 
 ```text
-eas-cli@23.2.0
+eas-cli@24.7.0
 ```
 
 Approved invocation:
 
 ```bash
-EAS_SPEC="eas-cli@23.2.0"
+EAS_SPEC="eas-cli@24.7.0"
 "$NODE_BIN" "$NPM_CLI" exec --yes --package "$EAS_SPEC" -- eas --version
 ```
 
@@ -153,11 +153,11 @@ Do not execute project-scoped EAS commands from the monorepo root.
 
 Known historical failure prevented by this rule: `eas update:list` failed when launched from the monorepo root even though authentication worked.
 
-### Command-specific flag policy for eas-cli@23.2.0
+### Command-specific flag policy for eas-cli@24.7.0
 
 The flags are not uniform across EAS subcommands. Use the behavior proven on this hosting, not a blanket flag template.
 
-- `eas update:view GROUP_ID --json` is the canonical exact-group read. **Do not add `--non-interactive`**: eas-cli 23.2.0 rejects that flag for `update:view`.
+- `eas update:view GROUP_ID --json` is the canonical exact-group read. **Do not add `--non-interactive`**: eas-cli 24.7.0 rejects that flag for `update:view`.
 - `eas update:list ... --json --non-interactive` is proven working from the Mobile root.
 - `eas update ... --environment production --json --non-interactive` and `eas update:republish ... --json --non-interactive` are proven working in the Build17 OTA flow. Expo export may print a warning recommending `CI=1`; a warning alone is not a failed EAS publish. Judge the command by its real return code and returned update JSON.
 - Do not globally install a newer EAS CLI merely because the CLI prints an upgrade notice. Test a new major version through the same pinned npm-exec mechanism in a dedicated read-only compatibility audit first; only then change the canonical pin in this file.
@@ -631,7 +631,7 @@ A new batch should answer these **before touching source**:
 [ ] Is the known .htaccess drift still exactly the known hash/diff?
 [ ] Is my temp directory outside the Git worktree?
 [ ] Am I using canonical Node/npm paths?
-[ ] If EAS is needed, am I using npm exec eas-cli@23.2.0?
+[ ] If EAS is needed, am I using npm exec eas-cli@24.7.0?
 [ ] Will every project-scoped EAS command run from apps/mobile/current?
 [ ] Does the script avoid python3, /dev/fd, and process substitution?
 [ ] Can any expected RC=1 be accidentally trapped by set -e/ERR?
@@ -758,7 +758,7 @@ Use quarantine/backup before deleting uncertain runtime artifacts.
 These failures have already happened and are now encoded as permanent guards:
 
 1. **Project EAS command from monorepo root** -> collection query failed. Run from Mobile root.
-2. **Physical/global `eas` binary assumption** -> `EAS_CLI_MISSING`. Use pinned npm-exec `eas-cli@23.2.0`.
+2. **Physical/global `eas` binary assumption** -> `EAS_CLI_MISSING`. Use pinned npm-exec `eas-cli@24.7.0`.
 3. **Local repo one documentation commit behind GitHub** -> preflight mismatch. Verify ancestry/delta and `git merge --ff-only` when safe.
 4. **Temp directory created inside repo** -> checkpoint detected its own temp files as contamination. Temp/lock outside worktree.
 5. **Broad grep for `FAIL`** -> false failure on `Ukupno FAIL: 0`. Parse exact failure semantics.
@@ -767,7 +767,7 @@ These failures have already happened and are now encoded as permanent guards:
 8. **Assuming a single OpenAPI location** -> topology failure. Active topology is exactly three tracked copies.
 9. **Using `php bin/php-lint.php` as full CMS authority** -> wrong gate. Canonical full gate is `php bin/static-check.php`.
 10. **Restarting recovery from scratch** -> risks duplicate commits/candidates/promotions. Recover from recorded state markers.
-11. **`eas update:view ... --non-interactive` on eas-cli 23.2.0** -> hard CLI failure. Use exact-group `update:view GROUP_ID --json` without `--non-interactive`.
+11. **`eas update:view ... --non-interactive` on eas-cli 24.7.0** -> hard CLI failure. Use exact-group `update:view GROUP_ID --json` without `--non-interactive`.
 
 Any new repeated infrastructure failure should be added to this section after its root cause is proven.
 
RC_git_diff_agents=0

============================================================
RUN - git_diff_check_agents
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=git diff --check -- AGENTS.md
RC_git_diff_check_agents=0
CHANGED_TRACKED_AFTER_BEGIN
AGENTS.md
apps/cms/current/public/.htaccess
CHANGED_TRACKED_AFTER_END

============================================================
VERIFY - EAS CLI 24.7.0 READ ONLY AUTHORITY
============================================================
eas-cli/24.7.0 linux-x64 node-v22.23.2
RC_eas24_version=0
EAS24_VERSION_EXACT=PASS_24.7.0

============================================================
RUN - eas24_whoami
============================================================
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas whoami
ald1n
pruzljanin@gmail.com

Accounts:
• ald1n (Role: Owner)
• ald1ns-team (Role: Owner)
(node:2907436) [UnparsedCommand] Warning: Command account:view did not parse its arguments. Did you forget to call 'this.parse'?
(Use `node --trace-warnings ...` to show where the warning was created)
RC_eas24_whoami=0

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
      "message": "\"Promote Batch162 V5 notification admin-order routing fix d73fde2 20260917-114858\" (1 day ago by ald1n)",
      "runtimeVersion": "1.0.0-build17",
      "isRollBackToEmbedded": false,
      "group": "9a3774f5-99fe-4008-8c85-2cad8b5c5a2e",
      "platforms": "android"
    }
  ]
}
RC_eas24_update_list=0
RC_eas24_update_list_STDOUT_TEE=0
RC_eas24_update_list_STDERR_TEE=0

============================================================
RUN - verify_eas24_list
============================================================
CWD=/home/icaffeco/.ald1n-batch166-20260918-120132
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /home/icaffeco/.ald1n-batch166-20260918-120132/verify-eas-json.mjs list /home/icaffeco/.ald1n-batch166-20260918-120132/eas24-list.json 9a3774f5-99fe-4008-8c85-2cad8b5c5a2e 1.0.0-build17
PARSED_BRANCH=production
PARSED_GROUP=9a3774f5-99fe-4008-8c85-2cad8b5c5a2e
PARSED_RUNTIME=1.0.0-build17
PARSED_PLATFORMS=android
VERIFY_LIST_RESULT=PASS
RC_verify_eas24_list=0

============================================================
RUN - eas24_update_view
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
RC_eas24_update_view=0
RC_eas24_update_view_STDOUT_TEE=0
RC_eas24_update_view_STDERR_TEE=0

============================================================
RUN - verify_eas24_view
============================================================
CWD=/home/icaffeco/.ald1n-batch166-20260918-120132
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /home/icaffeco/.ald1n-batch166-20260918-120132/verify-eas-json.mjs view /home/icaffeco/.ald1n-batch166-20260918-120132/eas24-view.json 9a3774f5-99fe-4008-8c85-2cad8b5c5a2e 1.0.0-build17 277118631d11828162e6d2992dd02d9c1eaddaf6
PARSED_BRANCH=production
PARSED_GROUP=9a3774f5-99fe-4008-8c85-2cad8b5c5a2e
PARSED_RUNTIME=1.0.0-build17
PARSED_PLATFORM=android
PARSED_COMMIT=277118631d11828162e6d2992dd02d9c1eaddaf6
VERIFY_VIEW_RESULT=PASS
RC_verify_eas24_view=0

============================================================
PRECOMMIT - EXACT ONE-FILE STAGED SCOPE
============================================================

============================================================
RUN - git_add_agents
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=git add -- AGENTS.md
RC_git_add_agents=0
STAGED_NAMES_BEGIN
AGENTS.md
STAGED_NAMES_END

============================================================
RUN - git_cached_diff_check
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=git diff --cached --check
RC_git_cached_diff_check=0

============================================================
RUN - git_cached_diff_agents
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=git diff --cached -- AGENTS.md
diff --git a/AGENTS.md b/AGENTS.md
index b1b6a9e..fbf0df3 100644
--- a/AGENTS.md
+++ b/AGENTS.md
@@ -120,13 +120,13 @@ Do **not** require or probe for a physical/global `eas` binary as a hard prerequ
 Canonical EAS CLI version:
 
 ```text
-eas-cli@23.2.0
+eas-cli@24.7.0
 ```
 
 Approved invocation:
 
 ```bash
-EAS_SPEC="eas-cli@23.2.0"
+EAS_SPEC="eas-cli@24.7.0"
 "$NODE_BIN" "$NPM_CLI" exec --yes --package "$EAS_SPEC" -- eas --version
 ```
 
@@ -153,11 +153,11 @@ Do not execute project-scoped EAS commands from the monorepo root.
 
 Known historical failure prevented by this rule: `eas update:list` failed when launched from the monorepo root even though authentication worked.
 
-### Command-specific flag policy for eas-cli@23.2.0
+### Command-specific flag policy for eas-cli@24.7.0
 
 The flags are not uniform across EAS subcommands. Use the behavior proven on this hosting, not a blanket flag template.
 
-- `eas update:view GROUP_ID --json` is the canonical exact-group read. **Do not add `--non-interactive`**: eas-cli 23.2.0 rejects that flag for `update:view`.
+- `eas update:view GROUP_ID --json` is the canonical exact-group read. **Do not add `--non-interactive`**: eas-cli 24.7.0 rejects that flag for `update:view`.
 - `eas update:list ... --json --non-interactive` is proven working from the Mobile root.
 - `eas update ... --environment production --json --non-interactive` and `eas update:republish ... --json --non-interactive` are proven working in the Build17 OTA flow. Expo export may print a warning recommending `CI=1`; a warning alone is not a failed EAS publish. Judge the command by its real return code and returned update JSON.
 - Do not globally install a newer EAS CLI merely because the CLI prints an upgrade notice. Test a new major version through the same pinned npm-exec mechanism in a dedicated read-only compatibility audit first; only then change the canonical pin in this file.
@@ -631,7 +631,7 @@ A new batch should answer these **before touching source**:
 [ ] Is the known .htaccess drift still exactly the known hash/diff?
 [ ] Is my temp directory outside the Git worktree?
 [ ] Am I using canonical Node/npm paths?
-[ ] If EAS is needed, am I using npm exec eas-cli@23.2.0?
+[ ] If EAS is needed, am I using npm exec eas-cli@24.7.0?
 [ ] Will every project-scoped EAS command run from apps/mobile/current?
 [ ] Does the script avoid python3, /dev/fd, and process substitution?
 [ ] Can any expected RC=1 be accidentally trapped by set -e/ERR?
@@ -758,7 +758,7 @@ Use quarantine/backup before deleting uncertain runtime artifacts.
 These failures have already happened and are now encoded as permanent guards:
 
 1. **Project EAS command from monorepo root** -> collection query failed. Run from Mobile root.
-2. **Physical/global `eas` binary assumption** -> `EAS_CLI_MISSING`. Use pinned npm-exec `eas-cli@23.2.0`.
+2. **Physical/global `eas` binary assumption** -> `EAS_CLI_MISSING`. Use pinned npm-exec `eas-cli@24.7.0`.
 3. **Local repo one documentation commit behind GitHub** -> preflight mismatch. Verify ancestry/delta and `git merge --ff-only` when safe.
 4. **Temp directory created inside repo** -> checkpoint detected its own temp files as contamination. Temp/lock outside worktree.
 5. **Broad grep for `FAIL`** -> false failure on `Ukupno FAIL: 0`. Parse exact failure semantics.
@@ -767,7 +767,7 @@ These failures have already happened and are now encoded as permanent guards:
 8. **Assuming a single OpenAPI location** -> topology failure. Active topology is exactly three tracked copies.
 9. **Using `php bin/php-lint.php` as full CMS authority** -> wrong gate. Canonical full gate is `php bin/static-check.php`.
 10. **Restarting recovery from scratch** -> risks duplicate commits/candidates/promotions. Recover from recorded state markers.
-11. **`eas update:view ... --non-interactive` on eas-cli 23.2.0** -> hard CLI failure. Use exact-group `update:view GROUP_ID --json` without `--non-interactive`.
+11. **`eas update:view ... --non-interactive` on eas-cli 24.7.0** -> hard CLI failure. Use exact-group `update:view GROUP_ID --json` without `--non-interactive`.
 
 Any new repeated infrastructure failure should be added to this section after its root cause is proven.
 
RC_git_cached_diff_agents=0

============================================================
PREPUSH - REMOTE HAS NOT MOVED
============================================================

============================================================
RUN - git_fetch_prepush
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=git fetch origin main
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
RC_git_fetch_prepush=0
LOCAL_BEFORE_COMMIT=08705c2487a86bca8bea748620f86d87d6aea1c7
REMOTE_BEFORE_COMMIT=08705c2487a86bca8bea748620f86d87d6aea1c7

============================================================
COMMIT - CANONICAL PIN UPGRADE
============================================================

============================================================
RUN - git_commit
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=git commit -m docs: upgrade canonical EAS CLI pin to 24.7.0
[main 25937dd] docs: upgrade canonical EAS CLI pin to 24.7.0
 1 file changed, 7 insertions(+), 7 deletions(-)
RC_git_commit=0
SOURCE_COMMIT=25937dd22a720f6df230340d3d8a98f381e1ad79

============================================================
PUSH - MAIN
============================================================

============================================================
RUN - git_push
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=git push origin main
To github.com:AldinAga/ald1n-project.git
   08705c2..25937dd  main -> main
RC_git_push=0

============================================================
POSTFLIGHT - REMOTE AUTHORITY
============================================================

============================================================
RUN - git_fetch_postpush
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=git fetch origin main
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
RC_git_fetch_postpush=0
LOCAL_POST=25937dd22a720f6df230340d3d8a98f381e1ad79
REMOTE_POST=25937dd22a720f6df230340d3d8a98f381e1ad79
POST_HTACCESS_SHA=d10ecf0d63f9a8e1bd978a440afcbadb34faf1d24eb899bf2582d1e5c0033cef
POST_HTACCESS_DIFF_SHA=8ce9fa55a71768bf711203fcc50be8eec6de26886bbf4852ceb30a97b36958fe

============================================================
FINAL SUMMARY
============================================================
BATCH166_RESULT=PASS_EAS_CLI_24_7_0_CANONICAL_PIN_UPGRADE
FAILED_STAGE=NONE
SOURCE_MUTATION=YES_AGENTS_MD_ONLY
COMMIT_CREATED=YES
PUSH_COMPLETED=YES
SOURCE_COMMIT=25937dd22a720f6df230340d3d8a98f381e1ad79
CANONICAL_EAS_PIN_CHANGED=YES_23_2_0_TO_24_7_0
CANONICAL_EAS_PIN=eas-cli@24.7.0
EAS24_READ_ONLY_VALIDATION=PASS
PRODUCTION_GROUP_VERIFIED=9a3774f5-99fe-4008-8c85-2cad8b5c5a2e
PRODUCTION_RUNTIME_VERIFIED=1.0.0-build17
PRODUCTION_UPDATE_COMMIT_VERIFIED=277118631d11828162e6d2992dd02d9c1eaddaf6
OTA_REQUIRED=NO
CANDIDATE_PUBLISHED=NO
PRODUCTION_PROMOTED=NO
BUILD_CREATED=NO
GOOGLE_PLAY_ACTION=NO
REPORT_COMMITTED=NO_PENDING_CHECKPOINT
NEXT_ACTION=PREPARE_FULL_SAFE_GITHUB_CHECKPOINT_BATCH166A
REPORT_PATH=/home/icaffeco/ald1n-project/docs/operations/BATCH166-EAS-CLI-24-7-0-CANONICAL-PIN-UPGRADE-20260918-120132.md
