# Report360 V2 - Build16 Catalog Runtime Stabilization Batch134 V3

- Timestamp: 20260908-121818
- Purpose: continue the remaining Build16 catalog runtime stabilization from the newer 8c869b58 main without redoing the already-committed image-bounds hotfix
- Minimum audited baseline: 8c869b58a9a017c9d4122954d3e8b0b520ba6f74
- Current repository authority: Build16 through Batch133 plus catalog image-bounds hotfix 33604307 and EXIF derivative fix 8c869b58
- User-reported runtime symptoms:
  - catalog product row expands to nearly full-screen height and visually exposes only one product at a time
  - catalog/product data can remain stale after direct sale/archive navigation
  - mobile product creation can be tapped again while the first create/upload request is still pending
- Fix scope:
  - preserve already-completed bounded catalog image row geometry without touching product-card.tsx
  - focus-driven catalog/product refresh and broader mutation cache invalidation
  - action-layer single-flight product creation
  - server-backed idempotent product create retries through existing IdempotencyService
  - read-only Dell 5591 / recent duplicate audit
- DirectSaleService stock ledger authority: PRESERVED, NOT REWRITTEN
- ProductAdminService archive semantics: PRESERVED; archive does not fabricate a zero-stock movement
- CatalogAccessService archived exclusion: PRESERVED
- Product Variants: MUST REMAIN DECOMMISSIONED
- Production data mutation by this batch: NO
- EAS build creation: NO
- OTA publish: NO
- Hygiene: LF/CR/trailing-whitespace scan + git diff --check + isolated staged diff check before expensive gates

============================================================
0. SOURCE AUTHORITY AND WORKTREE GUARDS
============================================================
SHELL_HOME_ENV=PASS_DIRECTORY_PRESERVED_FOR_EXPO
BRANCH=main
LOCAL_HEAD=8c869b58a9a017c9d4122954d3e8b0b520ba6f74
REMOTE_HEAD=8c869b58a9a017c9d4122954d3e8b0b520ba6f74
SOURCE_BASELINE=PASS_CURRENT_HEAD_DESCENDS_FROM_8C869B58
CATALOG_IMAGE_BOUNDS_HISTORY=PASS_ALREADY_COMPLETE_33604307
HTACCESS_DIFF_SHA=8ce9fa55a71768bf711203fcc50be8eec6de26886bbf4852ceb30a97b36958fe
HTACCESS_DRIFT=PASS_KNOWN_RUNTIME_EXCEPTION
HISTORICAL_BUILD16_BACKUP_DIRS_ALLOWED=28
UNTRACKED_POLICY=PASS_OPERATIONAL_PATHS_AND_VERIFIED_BUILD16_BACKUP_DIRECTORIES_ONLY
PRECONDITIONS=PASS_BUILD16_WITH_IMAGE_BOUNDS_ALREADY_COMPLETE

============================================================
1. READ-ONLY RUNTIME AUDIT BEFORE SOURCE MUTATION
============================================================
DELL5591_MATCHES=2
PRODUCT={"id":54,"sku":"DELL-000052","name":"Dell Latitude 5591 Intel Core i7 8850H 16GB 512GB","status":"archived","stock_quantity":0,"deleted_at":"2026-09-07T19:47:35.000000Z","created_at":"2026-09-07T19:00:33.000000Z","updated_at":"2026-09-07T19:47:35.000000Z"}
STOCK_MOVEMENT={"id":85,"movement_type":"sale","source":"direct_sale","quantity_change":-1,"quantity_before":1,"quantity_after":0,"order_id":22,"created_at":"2026-09-07 21:01:52"}
STOCK_MOVEMENT={"id":84,"movement_type":"initial","source":"product_creation","quantity_change":1,"quantity_before":0,"quantity_after":1,"order_id":null,"created_at":"2026-09-07 21:00:33"}
DIRECT_SALE={"id":22,"order_number":"APC-20260907-00000022","status":"shipped","payment_state":"paid","completed_at":"2026-09-07 21:01:52","quantity":1}
PRODUCT={"id":53,"sku":"DELL-000051","name":"Dell Latitude 5591 Intel Core i7 8850H 16GB 512GB","status":"active","stock_quantity":1,"deleted_at":null,"created_at":"2026-09-07T19:00:01.000000Z","updated_at":"2026-09-07T19:00:01.000000Z"}
STOCK_MOVEMENT={"id":83,"movement_type":"initial","source":"product_creation","quantity_change":1,"quantity_before":0,"quantity_after":1,"order_id":null,"created_at":"2026-09-07 21:00:01"}
RECENT_DUPLICATE_NAME_GROUPS=1
RECENT_DUPLICATE={"name":"Dell Latitude 5591 Intel Core i7 8850H 16GB 512GB","duplicate_count":2,"first_id":53,"last_id":54}
DELL5591_RUNTIME_AUDIT_RC=0
DELL5591_RUNTIME_AUDIT=PASS_READ_ONLY_EXECUTED
RUNTIME_AUDIT_DATABASE_WRITES=0

