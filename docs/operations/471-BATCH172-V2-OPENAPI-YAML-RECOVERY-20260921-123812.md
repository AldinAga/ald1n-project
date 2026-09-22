
============================================================
471 - BATCH172 V2 OPENAPI YAML RECOVERY
============================================================
TIMESTAMP=20260921-123812
EXPECTED_HEAD=39ef358f2a849060c16c38400898dfe169246768
PREDECESSOR_REPORT=470
TASK=BATCH172_V2_OPENAPI_YAML_RECOVERY
ROOT_CAUSE_PRIMARY=BATCH172_OPENAPI_NOWDOC_INSERTION_MISSING_TERMINATING_NEWLINE
ROOT_CAUSE_SECONDARY=PREEXISTING_OPENAPI_SECURITY_ROOT_SPLIT_BEFORE_134_PATHS
RECOVERY_SCOPE=ADVANCED_SMOKE_PLUS_OPENAPI_3_COPIES_ONLY
DATABASE_MUTATION=NO
MANAGEMENT_REPORT_SERVICE_MUTATION=NO
MOBILE_API_TYPE_MUTATION=NO
MOBILE_VISIBLE_ANALYTICS_UI=NO_DEFERRED_UNTIL_BATCH173
CUSTOMER360_PROFITABILITY_UI=NO_DEFERRED_UNTIL_BATCH173
EAS_COMMANDS_RUN=NO
OTA_ACTION=NO
BUILD_ACTION=NO
GOOGLE_PLAY_ACTION=NO

============================================================
RUN - git_fetch_preflight
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=git fetch origin main 
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
RC_git_fetch_preflight=0
BRANCH=main
LOCAL_HEAD=39ef358f2a849060c16c38400898dfe169246768
REMOTE_HEAD=39ef358f2a849060c16c38400898dfe169246768
REPORT470_SHA_ACTUAL=f14e7993a27205aa634caaf24b0b2a40bf6a45619f1d60601613a999fc18fc88
REPORT470_SHA_EXPECTED=f14e7993a27205aa634caaf24b0b2a40bf6a45619f1d60601613a999fc18fc88
REPORT470_INTERNAL_STATUS=PASS_MARKER_PRESENT_BUT_EXTERNAL_YAML_VALIDATION_OVERRIDES_CERTIFICATION
OPENAPI_PKG_SHA_BEFORE=781b432a00acc0400becc112726763de9087d31f93aa94bc65c9e2867af65b18
OPENAPI_CMS_SHA_BEFORE=781b432a00acc0400becc112726763de9087d31f93aa94bc65c9e2867af65b18
OPENAPI_MOBILE_SHA_BEFORE=781b432a00acc0400becc112726763de9087d31f93aa94bc65c9e2867af65b18
OPENAPI_JOINED_SCHEMA_DEFECT_COUNT=1
OPENAPI_ROUTES_BETWEEN_SECURITY_AND_COMPONENTS=134
MANAGEMENT_REPORT_SERVICE_SHA_BEFORE=c5e05bdf718cd5a4d1cfe529130354f3b90f617d80a0ffdbae02f43206928498
MOBILE_REPORTS_API_SHA_BEFORE=a35c6ce55514707c020fc060ede53ea2e32c8181e16e54ff9a2144dcd72eef5c
ADMIN_QUERY_KEYS_SHA_BEFORE=d02193a5771d768c36cc5d3a0b4e831455b0c806a79d76d4e76396d1384bf9c2
HTACCESS_SHA_ACTUAL=d10ecf0d63f9a8e1bd978a440afcbadb34faf1d24eb899bf2582d1e5c0033cef
HTACCESS_DIFF_SHA_ACTUAL=8ce9fa55a71768bf711203fcc50be8eec6de26886bbf4852ceb30a97b36958fe
PREFLIGHT_MACHINE_MANIFESTS=PASS_EXACT_REPORT470_STATE

============================================================
RUN - lint_backup-count
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=php -l /home/icaffeco/.ald1n-batch172-v2-openapi-20260921-123812/backup-count.php 
No syntax errors detected in /home/icaffeco/.ald1n-batch172-v2-openapi-20260921-123812/backup-count.php
RC_lint_backup-count=0

