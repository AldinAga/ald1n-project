
============================================================
BATCH168 - BUILD18 FOUNDATION UX CRM PURGE ANALYTICS AUDIT - READ ONLY
============================================================
TIMESTAMP=20260919-081304
SCOPE=build18_foundation_ux_simplification_total_product_purge_crm_customer_360_advanced_profitability
MODE=READ_ONLY_NO_SOURCE_OR_BUSINESS_MUTATION
EXPECTED_HEAD=77a0fe15767beb34574c29bbfc32e5586d61ea0c
BUILD18_RELEASE_TARGET=ANDROID_BUILD18
BUILD18_RUNTIME_TARGET=1.0.0-build18
APP_SOURCE_CHANGE=NO
DOCUMENTATION_CHANGE=NO_EXCEPT_CURRENT_UNTRACKED_REPORT
DATABASE_WRITES=NO
DEPENDENCY_INSTALL=NO
EAS_COMMANDS_RUN=NO
OTA_ACTION=NO
BUILD_ACTION=NO
GOOGLE_PLAY_ACTION=NO

============================================================
0. BIND CLEAN CHECKPOINT AUTHORITY
============================================================
REPORT167A_SHA_ACTUAL=645f0f76e6d5927619de16d20abae5eea81348073f372cbf42d501f62c51096a
REPORT167A_SHA_EXPECTED=645f0f76e6d5927619de16d20abae5eea81348073f372cbf42d501f62c51096a
REPORT167A_MARKER=PASS BATCH167A_RESULT=PASS_FULL_SAFE_GITHUB_CHECKPOINT
REPORT167A_MARKER=PASS CHECKPOINT_COMMIT=77a0fe15767beb34574c29bbfc32e5586d61ea0c
REPORT167A_MARKER=PASS CANONICAL_EAS_PIN=eas-cli@24.7.0
REPORT167A_MARKER=PASS APPLICATION_SOURCE_TREES_IMMUTABLE=PASS
REPORT167A_MARKER=PASS ROADMAP_STATE=CLEAN_BASELINE_NO_PREAPPROVED_UNFINISHED_FEATURE_FOUND
BATCH167A_PASS_AUTHORITY=PASS_BOUND

============================================================
1. GIT AND HOSTING AUTHORITY
============================================================

============================================================
RUN - git_fetch_preflight
============================================================
CWD=/home/icaffeco
COMMAND=git -C /home/icaffeco/ald1n-project fetch origin main
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
RC_git_fetch_preflight=0
BRANCH=main
LOCAL_HEAD=77a0fe15767beb34574c29bbfc32e5586d61ea0c
REMOTE_HEAD=77a0fe15767beb34574c29bbfc32e5586d61ea0c
STAGED_COUNT=0
HTACCESS_SHA_ACTUAL=d10ecf0d63f9a8e1bd978a440afcbadb34faf1d24eb899bf2582d1e5c0033cef
HTACCESS_DIFF_SHA_ACTUAL=8ce9fa55a71768bf711203fcc50be8eec6de26886bbf4852ceb30a97b36958fe
KNOWN_HTACCESS_DRIFT=PASS
CMS_TREE=703f24e38d294911b002268196869d606865abc1
MOBILE_TREE=1a537e41b67f607fbb5b3f23ea48ea8059c6b9c6
API_TREE=a30324349de94b9511e5341887940d2cbf2baa14

============================================================
2. WORKTREE ALLOWLIST
============================================================
 M apps/cms/current/public/.htaccess
?? docs/operations/BATCH167A-FULL-SAFE-GITHUB-CHECKPOINT-20260918-140209.md
?? docs/operations/BATCH168-BUILD18-FOUNDATION-UX-CRM-PURGE-ANALYTICS-AUDIT-READ-ONLY-20260919-081304.md
WORKTREE_ALLOWLIST=PASS_KNOWN_HTACCESS_REPORT167A_CURRENT_REPORT_ONLY

============================================================
3. CANONICAL TOOLING AND BUILD17 BASELINE
============================================================
AGENTS_NEW_PIN_COUNT=5
AGENTS_OLD_PIN_COUNT=0
CURRENT_APP_VERSION=1.0.0
CURRENT_RUNTIME_VERSION=1.0.0-build17
BUILD18_VERSION_POLICY=KEEP_APP_VERSION_1.0.0_AND_ADVANCE_RUNTIME_TO_1.0.0-build18_AT_RELEASE_LOCK
BUILD18_EAS_BUILD_POLICY=ONE_FINAL_BUILD_AFTER_SOURCE_FREEZE

============================================================
4. OPENAPI THREE COPY AUTHORITY
============================================================
OPENAPI_SHA=6a4ab29bee43be3af1fa9ff58466039ab5bf33cf3d7375e1d8dbb21e9f838577 PATH=/home/icaffeco/ald1n-project/apps/cms/current/docs/openapi.yaml
OPENAPI_SHA=6a4ab29bee43be3af1fa9ff58466039ab5bf33cf3d7375e1d8dbb21e9f838577 PATH=/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml
OPENAPI_SHA=6a4ab29bee43be3af1fa9ff58466039ab5bf33cf3d7375e1d8dbb21e9f838577 PATH=/home/icaffeco/ald1n-project/packages/api-contract/openapi.yaml
OPENAPI_PARITY=PASS_EXACT_3_COPIES

