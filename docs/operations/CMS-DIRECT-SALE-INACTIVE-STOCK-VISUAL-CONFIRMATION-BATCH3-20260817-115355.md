============================================================
CMS DIRECT SALE - INACTIVE STOCK VISUAL CONFIRMATION BATCH 3
============================================================
DATE=Mon Aug 17 11:53:55 CEST 2026
ROOT=/home/icaffeco/ald1n-project
CMS=/home/icaffeco/ald1n-project/apps/cms/current
REPORT=/home/icaffeco/ald1n-project/docs/operations/CMS-DIRECT-SALE-INACTIVE-STOCK-VISUAL-CONFIRMATION-BATCH3-20260817-115355.md
MODE=READ_ONLY_BROWSER_CONFIRMATION
APPLICATION_SOURCE_WRITES_EXPECTED=0
DATABASE_BUSINESS_DATA_WRITES_EXPECTED=0
MIGRATIONS_RUN=NO
ROUTE_CHANGES=NO
OPENAPI_CHANGED=NO
MOBILE_SOURCE_CHANGED=0
EAS_BUILD=NO

============================================================
0. PREFLIGHT + BATCH 2 V2 EVIDENCE
============================================================
BATCH2_V2_REPORT=/home/icaffeco/ald1n-project/docs/operations/CMS-DIRECT-SALE-INACTIVE-STOCK-ELIGIBILITY-REPAIR-BATCH2-V2-20260817-114840.md
BATCH2_V2_EVIDENCE=PASS
SAME_PROBLEM_PRODUCT_IDS=4,16,18,21
SAME_PROBLEM_PRODUCTS_UNLOCKED=4
PREFLIGHT=PASS
MANAGED_SOURCE_FINGERPRINT_BEFORE=f44ccf76da138df5334753159b9ab4d49750256f4b3d0ea5cfc0228bbececdf3

============================================================
1. MANUAL BROWSER CONFIRMATION
============================================================
RULE=Do not submit a sale. Only confirm that the Direct Sale UI is visible and usable.
EXPECTED_BROWSER_CHECKS=2

[01] HP EliteDesk 800 G2 - Direct Sale visible
URL=https://cms.ald1n.com/catalog/hp-elitedesk-800-g2-i5-6500t
Refresh the page as SuperAdmin. Confirm that the Direct Sale / Evidentiraj prodaju section is now visible for this inactive product with stock. Do NOT submit the sale.
Unesi P za PASS ili F za FAIL: p
VISUAL_CHECK_01=PASS|HP EliteDesk 800 G2 - Direct Sale visible

[02] Second inactive in-stock product - Direct Sale visible
URL=https://cms.ald1n.com/catalog/lenovo-thinkpad-t490s-intel-core-i7-i7-8565u-8gb-256gb
Open the Lenovo T490s product as SuperAdmin. Confirm the same Direct Sale section is visible there too. Do NOT submit the sale.
Unesi P za PASS ili F za FAIL: P
VISUAL_CHECK_02=PASS|Second inactive in-stock product - Direct Sale visible
MANUAL_BROWSER_CHECKS_TOTAL=2
MANUAL_BROWSER_CHECKS_FAILED=0

============================================================
2. SOURCE IMMUTABILITY RECHECK
============================================================
MANAGED_SOURCE_FINGERPRINT_AFTER=f44ccf76da138df5334753159b9ab4d49750256f4b3d0ea5cfc0228bbececdf3
APPLICATION_SOURCE_MUTATED_DURING_BATCH3=NO
DATABASE_BUSINESS_DATA_WRITES_BY_BATCH3=0
SOURCE_IMMUTABILITY=PASS

============================================================
3. FINAL
============================================================
CMS_DIRECT_SALE_INACTIVE_STOCK_VISUAL_CONFIRMATION_BATCH3=PASS
DIRECT_SALE_INACTIVE_STOCK_FIX_STATUS=CLOSED_PASS
TARGET_HP_BROWSER_CONFIRMATION=PASS
SECOND_PRODUCT_BROWSER_CONFIRMATION=PASS
SAME_PROBLEM_PRODUCTS_COVERED=4
NEXT_ACTION=RETURN_TO_NEXT_PROJECT_BACKLOG_ITEM
REPORT=/home/icaffeco/ald1n-project/docs/operations/CMS-DIRECT-SALE-INACTIVE-STOCK-VISUAL-CONFIRMATION-BATCH3-20260817-115355.md

PASS: CMS DIRECT SALE INACTIVE STOCK VISUAL CONFIRMATION BATCH 3 COMPLETE