============================================================
RUN - lint_business-counts
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=php -l /home/icaffeco/.ald1n-batch172-v2-openapi-20260921-123812/business-counts.php 
No syntax errors detected in /home/icaffeco/.ald1n-batch172-v2-openapi-20260921-123812/business-counts.php
RC_lint_business-counts=0

============================================================
RUN - lint_patch-smoke
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=php -l /home/icaffeco/.ald1n-batch172-v2-openapi-20260921-123812/patch-smoke.php 
No syntax errors detected in /home/icaffeco/.ald1n-batch172-v2-openapi-20260921-123812/patch-smoke.php
RC_lint_patch-smoke=0

============================================================
RUN - lint_patch-openapi
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=php -l /home/icaffeco/.ald1n-batch172-v2-openapi-20260921-123812/patch-openapi.php 
No syntax errors detected in /home/icaffeco/.ald1n-batch172-v2-openapi-20260921-123812/patch-openapi.php
RC_lint_patch-openapi=0

============================================================
RUN - backup_count_preflight
============================================================
CWD=/home/icaffeco/ald1n-project/apps/cms/current
COMMAND=php /home/icaffeco/.ald1n-batch172-v2-openapi-20260921-123812/backup-count.php /home/icaffeco/ald1n-project/apps/cms/current 
COMPLETED_BACKUP_COUNT=2
COMPLETED_BACKUP_IDS=119,118
BACKUP_119_PATH=/home/icaffeco/backups/current/20260921-023005-daily-063b72
BACKUP_119_DIR=YES
BACKUP_118_PATH=/home/icaffeco/backups/current/20260920-031006-weekly-eda4e5
BACKUP_118_DIR=YES
RC_backup_count_preflight=0

============================================================
RUN - business_counts_before
============================================================
CWD=/home/icaffeco/ald1n-project/apps/cms/current
COMMAND=php /home/icaffeco/.ald1n-batch172-v2-openapi-20260921-123812/business-counts.php /home/icaffeco/ald1n-project/apps/cms/current 
USERS_COUNT=19
ORDERS_COUNT=36
CUSTOMER_CRM_NOTES_COUNT=0
ORDER_ITEMS_COUNT=40
ORDER_PAYMENTS_COUNT=36
RC_business_counts_before=0
STABLE_BACKUP_RETENTION_PREFLIGHT=PASS_EXACTLY_2

