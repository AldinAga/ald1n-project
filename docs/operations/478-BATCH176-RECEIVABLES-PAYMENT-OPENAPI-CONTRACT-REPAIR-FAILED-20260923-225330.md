
============================================================
478 - BATCH176 RECEIVABLES PAYMENT OPENAPI CONTRACT REPAIR
============================================================
TIMESTAMP=20260923-225330
EXPECTED_HEAD=21255d2b7d329eb8a757805b243be0fedea45780
EXPECTED_PARENT=d7a58590100dea0d177751d096c9597b17eb8258
EXPECTED_BATCH174_SOURCE=2a64be26ca32c8ea922c32bc3ed0b4cc2de574da
TASK=REPAIR_VERIFIED_RUNTIME_OPENAPI_DRIFT_POST_ADMIN_RECEIVABLE_PAYMENT
SOURCE_SCOPE=CANONICAL_OPENAPI_CMS_COPY_MOBILE_COPY_VALIDATOR
REPORT_ARCHIVE_POLICY=APPEND_ONLY
REPORT_CANONICAL_DIRECTORY=/home/icaffeco/ald1n-project/docs/operations
LARAVEL_ROUTE_MUTATION=NO
LARAVEL_CONTROLLER_MUTATION=NO
MOBILE_RECEIVABLES_API_MUTATION=NO
DATABASE_MUTATION=NO
EAS_COMMANDS_RUN=NO
OTA_ACTION=NO
BUILD_ACTION=NO
GOOGLE_PLAY_ACTION=NO

============================================================
0. PREFLIGHT EXACT MAIN, SOURCE BLOBS AND APPEND-ONLY ARCHIVE
============================================================

============================================================
RUN - git_fetch_preflight
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=git fetch origin main
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
RC_git_fetch_preflight=0
BRANCH=main
LOCAL_HEAD=21255d2b7d329eb8a757805b243be0fedea45780
REMOTE_HEAD=21255d2b7d329eb8a757805b243be0fedea45780
HEAD_PARENT=d7a58590100dea0d177751d096c9597b17eb8258
HEAD_SUBJECT=docs: recertify Batch175 with runtime route inventory
UNRELATED_DIRTY_BEFORE_COUNT=1
REPORT477_V3_BLOB_ACTUAL=f4e766e53db3e74a479f1457147654b120510081
REPORT477_V3_BLOB_EXPECTED=f4e766e53db3e74a479f1457147654b120510081
ROUTES_API_BLOB_ACTUAL=d0194bccd08c0d9681da0c756ef31c1b94fae3a9
ROUTES_API_BLOB_EXPECTED=d0194bccd08c0d9681da0c756ef31c1b94fae3a9
RECEIVABLES_CONTROLLER_BLOB_ACTUAL=683d900f49c7b8e85fa7c8613d9548af99467f30
RECEIVABLES_CONTROLLER_BLOB_EXPECTED=683d900f49c7b8e85fa7c8613d9548af99467f30
RECEIVABLES_MOBILE_API_BLOB_ACTUAL=4e8d97f9229caaccf60cbd9c868885e221bb1e58
RECEIVABLES_MOBILE_API_BLOB_EXPECTED=4e8d97f9229caaccf60cbd9c868885e221bb1e58
VALIDATOR_BLOB_ACTUAL=cc840240b162a4cbbb8c7d2e398e2fcb9fb8ae1b
VALIDATOR_BLOB_EXPECTED=cc840240b162a4cbbb8c7d2e398e2fcb9fb8ae1b
OPENAPI_PKG_BLOB_ACTUAL=6998966112fae0dacbb7ca4c9d81300b603defcb
OPENAPI_CMS_BLOB_ACTUAL=6998966112fae0dacbb7ca4c9d81300b603defcb
OPENAPI_MOBILE_BLOB_ACTUAL=6998966112fae0dacbb7ca4c9d81300b603defcb
NUMBERED_REPORTS_IN_OPERATIONS_BEFORE=485
NUMBERED_REPORTS_IN_OPERATIONS_EXPECTED_AFTER=486

============================================================
1. BUILD CONTRACT PATCH AND VERIFICATION HELPERS
============================================================

============================================================
RUN - lint_patch_helper
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=php -l /home/icaffeco/.ald1n-batch176-receivables-openapi-contract-repair-20260923-225330/patch-contract.php
No syntax errors detected in /home/icaffeco/.ald1n-batch176-receivables-openapi-contract-repair-20260923-225330/patch-contract.php
RC_lint_patch_helper=0

