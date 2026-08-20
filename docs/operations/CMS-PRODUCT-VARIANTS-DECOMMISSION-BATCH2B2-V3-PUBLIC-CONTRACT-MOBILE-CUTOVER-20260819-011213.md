
============================================================
CMS PRODUCT VARIANTS - DECOMMISSION BATCH 2B2 V3 PUBLIC CONTRACT + MOBILE CUTOVER
============================================================
DATE=Wed Aug 19 01:12:14 CEST 2026
ROOT=/home/icaffeco/ald1n-project
CMS=/home/icaffeco/ald1n-project/apps/cms/current
MOBILE=/home/icaffeco/ald1n-project/apps/mobile/current
REPORT=/home/icaffeco/ald1n-project/docs/operations/CMS-PRODUCT-VARIANTS-DECOMMISSION-BATCH2B2-V3-PUBLIC-CONTRACT-MOBILE-CUTOVER-20260819-011213.md
BACKUP=/home/icaffeco/backups/releases/cms-product-variants-decommission-batch2b2-v3-public-contract-mobile-cutover-20260819-011213
MODE=MUTATING_PUBLIC_CONTRACT_AND_MOBILE_CUTOVER_V3_WITH_BACKUP_AND_ROLLBACK
PATCH_OBSERVABILITY=PER_TARGET_BEGIN_PASS_FAIL_WITH_STDERR_CAPTURE
PURPOSE=REMOVE_PRODUCT_VARIANTS_FROM_PUBLIC_API_OPENAPI_MOBILE_WHILE_PRESERVING_DORMANT_DEEP_COMPATIBILITY_UNTIL_BATCH2C
MANAGED_FILE_COUNT=14
DATABASE_WRITES_EXPECTED=0
DATABASE_SCHEMA_CHANGES=0
MIGRATIONS_RUN=NO
DEPENDENCY_CHANGES=NO
EAS_BUILD=NO

============================================================
0. PREFLIGHT + PREREQUISITES + CONCURRENCY
============================================================
PREFLIGHT_COMMAND_bash=PASS
PREFLIGHT_COMMAND_grep=PASS
PREFLIGHT_COMMAND_sed=PASS
PREFLIGHT_COMMAND_awk=PASS
PREFLIGHT_COMMAND_cat=PASS
PREFLIGHT_COMMAND_cp=PASS
PREFLIGHT_COMMAND_mkdir=PASS
PREFLIGHT_COMMAND_rm=PASS
PREFLIGHT_COMMAND_sha256sum=PASS
PREFLIGHT_COMMAND_find=PASS
PREFLIGHT_COMMAND_sort=PASS
PREFLIGHT_COMMAND_wc=PASS
PREFLIGHT_COMMAND_git=PASS
PREFLIGHT_COMMAND_cmp=PASS
PREFLIGHT_COMMAND_mktemp=PASS
PREFLIGHT_COMMAND_date=PASS
PREFLIGHT_COMMAND_php=PASS
PREFLIGHT_COMMAND_diff=PASS
PREFLIGHT_COMMAND_dirname=PASS
PREFLIGHT_COMMAND_basename=PASS
PREFLIGHT_COMMAND_chmod=PASS
PREFLIGHT_COMMAND_touch=PASS
PREFLIGHT_COMMAND_tr=PASS
PREFLIGHT_COMMAND_tee=PASS
NODE_VERSION=v22.23.2
NPM_VERSION=10.9.8
BATCH2A_V4_REPORT=/home/icaffeco/ald1n-project/docs/operations/CMS-PRODUCT-VARIANTS-DECOMMISSION-BATCH2A-V4-OPERATIONAL-RETIREMENT-20260819-001247.md
BATCH2A_V4_PREREQUISITE=PASS
BATCH2B1_V3_REPORT=/home/icaffeco/ald1n-project/docs/operations/CMS-PRODUCT-VARIANTS-DECOMMISSION-BATCH2B1-V3-CORE-RUNTIME-SINGLE-PRODUCT-LOCK-20260819-002527.md
BATCH2B1_V3_PREREQUISITE=PASS
PRIOR_BATCH2B2_V2_FAILED_REPORT=/home/icaffeco/ald1n-project/docs/operations/CMS-PRODUCT-VARIANTS-DECOMMISSION-BATCH2B2-V2-PUBLIC-CONTRACT-MOBILE-CUTOVER-20260819-005352.md
PRIOR_BATCH2B2_V2_FAILURE=MOBILE_CART_PROVIDER_VARIANT_IDENTITY_REMAINED_IN_TEMP
PRIOR_BATCH2B2_V2_ROLLBACK=PASS_SOURCE_RESTORED_NO_DB_WRITES
CONCURRENCY_LOCK=ACQUIRED
MOBILE_APP_VERSION=0.7.0

