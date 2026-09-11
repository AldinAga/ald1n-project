
============================================================
0. V1+V2+V3+V4+V5+V6+V7 FAILURE RECONCILIATION + STABLE CHECKPOINT + SOURCE AUTHORITY PREFLIGHT
============================================================
V1_FAILURE_RECONCILIATION=PASS_WRONG_EXPO_ROUTER_GROUP_PATH_ONLY_NO_MIGRATION_NO_COMMIT
V2_FAILURE_RECONCILIATION=PASS_ROUTE_ANCHOR_ONLY_NO_MIGRATION_NO_COMMIT
V3_FAILURE_RECONCILIATION=PASS_DUPLICATE_STATIC_ASSIGNMENT_ANCHOR_ONLY_NO_MIGRATION_NO_COMMIT
V4_FAILURE_RECONCILIATION=PASS_PHP_TEMPLATE_INTERPOLATION_ANCHOR_ONLY_NO_MIGRATION_NO_COMMIT
V5_FAILURE_RECONCILIATION=PASS_ARTISAN_TEST_COMMAND_UNAVAILABLE_NO_MIGRATION_NO_COMMIT
V6_FAILURE_RECONCILIATION=PASS_PHPUNIT_DEV_BINARY_ABSENT_NO_MIGRATION_NO_COMMIT
V7_FAILURE_RECONCILIATION=PASS_HISTORICAL_MYSQL_ONLY_MIGRATION_INCOMPATIBLE_WITH_SQLITE_NO_MIGRATION_NO_COMMIT
STABLE_CHECKPOINT_GUARD=PASS
STABLE_CHECKPOINT_PATH=/home/icaffeco/backups/stable/ald1n-stable-20260909-112358-7e84c70
LOCAL_HEAD=7e84c7032b03aae31dbad2fafc8bd50156c69790
REMOTE_HEAD=7e84c7032b03aae31dbad2fafc8bd50156c69790
 M apps/cms/current/public/.htaccess
SOURCE_AUTHORITY=PASS_EXACT_REDIS_FINAL_BASELINE

============================================================
1. COPY-FIRST SOURCE BACKUP
============================================================
SOURCE_BACKUP=PASS_10_EXISTING_FILES

============================================================
2. CREATE ADDITIVE ALLOCATION MIGRATION + MODEL
============================================================
ALLOCATION_SCHEMA_SOURCE=PASS_ADDITIVE_RECOVERY_SAFE

============================================================
3. PATCH CMS + MOBILE SOURCE WITH EXACT BASELINE ANCHORS
============================================================
SOURCE_PATCH=PASS_ALL_EXACT_ANCHORS

============================================================
4. PRE-MIGRATION SOURCE VALIDATION - DB UNTOUCHED
============================================================
PHP_LINT=PASS_10_FILES
CMS_STATIC_SUMMARY=Ukupno: 983, neuspešno: 0
CMS_STATIC=PASS_983_TOTAL_0_FAILED
PDO_SQLITE_EXTENSION=yes
ISOLATED_BASE_SCHEMA=PASS_MINIMAL_REQUIRED_TABLES
ISOLATED_NEW_MIGRATION=PASS_ALLOCATION_MIGRATION_ON_MINIMAL_SQLITE
ISOLATED_DB=PASS_SQLITE_BATCH_WORKSPACE_ONLY
PAYMENT_1=PASS_10000_RANDOM_DATE_CASH
PAYMENT_2=PASS_35000_RANDOM_DATE_BANK_TRANSFER
FIFO_ALLOCATION=PASS_10000_20000_15000
INSTALLMENT_COMPLETION_DATE=PASS_SECOND_REAL_PAYMENT_DATE
PAYMENT_TIMELINE=PASS_REAL_ACTUAL_DATES_METHOD_REFERENCE_NOTE
RECEIVABLES_SMOKE=PASS_REAL_ORDER_PAYMENT_SERVICE
RECEIVABLES_FEATURE_TEST=PASS_MINIMAL_LARAVEL_SQLITE_SMOKE_NO_HISTORICAL_MYSQL_DDL_NO_PHPUNIT
RECEIVABLE_PAYMENT_API_ROUTE=PASS_EXACT_PREFIX
RECEIVABLE_PAYMENT_PERMISSION=PASS_RECEIVABLES_PLUS_PAYMENTS_MANAGE

> ald1n-mobile@1.0.0 typecheck
> tsc --noEmit

MOBILE_TYPECHECK=PASS
PRE_MIGRATION_VALIDATION=PASS_SOURCE_STATIC_MINIMAL_SQLITE_SMOKE_ROUTES_TYPECHECK

