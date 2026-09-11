============================================================
269 - MOBILE v1.0.0 + CMS v2.2.0 STABLE BACKUP RECOVERY AUDIT - BATCH56R
============================================================
DATE=Fri Aug 28 08:50:56 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
PURPOSE=READ_ONLY_DIAGNOSE_INCOMPLETE_BATCH56_BEFORE_ANY_RETRY_OR_CLEANUP
SOURCE_MUTATION=NO
DATABASE_WRITES=0
CLEANUP_PERFORMED=NO
BUILD14_CREATED=NO

============================================================
0. GIT AUTHORITY
============================================================
BRANCH=main
HEAD=8d51c71b0fdc470f204d4644234b38769971ab3b
EXPECTED_HEAD=8d51c71b0fdc470f204d4644234b38769971ab3b
HEAD_MATCH=PASS
GIT_INDEX=CLEAN
TRACKED_WORKTREE=PASS_ONLY_PRESERVED_HTACCESS
HTACCESS_DIFF_SHA256=8ce9fa55a71768bf711203fcc50be8eec6de26886bbf4852ceb30a97b36958fe
HTACCESS_AUTHORITY_MATCH=PASS

============================================================
1. DISK + ACTIVE PROCESS STATE
============================================================
RELEASES_FREE_KB=787241484
RELEASES_USED_PERCENT=90%
ACTIVE_BACKUP_PROCESS_COUNT=0
ACTIVE_BACKUP_PROCESSES=NONE

============================================================
2. LARAVEL BACKUP AUTHORITY + RECENT RUNS
============================================================
BACKUP_PATH=/home/icaffeco/backups/current
BACKUP_DAILY_RETENTION=7
BACKUP_WEEKLY_RETENTION=4
BACKUP_RUNS_TABLE=PRESENT
BACKUP_RUN id=86 | key=20260828-083828-manual-9327a6 | type=manual | status=completed | path=/home/icaffeco/backups/current/20260828-083828-manual-9327a6 | size_bytes=482743525 | started_at=2026-08-28 08:38:28 | finished_at=2026-08-28 08:38:39
BACKUP_RUN id=85 | key=20260828-023006-daily-001b03 | type=daily | status=completed | path=/home/icaffeco/backups/current/20260828-023006-daily-001b03 | size_bytes=482742823 | started_at=2026-08-28 02:30:06 | finished_at=2026-08-28 02:30:17
BACKUP_RUN id=84 | key=20260827-023006-daily-4e8006 | type=daily | status=completed | path=/home/icaffeco/backups/current/20260827-023006-daily-4e8006 | size_bytes=436291229 | started_at=2026-08-27 02:30:06 | finished_at=2026-08-27 02:30:16
BACKUP_RUN id=83 | key=20260826-023006-daily-c8bef1 | type=daily | status=completed | path=/home/icaffeco/backups/current/20260826-023006-daily-c8bef1 | size_bytes=428499790 | started_at=2026-08-26 02:30:06 | finished_at=2026-08-26 02:30:15
BACKUP_RUN id=82 | key=20260825-023005-daily-830493 | type=daily | status=completed | path=/home/icaffeco/backups/current/20260825-023005-daily-830493 | size_bytes=428498285 | started_at=2026-08-25 02:30:05 | finished_at=2026-08-25 02:30:14
BACKUP_RUN id=81 | key=20260824-154009-manual-240b58 | type=manual | status=completed | path=/home/icaffeco/backups/current/20260824-154009-manual-240b58 | size_bytes=399302544 | started_at=2026-08-24 15:40:09 | finished_at=2026-08-24 15:40:14
BACKUP_RUN id=80 | key=20260824-152723-manual-210e61 | type=manual | status=completed | path=/home/icaffeco/backups/current/20260824-152723-manual-210e61 | size_bytes=399302373 | started_at=2026-08-24 15:27:23 | finished_at=2026-08-24 15:27:27
BACKUP_RUN id=79 | key=20260824-150802-manual-8883c8 | type=manual | status=completed | path=/home/icaffeco/backups/current/20260824-150802-manual-8883c8 | size_bytes=399302284 | started_at=2026-08-24 15:08:02 | finished_at=2026-08-24 15:08:07

============================================================
3. PARTIAL STABLE PACKAGES
============================================================
PARTIAL_STABLE_COUNT=0
PARTIAL_STABLE=NONE

============================================================
4. FINALIZED STABLE PACKAGES
============================================================
FINAL_STABLE_COUNT=1
LATEST_STABLE_MARKER=/home/icaffeco/backups/releases/ALD1N-STABLE-v1.0.0-cms-v2.2.0-20260828-083827
FINAL_STABLE_DIR=/home/icaffeco/backups/releases/ALD1N-STABLE-v1.0.0-cms-v2.2.0-20260828-083827
FINAL_STABLE_SIZE_KB=89240
FINAL_STABLE_SHA256_VERIFY=PASS
FINAL_STABLE_MANIFEST=PRESENT

============================================================
5. CLASSIFICATION
============================================================
RECOVERY_CLASS=FINAL_STABLE_PACKAGE_EXISTS_NEEDS_CERTIFICATION_RECONCILIATION
NEXT_ACTION=VERIFY_EXISTING_STABLE_AND_COMPLETE_REPORT_WITHOUT_NEW_BACKUP_IF_POSSIBLE
BATCH56R_RESULT=PASS_READ_ONLY_AUDIT_COMPLETE
