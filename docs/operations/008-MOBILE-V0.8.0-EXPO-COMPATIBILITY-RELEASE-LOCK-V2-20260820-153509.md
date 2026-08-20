
============================================================
MOBILE v0.8.0 - EXPO COMPATIBILITY REFRESH + RELEASE METADATA LOCK V2
============================================================
DATE=Thu Aug 20 15:35:09 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
CMS=/home/icaffeco/ald1n-project/apps/cms/current
MOBILE=/home/icaffeco/ald1n-project/apps/mobile/current
REPORT=/home/icaffeco/ald1n-project/docs/operations/008-MOBILE-V0.8.0-EXPO-COMPATIBILITY-RELEASE-LOCK-V2-20260820-153509.md
BACKUP=/home/icaffeco/backups/releases/mobile-v0.8.0-expo-compatibility-release-lock-v2-20260820-153509
SCRIPT_SEQUENCE=007
REPORT_SEQUENCE=008
NEXT_SEQUENCE=009
TARGET_VERSION=0.8.0
TARGET=REFRESH_EXPO_SDK57_MATRIX_THEN_LOCK_V0_8_RELEASE_METADATA
SOURCE_RUNTIME_CERTIFICATION=ALREADY_100_PERCENT
PRIOR_006_RESULT=SAFE_FAIL_EXPO_INSTALL_CHECK_AND_RELEASE_METADATA_ROLLBACK
NATIVE_DEPENDENCY_ADDITION=NO_EXISTING_EXPO_PACKAGES_PATCH_REFRESH_ONLY
EAS_COMMANDS_RUN=NO
EAS_BUILD=NO_FINAL_SLOT_PRESERVED
DATABASE_WRITES_EXPECTED=0
MIGRATIONS_RUN=NO
GIT_COMMANDS=UNIVERSAL_HELPER_FINAL_STEP_ONLY

============================================================
0. PREFLIGHT - NO GIT COMMANDS
============================================================
GITHUB_BACKUP_HELPER=PASS_PRESENT_AND_BASH_N
NODE_VERSION=v22.23.2
NPM_VERSION=10.9.8
NPM_MAJOR_10=PASS
PRIOR_006_REPORT=/home/icaffeco/ald1n-project/docs/operations/006-MOBILE-V0.8.0-RELEASE-METADATA-LOCK-READINESS-20260820-142836.md
PRIOR_006_SAFE_ROLLBACK=PASS
SOURCE_RUNTIME_100_PERCENT_BASELINE=PASS
EAS_JSON_SHA_BEFORE=801eb3035c0e0a72ca27bef8289da249a4ce69b4ba860a4855b30aa0466b82e4
CURRENT_PACKAGE_VERSION=0.7.0
CURRENT_PACKAGE_LOCK_VERSION=0.7.0
CURRENT_PACKAGE_LOCK_ROOT_VERSION=0.7.0
CURRENT_EXPO_SPEC=~57.0.14
CURRENT_EXPO_INSTALLED=57.0.14
CURRENT_EXPO_CONSTANTS_SPEC=~57.0.12
CURRENT_EXPO_CONSTANTS_INSTALLED=57.0.12
CURRENT_EXPO_DEV_CLIENT_SPEC=~57.0.13
CURRENT_EXPO_DEV_CLIENT_INSTALLED=57.0.13
CURRENT_EXPO_FILE_SYSTEM_SPEC=~57.0.4
CURRENT_EXPO_FILE_SYSTEM_INSTALLED=57.0.4
CURRENT_EXPO_LINKING_SPEC=~57.0.6
CURRENT_EXPO_LINKING_INSTALLED=57.0.6
CURRENT_EXPO_NOTIFICATIONS_SPEC=~57.0.12
CURRENT_EXPO_NOTIFICATIONS_INSTALLED=57.0.12
CURRENT_EXPO_ROUTER_SPEC=~57.0.14
CURRENT_EXPO_ROUTER_INSTALLED=57.0.14
CURRENT_EXPO_SHARING_SPEC=~57.0.13
CURRENT_EXPO_SHARING_INSTALLED=57.0.13
CURRENT_EXPO_UPDATES_SPEC=~57.0.15
CURRENT_EXPO_UPDATES_INSTALLED=57.0.15
RELEASE_METADATA_BASELINE=PASS_V0_7_ROLLBACK_STATE

