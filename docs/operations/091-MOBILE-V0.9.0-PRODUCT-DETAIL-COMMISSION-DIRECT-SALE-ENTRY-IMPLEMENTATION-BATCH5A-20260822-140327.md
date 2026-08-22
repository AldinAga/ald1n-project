============================================================
090 - MOBILE v0.9.0 PRODUCT DETAIL + COMMISSION + DIRECT SALE ENTRY IMPLEMENTATION BATCH 5A
============================================================
DATE=Sat Aug 22 14:03:27 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
PURPOSE=IMPLEMENT_PRODUCT_DETAIL_COMMISSION_CATALOG_COMMISSION_DIRECT_SALE_ENTRY_AND_EDIT_ABSOLUTE_BOTTOM
DEFERRED_PAYMENT_BACKEND_EXTENSION=DEFERRED_TO_FOCUSED_FOLLOWUP_AFTER_THIS_SOURCE_PASS
NAVIGATION_REORGANIZATION=DEFERRED_TO_NEXT_SOURCE_BATCH
PRODUCT_VARIANTS=DECOMMISSIONED_AND_FORBIDDEN
DATABASE_SCHEMA_CHANGES=NO
MIGRATIONS_RUN=NO
DEPENDENCY_INSTALL=NO
EAS_COMMANDS_RUN=NO

============================================================
0. PREFLIGHT + 087/089 PREREQUISITES + CANONICAL NODE
============================================================
PHP_VERSION=8.4.24
NODE_VERSION=v22.23.2
NPM_VERSION=10.9.8
PREREQUISITE_087=PASS
PREREQUISITE_089=PASS
PREREQUISITE_089_REPORT=/home/icaffeco/ald1n-project/docs/operations/089-MOBILE-V0.9.0-PRODUCT-DETAIL-COMMISSION-DIRECT-SALE-NAVIGATION-TOPOLOGY-AUDIT-BATCH5-20260822-133012.md
OPENAPI_PRESTATE_PARITY=PASS_3_COPIES
PRODUCT_VARIANTS_TARGET_SOURCE=ABSENT_PASS

============================================================
1. TARGETED SOURCE BACKUP + IMMUTABILITY BASELINE
============================================================
TARGETED_SOURCE_BACKUP=/home/icaffeco/backups/releases/mobile-v0.9.0-product-detail-commission-direct-sale-entry-batch5a-20260822-140327
BACKUP_HOME_SHA=af4f1fb692c729847d5a7a2747c2530626f0b4be05f674a2e409d1d43b1fb972
BACKUP_CARD_SHA=012891eac5ba8adb4c6a35712665ad726bcb66e3993fff2229c50c3e1ff0c6d2
BACKUP_VALIDATOR_SHA=52cd658f1100c40ac38933d11ad57a7003eee1496dc7c796e1bcc4f3bacead23

============================================================
2. BUILD PATCH IN TEMP
============================================================
file:///home/icaffeco/ald1n-project/incoming/.mobile-v0.9.0-product-detail-batch5a.nbvYrT/patch.mjs:4
const fail = (message) => { throw new Error(message); };
                                  ^

Error: Product detail ScrollView closing anchor not found
    at fail (file:///home/icaffeco/ald1n-project/incoming/.mobile-v0.9.0-product-detail-batch5a.nbvYrT/patch.mjs:4:35)
    at file:///home/icaffeco/ald1n-project/incoming/.mobile-v0.9.0-product-detail-batch5a.nbvYrT/patch.mjs:31:22
    at ModuleJob.run (node:internal/modules/esm/module_job:343:25)
    at async onImport.tracePromise.__proto__ (node:internal/modules/esm/loader:681:26)
    at async asyncRunEntryPointWithESMLoader (node:internal/modules/run_main:117:5)

Node.js v22.23.2
FAIL: Batch 5A stopped with rc=1
