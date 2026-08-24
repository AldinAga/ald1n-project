============================================================
155 - MOBILE v1.0 ADMIN CATALOG DICTIONARIES + NAV HUB PARITY - BATCH 22
============================================================
DATE=Mon Aug 24 13:19:12 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
PURPOSE=CLOSE_ADMIN_CAT_10_FULL_CATALOG_DICTIONARIES_PARITY_WITH_SHARED_WEB_API_BUSINESS_LOGIC_AND_HIERARCHICAL_MOBILE_NAVIGATION
SOURCE_SCOPE=EXACT_16_PATHS
EXISTING_SOURCE_PATHS=9
NEW_SOURCE_PATHS=7
DATABASE_WRITES=0
DATABASE_SCHEMA_CHANGES=0
MIGRATIONS_RUN=NO
DEPENDENCY_INSTALL=NO
PRODUCT_VARIANTS=DECOMMISSIONED_AND_FORBIDDEN
EAS_COMMANDS_RUN=NO
EAS_BUILD=NO
NAVIGATION=ADMINISTRATION_TO_CATALOG_AND_INVENTORY_TO_DICTIONARIES
REPORT=/home/icaffeco/ald1n-project/docs/operations/155-MOBILE-V1.0-ADMIN-CATALOG-DICTIONARIES-NAV-HUB-PARITY-BATCH22-20260824-131912.md
BACKUP=/home/icaffeco/backups/releases/mobile-v1.0-admin-catalog-dictionaries-nav-hub-parity-batch22-20260824-131912
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
REPORT_153_EVIDENCE=PASS_COMMIT_PUSH_SUCCEEDED_POST_CERT_FALSE_NEGATIVE
REPORT_154_FOUNDATION=PASS_GITHUB_CHECKPOINT_8baa290b2ae758d3733f5b8abddb7ff855897018
HARD_PRECONDITIONS=PASS
SOURCE_HEAD=8baa290b2ae758d3733f5b8abddb7ff855897018
PRESTATE_TRACKED_WORKTREE=CLEAN
PRESTATE_REAL_GIT_INDEX=CLEAN
PRESTATE_UNTRACKED_EVIDENCE=PASS_REPORTS_153_154_PLUS_CURRENT_155
TARGETED_SOURCE_BACKUP=PASS_9_EXISTING_PATHS
/tmp/ald1n-batch22.ThbKjE/patch-existing.js:11
  if (count !== 1) throw new Error(`${label}: expected one anchor, found ${count}`);
                   ^

Error: apps/mobile/current/scripts/validate-project.mjs assertions: expected one anchor, found 0
    at replaceOnce (/tmp/ald1n-batch22.ThbKjE/patch-existing.js:11:26)
    at Object.<anonymous> (/tmp/ald1n-batch22.ThbKjE/patch-existing.js:94:12)
    at Module._compile (node:internal/modules/cjs/loader:1781:14)
    at Module._extensions..js (node:internal/modules/cjs/loader:1913:10)
    at Module.load (node:internal/modules/cjs/loader:1505:32)
    at Module._load (node:internal/modules/cjs/loader:1309:12)
    at wrapModuleLoad (node:internal/modules/cjs/loader:254:19)
    at Function.executeUserEntryPoint [as runMain] (node:internal/modules/run_main:171:5)
    at node:internal/main/run_main_module:36:49

Node.js v22.23.2
FAIL_STAGE=SOURCE_PATCH
FAIL: existing source patch failed
SOURCE_ROLLBACK=EXECUTED_TO_PRE_BATCH22_CHECKPOINT_STATE
DATABASE_WRITES=0
MIGRATIONS_RUN=NO
DEPENDENCY_INSTALL=NO
EAS_COMMANDS_RUN=NO
EAS_BUILD=NO
EXIT_CODE=2