============================================================
1. DUPLICATE-RUN SAFE CHECK
============================================================
ALREADY_APPLIED=NO

============================================================
2. LIVE DATABASE + ROUTE ZERO-STATE SAFETY RECHECK
============================================================
PRODUCT_VARIANT_COUNT=0
PRODUCT_VARIANT_SPEC_VALUE_COUNT=0
NON_NULL_PRODUCT_VARIANT_ID_PRODUCT_IMAGES=0
NON_NULL_PRODUCT_VARIANT_ID_ORDER_ITEMS=0
NON_NULL_PRODUCT_VARIANT_ID_STOCK_MOVEMENTS=0
NON_NULL_PRODUCT_VARIANT_ID_AFTER_SALES_CASE_ITEMS=0
NON_NULL_PRODUCT_VARIANT_ID_AFTER_SALES_ACTION_ITEMS=0
NON_NULL_PRODUCT_VARIANT_ID_PRODUCT_WARRANTIES=0
PRODUCTS_VARIANTS_ENABLED=0
PRODUCTS_DEFAULT_VARIANT_ID_NON_NULL=0
BUSINESS_VARIANT_REFERENCE_NON_NULL_TOTAL=0
LIVE_DB_ZERO_VARIANT_STATE=PASS
VARIANT_WEB_ROUTE_SIGNAL_COUNT=0
VARIANT_WEB_ROUTE_RUNTIME=PASS_ZERO

============================================================
3. OPENAPI PRE-PATCH PARITY + EXACT PUBLIC VARIANT COUNT
============================================================
OPENAPI_PRODUCT_VARIANT_ID_COUNT_BEFORE_api-contract_openapi.yaml=3
OPENAPI_PRODUCT_VARIANT_ID_COUNT_BEFORE_docs_openapi.yaml=3
OPENAPI_PRODUCT_VARIANT_ID_COUNT_BEFORE_docs_openapi.yaml=3
OPENAPI_PREPATCH_PARITY=PASS_3_COPIES_3_PUBLIC_VARIANT_PROPERTIES_EACH

============================================================
4. IMMUTABLE BASELINE + GIT SNAPSHOT
============================================================
IMMUTABLE_BASELINE_FILE_COUNT=22
GIT_BASELINE_CAPTURED=YES

============================================================
5. VERIFIED 14-FILE BACKUP
============================================================
BACKUP_FILE_COUNT=14
BACKUP_VERIFIED=PASS

