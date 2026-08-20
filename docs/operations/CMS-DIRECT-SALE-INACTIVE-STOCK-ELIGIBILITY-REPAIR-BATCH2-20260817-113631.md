
============================================================
CMS DIRECT SALE - INACTIVE STOCK ELIGIBILITY REPAIR - BATCH 2
============================================================
DATE=Mon Aug 17 11:36:31 CEST 2026
ROOT=/home/icaffeco/ald1n-project
CMS=/home/icaffeco/ald1n-project/apps/cms/current
REPORT=/home/icaffeco/ald1n-project/docs/operations/CMS-DIRECT-SALE-INACTIVE-STOCK-ELIGIBILITY-REPAIR-BATCH2-20260817-113631.md
BACKUP=/home/icaffeco/backups/releases/cms-direct-sale-inactive-stock-eligibility-repair-batch2-20260817-113631
TARGET_POLICY=SUPERADMIN_DIRECT_SALE_FOR_ACTIVE_OR_INACTIVE_NON_ARCHIVED_PRODUCTS
PRODUCT_STATUS_DATA_WRITES=NO
PRODUCT_STOCK_DATA_WRITES=NO
DATABASE_BUSINESS_DATA_WRITES_EXPECTED=0
MIGRATIONS_RUN=NO
ROUTE_CHANGES=NO
OPENAPI_CHANGED=NO
MOBILE_SOURCE_CHANGED=0
EAS_BUILD=NO

============================================================
0. PREFLIGHT + BATCH 1 V2 EVIDENCE
============================================================
PASS command: php
PASS command: grep
PASS command: sed
PASS command: awk
PASS command: sha256sum
PASS command: cp
PASS command: cmp
PASS command: mkdir
PASS command: rmdir
PASS command: mktemp
PASS command: date
PASS command: find
PASS command: sort
PASS command: wc
PASS command: git
CONCURRENCY_LOCK=ACQUIRED
BATCH1_V2_REPORT=/home/icaffeco/ald1n-project/docs/operations/CMS-DIRECT-SALE-PRODUCT-ELIGIBILITY-AUDIT-BATCH1-V2-20260817-112207.md
BATCH1_V2_EVIDENCE=PASS
EXACT_V2_SOURCE_PRESTATE=PASS
No syntax errors detected in /tmp/ald1n-direct-sale-b2.mHDO7L/patch.php
No syntax errors detected in /tmp/ald1n-direct-sale-b2.mHDO7L/validate.php
No syntax errors detected in /tmp/ald1n-direct-sale-b2.mHDO7L/snapshot.php
No syntax errors detected in /tmp/ald1n-direct-sale-b2.mHDO7L/runtime.php
LOCAL_PATCH_FIXTURE=PASS
PRODUCT_BUSINESS_SNAPSHOT_SHA256_BEFORE=4c5ec74f320f051324b3cd679f907a1695c1fa5dda9e3952adaaff3203d8719c
OPENAPI_SHA256_BEFORE=caf7a4d0238e4c085dadb5dcee68b24bbbfb67f16382eb31cf1c64c2a93cbc37

============================================================
1. TARGETED BACKUP
============================================================
/home/icaffeco/backups/releases/cms-direct-sale-inactive-stock-eligibility-repair-batch2-20260817-113631/SHA256SUMS.txt: FAILED
/home/icaffeco/backups/releases/cms-direct-sale-inactive-stock-eligibility-repair-batch2-20260817-113631/cms/app/Http/Controllers/CatalogController.php: OK
/home/icaffeco/backups/releases/cms-direct-sale-inactive-stock-eligibility-repair-batch2-20260817-113631/cms/app/Services/DirectSaleService.php: OK
/home/icaffeco/backups/releases/cms-direct-sale-inactive-stock-eligibility-repair-batch2-20260817-113631/cms/resources/views/admin/products/form.blade.php: OK
