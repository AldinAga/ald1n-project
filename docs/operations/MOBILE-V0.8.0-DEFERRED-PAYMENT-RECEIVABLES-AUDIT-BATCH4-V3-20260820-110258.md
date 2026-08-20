
============================================================
MOBILE v0.8.0 - DEFERRED PAYMENT + RECEIVABLES - READ-ONLY AUDIT - BATCH 4 V3
============================================================
DATE=Thu Aug 20 11:02:58 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
CMS=/home/icaffeco/ald1n-project/apps/cms/current
MOBILE=/home/icaffeco/ald1n-project/apps/mobile/current
REPORT=/home/icaffeco/ald1n-project/docs/operations/MOBILE-V0.8.0-DEFERRED-PAYMENT-RECEIVABLES-AUDIT-BATCH4-V3-20260820-110258.md
TARGET=ADD_DEFERRED_PAYMENT_THROUGH_EXISTING_RECEIVABLES_WEB_AND_MOBILE
CANONICAL_PAYMENT_METHOD_VALUE=deferred_payment
CUSTOMER_LABEL=Odlozeno placanje
NEW_PARALLEL_DEBT_SYSTEM=FORBIDDEN
APPLICATION_SOURCE_WRITES_EXPECTED=0
GIT_METADATA_WRITES=YES_ONLY_AFTER_ALL_PASS_GATES
DATABASE_WRITES_EXPECTED=0
MIGRATIONS_RUN=NO
DEPENDENCY_CHANGES=NO
EAS_COMMANDS_RUN=NO
EAS_BUILD=NO

============================================================
0. PREREQUISITE + GITHUB BASELINE - AUTO CHECKPOINT AT END
============================================================
BATCH3_PREREQUISITE=PASS
GIT_COMMANDS=DEFERRED_ALL_UNTIL_AUDIT_PASS
PREEXISTING_STAGED_CHANGES=ALLOWED_AND_PRESERVED_UNTIL_FINAL_TARGETED_CHECKPOINT
GITHUB_CHECKPOINT_SCOPE=TARGETED_BATCH_PATHS_ONLY_NO_GLOBAL_GIT_ADD
NODE_VERSION=v22.23.2
PHP_VERSION=8.4.24

============================================================
1. REQUIRED SOURCE SURFACE
============================================================
REQUIRED_SOURCE_FILES=PASS

============================================================
2. CURRENT PAYMENT METHOD CONTRACT - SOURCE READ ONLY
============================================================
WEB_REQUEST_CURRENT_TWO_METHODS=PASS
API_OPTIONS_COD_PRESENT=PASS
API_OPTIONS_BANK_PRESENT=PASS
WEB_CREATE_COD_PRESENT=PASS
WEB_CREATE_BANK_PRESENT=PASS
MOBILE_PAYMENT_METHOD_CURRENT_TWO_METHODS=PASS
MOBILE_CHECKOUT_SERVER_DRIVEN_METHOD_LIST=PASS
MOBILE_ORDER_OPTIONS_CANONICAL_ENDPOINT=PASS
WEB_REQUEST_DEFERRED_NOT_YET_IMPLEMENTED=PASS_ABSENT
API_OPTIONS_DEFERRED_NOT_YET_IMPLEMENTED=PASS_ABSENT
WEB_CREATE_DEFERRED_NOT_YET_IMPLEMENTED=PASS_ABSENT
MOBILE_TYPES_DEFERRED_NOT_YET_IMPLEMENTED=PASS_ABSENT
MOBILE_CHECKOUT_DEFERRED_NOT_YET_IMPLEMENTED=PASS_ABSENT

============================================================
3. RECEIVABLES AUTHORITY + PAYMENT SYNC CONTRACT
============================================================
ORDER_SERVICE_REUSES_RECEIVABLES=PASS
ORDER_CREATE_ALREADY_CALLS_RECEIVABLE_AUTHORITY=PASS
RECEIVABLE_CREATE_CURRENTLY_BANK_TRANSFER_ONLY=PASS
RECEIVABLE_AUTOMATION_CURRENTLY_BANK_TRANSFER_ONLY=PASS
RECEIVABLE_EXISTING_INSTALLMENT_PLAN_AUTHORITY=PASS
PAYMENT_SERVICE_REUSES_RECEIVABLES=PASS
PAYMENT_CHANGES_ALREADY_SYNC_RECEIVABLES=PASS
PAYMENT_LEDGER_RECALCULATES_ORDER_BALANCE=PASS
RECEIVABLES_EXISTING_AUTHORITY=PASS_REUSE_REQUIRED
PARALLEL_DEBT_MODEL_REQUIRED=NO