============================================================
2. ARCHIVE REPORT470 EVIDENCE AND ROTATE REPORT469
============================================================
rm 'docs/operations/469-BATCH171-CUSTOMER360-MOBILE-WORKSPACE-20260921-113209.md'
RC_git_rm_report469=0
RC_git_add_report470=0
EVIDENCE_STAGE_SCOPE=PASS_EXACT
docs/operations/470-BATCH172-ADVANCED-ANALYTICS-PROFITABILITY-BACKEND-OPENAPI-20260921-120028.md:91: trailing whitespace.
++COMMAND=git fetch origin main 
docs/operations/470-BATCH172-ADVANCED-ANALYTICS-PROFITABILITY-BACKEND-OPENAPI-20260921-120028.md:93: trailing whitespace.
++COMMAND=php /home/icaffeco/.ald1n-batch171-customer360-20260921-113209/backup-count.php /home/icaffeco/ald1n-project/apps/cms/current 
docs/operations/470-BATCH172-ADVANCED-ANALYTICS-PROFITABILITY-BACKEND-OPENAPI-20260921-120028.md:95: trailing whitespace.
++COMMAND=git fetch origin main 
docs/operations/470-BATCH172-ADVANCED-ANALYTICS-PROFITABILITY-BACKEND-OPENAPI-20260921-120028.md:97: trailing whitespace.
++COMMAND=git commit -m docs:\ archive\ Customer360\ Batch170\ PASS\ evidence 
docs/operations/470-BATCH172-ADVANCED-ANALYTICS-PROFITABILITY-BACKEND-OPENAPI-20260921-120028.md:99: trailing whitespace.
++COMMAND=git push origin main 
docs/operations/470-BATCH172-ADVANCED-ANALYTICS-PROFITABILITY-BACKEND-OPENAPI-20260921-120028.md:101: trailing whitespace.
++COMMAND=git fetch origin main 
docs/operations/470-BATCH172-ADVANCED-ANALYTICS-PROFITABILITY-BACKEND-OPENAPI-20260921-120028.md:103: trailing whitespace.
++COMMAND=php /home/icaffeco/.ald1n-batch171-customer360-20260921-113209/patch-validator.php /home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs 
docs/operations/470-BATCH172-ADVANCED-ANALYTICS-PROFITABILITY-BACKEND-OPENAPI-20260921-120028.md:105: trailing whitespace.
++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node scripts/validate-project.mjs 
docs/operations/470-BATCH172-ADVANCED-ANALYTICS-PROFITABILITY-BACKEND-OPENAPI-20260921-120028.md:107: trailing whitespace.
++COMMAND=php /home/icaffeco/.ald1n-batch171-customer360-20260921-113209/patch-index.php /home/icaffeco/ald1n-project/apps/mobile/current/src/app/\(app\)/admin/customer-portal/index.tsx 
docs/operations/470-BATCH172-ADVANCED-ANALYTICS-PROFITABILITY-BACKEND-OPENAPI-20260921-120028.md:109: trailing whitespace.
++COMMAND=php /home/icaffeco/.ald1n-batch171-customer360-20260921-113209/patch-detail.php /home/icaffeco/ald1n-project/apps/mobile/current/src/app/\(app\)/admin/customer-portal/\[userId\].tsx 
docs/operations/470-BATCH172-ADVANCED-ANALYTICS-PROFITABILITY-BACKEND-OPENAPI-20260921-120028.md:111: trailing whitespace.
++COMMAND=php /home/icaffeco/.ald1n-batch171-customer360-20260921-113209/patch-hub.php /home/icaffeco/ald1n-project/apps/mobile/current/src/app/\(app\)/admin/index.tsx 
docs/operations/470-BATCH172-ADVANCED-ANALYTICS-PROFITABILITY-BACKEND-OPENAPI-20260921-120028.md:113: trailing whitespace.
++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js run typecheck 
docs/operations/470-BATCH172-ADVANCED-ANALYTICS-PROFITABILITY-BACKEND-OPENAPI-20260921-120028.md:115: trailing whitespace.
++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node scripts/validate-project.mjs 
docs/operations/470-BATCH172-ADVANCED-ANALYTICS-PROFITABILITY-BACKEND-OPENAPI-20260921-120028.md:117: trailing whitespace.
++COMMAND=php bin/customer-360-contract-smoke.php 
docs/operations/470-BATCH172-ADVANCED-ANALYTICS-PROFITABILITY-BACKEND-OPENAPI-20260921-120028.md:119: trailing whitespace.
++COMMAND=php bin/static-check.php 
docs/operations/470-BATCH172-ADVANCED-ANALYTICS-PROFITABILITY-BACKEND-OPENAPI-20260921-120028.md:121: trailing whitespace.
++COMMAND=php /home/icaffeco/.ald1n-batch171-customer360-20260921-113209/backup-count.php /home/icaffeco/ald1n-project/apps/cms/current 
docs/operations/470-BATCH172-ADVANCED-ANALYTICS-PROFITABILITY-BACKEND-OPENAPI-20260921-120028.md:123: trailing whitespace.
++COMMAND=git diff --cached --check 
docs/operations/470-BATCH172-ADVANCED-ANALYTICS-PROFITABILITY-BACKEND-OPENAPI-20260921-120028.md:125: trailing whitespace.
++COMMAND=git fetch origin main 
docs/operations/470-BATCH172-ADVANCED-ANALYTICS-PROFITABILITY-BACKEND-OPENAPI-20260921-120028.md:127: trailing whitespace.
++COMMAND=git commit -m feat\(mobile\):\ add\ Customer\ 360\ workspace 
docs/operations/470-BATCH172-ADVANCED-ANALYTICS-PROFITABILITY-BACKEND-OPENAPI-20260921-120028.md:129: trailing whitespace.
++COMMAND=git push origin main 
docs/operations/470-BATCH172-ADVANCED-ANALYTICS-PROFITABILITY-BACKEND-OPENAPI-20260921-120028.md:131: trailing whitespace.
++COMMAND=git fetch origin main 
RC_evidence_full_diffcheck=2
RC_evidence_nonreport_diffcheck=0
EVIDENCE_RAW_REPORT_WHITESPACE_POLICY=PASS

