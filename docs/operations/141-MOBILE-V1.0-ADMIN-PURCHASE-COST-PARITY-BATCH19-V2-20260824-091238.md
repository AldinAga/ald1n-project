============================================================
141 - MOBILE v1.0 ADMIN PURCHASE COST PARITY - BATCH 19 V2
============================================================
DATE=Mon Aug 24 09:12:38 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
PURPOSE=CLOSE_ADMIN_CAT_06_MASS_PURCHASE_COST_ENTRY_FULL_CMS_API_MOBILE_PARITY_AFTER_BATCH19_ROUTE_NESTING_FALSE_FAIL
PARITY_AUDIT_SOURCE=REPORT_139
SOURCE_SCOPE=EXACT_11_PATHS
DATABASE_SCHEMA_CHANGES=0
MIGRATIONS_RUN=NO
DEPENDENCY_INSTALL=NO
PRODUCT_VARIANTS=DECOMMISSIONED_AND_FORBIDDEN
EAS_COMMANDS_RUN=NO
EAS_BUILD=NO
REPORT=/home/icaffeco/ald1n-project/docs/operations/141-MOBILE-V1.0-ADMIN-PURCHASE-COST-PARITY-BATCH19-V2-20260824-091238.md
BACKUP=/home/icaffeco/backups/releases/mobile-v1.0-admin-purchase-cost-parity-batch19-v2-20260824-091238
REPORT_140_FAILURE_EVIDENCE=PASS_ROUTE_CONTRACT_ROLLBACK
HARD_PRECONDITIONS=PASS
SOURCE_HEAD=6f849cf343be971356ecd319fd287f3b6c9767ab
TARGETED_BACKUP=PASS_8_EXISTING_PATHS
BATCH19_ROOT_CAUSE=ROUTE_BLOCK_WAS_DOUBLE_NESTED_UNDER_EXISTING_API_V1_ADMIN_GROUP
BATCH19_V2_FIX=USE_RELATIVE_CATALOG_PURCHASE_COSTS_PREFIX_AND_RELATIVE_CATALOG_ROUTE_NAME
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductPurchaseCostService.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductPurchaseCostController.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/ProductPurchaseCostController.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/routes/api.php
TARGETED_SYNTAX=PASS
PURCHASE_COST_ROUTE_DIAGNOSTICS=[{"method":"GET|HEAD","uri":"admin/catalog/purchase-costs","name":"admin.products.purchase-costs"},{"method":"POST","uri":"admin/catalog/purchase-costs","name":"admin.products.purchase-costs.update"}]
/tmp/ald1n-batch19v2.ImDomV/route-contract.js:8
  throw new Error(`expected exactly 2 purchase-cost routes at ${expectedUri}; any=${anyPurchaseCost.length}, exact=${matches.length}`);
  ^

Error: expected exactly 2 purchase-cost routes at api/v1/admin/catalog/purchase-costs; any=2, exact=0
    at Object.<anonymous> (/tmp/ald1n-batch19v2.ImDomV/route-contract.js:8:9)
    at Module._compile (node:internal/modules/cjs/loader:1781:14)
    at Module._extensions..js (node:internal/modules/cjs/loader:1913:10)
    at Module.load (node:internal/modules/cjs/loader:1505:32)
    at Module._load (node:internal/modules/cjs/loader:1309:12)
    at wrapModuleLoad (node:internal/modules/cjs/loader:254:19)
    at Function.executeUserEntryPoint [as runMain] (node:internal/modules/run_main:171:5)
    at node:internal/main/run_main_module:36:49

Node.js v22.23.2
FAIL_STAGE=ROUTE_CONTRACT
FAIL: purchase cost API route contract failed
ROLLBACK=EXECUTED
EAS_COMMANDS_RUN=NO
EAS_BUILD=NO
EXIT_CODE=2
