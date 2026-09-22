
============================================================
0. V6 V2 FAILURE RECONCILIATION + EXACT SOURCE AUTHORITY
============================================================
BATCH149E_V6_V2_RECONCILIATION=PASS_FALSE_POSITIVE_RAW_TOKEN_VALIDATOR_FAILURE_AND_FULL_PRECOMMIT_ROLLBACK_CONFIRMED
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
BRANCH=main
LOCAL_HEAD=939464bafc7230d0387ed17c556c4eebb7dac37e
REMOTE_HEAD=939464bafc7230d0387ed17c556c4eebb7dac37e
HTACCESS_FILE_SHA256=d10ecf0d63f9a8e1bd978a440afcbadb34faf1d24eb899bf2582d1e5c0033cef
HTACCESS_DIFF_SHA256=8ce9fa55a71768bf711203fcc50be8eec6de26886bbf4852ceb30a97b36958fe
HTACCESS_DRIFT=PASS_PRESERVED_EXACT_KNOWN_403_SHTML_BLOCK
WORKTREE_TRACKED_POLICY=PASS_ONLY_EXACT_KNOWN_PUBLIC_HTACCESS_DRIFT_ALLOWED
WORKTREE_UNTRACKED_POLICY=PASS_ONLY_OPERATION_REPORTS_AND_ROOT_ERROR_LOG_ALLOWED
DEPENDENCY_SOURCE_IDENTITY=PASS_TWELVE_AUDITED_FILES_MATCH_939464B

============================================================
1. DEPENDENCY AUDIT BEFORE ANY SOURCE MUTATION
============================================================
No syntax errors detected in /home/icaffeco/ald1n-project/tmp/product-editor-true-draft-save-batch149e-v6-v3-20260910-100801/dependency-audit.php
DEPENDENCY_PASS=WEB_ROUTES_PRODUCT_STORE_UPDATE
DEPENDENCY_PASS=API_ROUTES_PRODUCT_STORE_UPDATE
DEPENDENCY_PASS=WEB_CONTROLLER_SHARED_PRODUCT_REQUEST
DEPENDENCY_PASS=API_CONTROLLER_SHARED_PRODUCT_REQUEST
DEPENDENCY_PASS=PRODUCT_SERVICE_SHARED_CREATE_UPDATE
DEPENDENCY_PASS=PRODUCT_SERVICE_COMPLETENESS_BEFORE_PERSIST
DEPENDENCY_PASS=PRODUCT_TEMPLATE_PRICE_COMPLETENESS_BASELINE
DEPENDENCY_PASS=PRODUCT_COMPLETENESS_RECALCULATES_THROUGH_TEMPLATE
DEPENDENCY_PASS=UX_RUNTIME_NATIVE_VALIDITY_GATE_PRESENT
DEPENDENCY_PASS=PRODUCT_MODEL_NON_NULL_PERSIST_FIELDS_FILLABLE
DEPENDENCY_PASS=TRUE_DRAFT_NOT_ALREADY_PRESENT
BASELINE_HTML_PRIMARY_SAVE_BUTTONS=1
BASELINE_RAW_PRIMARY_TOKEN_COUNT=2
V6_V2_ROOT_CAUSE=PASS_RAW_TOKEN_COUNT_INCLUDED_JS_SELECTOR_HTML_AWARE_VALIDATOR_REQUIRED
DEPENDENCY_AUDIT_SOURCE=PASS_WEB_API_SHARED_REQUEST_SERVICE_TEMPLATE_RECALC_FORM_UX_AND_MODEL_CHAIN_CAPTURED
No syntax errors detected in /home/icaffeco/ald1n-project/tmp/product-editor-true-draft-save-batch149e-v6-v3-20260910-100801/dependency-runtime-audit.php
DB_DRIVER=mysql
SCHEMA_CONTRACT=PASS_DRAFT_CORE_PERSISTENCE_COLUMNS_NON_NULL_REQUIRE_SERVICE_DEFAULTS
ACTIVE_ZERO_OR_NEGATIVE_PRICE_PRODUCTS=0
PRICE_COMPLETENESS_HARDENING_PREFLIGHT=PASS_NO_ACTIVE_ZERO_PRICE_PRODUCTS
DEPENDENCY_AUDIT=PASS_WEB_ONLY_DRAFT_SCOPE_SHARED_MOBILE_REQUEST_UX_FORMNOVALIDATE_SERVICE_PERSISTENCE_AND_SCHEMA_REVIEWED

============================================================
2. COPY-FIRST BACKUP OF ALL SIX SOURCE TARGETS
============================================================
SOURCE_BACKUP=PASS_EXACT_SIX_DEPENDENCY_AUDITED_TARGET_FILES
BACKUP_PATH=/home/icaffeco/ald1n-project/tmp/product-editor-true-draft-save-batch149e-v6-v3-20260910-100801/source-backup

============================================================
3. APPLY TRUE DRAFT WITH WEB-ONLY SCOPE + UX RUNTIME COMPATIBILITY
============================================================
No syntax errors detected in /home/icaffeco/ald1n-project/tmp/product-editor-true-draft-save-batch149e-v6-v3-20260910-100801/patch.php
PATCH=request-web-draft-mode-for-rules
PATCH=request-rule--price-amount-required-numeric-min-0-max-9999999999-99-
PATCH=request-rule--description-required-string-max-65000-
PATCH=request-rule--stock-quantity-required-integer-min-0-max-1000000-
PATCH=request-rule--low-stock-threshold-required-integer-min-0-max-1000000-
PATCH=request-rule--spec-structured-type-required-string-max-255-
PATCH=request-web-draft-mode-for-after-validation
PATCH=required-specifications-relaxed-only-for-explicit-web-draft
PATCH=request-explicit-web-draft-status-normalization
PATCH=request-normalized-status-from-explicit-draft-intent
PATCH=request-web-route-scoped-draft-intent-helper
ANCHOR_FAIL=service-draft-persistence-defaults-after-completeness-create-and-update COUNT=3 EXPECTED=2
FAIL=PATCH_APPLICATION_FAILED

============================================================
ROLLBACK PRE-COMMIT
============================================================

   INFO  Compiled views cleared successfully.  



   INFO  Blade templates cached successfully.  

ROLLBACK=PASS_PRECOMMIT_ALL_SIX_SOURCE_FILES_AND_VIEW_CACHE_RESTORED
SOURCE_COMMIT_DONE=0
SOURCE_PUSH_DONE=0
PRODUCTION_BUSINESS_WRITES=NONE_BY_BATCH
