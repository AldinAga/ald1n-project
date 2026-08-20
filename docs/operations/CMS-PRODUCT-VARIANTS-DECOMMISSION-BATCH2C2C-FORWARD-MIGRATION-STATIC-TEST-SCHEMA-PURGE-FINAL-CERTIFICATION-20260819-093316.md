
============================================================
CMS PRODUCT VARIANTS - BATCH 2C2C FINAL PURGE + CERTIFICATION
============================================================
DATE=Wed Aug 19 09:33:16 CEST 2026
ROOT=/home/icaffeco/ald1n-project
CMS=/home/icaffeco/ald1n-project/apps/cms/current
MOBILE=/home/icaffeco/ald1n-project/apps/mobile/current
REPORT=/home/icaffeco/ald1n-project/docs/operations/CMS-PRODUCT-VARIANTS-DECOMMISSION-BATCH2C2C-FORWARD-MIGRATION-STATIC-TEST-SCHEMA-PURGE-FINAL-CERTIFICATION-20260819-093316.md
BACKUP=/home/icaffeco/backups/releases/product-variants-decommission-batch2c2c-final-20260819-093316
MODE=FINAL_FORWARD_MIGRATION_STATIC_TEST_CONTRACT_REWRITE_SCHEMA_PURGE_CERTIFICATION
SOURCE_REPLACEMENTS_EXPECTED=15
FORWARD_MIGRATION_EXPECTED=1
DATABASE_SCHEMA_CHANGES=PRODUCT_VARIANTS_FINAL_PURGE
DEPENDENCY_CHANGES=NO
EAS_BUILD=NO

============================================================
0. PREFLIGHT + BATCH2C2B V3 PREREQUISITE
============================================================
PREFLIGHT_COMMAND_bash=PASS
PREFLIGHT_COMMAND_php=PASS
PREFLIGHT_COMMAND_grep=PASS
PREFLIGHT_COMMAND_sed=PASS
PREFLIGHT_COMMAND_awk=PASS
PREFLIGHT_COMMAND_cat=PASS
PREFLIGHT_COMMAND_cp=PASS
PREFLIGHT_COMMAND_mkdir=PASS
PREFLIGHT_COMMAND_rm=PASS
PREFLIGHT_COMMAND_rmdir=PASS
PREFLIGHT_COMMAND_sha256sum=PASS
PREFLIGHT_COMMAND_find=PASS
PREFLIGHT_COMMAND_sort=PASS
PREFLIGHT_COMMAND_wc=PASS
PREFLIGHT_COMMAND_git=PASS
PREFLIGHT_COMMAND_cmp=PASS
PREFLIGHT_COMMAND_mktemp=PASS
PREFLIGHT_COMMAND_date=PASS
PREFLIGHT_COMMAND_dirname=PASS
PREFLIGHT_COMMAND_basename=PASS
PREFLIGHT_COMMAND_tee=PASS
PREFLIGHT_COMMAND_tr=PASS
PREFLIGHT_COMMAND_head=PASS
PREFLIGHT_COMMAND_tail=PASS
PREFLIGHT_COMMAND_cut=PASS
PREFLIGHT_COMMAND_stat=PASS
PREFLIGHT_COMMAND_chmod=PASS
BATCH2C2B_V3_PREREQUISITE=PASS_DEEP_RUNTIME_PRODUCT_ONLY_SOURCE_CUTOVER

============================================================
1. EXACT POST-2C2B SOURCE CONTRACT + DEFERRED 15 BASELINES
============================================================
POST_BATCH2C2B_RUNTIME_VARIANT_SIGNALS=PASS_ZERO
POST_BATCH2C2B_CORE_HASHES=PASS_48_OF_48_EXACT
RETIRED_VARIANT_FEATURE_FILES=PASS_8_OF_8_ABSENT
POST_BATCH2C2B_RESIDUAL_HASH_BASELINE=CAPTURED_9_ZERO_VARIANT_FILES
DEFERRED_STATIC_TEST_BASELINE=PASS_15_OF_15_EXACT
HISTORICAL_VARIANT_MIGRATION=PASS_PRESERVED_EXACT
ROUTE_CACHE_BASELINE=CAPTURED_0_FILES