============================================================
4. LIVE DATABASE + SCHEMA AUDIT - READ ONLY
============================================================
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.ald1n-mobile-v0.8.0-deferred-payment-receivables-audit-batch4-v3.20260820-110258.2887336/db-audit.php
DB_DRIVER=mysql
ORDERS_TABLE_PRESENT=YES
ORDER_PAYMENTS_TABLE_PRESENT=YES
RECEIVABLE_CASES_TABLE_PRESENT=YES
RECEIVABLE_INSTALLMENTS_TABLE_PRESENT=YES
RECEIVABLE_CONTACTS_TABLE_PRESENT=YES
ORDERS_COLUMN_PAYMENT_METHOD=YES
ORDERS_COLUMN_PAYMENT_STATUS=YES
ORDERS_COLUMN_PAYMENT_STATE=YES
ORDERS_COLUMN_PAID_TOTAL_RSD=YES
ORDERS_COLUMN_PAYMENT_DUE_AT=YES
ORDERS_COLUMN_SUBTOTAL_RSD=YES
ORDERS_COLUMN_STATUS=YES
ORDERS_PAYMENT_METHOD_TYPE=enum
ORDERS_PAYMENT_METHOD_NULLABLE=NO
ORDERS_PAYMENT_METHOD_DEFAULT='cash_on_delivery'
LIVE_ORDER_PAYMENT_METHOD_COUNTS=[{"payment_method":"cash_on_delivery","total":3},{"payment_method":"bank_transfer","total":3},{"payment_method":"cash","total":5}]
LIVE_ORDERS_TOTAL=11
LIVE_OPEN_UNPAID_BANK_TRANSFER=0
LIVE_ORDERS_WITH_PAYMENT_DUE_AT=1
LIVE_RECEIVABLE_CASES_TOTAL=1
LIVE_RECEIVABLE_INSTALLMENTS_TOTAL=0
LIVE_RECEIVABLE_OPEN_CASES=1
RECEIVABLES_SERVICE_READY=YES
SETTING_RECEIVABLES_ENABLED=1
SETTING_RECEIVABLES_AUTO_CREATE_CASES=1
SETTING_RECEIVABLES_AUTO_REMINDERS_ENABLED=1
SETTING_RECEIVABLES_PAUSE_ON_PROMISE=1
SETTING_RECEIVABLES_REMINDER_STAGES=0,3,7,15,30
RECEIVABLE_CASE_COLUMNS=["id","order_id","case_number","status","collection_stage","assigned_to","next_action_at","promised_payment_at","last_contact_at","last_reminder_stage","last_reminder_at","internal_note","metadata_json","created_by","updated_by","closed_at","created_at","updated_at"]
RECEIVABLE_INSTALLMENT_COLUMNS=["id","receivable_case_id","sequence_no","due_at","amount_rsd","paid_amount_rsd","status","paid_at","note","created_at","updated_at"]
DEFERRED_PAYMENT_AUDIT_DB_STATE_HASH=73eefe24096438752bb7eef47d1326633433e046e2d50b0c42ce436967522f52
DEFERRED_PAYMENT_PAYMENT_METHOD_SCHEMA_DECISION=MIGRATION_REQUIRED_ENUM
DEFERRED_PAYMENT_SCHEMA_MIGRATION_EXPECTED=YES_ENUM_EXTENSION_ONLY
DEFERRED_PAYMENT_DUE_DATE_COLUMN=PASS_EXISTING_orders.payment_due_at
DEFERRED_PAYMENT_BALANCE_FIELDS=PASS_EXISTING_subtotal_rsd_paid_total_rsd_payment_state
DEFERRED_PAYMENT_INSTALLMENTS=PASS_EXISTING_receivable_installments

============================================================
5. ROUTE + OPENAPI SURFACE AUDIT
============================================================
API_ORDER_OPTIONS_ROUTE_PRESENT=PASS
WEB_ORDER_STORE_ROUTE_PRESENT=PASS
OPENAPI_PRE_PARITY=PASS_3_COPIES
OPENAPI_DEFERRED_NOT_YET_IMPLEMENTED=PASS_ABSENT
OPENAPI_COD_PRESENT=PASS
OPENAPI_BANK_TRANSFER_PRESENT=PASS

