# Report365 V2 - Mobile Server Workspace Hygiene Batch139 Quarantine Recovery

- Timestamp: 20260908-224335
- Purpose: reconcile Batch139 V1 quarantine miss, exclude strict Build16/Build17 backup/tmp/quarantine root workspaces from future EAS archives, then remove only those completed operational workspaces
- Expected source authority: 6a827503735bd274e19cef65cb1a5accadcbd125
- Build creation: FORBIDDEN
- OTA publish: FORBIDDEN
- Google Play submit: FORBIDDEN
- Database writes: NO
- CMS product media deletion: FORBIDDEN
- Mobile node_modules deletion: FORBIDDEN
- CMS vendor deletion: FORBIDDEN

============================================================
0. SOURCE + V1 FAILURE RECONCILIATION
============================================================
V1_FAILURE_RECONCILIATION=PASS_ARCHIVE_ONLY_FAILURE_EASIGNORE_ROLLED_BACK_NO_SERVER_DELETION_REACHED
V1_FAILURE_REPORT=/home/icaffeco/ald1n-project/docs/operations/365-MOBILE-SERVER-WORKSPACE-HYGIENE-BATCH139-V1-20260908-224105.md
LOCAL_HEAD=6a827503735bd274e19cef65cb1a5accadcbd125
REMOTE_HEAD=6a827503735bd274e19cef65cb1a5accadcbd125
SOURCE_HEAD=PASS_EXACT_BATCH138_V3_COMMIT
 M apps/cms/current/public/.htaccess
HTACCESS_DIFF_SHA=8ce9fa55a71768bf711203fcc50be8eec6de26886bbf4852ceb30a97b36958fe
TRACKED_WORKTREE_POLICY=PASS_KNOWN_RUNTIME_HTACCESS_ONLY
PROTECTED_PATHS_PRECHECK=PASS