============================================================
5. UX UI REDUNDANCY AND INFORMATION ARCHITECTURE AUDIT
============================================================
PRIMARY_TAB_DESTINATION_COUNT=5
PRIMARY_TAB_BAR_HIDDEN_MARKER_COUNT=1
HOME_DUPLICATE_CATALOG_SHORTCUT_COUNT=2
HOME_DUPLICATE_ORDERS_SHORTCUT_COUNT=1
HOME_ADMIN_ENTRY_COUNT=1
ADMIN_SEARCH_PRESENT_COUNT=2
ADMIN_NONINTERACTIVE_MODULE_SECTION_COUNT=1
ADMIN_NONINTERACTIVE_MODULE_MAP_COUNT=1
REPORTS_WORKSPACE_BYTES=48753
UX_FINDING_1=FIVE_PRIMARY_DESTINATIONS_EXIST_BUT_TAB_BAR_IS_HIDDEN
UX_FINDING_2=HOME_REPEATS_CATALOG_AND_ORDERS_DESTINATIONS_ALREADY_DEFINED_AS_PRIMARY_TABS
UX_FINDING_3=ADMIN_HUB_HAS_REAL_GROUPED_ACTIONS_PLUS_NONINTERACTIVE_ADMIN_MODULE_CARDS
UX_FINDING_4=HOME_DASHBOARD_ALREADY_DUPLICATES_PART_OF_FULL_REPORTS_WORKSPACE
BUILD18_UX_DIRECTION=VISIBLE_5_DESTINATION_PRIMARY_NAV_PLUS_PROGRESSIVE_DISCLOSURE
BUILD18_UX_PRESERVE=ALL_EXISTING_ROUTES_PERMISSIONS_AND_BUSINESS_ACTIONS
BUILD18_UX_REMOVE_ONLY=REDUNDANT_ENTRY_POINTS_AND_NONINTERACTIVE_DUPLICATE_MODULE_CARDS
BUILD18_UX_HOME_POLICY=FOCUS_TODAY_PLUS_MAX_4_PRIMARY_KPIS_PLUS_CONTEXTUAL_QUICK_ACTIONS
BUILD18_UX_ANALYTICS_POLICY=DETAILED_METRICS_LIVE_IN_ANALYTICS_WORKSPACE_NOT_DUPLICATED_ON_HOME
BUILD18_UX_ADMIN_GROUPS=SALES_CATALOG_INVENTORY_CUSTOMERS_CRM_AFTER_SALES_ANALYTICS_SYSTEM

============================================================
6. TOTAL PRODUCT PURGE EXISTING AUTHORITY
============================================================
PASS order_items.product_id remains nullable
PASS order_items.product_id remains ON DELETE SET NULL
PASS stock_movements.product_id remains nullable
PASS stock_movements.product_id remains ON DELETE SET NULL
PASS admin.products.total-purge route exists
PASS total purge route uses DELETE
PASS total purge route remains inside catalog.manage_products middleware
PASS ProductController totalPurge action exists
PASS Total Product Purge service is SuperAdmin-only
PASS second irreversible confirmation phrase exists
PASS private file quarantine exists
PASS Total Product Purge uses DB transaction
PASS product row explicit delete path exists
PASS in-transaction database ZERO TRACE gate exists
PASS post-commit filesystem/PDF ZERO TRACE gate exists
PASS unknown direct dependency guard exists
PASS unknown text/JSON dependency guard exists
PASS external sent-email boundary guard exists
PASS SuperAdmin Total Product Purge UI exists
PASS second irreversible UI confirmation exists
PASS backup/off-host retention acknowledgement exists
PASS existing safe ProductDeletionService remains present
PASS existing safe ProductDeletionService purge remains present
FAIL installation itself did not purge Product 19
FAIL Product 19 remains archived after installation
TOTAL_PRODUCT_PURGE_CONTRACT_SMOKE=25_CHECKS_23_PASS_2_FAIL
PRODUCTS_PURGED_BY_BATCH7B_INSTALLATION=0
RC_total_product_purge_contract_smoke=1

============================================================
FINAL SUMMARY
============================================================
BATCH168_RESULT=FAIL
FAILED_STAGE=TOTAL_PURGE_CONTRACT_SMOKE
SOURCE_MUTATION=NO
DATABASE_WRITES=NO
EAS_COMMANDS_RUN=NO
OTA_ACTION=NO
BUILD_ACTION=NO
GOOGLE_PLAY_ACTION=NO
REPORT_PATH=/home/icaffeco/ald1n-project/docs/operations/BATCH168-BUILD18-FOUNDATION-UX-CRM-PURGE-ANALYTICS-AUDIT-READ-ONLY-20260919-081304.md