============================================================
2. LIVE DB ZERO-STATE + EXACT 39/13/20 SCHEMA TOPOLOGY BEFORE DDL
============================================================
PRODUCT_VARIANTS_COUNT=0
PRODUCT_VARIANT_SPEC_VALUES_COUNT=0
NON_NULL_PRODUCT_IMAGES_PRODUCT_VARIANT_ID=0
NON_NULL_ORDER_ITEMS_PRODUCT_VARIANT_ID=0
NON_NULL_STOCK_MOVEMENTS_PRODUCT_VARIANT_ID=0
NON_NULL_AFTER_SALES_CASE_ITEMS_PRODUCT_VARIANT_ID=0
NON_NULL_AFTER_SALES_ACTION_ITEMS_PRODUCT_VARIANT_ID=0
NON_NULL_PRODUCT_WARRANTIES_PRODUCT_VARIANT_ID=0
NON_NULL_PRODUCTS_DEFAULT_VARIANT_ID=0
PRODUCTS_VARIANTS_ENABLED=0
NON_EMPTY_ORDER_ITEMS_VARIANT_SKU_SNAPSHOT=0
NON_EMPTY_ORDER_ITEMS_VARIANT_NAME_SNAPSHOT=0
NON_EMPTY_ORDER_ITEMS_VARIANT_ATTRIBUTES_JSON=0
VARIANT_RELATED_COLUMN_COUNT=39
VARIANT_RELATED_FOREIGN_KEY_COUNT=13
VARIANT_RELATED_INDEX_COUNT=20
LIVE_DB_ZERO_VARIANT_STATE=PASS
VARIANT_SNAPSHOT_DROP_ELIGIBLE=YES
CORE_VARIANT_SCHEMA_DROP_ELIGIBLE=YES
EXACT_SCHEMA_TOPOLOGY=PASS_39_COLUMNS_13_FKS_20_INDEXES

============================================================
3. BACKUP 15 CONTRACT FILES + BUILD TEMP REPLACEMENTS + FORWARD MIGRATION
============================================================
BACKUP_VERIFIED=PASS_15_OF_15
BACKUP_PATH=/home/icaffeco/backups/releases/product-variants-decommission-batch2c2c-final-20260819-093316
TEMP_REPLACEMENT_HASHES=PASS_15_CONTRACT_FILES_PLUS_1_FORWARD_MIGRATION
No syntax errors detected in /tmp/ald1n-variants-2c2c.fN2MKY/new/bin/catalog-settings-integrity-hotfix-smoke.php
No syntax errors detected in /tmp/ald1n-variants-2c2c.fN2MKY/new/bin/cms-v2.1.6-smoke.php
No syntax errors detected in /tmp/ald1n-variants-2c2c.fN2MKY/new/bin/product-media-ux-smoke.php
No syntax errors detected in /tmp/ald1n-variants-2c2c.fN2MKY/new/bin/product-save-regex-hotfix-smoke.php
No syntax errors detected in /tmp/ald1n-variants-2c2c.fN2MKY/new/bin/product-variant-smoke.php
No syntax errors detected in /tmp/ald1n-variants-2c2c.fN2MKY/new/bin/stable-maintenance-smoke.php
No syntax errors detected in /tmp/ald1n-variants-2c2c.fN2MKY/new/bin/static-check.php
No syntax errors detected in /tmp/ald1n-variants-2c2c.fN2MKY/new/bin/storage-capacity-total-smoke.php
No syntax errors detected in /tmp/ald1n-variants-2c2c.fN2MKY/new/tests/Feature/CatalogDetailPageTest.php
No syntax errors detected in /tmp/ald1n-variants-2c2c.fN2MKY/new/tests/Feature/ProductVariantsWorkflowTest.php
No syntax errors detected in /tmp/ald1n-variants-2c2c.fN2MKY/new/tests/Unit/CatalogSettingsIntegrityHotfixContractTest.php
No syntax errors detected in /tmp/ald1n-variants-2c2c.fN2MKY/new/tests/Unit/DirectSaleMaxUnitPriceContractTest.php
No syntax errors detected in /tmp/ald1n-variants-2c2c.fN2MKY/new/tests/Unit/ProductSaveRegexHotfixContractTest.php
No syntax errors detected in /tmp/ald1n-variants-2c2c.fN2MKY/new/tests/Unit/ProductVariantsMigrationContractTest.php
No syntax errors detected in /tmp/ald1n-variants-2c2c.fN2MKY/new/tests/Unit/ProductVariantsUiContractTest.php
No syntax errors detected in /tmp/ald1n-variants-2c2c.fN2MKY/new/database/migrations/2026_08_19_091800_decommission_product_variants.php
TEMP_PHP_SYNTAX=PASS_16_PHP_FILES
TEMP_MANAGED_WHITESPACE_GUARD=PASS_16_OF_16
TEMP_STATIC_TEST_DECOMMISSION_CONTRACT=PASS_NO_POSITIVE_RETIRED_FILE_READS

