
============================================================
CMS PRODUCT VARIANTS - DECOMMISSION BATCH 2B1 CORE RUNTIME SINGLE-PRODUCT LOCK
============================================================
DATE=Wed Aug 19 00:19:00 CEST 2026
ROOT=/home/icaffeco/ald1n-project
CMS=/home/icaffeco/ald1n-project/apps/cms/current
MOBILE=/home/icaffeco/ald1n-project/apps/mobile/current
REPORT=/home/icaffeco/ald1n-project/docs/operations/CMS-PRODUCT-VARIANTS-DECOMMISSION-BATCH2B1-CORE-RUNTIME-SINGLE-PRODUCT-LOCK-20260819-001900.md
BACKUP=/home/icaffeco/backups/releases/cms-product-variants-decommission-batch2b1-core-runtime-single-product-lock-20260819-001900
MODE=MUTATING_CORE_RUNTIME_FUTURE_WRITE_LOCK_WITH_BACKUP_AND_ROLLBACK
PURPOSE=BLOCK_ALL_NEW_PRODUCT_VARIANT_REFERENCES_AT_TRANSACTION_ENTRY_POINTS_BEFORE_DEEP_RUNTIME_AND_SCHEMA_REMOVAL
DATABASE_BUSINESS_WRITES_EXPECTED=0
DATABASE_SCHEMA_CHANGES=0
MIGRATIONS_RUN=NO
OPENAPI_CHANGES=0
MOBILE_SOURCE_CHANGES=0
DEPENDENCY_CHANGES=0
EAS_BUILD=NO
MANAGED_EXISTING_FILES=5
MANAGED_NEW_TEST_FILES=1

============================================================
0. PREFLIGHT + CONCURRENCY + BATCH 2A V4 PREREQUISITE
============================================================
CONCURRENCY_LOCK=ACQUIRED
BATCH2A_V4_REPORT=/home/icaffeco/ald1n-project/docs/operations/CMS-PRODUCT-VARIANTS-DECOMMISSION-BATCH2A-V4-OPERATIONAL-RETIREMENT-20260819-001247.md
BATCH2A_V4_PREREQUISITE=PASS
SOURCE_BASELINE=PRE_BATCH2B1_EXPECTED
VARIANT_WEB_ROUTE_SOURCE_GUARD=PASS_ZERO_NAMESPACE
IMMUTABLE_BASELINE_FILE_COUNT=11
GIT_BASELINE_CAPTURED=YES

============================================================
1. LIVE DATABASE ZERO-VARIANT SAFETY RECHECK
============================================================
No syntax errors detected in /tmp/ald1n-variants-2b1.ECiYoj/db-probe.php
PRODUCT_VARIANT_COUNT_RECHECK=0
NON_NULL_PRODUCT_VARIANT_ID_PRODUCT_IMAGES=0
NON_NULL_PRODUCT_VARIANT_ID_ORDER_ITEMS=0
NON_NULL_PRODUCT_VARIANT_ID_STOCK_MOVEMENTS=0
NON_NULL_PRODUCT_VARIANT_ID_AFTER_SALES_CASE_ITEMS=0
NON_NULL_PRODUCT_VARIANT_ID_AFTER_SALES_ACTION_ITEMS=0
NON_NULL_PRODUCT_VARIANT_ID_PRODUCT_WARRANTIES=0
BUSINESS_VARIANT_REFERENCE_NON_NULL_TOTAL=0
PRODUCTS_VARIANTS_ENABLED=0
PRODUCTS_DEFAULT_VARIANT_ID_NON_NULL=0
LIVE_DB_SAFETY_RECHECK=PASS

============================================================
2. VERIFIED TARGETED BACKUP
============================================================
TARGETED_BACKUP=PASS

============================================================
3. BUILD PATCHED CORE RUNTIME IN TEMP
============================================================
No syntax errors detected in /tmp/ald1n-variants-2b1.ECiYoj/patch.php
PATCH_CORE_RUNTIME_SINGLE_PRODUCT_LOCK=PASS
No syntax errors detected in /tmp/ald1n-variants-2b1.ECiYoj/new/app/Http/Requests/StoreOrderRequest.php
No syntax errors detected in /tmp/ald1n-variants-2b1.ECiYoj/new/app/Services/OrderService.php
No syntax errors detected in /tmp/ald1n-variants-2b1.ECiYoj/new/app/Http/Controllers/Admin/DirectSaleController.php
No syntax errors detected in /tmp/ald1n-variants-2b1.ECiYoj/new/app/Services/DirectSaleService.php
No syntax errors detected in /tmp/ald1n-variants-2b1.ECiYoj/new/app/Services/ProductAdminService.php
TEMP_CORE_RUNTIME_PATCH=PASS_5_FILES
TEMP_MANAGED_WHITESPACE_GUARD=PASS_5_OF_5

============================================================
4. BUILD TARGETED DECOMMISSION CONTRACT TEST
============================================================
No syntax errors detected in /tmp/ald1n-variants-2b1.ECiYoj/new/tests/Unit/ProductVariantsDecommissionRuntimeContractTest.php
TARGETED_TEST_BUILD=PASS

============================================================
5. INSTALL MANAGED SOURCE
============================================================
SOURCE_WRITES=6_MANAGED_FILES

============================================================
6. PHP SYNTAX + TARGETED CONTRACT TEST
============================================================
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreOrderRequest.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/DirectSaleController.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/ProductVariantsDecommissionRuntimeContractTest.php

   ERROR  Command "test" is not defined. Did you mean one of these?  

  ⇂ app:send-test-mail  
  ⇂ app:test-database-doctor  
  ⇂ make:test  
  ⇂ schedule:test  


============================================================
ROLLBACK
============================================================
PASS restored app/Http/Requests/StoreOrderRequest.php
PASS restored app/Services/OrderService.php
PASS restored app/Http/Controllers/Admin/DirectSaleController.php
PASS restored app/Services/DirectSaleService.php
PASS restored app/Services/ProductAdminService.php
PASS removed newly created tests/Unit/ProductVariantsDecommissionRuntimeContractTest.php

   INFO  Clearing cached bootstrap files.  

  config ............................................................................................................................... 1.73ms DONE
  cache ................................................................................................................................ 9.38ms DONE
  compiled ............................................................................................................................. 2.76ms DONE
  events ............................................................................................................................... 1.27ms DONE
  routes ............................................................................................................................... 0.87ms DONE
  views ................................................................................................................................ 4.97ms DONE

ROLLBACK_SOURCE=RESTORED
