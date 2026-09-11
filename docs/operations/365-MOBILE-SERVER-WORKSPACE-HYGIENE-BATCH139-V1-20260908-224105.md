# Report365 - Mobile Server Workspace Hygiene Batch139 V1

- Timestamp: 20260908-224105
- Purpose: exclude completed root build workspaces from future EAS archives and remove only strict Build16/Build17 operational backup/tmp directories from the server
- Expected source authority: 6a827503735bd274e19cef65cb1a5accadcbd125
- Build creation: FORBIDDEN
- OTA publish: FORBIDDEN
- Google Play submit: FORBIDDEN
- Database writes: NO
- CMS product media deletion: FORBIDDEN
- Mobile node_modules deletion: FORBIDDEN
- CMS vendor deletion: FORBIDDEN

============================================================
0. SOURCE + SAFETY PREFLIGHT
============================================================
LOCAL_HEAD=6a827503735bd274e19cef65cb1a5accadcbd125
REMOTE_HEAD=6a827503735bd274e19cef65cb1a5accadcbd125
SOURCE_HEAD=PASS_EXACT_BATCH138_V3_COMMIT
 M apps/cms/current/public/.htaccess
HTACCESS_DIFF_SHA=8ce9fa55a71768bf711203fcc50be8eec6de26886bbf4852ceb30a97b36958fe
TRACKED_WORKTREE_POLICY=PASS_KNOWN_RUNTIME_HTACCESS_ONLY
PROTECTED_PATHS_PRECHECK=PASS

============================================================
1. STRICT ROOT OPERATIONAL WORKSPACE INVENTORY
============================================================
CANDIDATE_COUNT=44
CANDIDATE_TOTAL_KB=195304
CANDIDATE_TOTAL_MB=190.7
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
28696	/home/icaffeco/ald1n-project/docs
5148	/home/icaffeco/ald1n-project/incoming
6032	/home/icaffeco/ald1n-project/backups
504	/home/icaffeco/ald1n-project/releases
PROTECTED_DISK_BEFORE_END
Filesystem     1024-blocks       Used Available Capacity Mounted on
/dev/sda3       8160465344 7252210636 496914832      94% /home/icaffeco

============================================================
2. EXTEND ROOT .EASIGNORE FOR OPERATIONAL WORKSPACES
============================================================
ROOT_EASIGNORE_OPERATIONAL_RULES=PASS
ROOT_EASIGNORE_DIFF_BEGIN
diff --git a/.easignore b/.easignore
index b505712..4152031 100644
--- a/.easignore
+++ b/.easignore
@@ -223,3 +223,10 @@ apps/cms/current/error_log.*
 /.locks
 /.mobile-*.lock
 /PROJECT-STATUS.md
+
+# ALD1N_EAS_ROOT_OPERATIONAL_WORKSPACE_HYGIENE_V1
+# Completed build preflight/rollback workspaces are server-only operational artifacts.
+/.build16-batch*-backup-*
+/.build16-batch*-tmp-*
+/.build17-batch*-backup-*
+/.build17-batch*-tmp-*
ROOT_EASIGNORE_DIFF_END

============================================================
3. ZERO-BUILD ARCHIVE VERIFICATION
============================================================
NODE_VERSION=v22.23.2
NPM_VERSION=10.9.8
EAS_CLI_SOURCE=REUSED_BUILD17_TEMP_BEFORE_CLEANUP
eas-cli/23.2.0 linux-x64 node-v22.23.2
⠋ Copying project directory to /home/icaffeco/ald1n-project/tmp/server-workspace-hygiene-batch139-20260908-224105/archive⠙ Copying project directory to /home/icaffeco/ald1n-project/tmp/server-workspace-hygiene-batch139-20260908-224105/archive⠹ Copying project directory to /home/icaffeco/ald1n-project/tmp/server-workspace-hygiene-batch139-20260908-224105/archive✔ Project directory saved to /home/icaffeco/ald1n-project/tmp/server-workspace-hygiene-batch139-20260908-224105/archive
EAS_BUILD_INSPECT_ARCHIVE=PASS_NO_CLOUD_BUILD_CREATED
ARCHIVE_REQUIRED_INPUTS=PASS
/home/icaffeco/ald1n-project/tmp/server-workspace-hygiene-batch139-20260908-224105/archive/.build17-batch137-v3-quarantine-20260908-210245
FAIL: operational build workspaces still present in inspected archive
BATCH139_RESULT=FAIL_RC_1_LINE_267
ROLLBACK_ROOT_EASIGNORE=PASS
REPORT_PATH=/home/icaffeco/ald1n-project/docs/operations/365-MOBILE-SERVER-WORKSPACE-HYGIENE-BATCH139-V1-20260908-224105.md