============================================================
5. ADDITIVE PRODUCTION MIGRATION
============================================================

   INFO  Running migrations.  

  2026_09_09_000147_create_receivable_payment_allocations_batch147 ................................................................... 181.64ms DONE

Unsuccessful stat on filename containing newline at /var/cpanel/ea4/ea_php_cli.pm line 87.
SCHEMA=PASS
MIGRATION=PASS_ADDITIVE_ALLOCATION_LEDGER

============================================================
6. FINAL VALIDATION AFTER MIGRATION
============================================================
POST_MIGRATION_VALIDATION=PASS_STATIC_983_TYPECHECK

============================================================
7. GIT SCOPE + COMMIT + PUSH
============================================================
apps/cms/current/app/Http/Controllers/Api/V1/Admin/ReceivablesController.php
apps/cms/current/app/Models/OrderPayment.php
apps/cms/current/app/Models/ReceivableCase.php
apps/cms/current/app/Models/ReceivableInstallment.php
apps/cms/current/app/Models/ReceivablePaymentAllocation.php
apps/cms/current/app/Services/ReceivablesService.php
apps/cms/current/bin/static-check.php
apps/cms/current/database/migrations/2026_09_09_000147_create_receivable_payment_allocations_batch147.php
apps/cms/current/routes/api.php
apps/cms/current/tests/Feature/ReceivablesCollectionTest.php
apps/mobile/current/src/app/(app)/admin/receivables/[id].tsx
apps/mobile/current/src/features/admin/receivables-admin-api.ts
[main 508a6d8] feat(receivables): track actual deferred payment allocations
 12 files changed, 457 insertions(+), 5 deletions(-)
 create mode 100644 apps/cms/current/app/Models/ReceivablePaymentAllocation.php
 create mode 100644 apps/cms/current/database/migrations/2026_09_09_000147_create_receivable_payment_allocations_batch147.php
SOURCE_COMMIT=508a6d8934b9b6dbf9a508e6d9a41fd3baf18e55
To github.com:AldinAga/ald1n-project.git
   7e84c70..508a6d8  main -> main
GIT_PUSH=PASS_REMOTE_MAIN_508a6d8934b9b6dbf9a508e6d9a41fd3baf18e55

============================================================
8. FINAL WORKTREE + FUNCTIONAL CONTRACT
============================================================
 M apps/cms/current/public/.htaccess
DEFERRED_PAYMENT_ACTUAL_DATE=PASS_ARBITRARY_PAID_AT
PAYMENT_ALLOCATION_FIFO_SPLIT=PASS_PAYMENT_CAN_SPLIT_ACROSS_INSTALLMENTS
LEGACY_PLAN_POLICY=PASS_NO_FABRICATED_HISTORICAL_ALLOCATION
MOBILE_RECEIVABLES_PAYMENTS_WORKSPACE=PASS
PAYMENT_WRITE_PERMISSION=PASS_PAYMENTS_MANAGE_REQUIRED

============================================================
9. FINAL RESULT
============================================================
BATCH147_RESULT=PASS_DEFERRED_PAYMENT_RANDOM_DATE_ALLOCATION_LEDGER
BATCH147_V8_RESULT=PASS_MINIMAL_SQLITE_SCHEMA_RECOVERY_AND_DEFERRED_PAYMENT_ALLOCATION_LEDGER
SOURCE_COMMIT=508a6d8934b9b6dbf9a508e6d9a41fd3baf18e55
CMS_STATIC=PASS_983_TOTAL_0_FAILED
MOBILE_TYPECHECK=PASS
MIGRATION=PASS
PAYMENT_TIMELINE=PASS_REAL_ACTUAL_DATES_METHOD_REFERENCE_NOTE
PAYMENT_ALLOCATION_FIFO_SPLIT=PASS
DATABASE_WRITES=ADDITIVE_MIGRATION_ONLY_NO_BUSINESS_PAYMENT_CREATED
BUILD_CREATED=NO
OTA_PUBLISHED=NO
GOOGLE_PLAY_ACTION=NO
NEXT_ACTION=RUN_BATCH148_RUNTIME_CONTRACT_VALIDATION_THEN_DEVICE_VALIDATE_RECEIVABLES_RANDOM_DATE_PAYMENT_ENTRY
REPORT_PATH=/home/icaffeco/ald1n-project/docs/operations/374-MOBILE-DEFERRED-PAYMENT-RANDOM-DATE-BATCH147-V8-20260909-122008.md