============================================================
2. BACKUP
============================================================
BACKUP_PATH=/home/icaffeco/ald1n-project/.build16-batch134-v3-backup-20260908-121818
BACKUP=PASS_EXACT_9_MUTATED_TARGETS

============================================================
3. APPLY REMAINING BUILD16 CATALOG RUNTIME STABILIZATION V2
============================================================
PATCH134_V3=PASS_9_MUTATED_TARGETS_IMAGE_BOUNDS_ALREADY_COMPLETE
CATALOG_RUNTIME_MUTATION=PASS_REMAINING_9_TARGETS
CATALOG_IMAGE_BOUNDS_MUTATION_THIS_RUN=0_ALREADY_COMPLETE_33604307
DIRECT_SALE_SERVICE_CHANGED=NO
PRODUCT_ADMIN_ARCHIVE_SEMANTICS_CHANGED=NO
CATALOG_ACCESS_SCOPE_CHANGED=NO

============================================================
4. EARLY GENERATED-OUTPUT HYGIENE + TEMP INDEX
============================================================
TARGET_TEXT_HYGIENE=PASS_LF_NO_CR_NO_TRAILING_WHITESPACE
EARLY_WORKTREE_DIFF_CHECK=PASS
EARLY_TEMP_INDEX_STAGED_DIFF_CHECK=PASS
EARLY_TEMP_INDEX_STAGE_SET=PASS_EXACT_9_FILES
GIT_DIFF_CACHED_CHECK_POLICY=PASS_BEFORE_EXPENSIVE_GATES

============================================================
5. BUILD16 RUNTIME REGRESSION GUARDS
============================================================
CATALOG_PRODUCT_ROW_HEIGHT=PASS_BOUNDED_142PX_IMAGE_COLUMN
CATALOG_IMAGE_BOUNDS_SOURCE=ALREADY_COMMITTED_33604307_NOT_REDONE
EXIF_DERIVATIVE_FIX_8C869B58=PRESERVED_NOT_TOUCHED
CATALOG_FOCUS_REFRESH=PASS
PRODUCT_DETAIL_FOCUS_REFRESH=PASS
CATALOG_MUTATION_INVALIDATION=PASS_PRODUCTS_PRODUCT_ADMIN_FILTERS
PRODUCT_CREATE_SINGLE_FLIGHT=PASS_ACTION_LAYER
PRODUCT_CREATE_IDEMPOTENCY=PASS_MOBILE_HEADER_SERVER_REPLAY_CONTRACT
BATCH134_V3_REPAIR=PASS_MUTATION_DESTRUCTURES_AND_CONSUMES_IDEMPOTENCY_KEY
PRODUCT_CREATE_REQUEST_TIMEOUT=PASS_60_SECONDS

============================================================
6. MOBILE GATES
============================================================

> ald1n-mobile@1.0.0 typecheck
> tsc --noEmit


> ald1n-mobile@1.0.0 validate
> node scripts/validate-project.mjs

file:///home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:858
    && build16CreateBatch134.includes("imageFiles,
                                      ^^^^^^^^^^^^

SyntaxError: Invalid or unexpected token
    at compileSourceTextModule (node:internal/modules/esm/utils:346:16)
    at ModuleLoader.moduleStrategy (node:internal/modules/esm/translators:110:18)
    at #translate (node:internal/modules/esm/loader:559:20)
    at afterLoad (node:internal/modules/esm/loader:612:29)
    at ModuleLoader.loadAndTranslate (node:internal/modules/esm/loader:617:12)
    at #createModuleJob (node:internal/modules/esm/loader:640:36)
    at #getJobFromResolveResult (node:internal/modules/esm/loader:353:34)
    at ModuleLoader.getModuleJobForImport (node:internal/modules/esm/loader:321:41)
    at async onImport.tracePromise.__proto__ (node:internal/modules/esm/loader:680:25)

Node.js v22.23.2

BATCH134_RESULT=FAIL
BATCH134_V3_RESULT=FAIL
REPORT360_RESULT=FAIL
REPORT360_V3_RESULT=FAIL
SOURCE_COMMIT_CREATED=0
FAIL_RC=1
ROLLBACK_UNCOMMITTED=YES