============================================================
4. INSTALL 15 CONTRACT REPLACEMENTS + FORWARD MIGRATION
============================================================
SOURCE_WRITES=15_STATIC_TEST_CONTRACT_REPLACEMENTS_PLUS_1_FORWARD_MIGRATION
INSTALLED_HASHES=PASS_16_OF_16

============================================================
5. EXECUTE ONLY THE FORWARD DECOMMISSION MIGRATION
============================================================

   INFO  Running migrations.  

  2026_08_19_091800_decommission_product_variants .................................................................................... 531.20ms DONE

FORWARD_MIGRATION=PASS_APPLIED_ONLY_DECOMMISSION_PATH

============================================================
6. POST-DDL ZERO-SCHEMA CERTIFICATION
============================================================
POST_PURGE_VARIANT_RELATED_COLUMN_COUNT=0
POST_PURGE_VARIANT_FOREIGN_KEY_COUNT=0
POST_PURGE_NAMED_INDEX_ROW_COUNT=0
DECOMMISSION_MIGRATION_RECORD_COUNT=1
VARIANT_SCHEMA_PURGE=PASS_ZERO_TABLES_ZERO_COLUMNS_ZERO_FKS_ZERO_INDEXES

============================================================
7. CMS RUNTIME + ROUTES + OPENAPI PRODUCT-ONLY FINAL RECERTIFICATION
============================================================
CMS_RUNTIME_PRODUCT_ONLY_CONTRACT=PASS_ZERO_VARIANT_SIGNALS_APP_CONFIG_RESOURCES
VARIANT_RUNTIME_ROUTE_SIGNAL_COUNT=0
VARIANT_RUNTIME_ROUTES=PASS_ZERO
FAIL: OpenAPI variant signal remains: /home/icaffeco/ald1n-project/packages/api-contract/openapi.yaml

============================================================
ROLLBACK
============================================================
SCHEMA_ROLLBACK=RESTORED_EMPTY_PRE_DECOMMISSION_VARIANT_SCHEMA
ROLLBACK_SOURCE=RESTORED_15_STATIC_TEST_CONTRACT_FILES_AND_REMOVED_FORWARD_MIGRATION
ROLLBACK_DATABASE_DATA=NO_PRODUCT_VARIANT_DATA_EXISTED_BY_EXACT_PREFLIGHT
REPORT_READY_TO_UPLOAD=/home/icaffeco/ald1n-project/docs/operations/CMS-PRODUCT-VARIANTS-DECOMMISSION-BATCH2C2C-FORWARD-MIGRATION-STATIC-TEST-SCHEMA-PURGE-FINAL-CERTIFICATION-20260819-093316.md