============================================================
6. TARGETED LINT + HASH BASELINE
============================================================
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreOrderRequest.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/OrderOptionsController.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/OrderController.php
TARGETED_PHP_LINT=PASS
d2f643c12918592edba4ebc7ddcb461c7535c431c36e90994fe759ec4604a24b  /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreOrderRequest.php
93c1a145fe41a52d23675cdd9d4ed5377af13ba2ae7690136db1a229f4272112  /home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php
39dcb27e728f11335fa9e4cbf8bc9c2d33f3f8e4d25866379f9630b00691b43d  /home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php
3e5fab70c672ad2d2a64f18413e8363cd4a9cbe0d4a40f709aed79965c1b5cea  /home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php
308ec5f3ffe9239f3efd7cd521b4df892d0aea9506944b7e1f316a03e7da32b8  /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/OrderOptionsController.php
c7881533dfc39534d6ce5267ab8c2b006ebd21b2c0c0ad0af22bd1e07bcf806d  /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/OrderController.php
ad7d4c47de63719f265d714303776318a2267950ce560e303d9e91b487e16ed5  /home/icaffeco/ald1n-project/apps/cms/current/resources/views/orders/create.blade.php
0f65159188f9a61d47c8d58199b46fee9d0b68d6071b4e8a5bee55d54c907f75  /home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts
d8d1301d3469b468d349c8e9cf451f09c3571905a52716e377b53c258485c945  /home/icaffeco/ald1n-project/apps/mobile/current/src/lib/api/endpoints.ts
2faa6808e0ff245e4b258dc9be5c084b9a372300de84f69c121712ce7c556587  /home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/checkout.tsx
6c1971efb1c968612ff70552966143a5c97a179e1ae2be657df99134934ecded  /home/icaffeco/ald1n-project/packages/api-contract/openapi.yaml
TARGETED_SOURCE_HASH_BASELINE=PASS
TARGETED_GIT_DIFF_CHECK=MOVED_TO_FINAL_GITHUB_SECTION

============================================================
7. IMPLEMENTATION CONTRACT DERIVED FROM AUDIT
============================================================
DEFERRED_PAYMENT_CANONICAL_VALUE=deferred_payment
DEFERRED_PAYMENT_LABEL=Odlozeno placanje
DEFERRED_PAYMENT_WEB_CREATE=REQUIRED
DEFERRED_PAYMENT_MOBILE_CREATE=REQUIRED
DEFERRED_PAYMENT_DUE_DATE=REQUIRED_EXPLICIT_FOR_DEFERRED_PAYMENT
DEFERRED_PAYMENT_BANK_ACCOUNT=NOT_REQUIRED
DEFERRED_PAYMENT_INITIAL_PAYMENT_STATE=UNPAID_EXISTING_AUTHORITY
DEFERRED_PAYMENT_RECEIVABLE_CASE=CREATE_THROUGH_EXISTING_ReceivablesService
DEFERRED_PAYMENT_RECEIVABLE_AMOUNT=EXISTING_subtotal_rsd_MINUS_paid_total_rsd
DEFERRED_PAYMENT_INSTALLMENT_PLAN=REUSE_EXISTING_replacePlan_NO_DUPLICATE_MODEL
DEFERRED_PAYMENT_PAYMENT_REDUCTION=REUSE_OrderPaymentService_RECALCULATION_AND_SYNC
DEFERRED_PAYMENT_ZERO_BALANCE_CLOSE=REUSE_ReceivablesService_syncForOrder
RECEIVABLE_ENSURE_SCOPE_CHANGE=bank_transfer_TO_bank_transfer_PLUS_deferred_payment
RECEIVABLE_AUTOMATION_SCOPE_CHANGE=bank_transfer_TO_bank_transfer_PLUS_deferred_payment
PAYMENT_PROOF_BANK_TRANSFER_ONLY=KEEP_UNCHANGED
OPENAPI_UPDATE=REQUIRED_3_COPY_PARITY
NEW_DEBT_TABLES=NO
NEW_NATIVE_DEPENDENCY=NO
EAS_BUILD=NO

============================================================
8. FINAL
============================================================
BATCH3_PREREQUISITE=PASS
BATCH3_GITHUB_CHECKPOINT=HANDLED_BY_TARGETED_AUTO_CHECKPOINT_AT_SCRIPT_END
DEFERRED_PAYMENT_CURRENT_IMPLEMENTATION=ABSENT_EXPECTED
EXISTING_RECEIVABLES_AUTHORITY=PASS
EXISTING_PAYMENT_LEDGER_SYNC=PASS
EXISTING_INSTALLMENT_PLAN_AUTHORITY=PASS
PAYMENT_DUE_AT_EXISTING_COLUMN=PASS
OPENAPI_PRE_PARITY=PASS_3_COPIES
DATABASE_WRITES_DURING_AUDIT=0_BY_SCRIPT_DESIGN
APPLICATION_SOURCE_WRITES_DURING_AUDIT=0
MIGRATIONS_RUN=NO
DEPENDENCY_CHANGES=NO
EAS_COMMANDS_RUN=NO
EAS_BUILD=NO
V0_8_IMPLEMENTATION_PROGRESS=50_PERCENT_BATCH4_AUDIT_COMPLETE_IMPLEMENTATION_PENDING
MOBILE_V0_8_DEFERRED_PAYMENT_RECEIVABLES_AUDIT_BATCH4=PASS
MOBILE_V0_8_DEFERRED_PAYMENT_RECEIVABLES_AUDIT_BATCH4_V3=PASS
GITHUB_AUTO_CHECKPOINT_MODE=TARGETED_ENABLED_AFTER_ALL_PASS_GATES
GITHUB_AUTO_CHECKPOINT_MESSAGE=v0.8 Batch 3 PASS + Batch 4 deferred payment receivables audit PASS
NEXT_ACTION=GENERATE_BATCH4_IMPLEMENTATION_FROM_THIS_AUDIT
UPLOAD_THIS_REPORT_TO_CHAT=/home/icaffeco/ald1n-project/docs/operations/MOBILE-V0.8.0-DEFERRED-PAYMENT-RECEIVABLES-AUDIT-BATCH4-V3-20260820-110258.md