============================================================
1. COMPLETE ROOT BUILD WORKSPACE CLASSIFICATION
============================================================
ALL_BUILD_WORKSPACE_COUNT=45
STRICT_DELETE_CANDIDATE_COUNT=45
UNKNOWN_BUILD_WORKSPACE_COUNT=0
BUILD_WORKSPACE_CLASSIFICATION=PASS_ALL_MATCH_BACKUP_TMP_OR_QUARANTINE
CANDIDATE_TOTAL_KB=195348
CANDIDATE_TOTAL_MB=190.8
DELETE_CANDIDATES_BEGIN
176920	/home/icaffeco/ald1n-project/.build17-batch137-v5-tmp-20260908-211834
1284	/home/icaffeco/ald1n-project/.build16-batch136-v2-backup-20260908-110558
1284	/home/icaffeco/ald1n-project/.build16-batch136-backup-20260908-110200
776	/home/icaffeco/ald1n-project/.build16-batch123-v6-backup-20260907-092919
776	/home/icaffeco/ald1n-project/.build16-batch123-v4-backup-20260907-091704
776	/home/icaffeco/ald1n-project/.build16-batch123-v3-backup-20260907-084320
776	/home/icaffeco/ald1n-project/.build16-batch123-v2-backup-20260907-083012
656	/home/icaffeco/ald1n-project/.build17-batch137-v4-tmp-20260908-211217
644	/home/icaffeco/ald1n-project/.build17-batch137-v4-backup-20260908-211217
640	/home/icaffeco/ald1n-project/.build17-batch137-v3-backup-20260908-210245
496	/home/icaffeco/ald1n-project/.build16-batch131-backup-20260907-144511
492	/home/icaffeco/ald1n-project/.build16-batch129-backup-20260907-142050
492	/home/icaffeco/ald1n-project/.build16-batch126-v5-backup-20260907-124438
492	/home/icaffeco/ald1n-project/.build16-batch126-v4-backup-20260907-123749
480	/home/icaffeco/ald1n-project/.build16-batch132-backup-20260907-145653
480	/home/icaffeco/ald1n-project/.build16-batch128-backup-20260907-141015
472	/home/icaffeco/ald1n-project/.build16-batch130-backup-20260907-143353
452	/home/icaffeco/ald1n-project/.build16-batch125-v3-backup-20260907-113801
452	/home/icaffeco/ald1n-project/.build16-batch125-v2-backup-20260907-113233
428	/home/icaffeco/ald1n-project/.build16-batch134-v6-backup-20260908-124724
428	/home/icaffeco/ald1n-project/.build16-batch134-v4-backup-20260908-123328
428	/home/icaffeco/ald1n-project/.build16-batch134-v3-backup-20260908-121818
428	/home/icaffeco/ald1n-project/.build16-batch134-v2-backup-20260908-115957
428	/home/icaffeco/ald1n-project/.build16-batch124-backup-20260907-111307
428	/home/icaffeco/ald1n-project/.build16-batch124-backup-20260907-110746
428	/home/icaffeco/ald1n-project/.build16-batch124-backup-20260907-100747
404	/home/icaffeco/ald1n-project/.build16-batch127-v3-backup-20260907-134035
404	/home/icaffeco/ald1n-project/.build16-batch127-v2-backup-20260907-132843
404	/home/icaffeco/ald1n-project/.build16-batch127-backup-20260907-125611
284	/home/icaffeco/ald1n-project/.build17-batch137-v3-tmp-20260908-210245
264	/home/icaffeco/ald1n-project/.build17-batch137-backup-20260908-204946
264	/home/icaffeco/ald1n-project/.build16-batch133-backup-20260907-151247
256	/home/icaffeco/ald1n-project/.build16-batch126-v3-backup-20260907-122835
256	/home/icaffeco/ald1n-project/.build16-batch126-v2-backup-20260907-121327
256	/home/icaffeco/ald1n-project/.build16-batch126-backup-20260907-120723
252	/home/icaffeco/ald1n-project/.build16-batch134-backup-20260908-104443
220	/home/icaffeco/ald1n-project/.build17-batch137-tmp-20260908-204946
176	/home/icaffeco/ald1n-project/.build16-batch123-backup-20260907-082312
44	/home/icaffeco/ald1n-project/.build17-batch137-v3-quarantine-20260908-210245
8	/home/icaffeco/ald1n-project/.build17-batch137-v2-tmp-20260908-205920
4	/home/icaffeco/ald1n-project/.build17-batch137-v2-backup-20260908-205920
4	/home/icaffeco/ald1n-project/.build16-batch134-v5-backup-20260908-124214
4	/home/icaffeco/ald1n-project/.build16-batch134-backup-20260908-115023
4	/home/icaffeco/ald1n-project/.build16-batch125-backup-20260907-112433
4	/home/icaffeco/ald1n-project/.build16-batch123-v5-backup-20260907-092359
DELETE_CANDIDATES_END
PROTECTED_DISK_BEFORE_BEGIN
632260	/home/icaffeco/ald1n-project/apps/cms/current/storage/app/public
716292	/home/icaffeco/ald1n-project/apps/mobile/current/node_modules
46424	/home/icaffeco/ald1n-project/apps/cms/current/vendor
28704	/home/icaffeco/ald1n-project/docs
5168	/home/icaffeco/ald1n-project/incoming
6032	/home/icaffeco/ald1n-project/backups
504	/home/icaffeco/ald1n-project/releases
PROTECTED_DISK_BEFORE_END
Filesystem     1024-blocks       Used Available Capacity Mounted on
/dev/sda3       8160465344 7252697692 496427776      94% /home/icaffeco

============================================================
2. EXTEND ROOT .EASIGNORE FOR ALL STRICT OPERATIONAL WORKSPACES
============================================================
ROOT_EASIGNORE_OPERATIONAL_RULES=PASS_BACKUP_TMP_QUARANTINE
ROOT_EASIGNORE_DIFF_BEGIN
diff --git a/.easignore b/.easignore
index b505712..591b905 100644
--- a/.easignore
+++ b/.easignore
@@ -223,3 +223,12 @@ apps/cms/current/error_log.*
 /.locks
 /.mobile-*.lock
 /PROJECT-STATUS.md
+
+# ALD1N_EAS_ROOT_OPERATIONAL_WORKSPACE_HYGIENE_V1
+# Completed Build16/Build17 preflight, rollback and quarantine workspaces are server-only operational artifacts.
+/.build16-batch*-backup-*
+/.build16-batch*-tmp-*
+/.build16-batch*-quarantine-*
+/.build17-batch*-backup-*
+/.build17-batch*-tmp-*
+/.build17-batch*-quarantine-*
ROOT_EASIGNORE_DIFF_END

