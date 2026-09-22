
============================================================
0. BATCH152 PASS AUTHORITY + EXACT SOURCE LOCK
============================================================
BATCH152_REPORT_SHA256=d622b5863bc26dc52e5f8da1a3d8ea393932eb7c1b43d6d732a328ce0265981f
BATCH152_AUTHORITY=PASS_EXACT_REPORT418
EXPECTED_CANDIDATE_GROUP=4397d7ea-1367-4ffb-8786-c07cbb740070
EXPECTED_PREVIOUS_PRODUCTION_GROUP=77d1fcac-f08e-46fa-be90-376c4443e9ff
BRANCH=main
LOCAL_HEAD=4154e7f115386f10a7676879f83333b0d51d8b6f
REMOTE_HEAD=4154e7f115386f10a7676879f83333b0d51d8b6f
HTACCESS_FILE_SHA256=d10ecf0d63f9a8e1bd978a440afcbadb34faf1d24eb899bf2582d1e5c0033cef
HTACCESS_DIFF_SHA256=8ce9fa55a71768bf711203fcc50be8eec6de26886bbf4852ceb30a97b36958fe
HTACCESS_DRIFT=PASS_KNOWN_RUNTIME_EXCEPTION
SOURCE_AUTHORITY=PASS_EXACT_4154E7F_WITH_KNOWN_HTACCESS_ONLY

============================================================
1. BUILD17 BINARY AUTHORITY
============================================================
BUILD17_ID=7f3b4381-a7f9-4d01-9312-2b7754b8cb01
BUILD17_AAB=/home/icaffeco/backups/releases/Ald1n-CMS-v1.0.0-production-vc17-7f3b4381-a7f9-4d01-9312-2b7754b8cb01.aab
BUILD17_AAB_SHA256=13d25760807fd20b0faa839d89c589f331845e5b1c12c2926d6ac906845b1b60
BUILD17_AAB_SIZE_BYTES=83304530
BUILD17_BINARY_AUTHORITY=PASS_EXACT_SHA256
BUILD18_REQUIRED=NO_BUILD17_COMPATIBLE_JS_TS_ONLY

============================================================
2. READ-ONLY EAS PRODUCTION OTA AUTHORITY
============================================================
EAS_CLI_SPEC=eas-cli@23.2.0
EAS_CLI_VERSION=npm warn deprecated inflight@1.0.6: This module is not supported, and leaks memory. Do not use it. Check out lru-cache if you want a good and tested way to coalesce async requests by a key value, which is much more comprehensive and powerful.
EAS_AUTHENTICATED_ACCOUNT=(Use `node --trace-warnings ...` to show where the warning was created)
UPDATE_GROUP=cdd2aebd-6eb9-4ef9-9a05-713d0a3acc04
UPDATE_BRANCH=production
UPDATE_RUNTIME=1.0.0-build17
UPDATE_PLATFORM=android
UPDATE_GIT_COMMIT=4154e7f115386f10a7676879f83333b0d51d8b6f
UPDATE_ROLLBACK=NO
PRODUCTION_BRANCH_STDERR_BEGIN
★ eas-cli@24.1.2 is now available.
To upgrade, run:
npm install -g eas-cli
Proceeding with outdated version.

    Error: branch:view command failed.
PRODUCTION_BRANCH_STDERR_END

============================================================
PRECONDITION FAILURE
============================================================
BATCH153_RESULT=FAIL_PRECONDITION_PRODUCTION_BRANCH_VIEW_FAILED
REPORT419_RESULT=FAIL
SOURCE_MUTATION=NO
BUILD_CREATED=NO
OTA_PUBLISHED=NO
GOOGLE_PLAY_ACTION=NO
PRODUCTION_MIGRATION=NONE
PRODUCTION_BUSINESS_WRITES_BY_BATCH=NONE
NEXT_ACTION=AUTOMATIC_BATCH153_RECOVERY_FROM_THIS_REPORT
REPORT_PATH=/home/icaffeco/ald1n-project/docs/operations/419-MOBILE-CONSOLIDATED-PHYSICAL-DEVICE-ACCEPTANCE-BATCH153-20260911-134823.md