============================================================
RUN - lint_route_inventory
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=php -l /home/icaffeco/.ald1n-batch176-receivables-openapi-contract-repair-20260923-225330/admin-route-inventory.php
No syntax errors detected in /home/icaffeco/.ald1n-batch176-receivables-openapi-contract-repair-20260923-225330/admin-route-inventory.php
RC_lint_route_inventory=0

============================================================
RUN - lint_runtime_audit
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node --check /home/icaffeco/.ald1n-batch176-receivables-openapi-contract-repair-20260923-225330/runtime-openapi-audit.cjs
RC_lint_runtime_audit=0

============================================================
RUN - lint_openapi_parser
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node --check /home/icaffeco/.ald1n-batch176-receivables-openapi-contract-repair-20260923-225330/parse-openapi.cjs
RC_lint_openapi_parser=0

============================================================
RUN - lint_business_counts
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=php -l /home/icaffeco/.ald1n-batch176-receivables-openapi-contract-repair-20260923-225330/business-counts.php
No syntax errors detected in /home/icaffeco/.ald1n-batch176-receivables-openapi-contract-repair-20260923-225330/business-counts.php
RC_lint_business_counts=0

============================================================
RUN - lint_backup_count
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=php -l /home/icaffeco/.ald1n-batch176-receivables-openapi-contract-repair-20260923-225330/backup-count.php
No syntax errors detected in /home/icaffeco/.ald1n-batch176-receivables-openapi-contract-repair-20260923-225330/backup-count.php
RC_lint_backup_count=0

============================================================
2. BASELINE SAFETY SNAPSHOT
============================================================

============================================================
RUN - business_counts_before
============================================================
CWD=/home/icaffeco/ald1n-project/apps/cms/current
COMMAND=php /home/icaffeco/.ald1n-batch176-receivables-openapi-contract-repair-20260923-225330/business-counts.php /home/icaffeco/ald1n-project/apps/cms/current
USERS_COUNT=19
ORDERS_COUNT=36
CUSTOMER_CRM_NOTES_COUNT=0
ORDER_ITEMS_COUNT=40
ORDER_PAYMENTS_COUNT=36
RC_business_counts_before=0

============================================================
RUN - backup_count_before
============================================================
CWD=/home/icaffeco/ald1n-project/apps/cms/current
COMMAND=php /home/icaffeco/.ald1n-batch176-receivables-openapi-contract-repair-20260923-225330/backup-count.php /home/icaffeco/ald1n-project/apps/cms/current
COMPLETED_BACKUP_COUNT=2
COMPLETED_BACKUP_IDS=121,120
BACKUP_121_PATH=/home/icaffeco/backups/current/20260923-023005-daily-22b123
BACKUP_121_DIR=YES
BACKUP_120_PATH=/home/icaffeco/backups/current/20260922-023004-daily-a7ca23
BACKUP_120_DIR=YES
RC_backup_count_before=0

============================================================
3. APPLY MINIMAL CONTRACT REPAIR
============================================================

============================================================
RUN - patch_contract
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=php /home/icaffeco/.ald1n-batch176-receivables-openapi-contract-repair-20260923-225330/patch-contract.php /home/icaffeco/ald1n-project/packages/api-contract/openapi.yaml /home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs
PATCH_CONTRACT=PASS
RC_patch_contract=0
OPENAPI_COPIES_REFRESHED_FROM_CANONICAL=YES
EXPECTED_CHANGED_BEGIN
apps/cms/current/docs/openapi.yaml
apps/mobile/current/docs/openapi.yaml
apps/mobile/current/scripts/validate-project.mjs
packages/api-contract/openapi.yaml
EXPECTED_CHANGED_END
ACTUAL_CHANGED_BEGIN
apps/cms/current/docs/openapi.yaml
apps/cms/current/public/.htaccess
apps/mobile/current/docs/openapi.yaml
apps/mobile/current/scripts/validate-project.mjs
packages/api-contract/openapi.yaml
ACTUAL_CHANGED_END

============================================================
FAIL
============================================================
BATCH176_RESULT=FAIL
FAILED_REASON=SOURCE_DIFF_SCOPE_MISMATCH
SOURCE_COMMITTED=0
SOURCE_COMMIT=NONE
REPORT_ARCHIVE_POLICY=APPEND_ONLY
REPORT_CANONICAL_DIRECTORY=/home/icaffeco/ald1n-project/docs/operations
SOURCE_SCOPE=OPENAPI_3_COPIES_PLUS_VALIDATOR_ONLY
LARAVEL_BUSINESS_LOGIC_MUTATION=NO
DATABASE_MUTATION=NO