============================================================
6. BUILD PATCHED 14-FILE SOURCE IN TEMP
============================================================
PATCH_STEP_BEGIN=CMS_PRODUCT_RESOURCE
PATCH_STEP_PASS=CMS_PRODUCT_RESOURCE
PATCH_STEP_BEGIN=CMS_ORDER_RESOURCE
PATCH_STEP_PASS=CMS_ORDER_RESOURCE
PATCH_STEP_BEGIN=CMS_AFTER_SALES_CUSTOMER_API
PATCH_STEP_PASS=CMS_AFTER_SALES_CUSTOMER_API
PATCH_STEP_BEGIN=CMS_AFTER_SALES_ADMIN_API
PATCH_STEP_PASS=CMS_AFTER_SALES_ADMIN_API
PATCH_STEP_BEGIN=OPENAPI_3_COPIES
PATCH_STEP_PASS=OPENAPI_3_COPIES
PATCH_STEP_BEGIN=MOBILE_API_TYPES
PATCH_STEP_PASS=MOBILE_API_TYPES
PATCH_STEP_BEGIN=MOBILE_ADMIN_AFTER_SALES_TYPES
PATCH_STEP_PASS=MOBILE_ADMIN_AFTER_SALES_TYPES
PATCH_STEP_BEGIN=MOBILE_CART_PROVIDER
CART_PROVIDER_VARIANT_SIGNALS_BEFORE_COUNT=5
CART_PROVIDER_VARIANT_SIGNALS_BEFORE_BEGIN
0011:  variantId: Nullable<number>;
0012:  variantName: Nullable<string>;
0033:function cartKey(productId: number, variantId: number | null): string {
0034:  return `${productId}:${variantId ?? 0}`;
0059:    const key = cartKey(input.productId, input.variantId);
CART_PROVIDER_VARIANT_SIGNALS_BEFORE_END
CART_PROVIDER_VARIANT_SIGNALS_AFTER_COUNT=0
CART_PROVIDER_VARIANT_SIGNALS_AFTER_BEGIN
CART_PROVIDER_VARIANT_SIGNALS_AFTER_END
CART_PROVIDER_PRODUCT_ONLY_IDENTITY_REWRITE=PASS
PATCH_STEP_PASS=MOBILE_CART_PROVIDER
PATCH_STEP_BEGIN=MOBILE_PRODUCT_SCREEN
PATCH_STEP_FAIL=MOBILE_PRODUCT_SCREEN
Error: Product screen variant behavior remains
    at need (file:///tmp/ald1n-variants-2b2-v3.AkjkaH/patch.mjs:30:59)
    at file:///tmp/ald1n-variants-2b2-v3.AkjkaH/patch.mjs:443:3
    at step (file:///tmp/ald1n-variants-2b2-v3.AkjkaH/patch.mjs:169:5)
    at file:///tmp/ald1n-variants-2b2-v3.AkjkaH/patch.mjs:371:1
    at ModuleJob.run (node:internal/modules/esm/module_job:343:25)
    at async onImport.tracePromise.__proto__ (node:internal/modules/esm/loader:681:26)
    at async asyncRunEntryPointWithESMLoader (node:internal/modules/run_main:117:5)
FAIL: temp patcher failed; see PATCH_STEP_FAIL above

============================================================
ROLLBACK
============================================================
PASS restored apps/cms/current/app/Http/Resources/ProductResource.php
PASS restored apps/cms/current/app/Http/Resources/OrderResource.php
PASS restored apps/cms/current/app/Http/Controllers/Api/V1/AfterSalesController.php
PASS restored apps/cms/current/app/Http/Controllers/Api/V1/Admin/AfterSalesController.php
PASS restored packages/api-contract/openapi.yaml
PASS restored apps/cms/current/docs/openapi.yaml
PASS restored apps/mobile/current/docs/openapi.yaml
PASS restored apps/mobile/current/src/types/api.ts
PASS restored apps/mobile/current/src/features/admin/after-sales-admin-api.ts
PASS restored apps/mobile/current/src/features/cart/cart-provider.tsx
PASS restored apps/mobile/current/src/app/(app)/product/[slug].tsx
PASS restored apps/mobile/current/src/app/(app)/cart.tsx
PASS restored apps/mobile/current/src/app/(app)/checkout.tsx
PASS restored apps/mobile/current/scripts/validate-project.mjs
ROLLBACK_SOURCE=RESTORED_14_MANAGED_FILES
DATABASE_ROLLBACK=NOT_REQUIRED_NO_DATABASE_WRITES
BACKUP=/home/icaffeco/backups/releases/cms-product-variants-decommission-batch2b2-v3-public-contract-mobile-cutover-20260819-011213
