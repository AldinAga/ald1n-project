
============================================================
0. V1 FAILURE RECONCILIATION - REUSE EXISTING 614MB BACKUP
============================================================
V1_FAILURE_RECONCILIATION=PASS_BACKUP_CREATED_WRAPPER_AUTHORITY_CHECK_ONLY
STABLE_BACKUP_REUSED_EXISTING=YES
DATABASE_DUMP_CREATED=NO

============================================================
1. SOURCE + WORKTREE PREFLIGHT
============================================================
LOCAL_HEAD=7e84c7032b03aae31dbad2fafc8bd50156c69790
REMOTE_HEAD=7e84c7032b03aae31dbad2fafc8bd50156c69790
 M apps/cms/current/public/.htaccess
HTACCESS_DIFF_SHA=8ce9fa55a71768bf711203fcc50be8eec6de26886bbf4852ceb30a97b36958fe
SOURCE_PREFLIGHT=PASS_EXACT_REDIS_FINAL_BASELINE
BUILD17_AAB_REFERENCE=PASS_SHA256

============================================================
2. VERIFY EXISTING CANONICAL BACKUP - NO REDUMP
============================================================
VERIFY_DATABASE_FILE=database.sql.gz
VERIFY_DATABASE_SHA256=28bdd4f55af9abee1e1a925eacbbc03054c9cde7538f316b07550014e9a25355
VERIFY_FILE_COUNT=933
VERIFY_RESULT=PASS
CANONICAL_BACKUP_PATH=/home/icaffeco/backups/current/20260909-104609-manual-a352a4
CANONICAL_BACKUP_TOTAL_KB=633408
CANONICAL_BACKUP_TOTAL_MB=618.56
CANONICAL_BACKUP_VERIFICATION=PASS_MANIFEST_SHA256_GZIP

============================================================
3. STATIC + REDIS FINAL POLICY RECONFIRMATION
============================================================
CMS_STATIC_SUMMARY=Ukupno: 983, neuspešno: 0
RUNTIME_POLICY_SAFE=redis|redis|file|database|phpredis
CMS_STATIC_CHECK=PASS_983_TOTAL_0_FAILED
REDIS_FINAL_POLICY=PASS_REDIS_REDIS_FILE_DATABASE_PHPREDIS

============================================================
4. CREATE SMALL STABLE CHECKPOINT - REFERENCES 614MB BACKUP
============================================================
GIT_BUNDLE_VERIFY=PASS_ALL_REFS
SOURCE_ARCHIVE_VERIFY=PASS_TAR_GZIP
CANONICAL-BACKUP-PATH.txt: OK
canonical-backup-manifest.json: OK
canonical-backup-verification.txt: OK
cms-production.env: OK
cms-public.htaccess.runtime: OK
ald1n-project-all-refs.bundle: OK
source-7e84c7032b03aae31dbad2fafc8bd50156c69790.tar.gz: OK
BUILD17-AAB-REFERENCE.txt: OK
STABLE-CHECKPOINT.txt: OK
STABLE_CHECKPOINT_PATH=/home/icaffeco/backups/stable/ald1n-stable-20260909-112358-7e84c70
STABLE_CHECKPOINT_SHA256=PASS

============================================================
5. POSTCHECK - SOURCE + CANONICAL BACKUP IMMUTABLE
============================================================
SOURCE_IMMUTABILITY=PASS
CANONICAL_BACKUP_IMMUTABILITY=PASS

============================================================
6. FINAL RESULT
============================================================
BATCH146_RESULT=PASS_STABLE_BACKUP_CREATED_AND_VERIFIED
BATCH146_V2_RESULT=PASS_EXISTING_CANONICAL_BACKUP_PROMOTED_WITHOUT_REDUMP
SOURCE_COMMIT=7e84c7032b03aae31dbad2fafc8bd50156c69790
CANONICAL_BACKUP_PATH=/home/icaffeco/backups/current/20260909-104609-manual-a352a4
STABLE_CHECKPOINT_PATH=/home/icaffeco/backups/stable/ald1n-stable-20260909-112358-7e84c70
STABLE_BACKUP_REUSED_EXISTING=YES
DATABASE_DUMP_CREATED=NO
DATABASE_WRITES=NO
SOURCE_MUTATION=NO
BUILD_CREATED=NO
OTA_PUBLISHED=NO
GOOGLE_PLAY_ACTION=NO
NEXT_ACTION=RUN_BATCH147_DEFERRED_PAYMENT_RANDOM_DATE_ALLOCATION_LEDGER
REPORT_PATH=/home/icaffeco/ald1n-project/docs/operations/373-MOBILE-STABLE-BACKUP-BATCH146-V2-20260909-112358.md
