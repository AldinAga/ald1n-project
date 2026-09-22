
============================================================
0. V1+V2+V3+V4 FAILURE RECONCILIATION + STABLE CHECKPOINT + SOURCE AUTHORITY PREFLIGHT
============================================================
V1_FAILURE_RECONCILIATION=PASS_WRONG_EXPO_ROUTER_GROUP_PATH_ONLY_NO_MIGRATION_NO_COMMIT
V2_FAILURE_RECONCILIATION=PASS_ROUTE_ANCHOR_ONLY_NO_MIGRATION_NO_COMMIT
V3_FAILURE_RECONCILIATION=PASS_DUPLICATE_STATIC_ASSIGNMENT_ANCHOR_ONLY_NO_MIGRATION_NO_COMMIT
V4_FAILURE_RECONCILIATION=PASS_PHP_TEMPLATE_INTERPOLATION_ANCHOR_ONLY_NO_MIGRATION_NO_COMMIT
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

   ERROR  Command "test" is not defined. Did you mean one of these?  

  ⇂ app:send-test-mail  
  ⇂ app:test-database-doctor  
  ⇂ make:test  
  ⇂ schedule:test  

BATCH147_RESULT=FAIL_RECEIVABLES_FEATURE_TEST_RC_1
MIGRATION_DONE=0
COMMIT_DONE=0
ROLLBACK_SOURCE=ATTEMPTED_PRE_MIGRATION
REPORT_PATH=/home/icaffeco/ald1n-project/docs/operations/374-MOBILE-DEFERRED-PAYMENT-RANDOM-DATE-BATCH147-V5-20260909-114602.md
