
============================================================
CMS WEB 500 - MODULE CONTROL DASHBOARD REPAIR - BATCH 2
============================================================
DATE=Tue Aug 18 17:28:27 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
CMS=/home/icaffeco/ald1n-project/apps/cms/current
MODE=MUTATING_SINGLE_BLADE_SOURCE_WITH_BACKUP_AND_ROLLBACK
CAUSE_TARGET=MODULE_CONTROL_DASHBOARD_BLADE_WRAPPER_PARSE_ERROR
REPAIR_STRATEGY=RESTORE_EXACT_PRE_MODULE_DASHBOARD_THEN_ADD_NON_NESTING_CSS_VISIBILITY_GUARDS
DATABASE_WRITES_EXPECTED=0
MIGRATIONS_RUN=NO
MOBILE_SOURCE_CHANGES=NO
OPENAPI_CHANGES=NO
EAS_BUILD=NO
REPORT_GENERATION=ENABLED_DOCS_OPERATIONS

============================================================
0. CONCURRENCY + PREREQUISITES
============================================================
CONCURRENCY_LOCK=ACQUIRED
DIAGNOSTIC_PREREQUISITE=PASS
DIAGNOSTIC_REPORT=/home/icaffeco/ald1n-project/docs/operations/CMS-WEB-500-DIAGNOSTIC-BATCH1-20260818-172656.md
MODULE_CONTROL_BATCH2_PREREQUISITE=PASS
MODULE_CONTROL_BATCH2_REPORT=/home/icaffeco/ald1n-project/docs/operations/CMS-MODULE-CONTROL-IMPLEMENTATION-BATCH2-20260818-144410.md
PRE_MODULE_DASHBOARD_BACKUP=/home/icaffeco/backups/releases/cms-module-control-implementation-batch2-20260818-144410/files/resources/views/dashboard/index.blade.php
OPENAPI_PRE_PARITY=PASS

============================================================
1. PROVE CURRENT DASHBOARD IS EXACT MODULE-CONTROL PATCH OUTPUT
============================================================
No syntax errors detected in /tmp/ald1n-cms-web-500-repair.lcvBG8/reproduce-old-dashboard.php
REPRODUCED_OLD_DASHBOARD_WRAPPERS=5
CURRENT_DASHBOARD_SHA=9ee5e83061b790b221e35a44e9ca634c3870ed1e6a5d974e11795927df6f03e4
EXPECTED_BATCH2_DASHBOARD_SHA=ad95c6af0b16b553d408cf066471d3b5bd60d27ba36cbdad6876803c603f0c04
FAIL: current dashboard contains changes beyond certified Module Control Batch 2; refusing to overwrite
CMS_WEB_500_MODULE_CONTROL_DASHBOARD_REPAIR_BATCH2=FAIL