============================================================
3. ZERO-BUILD ARCHIVE VERIFICATION
============================================================
NODE_VERSION=v22.23.2
NPM_VERSION=10.9.8
EAS_CLI_SOURCE=REUSED_BUILD17_TEMP_BEFORE_CLEANUP
eas-cli/23.2.0 linux-x64 node-v22.23.2
⠋ Copying project directory to /home/icaffeco/ald1n-project/tmp/server-workspace-hygiene-batch139-v2-20260908-224335/archive⠙ Copying project directory to /home/icaffeco/ald1n-project/tmp/server-workspace-hygiene-batch139-v2-20260908-224335/archive⠹ Copying project directory to /home/icaffeco/ald1n-project/tmp/server-workspace-hygiene-batch139-v2-20260908-224335/archive✔ Project directory saved to /home/icaffeco/ald1n-project/tmp/server-workspace-hygiene-batch139-v2-20260908-224335/archive
EAS_BUILD_INSPECT_ARCHIVE=PASS_NO_CLOUD_BUILD_CREATED
ARCHIVE_REQUIRED_INPUTS=PASS
ARCHIVE_ROOT_OPERATIONAL_WORKSPACES=PASS_ALL_EXCLUDED
ARCHIVE_PRIOR_HYGIENE_CONTRACT=PASS
INSPECT_OUTPUT_TOTAL_KB=18808
INSPECT_OUTPUT_TOTAL_MB=18.4

============================================================
4. COMMIT .EASIGNORE ONLY
============================================================
FINAL_STAGE_SET=PASS_EASIGNORE_ONLY
[main 1f1b240] chore(eas): ignore root build workspaces
 1 file changed, 9 insertions(+)
SOURCE_COMMIT=1f1b2407a4aaecf88f7fc724d3964921fe30585b
To github.com:AldinAga/ald1n-project.git
   6a82750..1f1b240  main -> main
GIT_PUSH=PASS_REMOTE_MAIN_1f1b2407a4aaecf88f7fc724d3964921fe30585b

