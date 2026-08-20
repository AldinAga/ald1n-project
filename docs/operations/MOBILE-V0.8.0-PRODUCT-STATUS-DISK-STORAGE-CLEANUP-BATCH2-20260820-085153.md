CONCURRENCY_LOCK=ACQUIRED

============================================================
MOBILE v0.8.0 - PRODUCT STATUS + DISK STORAGE CLEANUP - BATCH 2
============================================================
DATE=Thu Aug 20 08:51:53 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
CMS=/home/icaffeco/ald1n-project/apps/cms/current
MOBILE=/home/icaffeco/ald1n-project/apps/mobile/current
REPORT=/home/icaffeco/ald1n-project/docs/operations/MOBILE-V0.8.0-PRODUCT-STATUS-DISK-STORAGE-CLEANUP-BATCH2-20260820-085153.md
BACKUP=/home/icaffeco/backups/releases/mobile-v0.8.0-product-status-disk-storage-cleanup-batch2-20260820-085153
TARGET_1=LIGHTWEIGHT_MANUAL_PRODUCT_STATUS_CONTROL_WITH_COMPLETENESS_REASON
TARGET_2=REMOVE_DUPLICATE_DISK_INTERFACE_STORAGE_CONTRACT
STATUS_STOCK_POLICY=NO_AUTOMATIC_STATUS_CHANGE_FROM_STOCK_QUANTITY
CANONICAL_STORAGE_COMPONENT=tip-diska
DERIVED_STORAGE_TOTAL=kapacitet-diska
REMOVED_STORAGE_FIELD=interfejs-diska
MOBILE_SOURCE_CHANGES=NO
OPENAPI_CHANGES=NO
DEPENDENCY_CHANGES=NO
NEW_NATIVE_DEPENDENCY=NO
EAS_COMMANDS_RUN=NO
EAS_BUILD=NO

============================================================
0. PREREQUISITE + TOOLCHAIN + BASELINE
============================================================
BATCH1_V2_PREREQUISITE=PASS
BATCH1_V2_REPORT=/home/icaffeco/ald1n-project/docs/operations/MOBILE-V0.8.0-FOUNDATION-READ-ONLY-AUDIT-BATCH1-V2-20260820-081346.md
NODE_VERSION=v22.23.2
NPM_VERSION=10.9.8
CURRENT_APP_VERSION=0.7.0
CURRENT_PACKAGE_LOCK_VERSION=0.7.0
OPENAPI_PRE_PARITY=PASS
GIT_BASELINE_CAPTURED=YES
PRODUCT_VARIANTS_SOURCE=REMAINS_DECOMMISSIONED

============================================================
1. LIVE DB PREFLIGHT + EXACT REVERSIBLE SNAPSHOT
============================================================
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.ald1n-mobile-v0.8.0-product-status-disk-storage-cleanup-batch2.20260820-085153.2375769/db-snapshot.php

In Connection.php line 857:
                                                                                                                                                                             
  SQLSTATE[42S22]: Column not found: 1054 Unknown column 'id' in 'ORDER BY' (Connection: mysql, Host: localhost, Port: 3306, Database: icaffeco_lrvl, SQL: select * from `s  
  pecification_option_dependencies` where `parent_option_id` in (23, 24, 25, 26, 27, 28) or `child_option_id` in (23, 24, 25, 26, 27, 28) order by `id` asc)                 
                                                                                                                                                                             

In Connection.php line 435:
                                                                             
  SQLSTATE[42S22]: Column not found: 1054 Unknown column 'id' in 'ORDER BY'  
                                                                             

FAIL: DB snapshot sentinel missing

============================================================
ROLLBACK
============================================================
ROLLBACK_DB_RESTORE=NOT_NEEDED
ROLLBACK_SOURCE=NOT_NEEDED
ROLLBACK=COMPLETE
EXIT_CODE=1
UPLOAD_THIS_REPORT_TO_CHAT=/home/icaffeco/ald1n-project/docs/operations/MOBILE-V0.8.0-PRODUCT-STATUS-DISK-STORAGE-CLEANUP-BATCH2-20260820-085153.md
