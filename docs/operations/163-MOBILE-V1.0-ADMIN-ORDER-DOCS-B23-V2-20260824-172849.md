============================================================
163 - MOBILE v1.0 ADMIN ORDER DOCUMENTS + INVOICE WORKFLOW PARITY - BATCH 23 V2
============================================================
DATE=Mon Aug 24 17:28:49 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
PURPOSE=CLOSE_ADMIN_ORDER_05_ADMIN_DOCUMENTS_AND_INVOICE_WORKFLOW_PARITY_USING_EXISTING_ORDER_DOCUMENT_SERVICE
SOURCE_SCOPE=EXACT_10_PATHS
EXISTING_SOURCE_PATHS=7
NEW_SOURCE_PATHS=3
DATABASE_WRITES=0
DATABASE_SCHEMA_CHANGES=0
MIGRATIONS_RUN=NO
DEPENDENCY_INSTALL=NO
PRODUCT_VARIANTS=DECOMMISSIONED_AND_FORBIDDEN
EAS_COMMANDS_RUN=NO
EAS_BUILD=NO
NAVIGATION=EXISTING_ADMINISTRATION_TO_SALES_TO_ORDERS_TO_DETAIL_DOCUMENT_WORKBENCH
REPORT=/home/icaffeco/ald1n-project/docs/operations/163-MOBILE-V1.0-ADMIN-ORDER-DOCS-B23-V2-20260824-172849.md
BACKUP=/home/icaffeco/backups/releases/mobile-v1.0-admin-order-documents-invoice-workflow-parity-batch23-v2-20260824-172849
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
REPORT_161_FOUNDATION=PASS_GITHUB_CHECKPOINT_a88ba478a83baa6f7c6ec0e60413324e16b55b73
REPORT_162_FAILED_BATCH23_EVIDENCE=PASS_DOCUMENT_BLOCK_ANCHOR_FULL_ROLLBACK_ZERO_DB_WRITES
PRESTATE_UNTRACKED_EVIDENCE=PASS_REPORTS_161_162_PLUS_CURRENT_163
HARD_PRECONDITIONS=PASS
SOURCE_HEAD=a88ba478a83baa6f7c6ec0e60413324e16b55b73
PRESTATE_TRACKED_WORKTREE=CLEAN
PRESTATE_REAL_GIT_INDEX=CLEAN
TARGETED_SOURCE_BACKUP=PASS_7_EXISTING_PATHS
/tmp/ald1n-batch23-v2.20260824-172849.3443164/patch-existing.js:10
  if (count !== 1) throw new Error(`${label}: expected one anchor, found ${count}`);
                   ^

Error: validator batch23 assertions: expected one anchor, found 0
    at replaceOnce (/tmp/ald1n-batch23-v2.20260824-172849.3443164/patch-existing.js:10:26)
    at Object.<anonymous> (/tmp/ald1n-batch23-v2.20260824-172849.3443164/patch-existing.js:85:13)
    at Module._compile (node:internal/modules/cjs/loader:1781:14)
    at Module._extensions..js (node:internal/modules/cjs/loader:1913:10)
    at Module.load (node:internal/modules/cjs/loader:1505:32)
    at Module._load (node:internal/modules/cjs/loader:1309:12)
    at wrapModuleLoad (node:internal/modules/cjs/loader:254:19)
    at Function.executeUserEntryPoint [as runMain] (node:internal/modules/run_main:171:5)
    at node:internal/main/run_main_module:36:49

Node.js v22.23.2
FAIL_STAGE=SOURCE_PATCH
FAIL: Existing source patch failed
SOURCE_ROLLBACK=EXECUTED_TO_PRE_BATCH23_V2_CHECKPOINT_STATE
DATABASE_WRITES=0
DATABASE_SCHEMA_CHANGES=0
MIGRATIONS_RUN=NO
DEPENDENCY_INSTALL=NO
EAS_COMMANDS_RUN=NO
EAS_BUILD=NO
EXIT_CODE=2
