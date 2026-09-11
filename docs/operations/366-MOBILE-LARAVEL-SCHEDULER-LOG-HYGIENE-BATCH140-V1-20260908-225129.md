# Report366 - Mobile Laravel Scheduler Log Hygiene Batch140 V1

- Timestamp: 20260908-225129
- Purpose: audit Laravel scheduler logging and safely rotate/compress scheduler.log when above threshold without changing cron, source, database, EAS, OTA or Google Play state
- Expected source authority: 1f1b2407a4aaecf88f7fc724d3964921fe30585b
- Rotation threshold bytes: 10485760
- Compressed scheduler archive retention: 3
- Source mutation: FORBIDDEN
- Cron mutation: FORBIDDEN
- Build creation: FORBIDDEN
- OTA publish: FORBIDDEN
- Google Play submit: FORBIDDEN
- Database writes: NO

============================================================
0. SOURCE + PRIOR PASS + SAFETY PREFLIGHT
============================================================
BATCH139_V2_RECONCILIATION=PASS
LOCAL_HEAD=1f1b2407a4aaecf88f7fc724d3964921fe30585b
REMOTE_HEAD=1f1b2407a4aaecf88f7fc724d3964921fe30585b
SOURCE_HEAD=PASS_EXACT_BATCH139_V2_COMMIT
 M apps/cms/current/public/.htaccess
HTACCESS_DIFF_SHA=8ce9fa55a71768bf711203fcc50be8eec6de26886bbf4852ceb30a97b36958fe
TRACKED_WORKTREE_POLICY=PASS_KNOWN_RUNTIME_HTACCESS_ONLY
CURRENT_UID=1109
SCHEDULER_LOG_UID=1109
SCHEDULER_LOG_MODE=644
SCHEDULER_LOG_FILE_SAFETY=PASS_REGULAR_NON_SYMLINK_OWNED_BY_RUNTIME_USER

============================================================
1. LOG INVENTORY + CRON AUDIT
============================================================
SCHEDULER_LOG_BYTES_BEFORE=24081100
SCHEDULER_LOG_MB_BEFORE=23.0
LOG_DIR_KB_BEFORE=23640
LARGEST_LOG_FILES_BEFORE_BEGIN
24081100	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/scheduler.log
11041	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-28.log
7495	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-09-08.log
5456	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-25.log
3228	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-27.log
3190	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-09-07.log
957	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-26.log
957	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-24.log
638	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-09-01.log
638	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-30.log
638	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-29.log
638	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-23.log
319	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-09-06.log
319	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-09-05.log
319	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-09-04.log
14	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/.gitignore
LARGEST_LOG_FILES_BEFORE_END
CRONTAB_AUDIT=YES
CRON_SCHEDULE_RUN_COUNT=1
CRON_SCHEDULE_WORK_COUNT=0
CRON_SCHEDULER_LOG_REFERENCE_COUNT=1
CRON_SCHEDULER_MATCH_SHA256=6ccbaab45f4800324dcde392e42373088321dc57b85a238f74d827ec2e8ebef1
CRON_CONTENT_DISCLOSURE=NO_RAW_CRONTAB_WRITTEN_TO_REPORT
ACTIVE_SCHEDULE_RUN_PROCESSES_INITIAL=0
ACTIVE_SCHEDULE_WORK_PROCESSES=0

============================================================
2. SAFE SCHEDULER.LOG ROTATION
============================================================
SCHEDULE_RUN_DRAIN_WAIT_SECONDS=0
ACTIVE_SCHEDULE_RUN_PROCESSES_BEFORE_ROTATION=0
SCHEDULER_LOG_SHA256_BEFORE=2b2d1cc95e6ceb637630926352a906c83fbdad0b4336f30d257fd4fedada267f
ROTATED_RAW_HASH=PASS
ROTATED_GZIP_INTEGRITY=PASS_SHA256_ROUNDTRIP
ROTATED_ARCHIVE=/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/archive/scheduler-20260908-225129.log.gz
ROTATED_SOURCE_BYTES=24081100
ROTATED_COMPRESSED_BYTES=1104028
ROTATION_COMPRESSION_SAVED_BYTES=22977072

============================================================
3. STRICT ARCHIVE RETENTION + POSTCHECK
============================================================
STRICT_SCHEDULER_ARCHIVE_COUNT_BEFORE_RETENTION=1
RETENTION_DELETED_ARCHIVE_COUNT=0
RETENTION_DELETED_ARCHIVE_BYTES=0
STRICT_SCHEDULER_ARCHIVE_COUNT_AFTER_RETENTION=1
SCHEDULER_LOG_BYTES_AFTER=0
LOG_DIR_KB_AFTER=1164
LARGEST_LOG_FILES_AFTER_BEGIN
1104028	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/archive/scheduler-20260908-225129.log.gz
11041	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-28.log
7495	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-09-08.log
5456	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-25.log
3228	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-27.log
3190	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-09-07.log
957	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-26.log
957	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-24.log
638	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-09-01.log
638	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-30.log
638	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-29.log
638	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-23.log
319	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-09-06.log
319	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-09-05.log
319	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-09-04.log
14	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/.gitignore
0	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/scheduler.log
LARGEST_LOG_FILES_AFTER_END
SOURCE_IMMUTABILITY=PASS_NO_GIT_MUTATION
CRON_IMMUTABILITY=PASS_NO_CRONTAB_WRITE_PERFORMED
BUILD_CREATED=NO
OTA_PUBLISHED=NO
GOOGLE_PLAY_ACTION=NO
DATABASE_WRITES=NO

============================================================
4. FINAL RESULT
============================================================
BATCH140_RESULT=PASS_SCHEDULER_LOG_ROTATED_COMPRESSED_RETENTION_ENFORCED
SCHEDULER_LOG_ROTATED=1
SCHEDULER_ARCHIVE_RETENTION=3
NEXT_ACTION=REVIEW_REMAINING_RUNTIME_LOGS_AND_DECIDE_IF_PERSISTENT_ROTATION_AUTOMATION_IS_NEEDED
REPORT_PATH=/home/icaffeco/ald1n-project/docs/operations/366-MOBILE-LARAVEL-SCHEDULER-LOG-HYGIENE-BATCH140-V1-20260908-225129.md
