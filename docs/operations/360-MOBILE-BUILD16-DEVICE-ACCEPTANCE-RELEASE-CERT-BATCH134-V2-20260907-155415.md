# Report360 V2 - Build16 Device Acceptance + Release Certification Batch134 V2

- Timestamp: 20260907-155415
- Purpose: repair the Batch134 V1 AAB manifest false-negative caused by grep -q under pipefail, then verify the exact Report359 Build16 production artifact on a physical Android device and close release-candidate acceptance without creating another build, OTA update, source mutation, submit, rollout, or database-writing test workflow
- Source authority: 7a35dd1d07fbb3d3da65d48b8237a85bccf04bbd
- EAS Build16 ID: d6bc1409-92b4-4d18-a252-a1ed9c5b6463
- Android versionCode: 16
- App version: 1.0.0
- Android package: com.ald1n.mobile
- AAB SHA-256 authority: 030d12689e2e76469e7fc7cdb9adc25fc39c4fd96a71894d3c6862e7eff4a441
- Previous authority: Report359 / Batch133 PASS
- Build creation: FORBIDDEN
- EAS submit: FORBIDDEN
- OTA publish: FORBIDDEN
- Google Play rollout automation: NONE
- Source mutation: NONE
- Database-writing acceptance actions: NOT REQUIRED

============================================================
0. SOURCE AND REPORT359 AUTHORITY
============================================================
BRANCH=main
LOCAL_HEAD=7a35dd1d07fbb3d3da65d48b8237a85bccf04bbd
REMOTE_HEAD=7a35dd1d07fbb3d3da65d48b8237a85bccf04bbd
HTACCESS_DIFF_SHA=8ce9fa55a71768bf711203fcc50be8eec6de26886bbf4852ceb30a97b36958fe
HTACCESS_DRIFT=PASS_KNOWN_RUNTIME_EXCEPTION
REPORT359_AUTHORITY=PASS_FINAL_BUILD16_NATIVE_AAB_READY_FOR_DEVICE_ACCEPTANCE

============================================================
1. EXACT BUILD16 AAB BYTE AUTHORITY
============================================================
AAB_PATH=/home/icaffeco/backups/releases/Ald1n-CMS-v1.0.0-production-vc16-d6bc1409-92b4-4d18-a252-a1ed9c5b6463.aab
AAB_SHA256=030d12689e2e76469e7fc7cdb9adc25fc39c4fd96a71894d3c6862e7eff4a441
AAB_SIZE_BYTES=83307162
AAB_LISTING_PIPEFAIL_REPAIR=PASS_MATERIALIZED_LISTING_NO_GREP_Q_PIPELINE
BUILD16_AAB_AUTHORITY=PASS_SHA256_SIZE_ZIP_MANIFEST_DEX
AAB_JARSIGNER_VERIFY=SKIP_TOOL_NOT_AVAILABLE

============================================================
2. OPERATOR DEVICE METADATA
============================================================
DEVICE_MODEL=Galaxy S26 Ultra
ANDROID_OS=16
PLAY_TRACK=Internal

============================================================
3. REQUIRED PHYSICAL DEVICE ACCEPTANCE
============================================================
DEVICE_BUILD16_INSTALL=PASS
DEVICE_COLD_LAUNCH=PASS
DEVICE_PASSWORD_AUTH=PASS
DEVICE_HOME_NAVIGATION=PASS
DEVICE_LIGHT_DARK=PASS
DEVICE_CATALOG_PRODUCT=PASS
DEVICE_CART_CHECKOUT_READONLY=PASS
DEVICE_ORDERS_DOCUMENTS=PASS
DEVICE_NOTIFICATIONS_ACCOUNT_ADMIN=PASS
DEVICE_VISUAL_STABILITY=PASS

============================================================
4. OPTIONAL NATIVE INTEGRATION SPOT CHECKS
============================================================
DEVICE_GOOGLE_SIGNIN=PASS
DEVICE_PUSH_NOTIFICATION=PASS
DEVICE_PRIVATE_FILE_SHARE=PASS

============================================================
5. OPERATOR RELEASE DECISION
============================================================
REQUIRED_DEVICE_FAILURE_COUNT=0
OPTIONAL_DEVICE_SKIP_COUNT=0
OPERATOR_RELEASE_CONFIRMATION=NO
BATCH134_V2_RESULT=FAIL_OPERATOR_RELEASE_CONFIRMATION_NOT_GIVEN
BATCH134_RESULT=FAIL_OPERATOR_RELEASE_CONFIRMATION_NOT_GIVEN
REPORT360_RESULT=FAIL
REPORT360_V2_RESULT=FAIL
BUILD16_RELEASE_STATUS=DEVICE_CORE_PASS_OPERATOR_CONFIRMATION_PENDING
