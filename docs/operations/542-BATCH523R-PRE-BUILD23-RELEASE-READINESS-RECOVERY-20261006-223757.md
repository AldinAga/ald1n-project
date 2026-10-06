============================================================
542 - BATCH523R PRE-BUILD23 RELEASE READINESS RECOVERY
============================================================
TIMESTAMP=20261006-223757
ROOT=/home/icaffeco/ald1n-project
PURPOSE=STATE_AWARE_RECOVERY_OF_BATCH523_RESTORE_RUNTIME_CHECKER_NO_BUILD_NO_SUBMIT
EXPECTED_DOCS_HEAD=78855461ff3234bcd73f63e9999c79b76a7973b5
EXPECTED_SOURCE_AUTHORITY=33316a89165c49f78215dd33e7ab03513f13f8a3
RECOVERY_OF_REPORT=541-BATCH523-FRESH-PRE-BUILD23-RELEASE-READINESS-20261006-222816.md
RECOVERY_OF_REPORT_SHA256=564286c0dadb016cc2d98ed26114ef0bc2db2cbe3b4dc7f96d1d205bc49e6dbc
APP_VERSION=1.0.0
RUNTIME_VERSION=1.0.0-build17
EXPECTED_REMOTE_VERSION_CODE=22
PLANNED_BUILD23_VERSION_CODE=23
BUILD_PROFILE=production
CHANNEL=production
PLAY_TRACK=production
EAS_BUILD_STARTED=NO
EAS_SUBMIT_STARTED=NO
OTA_PUBLISHED=NO
GOOGLE_PLAY_ACTION=NO

============================================================
PHASE authority-reconstruction-and-report541-binding
============================================================
BRANCH=main
+ git -C /home/icaffeco/ald1n-project fetch origin main 
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
LOCAL_HEAD=78855461ff3234bcd73f63e9999c79b76a7973b5
REMOTE_HEAD=78855461ff3234bcd73f63e9999c79b76a7973b5
REPORT541_ACTUAL_SHA256=564286c0dadb016cc2d98ed26114ef0bc2db2cbe3b4dc7f96d1d205bc49e6dbc
REPORT541_BINDING=PASS_EXACT_FAILED_CHECKER_ONLY_AFTER_ALL_PRIOR_READINESS_GATES
SOURCE_AUTHORITY=PASS_UNCHANGED_FROM_BATCH522

============================================================
PHASE worktree-preflight
============================================================
HTACCESS_FILE_SHA256=d10ecf0d63f9a8e1bd978a440afcbadb34faf1d24eb899bf2582d1e5c0033cef
HTACCESS_DIFF_SHA256=8ce9fa55a71768bf711203fcc50be8eec6de26886bbf4852ceb30a97b36958fe
TRACKED_DRIFT=PASS_ONLY_APPROVED_HTACCESS
MOBILE_TREE_CURRENT=7d4056c540f6c0b9ced1b9a6a4a406fe475c0024
OPENAPI_SHA_apps_cms_current_docs_openapi.yaml=5b3d69c3ec2011cda4a45e5d6ede4c5149d03de7bdfb91968523515c6ec1dc25
OPENAPI_SHA_apps_mobile_current_docs_openapi.yaml=5b3d69c3ec2011cda4a45e5d6ede4c5149d03de7bdfb91968523515c6ec1dc25
OPENAPI_SHA_packages_api-contract_openapi.yaml=5b3d69c3ec2011cda4a45e5d6ede4c5149d03de7bdfb91968523515c6ec1dc25
INHERITED_GATES=PASS_REPORT541_STABLE_MOBILE_EXPO_OPENAPI_BOUND_TO_UNCHANGED_SOURCE

============================================================
PHASE deterministic-release-manifest-reapply
============================================================
+ /home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node --check /home/icaffeco/.ald1n-batch523r-20261006-223757/build-manifest.mjs 
+ /home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /home/icaffeco/.ald1n-batch523r-20261006-223757/build-manifest.mjs /home/icaffeco/ald1n-project 33316a89165c49f78215dd33e7ab03513f13f8a3 /home/icaffeco/.ald1n-batch523r-20261006-223757/MANIFEST-SHA256.new 
MANIFEST_ENTRY_COUNT=937
MANIFEST_ENTRY_COUNT=937
MANIFEST_SHA256=94b9855b48108c2277845d0551b1ddcffad928f5665092d66a9a1a00b6020713
FAIL_STAGE=release-manifest
FAIL_REASON=Regenerated manifest SHA differs from Report541
BATCH_RESULT=FAIL
FAILED_STAGE=release-manifest
FAIL_REASON=Regenerated manifest SHA differs from Report541
SOURCE_MUTATION=NO
COMMIT_CREATED=NO
PUSH_COMPLETED=NO
SOURCE_COMMIT=NONE
EAS_BUILD_STARTED=NO
EAS_SUBMIT_STARTED=NO
OTA_PUBLISHED=NO
GOOGLE_PLAY_ACTION=NO
BUILD23_DEFERRED=YES