============================================================
1. BACKUP TRACKED RELEASE + VALIDATOR + DOCUMENTATION FILES
============================================================
BACKUP_TRACKED_FILES=PASS_8_FILES

============================================================
2. PATCHER SELF-TEST ON EXACT CURRENT SOURCE COPIES
============================================================
RELEASE_AND_EXPO_MATRIX_SENTINELS=PASS
SELF_TEST_FIXTURE=EXACT_CURRENT_SOURCE_COPY
SELF_TEST=PASS

============================================================
3. DIRECT AUDITED NPM 10 INSTALL OF EXPO-RECOMMENDED PATCH VERSIONS
============================================================
npm warn deprecated uuid@7.0.3: uuid@10 and below is no longer supported.  For ESM codebases, update to uuid@latest.  For CommonJS codebases, use uuid@11 (but be aware this version will likely be deprecated in 2028).

added 571 packages in 37s
DIRECT_AUDITED_NPM_INSTALL=PASS
EXPO_INSTALL_FIX_USED=NO
CLOUDLINUX_NPM_CHILD_WRAPPER_BYPASSED=YES_DIRECT_NODE_NPM_CLI

============================================================
4. NORMALIZE DEPENDENCY SPECS TO EXPO TILDE CONTRACT + LOCK RELEASE 0.8.0
============================================================
FAIL expo installed=57.0.14
FAIL expo-constants installed=57.0.12
FAIL expo-dev-client installed=57.0.13
FAIL expo-file-system installed=57.0.4
FAIL expo-linking installed=57.0.6
FAIL expo-notifications installed=57.0.12
FAIL expo-router installed=57.0.14
FAIL expo-sharing installed=57.0.13
FAIL expo-updates installed=57.0.15

============================================================
ROLLBACK
============================================================
ROLLBACK_TRACKED_FILES=PASS
ROLLBACK_NODE_MODULES=REINSTALL_FROM_RESTORED_LOCK
npm error code EUSAGE
npm error
npm error `npm ci` can only install packages when your package.json and package-lock.json or npm-shrinkwrap.json are in sync. Please update your lock file with `npm install` before continuing.
npm error
npm error Invalid: lock file's react-native-worklets@0.11.4 does not satisfy react-native-worklets@0.10.4
npm error
npm error Clean install a project
npm error
npm error Usage:
npm error npm ci
npm error
npm error Options:
npm error [--install-strategy <hoisted|nested|shallow|linked>] [--legacy-bundling]
npm error [--global-style] [--omit <dev|optional|peer> [--omit <dev|optional|peer> ...]]
npm error [--include <prod|dev|optional|peer> [--include <prod|dev|optional|peer> ...]]
npm error [--strict-peer-deps] [--foreground-scripts] [--ignore-scripts] [--no-audit]
npm error [--no-bin-links] [--no-fund] [--dry-run]
npm error [-w|--workspace <workspace-name> [-w|--workspace <workspace-name> ...]]
npm error [-ws|--workspaces] [--include-workspace-root] [--install-links]
npm error
npm error aliases: clean-install, ic, install-clean, isntall-clean
npm error
npm error Run "npm help ci" for more info
npm error A complete log of this run can be found in: /home/icaffeco/.npm/_logs/2026-08-20T13_35_56_366Z-debug-0.log
ROLLBACK_NODE_MODULES=FAIL_EXIT_1
EXIT_CODE=1
UPLOAD_THIS_REPORT_TO_CHAT=/home/icaffeco/ald1n-project/docs/operations/008-MOBILE-V0.8.0-EXPO-COMPATIBILITY-RELEASE-LOCK-V2-20260820-153509.md