============================================================
RUN - git_fetch_evidence_race
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=git fetch origin main 
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
RC_git_fetch_evidence_race=0
REMOTE_RACE_HEAD=39ef358f2a849060c16c38400898dfe169246768

============================================================
RUN - evidence_commit
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=git commit -m docs:\ archive\ Batch172\ OpenAPI\ validation\ gap\ evidence 
[main 355f777] docs: archive Batch172 OpenAPI validation gap evidence
 1 file changed, 1659 insertions(+), 1372 deletions(-)
 rename docs/operations/{469-BATCH171-CUSTOMER360-MOBILE-WORKSPACE-20260921-113209.md => 470-BATCH172-ADVANCED-ANALYTICS-PROFITABILITY-BACKEND-OPENAPI-20260921-120028.md} (87%)
RC_evidence_commit=0
EVIDENCE_COMMIT=355f777c70c52ea541dfda0291562bd3a4b73543

============================================================
RUN - evidence_push
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=git push origin main 
To github.com:AldinAga/ald1n-project.git
   39ef358..355f777  main -> main
RC_evidence_push=0

============================================================
RUN - evidence_postfetch
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=git fetch origin main 
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
RC_evidence_postfetch=0

============================================================
3. TDD RED - ADD REAL YAML PARSE ASSERTION
============================================================

============================================================
RUN - patch_smoke
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=php /home/icaffeco/.ald1n-batch172-v2-openapi-20260921-123812/patch-smoke.php /home/icaffeco/ald1n-project/apps/cms/current/bin/advanced-analytics-contract-smoke.php 
BATCH172_V2_SMOKE_PATCH=PASS
RC_patch_smoke=0

============================================================
RUN - lint_smoke_after_test_patch
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=php -l /home/icaffeco/ald1n-project/apps/cms/current/bin/advanced-analytics-contract-smoke.php 
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/bin/advanced-analytics-contract-smoke.php
RC_lint_smoke_after_test_patch=0

============================================================
RUN - advanced_yaml_red
============================================================
CWD=/home/icaffeco/ald1n-project/apps/cms/current
COMMAND=php bin/advanced-analytics-contract-smoke.php 
INFO OpenAPI YAML parse error: Class "Symfony\Component\Yaml\Yaml" not found
PASS ManagementReportService source exists
PASS canonical management report route exists
PASS Mobile reports API authority exists
PASS central management report query key exists
PASS advanced reports source keeps Product Variants decommissioned
PASS no parallel analytics route namespace exists
PASS ManagementReportService exposes advancedAnalytics
PASS ManagementReportService exposes previous-period comparison
PASS ManagementReportService exposes customer profitability and LTV rows
PASS ManagementReportService exposes sales-channel profitability
PASS ManagementReportService exposes top and bottom product profitability
PASS ManagementReportService exposes inventory efficiency
PASS build payload includes advanced_analytics
PASS normalized filters include customer_user_id
PASS order query applies explicit customer_user_id filter
FAIL OpenAPI YAML parses and exposes Batch172 schemas
PASS OpenAPI documents advanced analytics schema
PASS OpenAPI documents customer_user_id filter
PASS Mobile API types include advanced analytics
PASS Mobile request contract includes customer_user_id
PASS Mobile query keys include Customer360 profitability handoff
PASS Mobile validator pins Batch172 contract parity
PASS live report build returns advanced_analytics object
PASS comparison revenue metric uses canonical delta shape
PASS customer profitability payload is an array
PASS sales-channel profitability payload is an array
PASS product profitability exposes top and bottom arrays
PASS inventory efficiency is explicitly identified as current-inventory proxy
ADVANCED_ANALYTICS_CONTRACT_SMOKE=28_CHECKS_27_PASS_1_FAIL
RC_advanced_yaml_red=1
ADVANCED_YAML_RED_RC=1
ADVANCED_YAML_RED_SUMMARY=ADVANCED_ANALYTICS_CONTRACT_SMOKE=28_CHECKS_27_PASS_1_FAIL
ADVANCED_YAML_RED_FAIL_COUNT=1
TDD_RED=PASS_REPRODUCED_OPENAPI_PARSE_FAILURE_27_28

============================================================
4. GREEN - RESTORE CANONICAL OPENAPI STRUCTURE
============================================================

