
============================================================
0. BATCH149G V1 PASS RECONCILIATION + TRUE-DRAFT SOURCE AUTHORITY
============================================================
BATCH149G_V1_RECONCILIATION=PASS_CART_ICON_AND_PRODUCT_SIDE_RAIL_FIX_COMMITTED_PUSHED
BATCH149E_V6_RECONCILIATION=PASS_PREVIOUS_TRUE_DRAFT_ATTEMPT_DID_NOT_PATCH_OR_COMMIT_SOURCE
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
TARGET_SOURCE_IDENTITY=PASS_PRODUCT_REQUEST_FORM_AND_CSS_MATCH_939464B

============================================================
1. COPY-FIRST BACKUP OF EXACT THREE TARGET FILES
============================================================
SOURCE_BACKUP=PASS_PRODUCT_REQUEST_FORM_AND_CSS
BACKUP_PATH=/home/icaffeco/ald1n-project/tmp/product-editor-true-draft-save-batch149e-v6-v2-20260910-094205/source-backup

============================================================
2. APPLY TRUE DRAFT VALIDATION SPLIT + EXPLICIT SAVE-AS-DRAFT UX
============================================================
No syntax errors detected in /home/icaffeco/ald1n-project/tmp/product-editor-true-draft-save-batch149e-v6-v2-20260910-094205/patch.php
PATCH=request-publishing-mode-for-rules
PATCH=request-rule--price-amount-required-numeric-min-0-max-9999999999-99-
PATCH=request-rule--description-required-string-max-65000-
PATCH=request-rule--stock-quantity-required-integer-min-0-max-1000000-
PATCH=request-rule--low-stock-threshold-required-integer-min-0-max-1000000-
PATCH=request-rule--spec-structured-type-required-string-max-255-
PATCH=request-publishing-mode-for-after-validation
PATCH=required-specifications-only-for-active-publication
PATCH=request-save-draft-intent-normalization
PATCH=request-normalized-status-from-submit-intent
PATCH=request-db-safe-defaults-for-non-active-draft-or-inactive
PATCH=form-initial-product-status-marker
PATCH=form-publication-guidance-and-active-price-required
PATCH=form-active-stock-required-markers
PATCH=form-sidebar-explicit-draft-and-context-save-actions
PATCH=form-description-required-only-for-active-publication
PATCH=form-sticky-action-bar-draft-and-context-save-actions
PATCH=form-dynamic-publication-mode-runtime
PATCH=true-draft-publication-guidance-css

============================================================
3. PHP + SOURCE CONTRACT
============================================================
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductRequest.php
No syntax errors detected in /home/icaffeco/ald1n-project/tmp/product-editor-true-draft-save-batch149e-v6-v2-20260910-094205/source-contract.php
FORM_PRIMARY_SAVE_COUNT_INVALID
FAIL=SOURCE_CONTRACT_FAILED

============================================================
ROLLBACK PRE-COMMIT
============================================================

   INFO  Compiled views cleared successfully.  



   INFO  Blade templates cached successfully.  

ROLLBACK=PASS_PRECOMMIT_SOURCE_AND_VIEW_CACHE_RESTORED
SOURCE_COMMIT_DONE=0
SOURCE_PUSH_DONE=0
PRODUCTION_BUSINESS_WRITES=NONE_BY_BATCH