AUDIT_RESULT=PASS_TARGETED_GITHUB_CHECKPOINT_STARTS_AFTER_REPORT_FINALIZATION

============================================================
9. GITHUB TARGETED AUTO CHECKPOINT - ALL GIT COMMANDS AT END
============================================================
GITHUB_REMOTE_URL=git@github.com:AldinAga/ald1n-project.git
GIT_STAGED_PATHS_PRE=17
GIT_DIRTY_PATHS_PRE=20
GIT_INDEX_PRESTATE_SNAPSHOT=PASS
PREEXISTING_STAGED_STATE=DETECTED_ACCEPTED_NO_MANUAL_UNSTAGE_REQUIRED
GIT_STAGED_PRE_PATH=apps/cms/current/app/Http/Controllers/Admin/ProductPurchaseCostController.php
GIT_STAGED_PRE_PATH=apps/cms/current/app/Http/Controllers/Api/V1/Admin/FoundationController.php
GIT_STAGED_PRE_PATH=apps/cms/current/app/Http/Controllers/DashboardController.php
GIT_STAGED_PRE_PATH=apps/cms/current/app/Services/ManagementReportService.php
GIT_STAGED_PRE_PATH=apps/cms/current/bin/v0.8-batch3-purchase-cost-kpi-smoke.php
GIT_STAGED_PRE_PATH=apps/cms/current/docs/openapi.yaml
GIT_STAGED_PRE_PATH=apps/cms/current/resources/views/admin/products/purchase-costs.blade.php
GIT_STAGED_PRE_PATH=apps/cms/current/resources/views/dashboard/index.blade.php
GIT_STAGED_PRE_PATH=apps/cms/current/routes/web.php
GIT_STAGED_PRE_PATH=apps/mobile/current/docs/openapi.yaml
GIT_STAGED_PRE_PATH=apps/mobile/current/src/app/(app)/admin/index.tsx
GIT_STAGED_PRE_PATH=apps/mobile/current/src/features/admin/admin-api.ts
GIT_STAGED_PRE_PATH=docs/HANDOFF-CURRENT.md
GIT_STAGED_PRE_PATH=docs/operations/MOBILE-V0.8.0-DEFERRED-PAYMENT-RECEIVABLES-AUDIT-BATCH4-20260820-105007.md
GIT_STAGED_PRE_PATH=docs/operations/MOBILE-V0.8.0-PURCHASE-COSTS-SUPERADMIN-INVENTORY-KPI-BATCH3-20260820-100509.md
GIT_STAGED_PRE_PATH=packages/api-contract/openapi.yaml
GIT_STAGED_PRE_PATH=scripts/github-checkpoint.sh
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
GITHUB_LOCAL_HEAD_PRE=8aeb80b7d233bdf26e69673b1b9b166fabb0f628
GITHUB_REMOTE_HEAD_PRE=8aeb80b7d233bdf26e69673b1b9b166fabb0f628
GITHUB_REMOTE_SYNC_PRE=PASS
BATCH3_GITHUB_CHECKPOINT_PRESTATE=PENDING_INCLUDED_NOW
GITHUB_CHECKPOINT_MESSAGE=v0.8 Batch 3 PASS + Batch 4 deferred payment receivables audit PASS
GITHUB_CHECKPOINT_TARGET_PATHS=20
GITHUB_CHECKPOINT_STAGED_TARGETS=20
GITHUB_CHECKPOINT_SECRET_SCAN=PASS
docs/operations/MOBILE-V0.8.0-PURCHASE-COSTS-SUPERADMIN-INVENTORY-KPI-BATCH3-20260820-100509.md:94: trailing whitespace.
+   INFO  Route cache cleared successfully.