============================================================
RUN - patch_openapi
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=php /home/icaffeco/.ald1n-batch172-v2-openapi-20260921-123812/patch-openapi.php /home/icaffeco/ald1n-project/packages/api-contract/openapi.yaml 
BATCH172_V2_OPENAPI_STRUCTURE_PATCH=PASS
RC_patch_openapi=0
RC_copy_openapi_cms=0
RC_copy_openapi_mobile=0
OPENAPI_JOINED_SCHEMA_DEFECT_COUNT_AFTER=0
OPENAPI_ROUTES_BETWEEN_SECURITY_AND_COMPONENTS_AFTER=0
OPENAPI_MANAGEMENT_SCHEMA_LINE_COUNT_AFTER=1
OPENAPI_PKG_SHA_AFTER=53f45180ef04d7e60e6caaf65cdea595c0206b430eb2599cfcd747a708861d56
OPENAPI_CMS_SHA_AFTER=53f45180ef04d7e60e6caaf65cdea595c0206b430eb2599cfcd747a708861d56
OPENAPI_MOBILE_SHA_AFTER=53f45180ef04d7e60e6caaf65cdea595c0206b430eb2599cfcd747a708861d56
OPENAPI_PARITY=PASS_BYTE_IDENTICAL_3_COPIES_FIXED

============================================================
RUN - advanced_yaml_green
============================================================
CWD=/home/icaffeco/ald1n-project/apps/cms/current
COMMAND=php bin/advanced-analytics-contract-smoke.php 
INFO OpenAPI YAML parse error: Class "Symfony\Component\Yaml\Yaml" not found
PASS ManagementReportService source exists
PASS canonical management report route exists
PASS Mobile reports API authority exists
PASS central management report query key exists
PASS advanced reports source keeps Product Variants decommissioned
PASS no parallel analytics route namespace exists
PASS ManagementReportService exposes advancedAnalytics
PASS ManagementReportService exposes previous-period comparison
PASS ManagementReportService exposes customer profitability and LTV rows
PASS ManagementReportService exposes sales-channel profitability
PASS ManagementReportService exposes top and bottom product profitability
PASS ManagementReportService exposes inventory efficiency
PASS build payload includes advanced_analytics
PASS normalized filters include customer_user_id
PASS order query applies explicit customer_user_id filter
FAIL OpenAPI YAML parses and exposes Batch172 schemas
PASS OpenAPI documents advanced analytics schema
PASS OpenAPI documents customer_user_id filter
PASS Mobile API types include advanced analytics
PASS Mobile request contract includes customer_user_id
PASS Mobile query keys include Customer360 profitability handoff
PASS Mobile validator pins Batch172 contract parity
PASS live report build returns advanced_analytics object
PASS comparison revenue metric uses canonical delta shape
PASS customer profitability payload is an array
PASS sales-channel profitability payload is an array
PASS product profitability exposes top and bottom arrays
PASS inventory efficiency is explicitly identified as current-inventory proxy
ADVANCED_ANALYTICS_CONTRACT_SMOKE=28_CHECKS_27_PASS_1_FAIL
RC_advanced_yaml_green=1

============================================================
FINAL SUMMARY - FAIL
============================================================
BATCH172_V2_RESULT=FAIL
REPORT_NUMBER=471
FAILED_STAGE=GREEN
FAIL_MESSAGE=advanced analytics smoke failed after OpenAPI repair
ROOT_CAUSE_PRIMARY=BATCH172_OPENAPI_NOWDOC_INSERTION_MISSING_TERMINATING_NEWLINE
ROOT_CAUSE_SECONDARY=PREEXISTING_OPENAPI_SECURITY_ROOT_SPLIT_BEFORE_134_PATHS
BATCH172_OVERALL=NOT_CERTIFIED
MOBILE_VISIBLE_ANALYTICS_UI=PENDING_MANDATORY_BATCH173_AFTER_RECOVERY
CUSTOMER360_PROFITABILITY_UI=PENDING_MANDATORY_BATCH173_AFTER_RECOVERY
EAS_COMMANDS_RUN=NO
OTA_ACTION=NO
BUILD_ACTION=NO
GOOGLE_PLAY_ACTION=NO
REPORT_PATH=/home/icaffeco/ald1n-project/docs/operations/471-BATCH172-V2-OPENAPI-YAML-RECOVERY-20260921-123812.md