============================================================
5. SERVER OPERATIONAL WORKSPACE CLEANUP
============================================================
DELETED=/home/icaffeco/ald1n-project/.build16-batch123-backup-20260907-082312
DELETED=/home/icaffeco/ald1n-project/.build16-batch123-v2-backup-20260907-083012
DELETED=/home/icaffeco/ald1n-project/.build16-batch123-v3-backup-20260907-084320
DELETED=/home/icaffeco/ald1n-project/.build16-batch123-v4-backup-20260907-091704
DELETED=/home/icaffeco/ald1n-project/.build16-batch123-v5-backup-20260907-092359
DELETED=/home/icaffeco/ald1n-project/.build16-batch123-v6-backup-20260907-092919
DELETED=/home/icaffeco/ald1n-project/.build16-batch124-backup-20260907-100747
DELETED=/home/icaffeco/ald1n-project/.build16-batch124-backup-20260907-110746
DELETED=/home/icaffeco/ald1n-project/.build16-batch124-backup-20260907-111307
DELETED=/home/icaffeco/ald1n-project/.build16-batch125-backup-20260907-112433
DELETED=/home/icaffeco/ald1n-project/.build16-batch125-v2-backup-20260907-113233
DELETED=/home/icaffeco/ald1n-project/.build16-batch125-v3-backup-20260907-113801
DELETED=/home/icaffeco/ald1n-project/.build16-batch126-backup-20260907-120723
DELETED=/home/icaffeco/ald1n-project/.build16-batch126-v2-backup-20260907-121327
DELETED=/home/icaffeco/ald1n-project/.build16-batch126-v3-backup-20260907-122835
DELETED=/home/icaffeco/ald1n-project/.build16-batch126-v4-backup-20260907-123749
DELETED=/home/icaffeco/ald1n-project/.build16-batch126-v5-backup-20260907-124438
DELETED=/home/icaffeco/ald1n-project/.build16-batch127-backup-20260907-125611
DELETED=/home/icaffeco/ald1n-project/.build16-batch127-v2-backup-20260907-132843
DELETED=/home/icaffeco/ald1n-project/.build16-batch127-v3-backup-20260907-134035
DELETED=/home/icaffeco/ald1n-project/.build16-batch128-backup-20260907-141015
DELETED=/home/icaffeco/ald1n-project/.build16-batch129-backup-20260907-142050
DELETED=/home/icaffeco/ald1n-project/.build16-batch130-backup-20260907-143353
DELETED=/home/icaffeco/ald1n-project/.build16-batch131-backup-20260907-144511
DELETED=/home/icaffeco/ald1n-project/.build16-batch132-backup-20260907-145653
DELETED=/home/icaffeco/ald1n-project/.build16-batch133-backup-20260907-151247
DELETED=/home/icaffeco/ald1n-project/.build16-batch134-backup-20260908-104443
DELETED=/home/icaffeco/ald1n-project/.build16-batch134-backup-20260908-115023
DELETED=/home/icaffeco/ald1n-project/.build16-batch134-v2-backup-20260908-115957
DELETED=/home/icaffeco/ald1n-project/.build16-batch134-v3-backup-20260908-121818
DELETED=/home/icaffeco/ald1n-project/.build16-batch134-v4-backup-20260908-123328
DELETED=/home/icaffeco/ald1n-project/.build16-batch134-v5-backup-20260908-124214
DELETED=/home/icaffeco/ald1n-project/.build16-batch134-v6-backup-20260908-124724
DELETED=/home/icaffeco/ald1n-project/.build16-batch136-backup-20260908-110200
DELETED=/home/icaffeco/ald1n-project/.build16-batch136-v2-backup-20260908-110558
DELETED=/home/icaffeco/ald1n-project/.build17-batch137-backup-20260908-204946
DELETED=/home/icaffeco/ald1n-project/.build17-batch137-tmp-20260908-204946
DELETED=/home/icaffeco/ald1n-project/.build17-batch137-v2-backup-20260908-205920
DELETED=/home/icaffeco/ald1n-project/.build17-batch137-v2-tmp-20260908-205920
DELETED=/home/icaffeco/ald1n-project/.build17-batch137-v3-backup-20260908-210245
DELETED=/home/icaffeco/ald1n-project/.build17-batch137-v3-quarantine-20260908-210245
DELETED=/home/icaffeco/ald1n-project/.build17-batch137-v3-tmp-20260908-210245
DELETED=/home/icaffeco/ald1n-project/.build17-batch137-v4-backup-20260908-211217
DELETED=/home/icaffeco/ald1n-project/.build17-batch137-v4-tmp-20260908-211217
DELETED=/home/icaffeco/ald1n-project/.build17-batch137-v5-tmp-20260908-211834
DELETED_COUNT=45
FREED_ESTIMATE_KB=195348
FREED_ESTIMATE_MB=190.8
ROOT_BUILD16_BUILD17_OPERATIONAL_WORKSPACES=PASS_CLEAN
PROTECTED_PATHS_POSTCHECK=PASS_UNTOUCHED
PROTECTED_DISK_AFTER_BEGIN
632260	/home/icaffeco/ald1n-project/apps/cms/current/storage/app/public
716292	/home/icaffeco/ald1n-project/apps/mobile/current/node_modules
46424	/home/icaffeco/ald1n-project/apps/cms/current/vendor
28708	/home/icaffeco/ald1n-project/docs
5168	/home/icaffeco/ald1n-project/incoming
6032	/home/icaffeco/ald1n-project/backups
504	/home/icaffeco/ald1n-project/releases
PROTECTED_DISK_AFTER_END
Filesystem     1024-blocks       Used Available Capacity Mounted on
/dev/sda3       8160465344 7252574264 496551204      94% /home/icaffeco
SCHEDULER_LOG_BYTES=24076338
SCHEDULER_LOG_ACTION=NOT_TOUCHED_REVIEW_FOR_LOG_ROTATION_NEXT

============================================================
6. FINAL RESULT
============================================================
BATCH139_RESULT=PASS_SERVER_WORKSPACE_HYGIENE
BATCH139_V2_RESULT=PASS_QUARANTINE_RECOVERY_AND_SERVER_WORKSPACE_HYGIENE
BUILD_CREATED=NO
OTA_PUBLISHED=NO
GOOGLE_PLAY_ACTION=NO
DATABASE_WRITES=NO
BUILD17_DEVICE_ACCEPTED=USER_CONFIRMED
ARCHIVE_OPERATIONAL_WORKSPACES=EXCLUDED_BACKUP_TMP_QUARANTINE
SERVER_OPERATIONAL_WORKSPACES=REMOVED_STRICT_BUILD16_BUILD17_BACKUP_TMP_QUARANTINE_ONLY
PROTECTED_RUNTIME_DATA=UNTOUCHED
NEXT_ACTION=REVIEW_LOG_ROTATION_AND_OPTIONAL_NONDESTRUCTIVE_DISK_HYGIENE
REPORT_PATH=/home/icaffeco/ald1n-project/docs/operations/365-MOBILE-SERVER-WORKSPACE-HYGIENE-BATCH139-V2-20260908-224335.md
