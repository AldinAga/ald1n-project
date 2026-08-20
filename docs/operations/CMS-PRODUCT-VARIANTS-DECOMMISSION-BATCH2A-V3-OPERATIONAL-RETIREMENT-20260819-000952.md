
============================================================
CMS PRODUCT VARIANTS - DECOMMISSION BATCH 2A V3 OPERATIONAL RETIREMENT
============================================================
DATE=Wed Aug 19 00:09:52 CEST 2026
ROOT=/home/icaffeco/ald1n-project
CMS=/home/icaffeco/ald1n-project/apps/cms/current
REPORT=/home/icaffeco/ald1n-project/docs/operations/CMS-PRODUCT-VARIANTS-DECOMMISSION-BATCH2A-V3-OPERATIONAL-RETIREMENT-20260819-000952.md
BACKUP=/home/icaffeco/backups/releases/cms-product-variants-decommission-batch2a-v3-operational-retirement-20260819-000952
MODE=MUTATING_OPERATIONAL_RETIREMENT_WITH_BACKUP_AND_ROLLBACK
TARGET=REMOVE_VARIANT_ROUTES_AND_VISIBLE_ADMIN_ENTRY_POINTS_NORMALIZE_SINGLE_PRODUCT_MODE
FULL_SCHEMA_PURGE=DEFERRED_TO_LATER_DECOMMISSION_BATCH_AFTER_RUNTIME_REFACTOR
APP_VERSION_CHANGE=NO
DEPENDENCY_CHANGES=NO
EAS_BUILD=NO

============================================================
0. PREFLIGHT + CANONICAL AUDIT PREREQUISITE
============================================================
PREFLIGHT_COMMAND_php=PASS
PREFLIGHT_COMMAND_grep=PASS
PREFLIGHT_COMMAND_sed=PASS
PREFLIGHT_COMMAND_awk=PASS
PREFLIGHT_COMMAND_cat=PASS
PREFLIGHT_COMMAND_cp=PASS
PREFLIGHT_COMMAND_mkdir=PASS
PREFLIGHT_COMMAND_rm=PASS
PREFLIGHT_COMMAND_sha256sum=PASS
PREFLIGHT_COMMAND_find=PASS
PREFLIGHT_COMMAND_sort=PASS
PREFLIGHT_COMMAND_wc=PASS
PREFLIGHT_COMMAND_git=PASS
PREFLIGHT_COMMAND_cmp=PASS
PREFLIGHT_COMMAND_mktemp=PASS
PREFLIGHT_COMMAND_date=PASS
AUDIT_PASS_REPORT=/home/icaffeco/ald1n-project/docs/operations/CMS-PRODUCT-VARIANTS-DECOMMISSION-AUDIT-BATCH1-20260818-235228.md
AUDIT_PREREQUISITE=PASS_ZERO_VARIANTS_ZERO_REFERENCES_FULL_SCHEMA_ELIGIBLE
PRIOR_BATCH2A_V2_FAILED_ATTEMPT=/home/icaffeco/ald1n-project/docs/operations/CMS-PRODUCT-VARIANTS-DECOMMISSION-BATCH2A-V2-OPERATIONAL-RETIREMENT-20260819-000639.md
PRIOR_BATCH2A_V2_ROLLBACK=PASS_SOURCE_AND_DB_RESTORED
FIELD_OPERATIONS_BATCH3_PASS_REPORT=/home/icaffeco/ald1n-project/docs/operations/MOBILE-V0.7.0-FIELD-OPERATIONS-ADMIN-MOBILE-CLIENT-UI-BATCH3-20260818-234543.md
FIELD_OPERATIONS_PROGRESS_PRESERVED=75_PERCENT
ROUTE_CACHE_BEFORE_COUNT=0
VARIANT_WEB_ROUTE_COUNT_BEFORE=8
SOURCE_ANCHORS=PASS_WITH_LEGACY_STATIC_ASSERTION_IDENTIFIED

============================================================
1. LIVE DATABASE SAFETY RECHECK
============================================================
PRODUCT_VARIANT_COUNT_RECHECK=0
NON_NULL_PRODUCT_VARIANT_ID_PRODUCT_IMAGES=0
NON_NULL_PRODUCT_VARIANT_ID_ORDER_ITEMS=0
NON_NULL_PRODUCT_VARIANT_ID_STOCK_MOVEMENTS=0
NON_NULL_PRODUCT_VARIANT_ID_AFTER_SALES_CASE_ITEMS=0
NON_NULL_PRODUCT_VARIANT_ID_AFTER_SALES_ACTION_ITEMS=0
NON_NULL_PRODUCT_VARIANT_ID_PRODUCT_WARRANTIES=0
PRODUCTS_VARIANTS_ENABLED_BEFORE=0
PRODUCTS_DEFAULT_VARIANT_ID_BEFORE=0
BUSINESS_VARIANT_REFERENCE_NON_NULL_TOTAL=0
LIVE_DB_SAFETY_RECHECK=PASS

============================================================
2. BACKUP SOURCE + PRODUCT FLAGS
============================================================
BACKUP_SOURCE_FILE_COUNT=8
BACKUP_PRODUCT_FLAGS=PASS

============================================================
3. BUILD PATCHED SOURCE IN TEMP
============================================================
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.cms-product-variants-decommission-batch2a-v3.20260819-000952.jzDm8h/routes/web.php
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.cms-product-variants-decommission-batch2a-v3.20260819-000952.jzDm8h/app/Http/Controllers/Admin/ProductController.php
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.cms-product-variants-decommission-batch2a-v3.20260819-000952.jzDm8h/bin/static-check.php
TEMP_PRODUCT_FORM_WHITESPACE_GUARD=PASS
TEMP_OPERATIONAL_RETIREMENT_CONTRACT=PASS

============================================================
4. INSTALL MANAGED SOURCE
============================================================
SOURCE_WRITES=8_MANAGED_FILES

============================================================
5. NORMALIZE ALL PRODUCTS TO SINGLE-PRODUCT MODE
============================================================
PRODUCT_ROWS_NORMALIZED=0
PRODUCTS_VARIANTS_ENABLED_AFTER=0
PRODUCTS_DEFAULT_VARIANT_ID_AFTER=0
SINGLE_PRODUCT_MODE_NORMALIZATION=PASS

============================================================
6. CACHE REFRESH + RUNTIME ROUTE RECERTIFICATION
============================================================

   INFO  Compiled views cleared successfully.  


   INFO  Route cache cleared successfully.  

ROUTE_CACHE_MODE_AFTER=PRESERVED_UNCACHED
VARIANT_WEB_ROUTE_COUNT_AFTER=0

============================================================
7. PHP / BLADE / CMS STATIC SAFETY
============================================================
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/routes/web.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductController.php


   INFO  Blade templates cached successfully.  


   INFO  Compiled views cleared successfully.  

PASS  postoji artisan
PASS  postoji composer.json
PASS  postoji composer.lock
PASS  postoji .env.example
PASS  postoji VERSION
PASS  postoji RELEASE-TAG
PASS  postoji UPGRADE-FROM
PASS  postoji docs/UPGRADE-V2.1-BETA1.md
PASS  postoji docs/UPGRADE-V2.1-BETA1.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA1.2.md
PASS  postoji docs/UPGRADE-V2.1-BETA1.3.md
PASS  postoji docs/UPGRADE-V2.1-BETA2.md
PASS  postoji docs/UPGRADE-V2.1-BETA3.md
PASS  postoji docs/UPGRADE-V2.1-BETA3.2.md
PASS  postoji docs/UPGRADE-V2.1-BETA3.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA4.md
PASS  postoji docs/UPGRADE-V2.1-BETA5.md
PASS  postoji docs/UPGRADE-V2.1-BETA6.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.2.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.3.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.4.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.5.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.6.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.7.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.8.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.9.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.10.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.11.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.12.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.13.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.14.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.14.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.15.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.16.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.17.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.17.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.17.2.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.18.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.18.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.19.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.20.md
PASS  postoji DATABASE-MIGRATION-REQUIRED.txt
PASS  postoji app/Models/Order.php
PASS  postoji app/Models/OrderItem.php
PASS  postoji app/Models/OrderDocument.php
PASS  postoji app/Models/OrderCommission.php
PASS  postoji app/Models/CommissionStatusHistory.php
PASS  postoji app/Models/CommissionPaymentBatch.php
PASS  postoji app/Models/OrderInternalNote.php
PASS  postoji app/Models/OrderAssignment.php
PASS  postoji app/Models/OrderStatusHistory.php
PASS  postoji app/Models/StockMovement.php
PASS  postoji app/Models/IdempotencyKey.php
PASS  postoji app/Models/OrderPayment.php
PASS  postoji app/Models/OrderDelivery.php
PASS  postoji app/Models/AfterSalesCase.php
PASS  postoji app/Models/AfterSalesCaseItem.php
PASS  postoji app/Models/AfterSalesMessage.php
PASS  postoji app/Models/AfterSalesAttachment.php
PASS  postoji app/Models/AfterSalesStatusHistory.php
PASS  postoji app/Models/AfterSalesAction.php
PASS  postoji app/Models/AfterSalesActionItem.php
PASS  postoji app/Models/FieldServiceTeam.php
PASS  postoji app/Models/FieldWorkOrder.php
PASS  postoji app/Models/FieldWorkOrderAttachment.php
PASS  postoji app/Models/ServicePartSupplier.php
PASS  postoji app/Models/ServicePart.php
PASS  postoji app/Models/FieldWorkOrderPart.php
PASS  postoji app/Models/ServicePartMovement.php
PASS  postoji app/Models/ServicePartPurchaseRequest.php
PASS  postoji app/Models/ServicePartPurchaseRequestItem.php
PASS  postoji app/Models/WarrantyRule.php
PASS  postoji app/Models/ProductWarranty.php
PASS  postoji app/Models/WarrantyMaintenanceRecord.php
PASS  postoji app/Models/OrderEmailOutbox.php
PASS  postoji app/Models/StockReceipt.php
PASS  postoji app/Models/StockReceiptItem.php
PASS  postoji app/Models/InventoryCount.php
PASS  postoji app/Models/InventoryCountItem.php
PASS  postoji app/Models/AutomationRun.php
PASS  postoji app/Models/OperationalAlert.php
PASS  postoji app/Models/NotificationPreference.php
PASS  postoji app/Models/BackupRun.php
PASS  postoji app/Models/SystemHealthSnapshot.php
PASS  postoji app/Models/SystemRuntimeState.php
PASS  postoji app/Models/SecurityEvent.php
PASS  postoji app/Services/OrderService.php
PASS  postoji app/Services/OrderWorkflowService.php
PASS  postoji app/Services/InventoryService.php
PASS  postoji app/Services/IdempotencyService.php
PASS  postoji app/Services/OrderPaymentService.php
PASS  postoji app/Services/IpsPaymentPayloadService.php
PASS  postoji app/Services/AdvancedInventoryService.php
PASS  postoji app/Services/LegacyReadOnlyGuard.php
PASS  postoji app/Services/OrderAccessService.php
PASS  postoji app/Services/OrderReportService.php
PASS  postoji app/Services/CommissionReportService.php
PASS  postoji app/Services/CommissionWorkflowService.php
PASS  postoji app/Services/OrderOperationalService.php
PASS  postoji app/Services/OrderTimelineService.php
PASS  postoji app/Services/OperationalNotificationService.php
PASS  postoji app/Notifications/OperationalNotification.php
PASS  postoji app/Services/OperationalAutomationService.php
PASS  postoji app/Services/AutomationReadinessService.php
PASS  postoji app/Services/BackupService.php
PASS  postoji app/Services/SystemHealthService.php
PASS  postoji app/Services/SecurityEventLogger.php
PASS  postoji app/Services/SensitiveDataSanitizer.php
PASS  postoji app/Services/OrderIndexService.php
PASS  postoji app/Services/OrderDetailService.php
PASS  postoji app/Services/OrderDetailPresenter.php
PASS  postoji app/Support/ViewValue.php
PASS  postoji app/Services/OrderDocumentService.php
PASS  postoji app/Services/AfterSalesAccessService.php
PASS  postoji app/Services/AfterSalesCaseService.php
PASS  postoji app/Services/AfterSalesActionService.php
PASS  postoji app/Services/FieldWorkOrderPlanner.php
PASS  postoji app/Services/FieldOperationsService.php
PASS  postoji app/Services/ServicePartsInventoryService.php
PASS  postoji app/Services/WarrantyService.php
PASS  postoji app/Services/OrderEmailOutboxService.php
PASS  postoji app/Services/OrderEmailDispatcher.php
PASS  postoji app/Services/NbsIpsQrService.php
PASS  postoji app/Services/DocumentNumberService.php
PASS  postoji app/Services/Pdf/SimplePdfWriter.php
PASS  postoji app/Services/Pdf/BusinessDocumentPdfService.php
PASS  postoji app/Services/Pdf/WarrantyCertificatePdfService.php
PASS  postoji app/Http/Requests/StoreOrderRequest.php
PASS  postoji app/Http/Requests/AdjustStockRequest.php
PASS  postoji app/Http/Requests/StoreAfterSalesCaseRequest.php
PASS  postoji app/Http/Requests/StoreAfterSalesMessageRequest.php
PASS  postoji app/Http/Requests/UpdateAfterSalesCaseRequest.php
PASS  postoji app/Http/Requests/StoreAfterSalesActionRequest.php
PASS  postoji app/Http/Requests/CompleteAfterSalesActionRequest.php
PASS  postoji app/Http/Requests/CancelAfterSalesActionRequest.php
PASS  postoji app/Http/Requests/StoreFieldServiceTeamRequest.php
PASS  postoji app/Http/Requests/UpdateFieldServiceTeamRequest.php
PASS  postoji app/Http/Requests/ScheduleFieldWorkOrderRequest.php
PASS  postoji app/Http/Requests/CompleteFieldWorkOrderRequest.php
PASS  postoji app/Http/Requests/CancelFieldWorkOrderRequest.php
PASS  postoji app/Http/Requests/StoreServicePartRequest.php
PASS  postoji app/Http/Requests/UpdateServicePartRequest.php
PASS  postoji app/Http/Requests/AdjustServicePartStockRequest.php
PASS  postoji app/Http/Requests/StoreServicePartSupplierRequest.php
PASS  postoji app/Http/Requests/UpdateServicePartSupplierRequest.php
PASS  postoji app/Http/Requests/StoreFieldWorkOrderPartRequest.php
PASS  postoji app/Http/Requests/StoreServicePartPurchaseRequest.php
PASS  postoji app/Http/Requests/CancelServicePartPurchaseRequest.php
PASS  postoji app/Http/Requests/StoreWarrantyRuleRequest.php
PASS  postoji app/Http/Requests/UpdateProductWarrantyRequest.php
PASS  postoji app/Http/Requests/ScheduleWarrantyMaintenanceRequest.php
PASS  postoji app/Http/Requests/CompleteWarrantyMaintenanceRequest.php
PASS  postoji app/Http/Controllers/OrderController.php
PASS  postoji app/Http/Controllers/WarrantyController.php
PASS  postoji app/Http/Controllers/AfterSalesController.php
PASS  postoji app/Http/Controllers/AfterSalesAttachmentController.php
PASS  postoji app/Http/Controllers/FieldWorkOrderAttachmentController.php
PASS  postoji app/Http/Controllers/CommissionController.php
PASS  postoji app/Http/Controllers/NotificationController.php
PASS  postoji app/Http/Controllers/Api/V1/OrderController.php
PASS  postoji app/Http/Controllers/Admin/OrderController.php
PASS  postoji app/Http/Controllers/Admin/AfterSalesController.php
PASS  postoji app/Http/Controllers/Admin/AfterSalesActionController.php
PASS  postoji app/Http/Controllers/Admin/FieldOperationsController.php
PASS  postoji app/Http/Controllers/Admin/FieldServiceTeamController.php
PASS  postoji app/Http/Controllers/Admin/ServicePartController.php
PASS  postoji app/Http/Controllers/Admin/ServicePartSupplierController.php
PASS  postoji app/Http/Controllers/Admin/FieldWorkOrderPartController.php
PASS  postoji app/Http/Controllers/Admin/ServicePartPurchaseRequestController.php
PASS  postoji app/Http/Controllers/Admin/WarrantyController.php
PASS  postoji app/Http/Controllers/Admin/CommissionController.php
PASS  postoji app/Http/Controllers/Admin/StockAdjustmentController.php
PASS  postoji app/Http/Controllers/OrderDocumentController.php
PASS  postoji app/Http/Controllers/Admin/OrderDocumentController.php
PASS  postoji app/Http/Controllers/Admin/ReportController.php
PASS  postoji app/Http/Controllers/Admin/DocumentSettingsController.php
PASS  postoji app/Http/Controllers/OrderPaymentController.php
PASS  postoji app/Http/Controllers/OrderDeliveryController.php
PASS  postoji app/Http/Controllers/Admin/PaymentController.php
PASS  postoji app/Http/Controllers/Admin/InventoryController.php
PASS  postoji app/Http/Controllers/Admin/AutomationController.php
PASS  postoji app/Http/Controllers/Admin/SystemHealthController.php
PASS  postoji app/Http/Controllers/Admin/TurnstileSettingsController.php
PASS  postoji app/Http/Controllers/Admin/OrderEmailSettingsController.php
PASS  postoji app/Http/Resources/OrderResource.php
PASS  postoji app/Console/Commands/OrdersDoctorCommand.php
PASS  postoji app/Console/Commands/OrderCreateDoctorCommand.php
PASS  postoji app/Console/Commands/CatalogOwnershipDoctorCommand.php
PASS  postoji app/Console/Commands/DetailPagesDoctorCommand.php
PASS  postoji app/Console/Commands/ReportsDoctorCommand.php
PASS  postoji app/Console/Commands/OperationsDoctorCommand.php
PASS  postoji app/Console/Commands/PaymentsInventoryDoctorCommand.php
PASS  postoji app/Console/Commands/RunOperationalAutomationCommand.php
PASS  postoji app/Console/Commands/AutomationDoctorCommand.php
PASS  postoji app/Console/Commands/CreateBackupCommand.php
PASS  postoji app/Console/Commands/BackupDoctorCommand.php
PASS  postoji app/Console/Commands/SystemHealthCommand.php
PASS  postoji app/Console/Commands/SchedulerHeartbeatCommand.php
PASS  postoji app/Console/Commands/TestDatabaseDoctorCommand.php
PASS  postoji app/Console/Commands/AfterSalesDoctorCommand.php
PASS  postoji app/Console/Commands/FieldOperationsDoctorCommand.php
PASS  postoji app/Console/Commands/ServicePartsDoctorCommand.php
PASS  postoji app/Console/Commands/WarrantiesDoctorCommand.php
PASS  postoji app/Console/Commands/WarrantiesBackfillCommand.php
PASS  postoji app/Console/Commands/OrderEmailDispatchCommand.php
PASS  postoji app/Console/Commands/OrderEmailsDoctorCommand.php
PASS  postoji database/migrations/2026_07_22_000006_enable_production_orders_inventory.php
PASS  postoji database/migrations/2026_07_23_000007_repair_production_schema_beta5.php
PASS  postoji database/migrations/2026_07_23_000008_repair_authenticated_runtime_beta6.php
PASS  postoji database/migrations/2026_07_23_000009_create_reports_documents_and_supplier_assignment.php
PASS  postoji database/migrations/2026_07_23_000010_repair_reports_schema_beta1_2.php
PASS  postoji database/migrations/2026_07_23_000011_create_operational_orders_commissions_beta2.php
PASS  postoji database/migrations/2026_07_23_000012_create_payments_advanced_inventory_beta3.php
PASS  postoji database/migrations/2026_07_23_000013_create_automation_alerts_beta4.php
PASS  postoji database/migrations/2026_07_23_000014_create_security_backup_health_beta6.php
PASS  postoji database/migrations/2026_07_29_000015_repair_order_documents_and_payments_beta7_5.php
PASS  postoji database/migrations/2026_07_30_000016_add_order_completion_beta7_7.php
PASS  postoji database/migrations/2026_07_30_000017_add_delivery_workflow_beta7_8.php
PASS  postoji database/migrations/2026_07_30_000018_fix_delivery_note_document_type_beta7_9.php
PASS  postoji database/migrations/2026_07_30_000019_create_after_sales_cases_beta7_10.php
PASS  postoji database/migrations/2026_07_30_000020_create_after_sales_actions_beta7_11.php
PASS  postoji database/migrations/2026_07_30_000021_create_field_operations_beta7_12.php
PASS  postoji database/migrations/2026_07_30_000022_create_service_parts_procurement_beta7_13.php
PASS  postoji database/migrations/2026_07_30_000023_enable_document_revisions_beta7_14.php
PASS  postoji database/migrations/2026_07_30_000024_create_warranties_preventive_maintenance_beta7_15.php
PASS  postoji database/migrations/2026_07_30_000025_create_order_email_outbox_beta7_16.php
PASS  postoji resources/views/orders/index.blade.php
PASS  postoji resources/views/admin/orders/show.blade.php
PASS  postoji resources/views/commissions/index.blade.php
PASS  postoji resources/views/notifications/index.blade.php
PASS  postoji resources/views/admin/commissions/index.blade.php
PASS  postoji resources/views/orders/create.blade.php
PASS  postoji resources/views/orders/show.blade.php
PASS  postoji resources/views/admin/reports/index.blade.php
PASS  postoji resources/views/admin/settings/documents.blade.php
PASS  postoji resources/views/admin/inventory/index.blade.php
PASS  postoji resources/views/admin/orders/partials/payments.blade.php
PASS  postoji resources/views/orders/partials/payments.blade.php
PASS  postoji resources/views/after-sales/index.blade.php
PASS  postoji resources/views/after-sales/create.blade.php
PASS  postoji resources/views/after-sales/show.blade.php
PASS  postoji resources/views/admin/after-sales/index.blade.php
PASS  postoji resources/views/admin/after-sales/show.blade.php
PASS  postoji resources/views/admin/field-operations/index.blade.php
PASS  postoji resources/views/admin/field-operations/show.blade.php
PASS  postoji resources/views/admin/field-operations/teams.blade.php
PASS  postoji resources/views/admin/service-parts/index.blade.php
PASS  postoji resources/views/admin/service-parts/suppliers.blade.php
PASS  postoji resources/views/admin/service-parts/purchase-requests.blade.php
PASS  postoji resources/views/admin/service-parts/purchase-show.blade.php
PASS  postoji resources/views/admin/settings/automation.blade.php
PASS  postoji resources/views/admin/settings/system-health.blade.php
PASS  postoji resources/views/admin/settings/turnstile.blade.php
PASS  postoji resources/views/admin/settings/order-emails.blade.php
PASS  postoji resources/views/emails/order-events.blade.php
PASS  postoji tests/Feature/AdminOrdersImageRotationTest.php
PASS  postoji tests/Feature/OperationalOrdersCommissionsTest.php
PASS  postoji tests/Feature/OperationalAutomationTest.php
PASS  postoji tests/Feature/PaymentsAdvancedInventoryTest.php
PASS  postoji tests/Feature/OrderDeliveryWorkflowTest.php
PASS  postoji tests/Feature/AfterSalesWorkflowTest.php
PASS  postoji tests/Feature/AfterSalesActionExecutionTest.php
PASS  postoji tests/Feature/FieldOperationsWorkflowTest.php
PASS  postoji tests/Feature/ServicePartsWorkflowTest.php
PASS  postoji tests/Feature/InventoryWorkspaceUiTest.php
PASS  postoji tests/Feature/SecurityHealthBackupTest.php
PASS  postoji tests/Feature/MySqlTestDatabaseSafetyTest.php
PASS  postoji tests/Unit/SensitiveDataSanitizerTest.php
PASS  postoji tests/Feature/ProductionOrderInventoryTest.php
PASS  postoji tests/Feature/InventoryAdjustmentTest.php
PASS  postoji tests/Feature/ProductionPermissionsTest.php
PASS  postoji tests/Feature/DashboardLegacyDesignTest.php
PASS  postoji tests/Feature/ReportsDocumentsSupplierTest.php
PASS  postoji tests/Fixtures/pdf-logo.jpg
PASS  postoji tests/Unit/BusinessDocumentPdfServiceTest.php
PASS  postoji tests/Unit/DeliveryNoteMigrationContractTest.php
PASS  postoji tests/Unit/DocumentRevisionMigrationContractTest.php
PASS  postoji tests/Unit/CommissionReportPdfServiceTest.php
PASS  postoji tests/Feature/OrderEmailsIpsWarrantyTest.php
PASS  postoji tests/Unit/OrderEmailIpsMigrationContractTest.php
PASS  postoji tests/Unit/ReceivablesPermissionMigrationContractTest.php
PASS  postoji tests/Unit/LegacyReadOnlyGuardTest.php
PASS  postoji tests/Unit/OrderDetailPresenterTest.php
PASS  postoji tests/Unit/ViewValueTest.php
PASS  postoji tests/Feature/CatalogDetailPageTest.php
PASS  postoji tests/Feature/LoginDashboardFallbackTest.php
PASS  postoji resources/views/components/icon.blade.php
PASS  postoji app/Http/Middleware/EnsureRuntimeDirectories.php
PASS  postoji app/Http/Middleware/AttachRequestId.php
PASS  postoji app/Http/Middleware/SecurityHeaders.php
PASS  postoji app/Console/Commands/AuthDoctorCommand.php
PASS  postoji .env.testing.mysql.example
PASS  postoji phpunit.mysql.xml
PASS  postoji bin/php-lint.php
PASS  postoji bin/autoload-check.php
PASS  postoji bin/pdf-smoke.php
PASS  postoji bin/delivery-note-smoke.php
PASS  postoji bin/warranty-pdf-smoke.php
PASS  postoji bin/ips-qr-pdf-smoke.php
PASS  postoji storage/framework/cache/data/.gitignore
PASS  postoji storage/framework/sessions/.gitignore
PASS  postoji storage/framework/views/.gitignore
PASS  postoji storage/logs/.gitignore
PASS  postoji storage/app/backups/.gitignore
PASS  postoji config/backup.php
PASS  postoji docs/UPGRADE-V2.1-BETA7.23.md
PASS  postoji docs/RELEASE-CHECK.md
PASS  postoji app/Console/Commands/ReleaseCheckCommand.php
PASS  postoji config/release.php
PASS  postoji bin/release-check-smoke.php
PASS  postoji tests/Unit/ReleaseCheckContractTest.php
PASS  postoji tests/Feature/ReleaseCheckCommandTest.php
PASS  postoji storage/app/release-check/.gitignore
PASS  postoji docs/UPGRADE-V2.1-BETA7.22.1.md
PASS  postoji bin/theme-css-smoke.php
PASS  postoji docs/UPGRADE-V2.1-BETA7.21.md
PASS  postoji database/migrations/2026_07_31_000030_create_management_reports_beta7_21.php
PASS  postoji app/Models/ReportSchedule.php
PASS  postoji app/Models/ReportDelivery.php
PASS  postoji app/Services/ManagementReportService.php
PASS  postoji app/Services/ReportScheduleService.php
PASS  postoji app/Services/Pdf/ManagementReportPdfService.php
PASS  postoji app/Http/Controllers/Admin/ManagementReportController.php
PASS  postoji app/Http/Controllers/Admin/ReportScheduleController.php
PASS  postoji app/Console/Commands/ManagementReportsDoctorCommand.php
PASS  postoji app/Console/Commands/OrderCostSnapshotsCommand.php
PASS  postoji app/Services/OrderItemCostSnapshotService.php
PASS  postoji app/Console/Commands/ManagementReportsDispatchCommand.php
PASS  postoji resources/views/admin/reports/management.blade.php
PASS  postoji resources/views/emails/management-report.blade.php
PASS  postoji tests/Feature/ManagementReportsProfitabilityTest.php
PASS  postoji tests/Unit/OrderCostSnapshotRepairContractTest.php
PASS  postoji bin/management-report-smoke.php
PASS  postoji bin/order-cost-snapshot-smoke.php
PASS  postoji docs/UPGRADE-V2.1-BETA7.19.md
PASS  postoji database/migrations/2026_07_31_000028_create_smart_product_management_beta7_19.php
PASS  postoji app/Services/ProductTemplateService.php
PASS  postoji app/Services/ProductCompletenessService.php
PASS  postoji app/Services/ProductBulkService.php
PASS  postoji app/Http/Controllers/Admin/ProductBulkController.php
PASS  postoji app/Console/Commands/SmartProductsDoctorCommand.php
PASS  postoji resources/views/admin/products/clone.blade.php
PASS  postoji resources/views/admin/products/bulk.blade.php
PASS  postoji tests/Unit/SmartProductManagementMigrationContractTest.php
PASS  postoji tests/Unit/SmartProductManagementUiContractTest.php
PASS  postoji tests/Feature/SmartProductManagementTest.php
PASS  postoji bin/smart-product-smoke.php
PASS  postoji docs/UPGRADE-V2.1-BETA7.18.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.19.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.23.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.23.2.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.24.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.24.1.md
PASS  postoji docs/UPGRADE-V2.1-RC1.md
PASS  postoji docs/RC-OPERATIONS.md
PASS  postoji docs/UPGRADE-V2.1-STABLE.md
PASS  postoji docs/UPGRADE-V2.1.1.md
PASS  postoji docs/UPGRADE-V2.1.2.md
PASS  postoji docs/UPGRADE-V2.1.3.md
PASS  postoji docs/UPGRADE-V2.1.3.1.md
PASS  postoji docs/UPGRADE-V2.1.3.2.md
PASS  postoji docs/UPGRADE-V2.1.3.3.md
PASS  postoji docs/STABLE-OPERATIONS.md
PASS  postoji docs/BACKUP-RESTORE-DRILL.md
PASS  postoji bin/rc-hardening-smoke.php
PASS  postoji bin/stable-hardening-smoke.php
PASS  postoji bin/stable-maintenance-smoke.php
PASS  postoji bin/product-media-ux-smoke.php
PASS  postoji bin/product-announcement-smoke.php
PASS  postoji bin/catalog-settings-product-data-smoke.php
PASS  postoji bin/catalog-settings-integrity-hotfix-smoke.php
PASS  postoji bin/product-save-regex-hotfix-smoke.php
PASS  postoji bin/storage-capacity-total-smoke.php
PASS  postoji tests/Unit/ProductSaveRegexHotfixContractTest.php
PASS  postoji tests/Unit/StorageCapacityTotalContractTest.php
PASS  postoji tests/Unit/CatalogSettingsProductDataContractTest.php
PASS  postoji tests/Unit/CatalogSettingsIntegrityHotfixContractTest.php
PASS  postoji app/Console/Commands/CatalogSettingsDoctorCommand.php
PASS  postoji app/Services/ProductTypeCategoryService.php
PASS  postoji app/Services/SpecificationFieldLifecycleService.php
PASS  postoji app/Services/StorageSpecificationService.php
PASS  postoji database/migrations/2026_08_04_000033_create_catalog_type_layout_v2_1_3.php
PASS  postoji database/migrations/2026_08_04_000034_repair_catalog_category_and_spec_integrity_v2_1_3_1.php
PASS  postoji database/migrations/2026_08_04_000035_link_storage_components_and_total_capacity_v2_1_3_3.php
PASS  postoji public/assets/js/dictionary-sort-manager.js
PASS  postoji resources/views/admin/dictionary/product-type.blade.php
PASS  postoji tests/Unit/ProductMediaUxContractTest.php
PASS  postoji tests/Unit/ProductAnnouncementContractTest.php
PASS  postoji app/Services/ProductAnnouncementService.php
PASS  postoji tests/Unit/ReleaseCandidateHardeningContractTest.php
PASS  postoji tests/Unit/StableReleaseContractTest.php
PASS  postoji tests/Unit/StableMaintenanceContractTest.php
PASS  postoji app/Console/Commands/SecurityHardeningDoctorCommand.php
PASS  postoji app/Console/Commands/MigrationsDoctorCommand.php
PASS  postoji app/Console/Commands/AccessControlDoctorCommand.php
PASS  postoji app/Console/Commands/ReleaseIntegrityCommand.php
PASS  postoji app/Console/Commands/BackupVerifyCommand.php
PASS  postoji app/Console/Commands/ProductMediaDoctorCommand.php
PASS  postoji app/Http/Controllers/ProductMediaDownloadController.php
PASS  postoji public/assets/js/product-media-manager.js
PASS  postoji resources/views/admin/products/partials/image-card.blade.php
PASS  postoji resources/views/admin/products/partials/image-upload.blade.php
PASS  postoji bin/catalog-detail-smoke.php
PASS  postoji bin/detail-pages-doctor-smoke.php
PASS  postoji tests/Unit/CatalogDetailBladeContractTest.php
PASS  postoji tests/Unit/SystemHealthRemediationContractTest.php
PASS  postoji tests/Unit/DetailPagesDoctorContractTest.php
PASS  postoji database/migrations/2026_07_31_000027_create_correlated_specifications_beta7_18.php
PASS  postoji app/Models/SpecificationOption.php
PASS  postoji app/Services/SpecificationDependencyService.php
PASS  postoji app/Services/CatalogSpecificationFilterService.php
PASS  postoji app/Console/Commands/CatalogCorrelationsDoctorCommand.php
PASS  postoji resources/views/partials/correlated-specification-filters.blade.php
PASS  postoji resources/views/partials/correlated-specification-filter-script.blade.php
PASS  postoji tests/Unit/CorrelatedSpecificationsMigrationContractTest.php
PASS  postoji tests/Unit/CorrelatedSpecificationUiContractTest.php
PASS  postoji docs/UPGRADE-V2.1.4.md
PASS  postoji bin/cms-v2.1.4-smoke.php
PASS  postoji tests/Unit/CmsV214ContractTest.php
PASS  postoji app/Console/Commands/CmsV214DoctorCommand.php
PASS  postoji app/Services/ProductDeletionService.php
PASS  postoji database/migrations/2026_08_04_000036_add_product_model_and_name_templates_v2_1_4.php
PASS  postoji docs/UPGRADE-V2.1.4.1.md
PASS  postoji bin/product-type-page-render-hotfix-smoke.php
PASS  postoji tests/Unit/ProductTypePageRenderHotfixContractTest.php
PASS  postoji docs/UPGRADE-V2.1.5.md
PASS  postoji bin/cms-v2.1.5-smoke.php
PASS  postoji tests/Unit/CmsV215ContractTest.php
PASS  postoji app/Console/Commands/CmsV215DoctorCommand.php
PASS  postoji public/assets/js/ux-runtime.js
PASS  postoji resources/views/errors/minimal.blade.php
PASS  postoji resources/views/errors/403.blade.php
PASS  postoji resources/views/errors/404.blade.php
PASS  postoji resources/views/errors/419.blade.php
PASS  postoji resources/views/errors/429.blade.php
PASS  postoji resources/views/errors/500.blade.php
PASS  postoji resources/views/errors/503.blade.php
PASS  postoji database/migrations/2026_08_05_000037_place_desktop_power_supply_field_v2_1_5.php
PASS  postoji docs/UPGRADE-V2.2.0.md
PASS  postoji docs/openapi.yaml
PASS  postoji bin/cms-v2.2.0-smoke.php
PASS  postoji tests/Feature/MobileApiFoundationTest.php
PASS  postoji tests/Unit/MobileApiFoundationContractTest.php
PASS  postoji app/Console/Commands/CmsV220DoctorCommand.php
PASS  postoji app/Http/Controllers/Api/V1/BootstrapController.php
PASS  postoji app/Http/Controllers/Api/V1/MobileDeviceController.php
PASS  postoji app/Models/MobileDevice.php
PASS  postoji database/migrations/2026_08_06_000039_create_mobile_devices_v2_2_0.php
PASS  postoji database/migrations/2026_08_06_000040_add_push_notification_preference_v2_2_0.php
PASS  postoji database/migrations/2026_08_06_000041_create_database_queue_tables_v2_2_0.php
PASS  verzija je 2.2.0 Mobile API Foundation
PASS  release tag je v2.2.0
PASS  upgrade osnova je v2.1.6
PASS  composer.json validan
PASS  PHP minimum 8.4
PASS  Laravel 13
PASS  Composer lint/autoload/test/release skripte postoje
PASS  runtime verzija je 2.2.0
PASS  migracija sadrži idempotency_keys
PASS  migracija sadrži source_system
PASS  migracija sadrži inventory_state
PASS  migracija sadrži inventory_returned_at
PASS  migracija sadrži event_key
PASS  migracija sadrži orders_user_idempotency_unique
PASS  porudžbina zaključava proizvode
PASS  porudžbina umanjuje lager u transakciji
PASS  povrat lagera ima jedinstveni event key
PASS  idempotency koristi unique zapis i row lock
PASS  legacy porudžbine su blokirane
PASS  legacy SQL guard je registrovan pre izvršavanja
PASS  legacy MySQL sesija je READ ONLY
PASS  Redis je uklonjen iz database konfiguracije
PASS  Redis je uklonjen iz cache konfiguracije
PASS  Redis je uklonjen iz queue konfiguracije
PASS  login rate limiter koristi file store
PASS  dozvola orders.create
PASS  dozvola orders.view_own
PASS  dozvola orders.cancel_own
PASS  dozvola orders.manage
PASS  dozvola stock.view
PASS  dozvola stock.adjust
PASS  dozvola reports.view
PASS  dozvola reports.export
PASS  dozvola invoices.manage
PASS  dozvola invoices.view_own
PASS  web ruta orders.store
PASS  web ruta orders.cancel
PASS  web ruta admin.orders.status
PASS  web ruta admin.orders.payment
PASS  web ruta admin.orders.tracking
PASS  web ruta admin.stock.adjust
PASS  API porudžbine postoje
PASS  porudžbina ima dodeljenog SuperAdmin/Admin dobavljača
PASS  admin scope vidi samo njemu dodeljene porudžbine
PASS  izveštaji podržavaju filtere i CSV/PDF
PASS  poslovni dokumenti koriste nepromenljivi snapshot
PASS  PDF renderer je lokalni i bez Redis/eksternog servisa
PASS  brojevi dokumenata su transakcioni i jedinstveni
PASS  web rute imaju reports CSV/PDF i dokumente
PASS  reports stranica ima schema fallback umesto 500
PASS  reports render je unutar zaštićenog controller toka
PASS  reports view ima render marker i bezbedne URL-ove
PASS  reports export vraća kontrolisani 503
PASS  reports doctor izvršava repair i stvarne SQL upite
PASS  reports doctor renderuje controller Blade i layout
PASS  reports logging je best-effort
PASS  beta1.2 repair migracija je nedestruktivna
PASS  hamburger dugme postoji
PASS  mobilni meni ima kontrolni JavaScript
PASS  mobilni meni nema horizontalni scroll
PASS  direktne mobilne stavke koriste zajednički levi wrapper
PASS  Početna Provizije i Izveštaji su poravnati ulevo
PASS  CSS ima pouzdan cache busting
PASS  legacy desktop header ima dva reda
PASS  legacy mobilni header zadržava kurs temu nalog i hamburger
PASS  dashboard ima moderni hero KPI prioritete i module
PASS  dashboard CSS ima 4 desktop i 2 mobilne kolone
PASS  admin gridovi su poravnati na vrh
PASS  forme koriste sadržajnu visinu
PASS  deployment check ima bezbedan repair režim
PASS  deployment check razlikuje runtime zaštitu i grant warning
PASS  deployment check proverava i operativne kolone
PASS  dashboard koristi DB fallback umesto 500
PASS  login telemetry je best-effort
PASS  login hvata session i remember-token probleme
PASS  authenticated layout nema direktan SettingsService upit
PASS  authenticated layout koristi bezbedne user helper metode
PASS  dashboard logging ne može da obori fallback
PASS  runtime middleware prethodi session/cache middleware-u
PASS  deployment repair kreira runtime direktorijume i kompajlira Blade
PASS  auth doctor može da renderuje kompletan dashboard
PASS  Turnstile hvata sve transportne/JSON greške
PASS  Turnstile podešavanja imaju DB prioritet i env fallback
PASS  Turnstile secret se čuva šifrovano i ne izlaže kroz all
PASS  Turnstile admin ekran i ruta postoje
PASS  beta6 repair migracija popravlja core login šemu
PASS  static check razdvaja runtime i ZIP režim
PASS  operativna migracija sadrži order_internal_notes
PASS  operativna migracija sadrži order_assignments
PASS  operativna migracija sadrži commission_payment_batches
PASS  operativna migracija sadrži notifications
PASS  operativna migracija sadrži payment_batch_id
PASS  operativna migracija sadrži status_updated_at
PASS  operativna migracija sadrži last_internal_note_at
PASS  beta2 dozvola commissions.view_own
PASS  beta2 dozvola orders.reassign
PASS  beta2 dozvola orders.internal_notes
PASS  beta2 dozvola notifications.view
PASS  provizije imaju odobravanje isplatu storniranje i istoriju
PASS  masovna isplata koristi transakciju row lock i batch
PASS  korisnik vidi samo svoje provizije i minimum 20 EUR
PASS  interne napomene nisu u javnom timeline-u
PASS  ponovna dodela je ograničena na SuperAdministratora
PASS  preuzimanje i rokovi porudžbine imaju audit i obaveštenja
PASS  database notifikacije su neblokirajuće i mail je opcioni
PASS  operativni doctor proverava šemu SQL i render
PASS  admin provizije imaju filtere CSV PDF i masovnu isplatu
PASS  commission tabela nema unutrašnji vertikalni scroll pri obradi
PASS  obrada provizije koristi veliki viewport modal
PASS  commission modal ima naslov i eksplicitno zatvaranje
PASS  otvaranje commission modala zatvara prethodni
PASS  porudžbina ima timeline interne napomene preuzimanje rokove i reassignment UI
PASS  inbox obaveštenja podržava read i read-all
PASS  operativni feature testovi postoje
PASS  commission modal regresioni feature test postoji
PASS  beta3.1 migracija nema globalni use Throwable
PASS  beta3.1 migracija koristi potpuno kvalifikovani Throwable
PASS  PHP lint odbija warning deprecated i notice izlaz
PASS  beta3 migracija sadrži order_payments
PASS  beta3 migracija sadrži stock_receipts
PASS  beta3 migracija sadrži stock_receipt_items
PASS  beta3 migracija sadrži inventory_counts
PASS  beta3 migracija sadrži inventory_count_items
PASS  beta3 migracija sadrži payment_state
PASS  beta3 migracija sadrži paid_total_rsd
PASS  beta3 migracija sadrži payment_due_at
PASS  beta3 dozvola payments.manage
PASS  beta3 dozvola payments.upload_proof
PASS  beta3 dozvola payments.view_own
PASS  beta3 dozvola inventory.receive
PASS  beta3 dozvola inventory.count
PASS  beta3 dozvola inventory.export
PASS  uplate koriste transakciju row lock audit i saldo
PASS  potvrde uplate su privatne i autorizovane
PASS  IPS podaci koriste snapshot porudžbine
PASS  predračun i račun postavljaju dospeće porudžbine
PASS  ulaz robe i popis koriste idempotency transakciju i row lock
PASS  napredni lager ima readiness fallback umesto 500
PASS  reports beta3 sažeci i izvozi su zaštićeni
PASS  beta3 doctor proverava repair SQL i render
PASS  beta3 feature testovi pokrivaju uplate ulaz i popis
PASS  beta7.5 repair migracija obnavlja PDF i payment šemu
PASS  beta7.5 repair migracija je nedestruktivna
PASS  beta7.5 potvrda koristi site name fallback
PASS  beta7.5 ručno evidentiranje uplate ima regresioni test
PASS  beta7.5 doctor proverava dokument i payment tabele
PASS  beta7.6 PDF dozvoljava lokalno uvezene porudžbine
PASS  beta7.6 uplate dozvoljavaju lokalno uvezene porudžbine
PASS  beta7.6 legacy lager zaštita ostaje aktivna
PASS  beta7.7 migracija dodaje terminalno stanje porudžbine
PASS  beta7.7 PDF podešavanja imaju upload pregled i uklanjanje logotipa
PASS  beta7.7 PDF logo se ugrađuje kao lokalni JPEG
PASS  beta7.7 PDF ne prikazuje subagent email kupca
PASS  beta7.7 kompletiranje COD porudžbine evidentira preostali saldo
PASS  beta7.7 kompletirana porudžbina zaključava dalje izmene
PASS  beta7.7 kompletiranje je jasno dostupno u detalju i listi
PASS  beta7.8 migracija dodaje evidenciju isporuke i reopening stanje
PASS  beta7.8 kompletiranje čuva dokaz isporuke privatno
PASS  beta7.8 otpremnica koristi OTP broj i delivery snapshot
PASS  beta7.8 ponovno otvaranje je superadmin-only i auditovano
PASS  beta7.8 detalj prikazuje strukturiranu evidenciju isporuke
PASS  beta7.8 doctor proverava novu šemu i dozvole
PASS  beta7.8 feature testovi pokrivaju dokaz otpremnicu i reopening
PASS  beta7.8 UI ima delivery workflow responsive stilove
PASS  beta7.9 migracija uklanja legacy ENUM blokadu za delivery_note
PASS  beta7.9 servis radi schema preflight pre izdavanja otpremnice
PASS  beta7.9 pomoćni notification kvar ne obara izdat dokument, a IPS važi samo za finansijske dokumente
PASS  beta7.9 kontroleri vraćaju incident poruku umesto Error 500
PASS  beta7.9 doctor proverava stvarni MySQL tip dokumenta
PASS  beta7.9 ima migration contract i delivery note PDF smoke test
PASS  detail koristi eksplicitan slug upit
PASS  slug upit primenjuje objedinjeni visibility scope
PASS  API detail koristi isti slug upit
PASS  neispravna slika ne obara detail
PASS  detail filtrira slike bez validnog URL-a
PASS  katalog generiše eksplicitan slug link
PASS  detail ima interaktivnu thumbnail galeriju
PASS  detail ima fullscreen lightbox i zoom kontrole
PASS  gallery podržava tastaturu swipe i preload
PASS  gallery radi i sa jednom slikom
PASS  gallery CSS ima fullscreen viewport i responsive mobile
PASS  gallery feature testovi postoje
PASS  beta4 migracija sadrži automation_runs
PASS  beta4 migracija sadrži operational_alerts
PASS  beta4 migracija sadrži notification_preferences
PASS  beta4 nema Redis i koristi scheduler/file lock
PASS  beta4 detektuje nepreuzete porudžbine dospele obaveze i nizak lager
PASS  beta4 upozorenja su deduplikovana i razrešavaju se
PASS  notification preferences upravljaju kanalima i kategorijama
PASS  automation settings UI i ručno pokretanje postoje
PASS  automation doctor proverava repair scheduler i run
PASS  beta4 dozvola automation.manage postoji
PASS  beta4 feature testovi pokrivaju deduplikaciju i preference
PASS  beta5 inventory koristi jednu aktivnu operaciju
PASS  beta5 inventory čuva filter i limit nakon knjiženja
PASS  beta5 inventory nema unutrašnji vertikalni scrollbar
PASS  beta5 inventory responsive tabela koristi data-label kartice
PASS  beta5 feature test pokriva inventory workspace
PASS  beta6 migracija sadrži backup_runs
PASS  beta6 migracija sadrži system_health_snapshots
PASS  beta6 migracija sadrži system_runtime_states
PASS  beta6 migracija sadrži security_events
PASS  beta6 migracija sadrži system.health
PASS  beta6 migracija sadrži backups.manage
PASS  beta6 migracija sadrži audit.export
PASS  beta6 migracija sadrži security.view
PASS  beta6 backup koristi mysqldump bez lozinke u argumentima
PASS  beta6 backup odbija public putanju i pravi SHA-256 manifest
PASS  beta6 system health proverava scheduler backup migracije i legacy
PASS  beta6 security header-i i request ID postoje
PASS  beta6 audit koristi rekurzivnu sanitizaciju i request ID
PASS  beta6 rate limiter-i pokrivaju upload export admin i backup
PASS  beta6 test DB doctor ima višestruku zaštitu
PASS  beta6 system health UI i backup akcije postoje
PASS  beta6 scheduler ima heartbeat backup i health snapshot
PASS  beta6 feature i unit testovi postoje
PASS  beta7.1 orders ima readiness SQL i render zaštitu
PASS  beta7.1 orders doctor proverava isti browser render
PASS  beta7.1 orders recovery ne završava generičkim 500
PASS  beta7.1 edit artikla ima rotaciju ulevo i udesno
PASS  beta7.1 legacy rotacija koristi copy-on-write
PASS  beta7.1 rotacija koristi privremeni fajl i kontrolisani Imagick/GD fallback
PASS  beta7.1 feature testovi postoje
PASS  beta7.2 order detail koristi opcioni schema-aware loader
PASS  beta7.2 admin i user detail imaju protected render
PASS  beta7.2 admin i user detail imaju readiness markere
PASS  beta7.2 timeline i IPS ne mogu oboriti detalj
PASS  beta7.2 orders doctor renderuje oba detalja
PASS  beta7.2 detail-pages doctor proverava ključne detail stranice
PASS  beta7.2 feature testovi pokrivaju detail i opcione tabele
PASS  beta7.3 detail koristi scalar presenter umesto Eloquent objekata u Blade-u
PASS  beta7.3 presenter bezbedno obrađuje raw i zero datume
PASS  beta7.3 presenter bezbedno generiše named rute
PASS  beta7.3 detail view nema direktne auth, relation ili datetime pozive
PASS  beta7.3 admin i user detail imaju ne-503 read-only fallback
PASS  beta7.3 orders doctor prikazuje tačan exception uzrok za oba detaila
PASS  beta7.3 orders doctor nastavlja admin i user audit
PASS  beta7.3 payment i inventory Gates su definisani
PASS  beta7.3 presenter i ViewValue regresioni testovi postoje
PASS  beta7.10 migracija sadrži after_sales_cases
PASS  beta7.10 migracija sadrži after_sales_case_items
PASS  beta7.10 migracija sadrži after_sales_messages
PASS  beta7.10 migracija sadrži after_sales_attachments
PASS  beta7.10 migracija sadrži after_sales_status_history
PASS  beta7.10 ima tri postprodajne dozvole
PASS  beta7.10 pristup poštuje vlasnika dodeljenog admina i superadmin scope
PASS  beta7.10 slučaj zahteva isporučenu ili kompletiranu porudžbinu
PASS  beta7.10 čuva pogođene stavke snapshot i SLA rok
PASS  beta7.10 privatni prilozi proveravaju MIME veličinu i autorizaciju
PASS  beta7.10 javne i interne poruke su odvojene
PASS  beta7.10 statusni tok zahteva obrazloženje konačne odluke
PASS  beta7.10 automatizacija upozorava na probijene rokove slučaja
PASS  beta7.10 UI ima korisnički i administratorski postprodajni tok
PASS  beta7.10 doctor proverava šemu dozvole i SQL
PASS  beta7.10 feature test pokriva privatni prilog i obradu
PASS  beta7.10 privatni download zabranjuje browser cache
PASS  beta7.10 konkurentno zatvaranje ne propušta novu poruku
PASS  beta7.10 reopening zahteva razlog i čuva vreme prethodnog rešenja
PASS  beta7.10 nedodeljeni slučajevi obaveštavaju superadministratore
PASS  beta7.10 dashboard prikazuje aktivne probijene i waiting slučajeve
PASS  beta7.11 migracija sadrži after_sales_actions
PASS  beta7.11 migracija sadrži after_sales_action_items
PASS  beta7.11 migracija sadrži after_sales_action_id
PASS  beta7.11 migracija sadrži after_sales.execute
PASS  beta7.11 podržava četiri izvršne radnje
PASS  beta7.11 lager efekti su zaključani i idempotentni
PASS  beta7.11 refundacija je vezana za radnju i ograničena neto uplatom
PASS  beta7.11 slučaj čeka završetak aktivnih radnji
PASS  beta7.11 UI ima planiranje pokretanje izvršenje i otkazivanje
PASS  beta7.11 Gate i permission middleware štite izvršne kontrole
PASS  beta7.11 controller ima sve izvršne endpoint-e
PASS  beta7.11 automatizacija prati rok izvršne radnje
PASS  beta7.11 dashboard prikazuje radnje za izvršenje
PASS  beta7.11 testovi pokrivaju idempotentni lager povrat i refundaciju
PASS  beta7.12 migracija sadrži field_service_teams
PASS  beta7.12 migracija sadrži field_work_orders
PASS  beta7.12 migracija sadrži field_work_order_attachments
PASS  beta7.12 migracija sadrži field_operations.view
PASS  beta7.12 migracija sadrži field_operations.manage
PASS  beta7.12 fizičke radnje automatski dobijaju radni nalog
PASS  beta7.12 sprečava preklapanje termina iste ekipe
PASS  beta7.12 završetak zahteva dolazak na lokaciju
PASS  beta7.12 radni nalog čuva troškove kilometražu i privatne dokaze
PASS  beta7.12 UI ima kalendar ekipe i operativne statuse
PASS  beta7.12 rute i Gate štite terenske operacije
PASS  beta7.12 automatizacija prati neplanirane i probijene radne naloge
PASS  beta7.12 doctor proverava tabele dozvole rute i SQL
PASS  beta7.12 test pokriva auto nalog konflikt i on-site završetak
PASS  beta7.13 migracija sadrži service_part_suppliers
PASS  beta7.13 migracija sadrži service_parts
PASS  beta7.13 migracija sadrži field_work_order_parts
PASS  beta7.13 migracija sadrži service_part_movements
PASS  beta7.13 migracija sadrži service_part_purchase_requests
PASS  beta7.13 migracija sadrži service_part_purchase_request_items
PASS  beta7.13 migracija sadrži service_parts.view
PASS  beta7.13 migracija sadrži service_parts.manage
PASS  beta7.13 migracija sadrži service_parts.procurement
PASS  beta7.13 početno stanje ulazi u movement ledger
PASS  beta7.13 rezervacija ne umanjuje fizičko stanje
PASS  beta7.13 završetak skida stvarni utrošak i oslobađa ostatak
PASS  beta7.13 otkazivanje oslobađa sve rezervacije
PASS  beta7.13 kretanja servisnog lagera su idempotentna i ponovo proverena pod lockom
PASS  beta7.13 nacrt nabavke koristi konkurentno bezbedan privremeni broj
PASS  beta7.13 prijem nabavke računa ponderisanu prosečnu cenu
PASS  beta7.13 UI ima servisni lager dobavljače nabavku i utrošak
PASS  beta7.13 Gate i rute štite lager i nabavku
PASS  beta7.13 automatizacija prati nizak lager i kašnjenje nabavke
PASS  beta7.13 doctor proverava tabele dozvole rute i SQL
PASS  beta7.13 testovi pokrivaju ledger rezervaciju utrošak i ponderisanu cenu
PASS  beta7.13 UI ima responsive stilove servisnog lagera
PASS  beta7.14.1 migracija prvo obezbeđuje FK indeks
PASS  beta7.14 migracija uklanja unique order/type ograničenje
PASS  beta7.14 migracija uvodi revizije i vezu sa prethodnim dokumentom
PASS  beta7.14 servis vraća samo aktivan dokument ili izdaje novu reviziju
PASS  beta7.14 storniranje zahteva razlog i čuva audit podatak
PASS  beta7.14 model podržava supersedes relaciju
PASS  beta7.14 UI razlikuje aktivan dokument i novu reviziju
PASS  beta7.14 PDF prikazuje broj revizije
PASS  beta7.14 regresioni test pokriva ponovno izdavanje
PASS  beta7.15 migracija sadrži warranty_rules
PASS  beta7.15 migracija sadrži product_warranties
PASS  beta7.15 migracija sadrži warranty_maintenance_records
PASS  beta7.15 migracija sadrži warranties.view_own
PASS  beta7.15 migracija sadrži warranties.manage
PASS  beta7.15 pravila imaju product category global prioritet
PASS  beta7.15 kompletiranje automatski izdaje garanciju bez obaranja porudžbine
PASS  beta7.15 otkazivanje poništava aktivne garancije
PASS  beta7.15 GAR poslovni broj je registrovan
PASS  beta7.15 garancija čuva snapshot kupca artikla uslova i serijskih brojeva
PASS  beta7.15 preventivno održavanje generiše sledeći termin
PASS  beta7.15 zakazivanje ne menja vreme tokom provere datuma
PASS  beta7.15 backfill bira samo stavke bez garancije
PASS  beta7.15 PDF garantni list prikazuje ključne snapshot podatke
PASS  beta7.15 korisnički i administratorski prikazi postoje
PASS  beta7.15 rute Gates i administratorski scope štite garancije
PASS  beta7.15 automatizacija prati istek i održavanje
PASS  beta7.15 dashboard prikazuje garancije
PASS  beta7.15 doctor i backfill komande postoje
PASS  beta7.15 feature test pokriva automatsko izdavanje i prioritet pravila
PASS  beta7.15 warranty PDF smoke postoji
PASS  beta7.16 migracija uvodi outbox QR snapshot i dane garancije
PASS  beta7.16 migracija ima recovery putanju za delimičan MariaDB DDL
PASS  beta7.16 e-mail outbox ima dedupe intervale i pojedinačne primaoce
PASS  beta7.16 e-mail prima autor odgovorno lice i dodatne adrese
PASS  beta7.16 workflow šalje status tracking plaćanje i dokumente
PASS  beta7.16 dispatcher ima retry stuck recovery i zaštitu storniranog priloga
PASS  beta7.16 scheduler šalje outbox svake minute
PASS  beta7.16 admin podešava intervale događaje i dokumente
PASS  beta7.16 e-mail šablon ima događaje i bezbedan action link
PASS  beta7.16 NBS payload koristi zvanične oznake i RSD zarez
PASS  beta7.16 NBS servis koristi zvanični HTTPS endpoint i čuva privatni PNG snapshot
PASS  beta7.16 stornirani istorijski dokument ostaje pregledljiv bez ponovnog NBS poziva
PASS  beta7.16 finansijski dokument bez validnog NBS QR se ne izdaje
PASS  beta7.16 PDF crta PNG bez GD i prikazuje NBS IPS QR oznaku
PASS  beta7.16 IPS QR smoke potvrđuje sliku oznaku i tačan RSD iznos
PASS  beta7.16 garancija podržava kombinaciju meseci i dana
PASS  beta7.16 admin može kreirati porudžbinu
PASS  beta7.16 doctor proverava outbox SMTP scheduler NBS i garancijske dane
PASS  beta7.16 feature test pokriva admin porudžbinu događaje NBS QR i dane garancije
PASS  beta7.17 migracija uvodi predmete rate i komunikaciju naplate
PASS  beta7.17 migracija je recovery-safe za delimičan DDL
PASS  beta7.17 servis automatski otvara zatvara i usklađuje predmete
PASS  beta7.17 rate se raspoređuju prema stvarno plaćenom iznosu
PASS  beta7.17 automatske opomene koriste faze dedupe i outbox
PASS  beta7.17 admin ima aging pregled plan i evidenciju komunikacije
PASS  beta7.17 podmeni se zatvara klikom van escape i izborom stavke
PASS  beta7.17 checkbox i radio imaju normalnu globalnu veličinu
PASS  beta7.17 doctor proverava šemu dozvolu i scheduler
PASS  beta7.17.1 permission seed je schema-aware
PASS  beta7.17.1 seeder ne zahteva permissions.updated_at
PASS  beta7.17.2 hover podmeni ima grace period i click pin
PASS  beta7.17.2 CSS premošćava razmak do podmenija
PASS  beta7.18 migracija uvodi strukturirane opcije i korelacije
PASS  beta7.18 migracija je recovery-safe za MariaDB
PASS  beta7.18 procesor ima porodicu i tačan model
PASS  beta7.18 brend filtrira samo sopstvene linije
PASS  beta7.18 generičke zavisnosti imaju server validaciju i zaštitu ciklusa
PASS  beta7.18 forma skriva nepovezane opcije i čuva detalj
PASS  beta7.18 kataloški filteri podržavaju select range boolean text i detalj
PASS  beta7.18 oba kataloga koriste korelisane filtere
PASS  beta7.18 doctor proverava procesor linije veze i tipove
PASS  beta7.18.1 forma artikla ne koristi nedostupni index filter servis
PASS  beta7.18.1 jedinstveni katalog dobija podatke za korelisane filtere
PASS  beta7.18.1 šifarnici dobijaju podatke za roditelje i mape zavisnosti
PASS  beta7.19 migracija uvodi šablone kompletnost i poreklo klona
PASS  beta7.19 migracija je recovery-safe i obračunava postojeći katalog
PASS  beta7.19 template servis podržava alias placeholdere
PASS  beta7.19 completeness servis vraća nepotpun aktivan artikal u nacrt
PASS  beta7.19 kloniranje čuva novi SKU i nulti lager
PASS  beta7.19 clone checkboxi eksplicitno šalju nulu
PASS  beta7.19 bulk zahteva pregled i blokira praznu operaciju
PASS  beta7.19 bulk promena brenda čisti neusklađenu liniju
PASS  beta7.19 preview naziva uklanja method spoof
PASS  beta7.19 doctor proverava šemu rute i kompletnost
PASS  beta7.19 feature test pokriva naziv klon i bulk
PASS  beta7.20 migracija uvodi varijante specifikacije slike i snapshot
PASS  beta7.20 migracija je recovery-safe za MariaDB i proširuje istorijske module
PASS  beta7.20 varijanta ima SKU cenu lager status default i garanciju
PASS  beta7.20 default preferira aktivnu varijantu i roditelj sabira aktivan lager
PASS  beta7.20 serverska validacija štiti SKU i korelisane specifikacije
PASS  beta7.20 admin ima CRUD lager slike i default varijantu
PASS  beta7.20 UI filtrira zavisne specifikacije varijante
PASS  beta7.20 porudžbina čuva variant snapshot i vraća isti lager
PASS  beta7.20 postprodaja garancija i stock movement nose variant id
PASS  beta7.20 decommission clone vise ne nudi kopiranje varijanti
PASS  beta7.20 parent inventory korekcija je blokirana
PASS  beta7.20 filter i pretraga vide aktivne varijante
PASS  beta7.20 doctor proverava SKU default snapshot i aggregate
PASS  beta7.20 feature i smoke testovi postoje
PASS  beta7.17 feature test pokriva dedupe rate zatvaranje i UI regresiju
PASS  beta7.21 migracija uvodi nabavne snapshotove i rasporede
PASS  beta7.21 migracija je recovery-safe i permission schema-aware
PASS  beta7.21 marža koristi snapshot i prikazuje pokrivenost troška
PASS  beta7.21 filteri važe za KPI trend i segmente
PASS  beta7.21 dashboard pokriva lager potraživanja postprodaju i tim
PASS  beta7.21 PDF upravljačkog izveštaja postoji
PASS  beta7.21 raspored ima retry dedupe i zasebne primaoce
PASS  beta7.21 ekran je bezbedan pre migracije
PASS  beta7.21 UI ima CSV PDF rasporede i cost coverage
PASS  beta7.22.1 management analytics koristi aktivnu temu bez belog fallback-a
PASS  beta7.22.1 CSS kompatibilni aliasi postoje
PASS  beta7.21 feature test pokriva ekran export i raspored
PASS  beta7.22 portal servis i fallback podaci postoje
PASS  beta7.22 portal objedinjuje porudžbine dokumente uplate garancije i servis
PASS  beta7.22 report grouping je kompatibilan sa ONLY_FULL_GROUP_BY
PASS  beta7.22 dashboard ima trend prioritete brze akcije i operativne module
PASS  beta7.22.1 portal doctor prosleđuje ViewErrorBag
PASS  beta7.22.1 layout bezbedno proverava errors bag
PASS  beta7.23 release-check komanda ima profile i kontrolisane režime
PASS  beta7.23 release registry ima quick standard i full profile
PASS  beta7.23 release plan ne dispatchuje poslovne akcije
PASS  beta7.23 release metadata i atomski JSON report postoje
PASS  beta7.23 release rezultat ima READY i NOT READY ugovor
PASS  beta7.23 smoke i dokumentacija postoje
PASS  beta7.23.1 catalog detail nema problematične inline Blade lance
PASS  beta7.23.1 catalog detail Blade direktive su izbalansirane
PASS  beta7.23.1 variant detail Feature i smoke regresija postoje
PASS  beta7.23.1 health daje čitljive runtime remediation komande
PASS  beta7.23.2 detail doctor rešava controller zavisnosti kroz container
PASS  beta7.23.2 detail doctor nema direktan edit poziv sa jednim argumentom
PASS  beta7.23.2 detail doctor smoke i contract regresija postoje
PASS  beta7.24 migracija uvodi aktivacije sesije komunikaciju i order-link audit
PASS  beta7.24 aktivacioni token je hashiran jednokratan i vremenski ograničen
PASS  beta7.24 session registry koristi hash i podržava revoke
PASS  beta7.24 kupac vidi samo javne poruke a admin interne
PASS  beta7.24 portal rute aktivacija i admin centar postoje
PASS  beta7.24 komunikacija razdvaja public i internal
PASS  beta7.24 smoke i PHPUnit regresije postoje
PASS  beta7.24 maintenance čisti tokene i stare session evidencije
PASS  beta7.24 reinvite ne deaktivira aktivnog kupca i aktivacija nije cache-ovana
PASS  beta7.24.1 management repair obrađuje missing snapshotove
PASS  beta7.24.1 repair ne prepisuje kompletne snapshotove
PASS  beta7.24.1 repair je transakcioni i koristi row lock
PASS  beta7.24.1 kandidati imaju transparentan izvor
PASS  beta7.24.1 ručna finansijska promena zahteva razlog i audit
PASS  beta7.24.1 audit/repair komanda i regresije postoje
PASS  rc1 profil sadrzi final hardening provere
PASS  rc1 security doctor proverava production debug HTTPS session i public fajlove
PASS  rc1 migration doctor proverava pending SQL mode i foreign keys
PASS  rc1 access doctor proverava route permission i superadmin
PASS  rc1 release integrity proverava SHA-256 i path traversal
PASS  rc1 backup verify je read-only i proverava SQL gzip i file hash
PASS  rc1 smoke i contract regresije postoje
PASS  rc1 nema novu migration datoteku
PASS  stable profil je identican potvrdenom rc profilu
PASS  stable smoke i contract regresije postoje
PASS  stable početna je univerzalni dashboard sa integrisanim korisničkim centrom
PASS  stable nema zasebnu Moj portal stranicu ni stavku menija
PASS  stable nema novu migration datoteku
PASS  v2.1.2 obaveštenja o novom artiklu su opt-in i koriste outbox
PASS  v2.1.2 novi artikal se šalje aktivnim registrovanim korisnicima bez duplikata
PASS  v2.1.2 mail podešavanja i šablon podržavaju nove artikle
PASS  v2.1.2 product announcement regresije postoje
PASS  v2.1.2 nema novu migration datoteku
PASS  APP_ENV production
PASS  Redis nije obavezan za database queue
PASS  file session/cache/limiter i database queue
PASS  secret vrednosti su prazne
PASS  import ne upisuje legacy konekciju
INFO  ZIP hygiene provere su preskočene na instaliranoj aplikaciji; za raspakovani sanitized ZIP koristi --package.
PASS  v2.1.3 tipovi proizvoda imaju posebne stranice i Drag & Drop
PASS  v2.1.3 specifikaciona polja mogu trajno da se obrišu
PASS  v2.1.3 tip automatski određuje kategoriju
PASS  v2.1.3 diskovi imaju pojedinačne celobrojne GB kapacitete
PASS  v2.1.3 catalog settings doctor postoji
PASS  v2.1.3 grana ima tri kontrolisane migration datoteke
PASS  v2.1.3.3 ProductRequest zadržava validan SKU regex delimiter
PASS  v2.1.3.3 ProductVariantRequest zadržava validan SKU regex delimiter
PASS  v2.1.3.3 migracija povezuje listu diskova i izvedeni ukupni kapacitet
PASS  v2.1.3.3 stari kapacitet se bezbedno prenosi na prvi disk
PASS  v2.1.3.3 backend ne veruje ručnom ukupnom zbiru
PASS  v2.1.3.3 ukupni kapacitet je ispod diskova i readonly
PASS  v2.1.3.3 frontend sabira diskove i čuva početni legacy zbir
PASS  v2.1.3.3 proizvod i varijante dele isti storage model
PASS  v2.1.3.3 storage smoke i contract test postoje
PASS  v2.1.4 migracija dodaje model proizvoda i usklađuje šablone
PASS  v2.1.4 model se validira čuva i koristi u nazivu
PASS  v2.1.4 forma ima model proizvoda posle linije
PASS  v2.1.4 trajno brisanje ima SKU potvrdu i izbor brisanja slika
PASS  v2.1.4 poslovna istorija blokira destruktivno brisanje
PASS  v2.1.4 semantički sistem tastera pokriva sve uloge
PASS  v2.1.4 route i stable doctor postoje
PASS  v2.1.4 smoke i contract test postoje
PASS  v2.1.4.1 controller priprema i prosledjuje orderedFields
PASS  v2.1.4.1 Blade bezbedno inicijalizuje orderedFields
PASS  v2.1.4.1 doctor renderuje formulare svih tipova
PASS  v2.1.4.1 smoke i contract test postoje
PASS  v2.1.5 globalni UX runtime štiti submit i nesačuvane izmene
PASS  v2.1.5 mobilni action dock koristi originalni submit
PASS  v2.1.5 validacija i accessibility markeri postoje
PASS  v2.1.5 dugi formulari su eksplicitno označeni
PASS  v2.1.5 sistemske error stranice postoje
PASS  v2.1.5 migracija koristi postojeće snaga-napajanja polje
PASS  v2.1.5 migracija postavlja napajanje u sredinu
PASS  v2.1.5 doctor proverava Blade, route akcije i napajanje
PASS  v2.1.5 stable release koristi render i repair
PASS  v2.1.5 smoke i contract test postoje
PASS  v2.1.6 migracija kreira snapshot istoriju i ciljane indekse
PASS  v2.1.6 Data Quality audit pokriva katalog slike varijante i specifikacije
PASS  v2.1.6 repair je nedestruktivan i preračunava izvedene vrednosti
PASS  v2.1.6 performance doctor proverava indekse cache i SQL pragove
PASS  v2.1.6 dashboard kešira schema metadata po requestu
PASS  v2.1.6 Data Quality Center rute i prikaz postoje
PASS  v2.1.6 katalog ima quality filtere
PASS  v2.1.6 doctor renderuje centar i pokreće performance audit
PASS  v2.1.6 Stable release uključuje render repair i strict
PASS  v2.1.6 smoke i contract test postoje
PASS  v2.2.0 bootstrap device catalog order i notification rute postoje
PASS  v2.2.0 bootstrap vraća permissions features i app policy
PASS  v2.2.0 uređaji deduplikuju push tokene i podržavaju opoziv
PASS  v2.2.0 API greške imaju stabilan envelope
PASS  v2.2.0 OpenAPI i Stable doctor su povezani

Ukupno: 983, neuspešno: 0
CMS_STATIC_CHECK=PASS
STATIC_CHECK_VARIANT_CLONE_CONTRACT=UPDATED_TO_OPERATIONAL_RETIREMENT

============================================================
8. VISIBLE OPERATIONAL TRACE CHECK
============================================================
VISIBLE_ADMIN_VARIANT_ENTRY_POINTS=REMOVED
CLONE_VARIANT_OPTION=REMOVED
VARIANT_ROUTE_NAMESPACE=REMOVED_8_OF_8

============================================================
9. REMAINING DECOMMISSION SURFACE FOR BATCH 2B
============================================================
REMAINING_VARIANT_SIGNAL_FILE_COUNT=81
REMAINING_VARIANT_SIGNAL_LINE_COUNT=473
NEXT_DECOMMISSION_SCOPE=BATCH2B_CORE_RUNTIME_API_MOBILE_REFACTOR_THEN_BATCH2C_SCHEMA_PURGE_FINAL_CERTIFICATION

--- REMAINING VARIANT SIGNAL MAP ---
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:97:                        'product_variant_id' => $item->product_variant_id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:98:                        'sku_snapshot' => $item->variant_sku_snapshot ?: $item->product_sku,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:99:                        'product_name_snapshot' => $item->product_name.($item->variant_name_snapshot ? ' — '.$item->variant_name_snapshot : ''),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AdvancedInventoryService.php:46:                if ($products->contains(static fn (Product $product): bool => (bool) $product->variants_enabled)) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AdvancedInventoryService.php:116:                if ($products->contains(static fn (Product $product): bool => (bool) $product->variants_enabled)) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDocumentService.php:355:            'sku' => $item->variant_sku_snapshot ?: $item->product_sku,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDocumentService.php:356:            'name' => $item->product_name.($item->variant_name_snapshot ? ' — '.$item->variant_name_snapshot : ''),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:8:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:16:final class ProductVariantService
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:21:    public function create(Product $product, array $data, User $actor): ProductVariant
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:23:        return DB::transaction(function () use ($product, $data, $actor): ProductVariant {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:32:            $variant = ProductVariant::query()->create([
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:54:                    'product_variant_id' => $variant->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:65:            $mustDefault = (bool) ($data['is_default'] ?? false) || !ProductVariant::query()->where('product_id', $locked->id)->where('id', '!=', $variant->id)->whereNull('deleted_at')->exists();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:74:    public function update(Product $product, ProductVariant $variant, array $data, User $actor): ProductVariant
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:77:        return DB::transaction(function () use ($product, $variant, $data, $actor): ProductVariant {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:79:            $locked = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:105:    public function adjustStock(Product $product, ProductVariant $variant, int $change, string $note, string $key, User $actor): StockMovement
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:113:            $locked = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:119:                'event_key' => $eventKey, 'product_id' => $product->id, 'product_variant_id' => $locked->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:130:    public function archive(Product $product, ProductVariant $variant, User $actor): void
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:135:            $locked = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:144:    public function setDefault(Product $product, ProductVariant $variant, User $actor): void
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:149:            $locked = ProductVariant::query()->whereNull('deleted_at')->lockForUpdate()->findOrFail($variant->id);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:165:    private function setDefaultLocked(Product $product, ProductVariant $variant): void
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:167:        ProductVariant::query()->where('product_id', $product->id)->where('id', '!=', $variant->id)->update(['is_default' => false]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:169:        $product->forceFill(['default_variant_id' => $variant->id, 'variants_enabled' => true])->save();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:174:        $base = ProductVariant::query()->where('product_id', $product->id)->whereNull('deleted_at');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:180:        else $product->forceFill(['default_variant_id' => null, 'variants_enabled' => false])->save();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:185:        $variants = ProductVariant::query()->where('product_id', $product->id)->whereNull('deleted_at')->get();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:187:            $product->forceFill(['variants_enabled' => false, 'default_variant_id' => null])->save();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:191:        $default = $active->firstWhere('id', (int) $product->default_variant_id) ?? $active->first() ?? $variants->firstWhere('id', (int) $product->default_variant_id) ?? $variants->first();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:192:        ProductVariant::query()->where('product_id', $product->id)->update(['is_default' => false]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:193:        if ($default) ProductVariant::query()->whereKey($default->id)->update(['is_default' => true]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:195:            'variants_enabled' => true,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:196:            'default_variant_id' => $default?->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:206:    private function syncSpecifications(Product $product, ProductVariant $variant, array $specs, array $details, array $structured = []): void
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:208:        DB::table('product_variant_spec_values')->where('product_variant_id', $variant->id)->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:213:            $row = ['product_variant_id' => $variant->id, 'field_id' => $field->id, 'value_text' => null, 'value_detail' => null, 'value_json' => null, 'value_number' => null, 'value_boolean' => null, 'created_at' => now(), 'updated_at' => now()];
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:231:                DB::table('product_variant_spec_values')->insert($row);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:258:    private function assertOwner(Product $product, ProductVariant $variant): void
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:264:    private function snapshot(ProductVariant $variant): array
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:12:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:53:            'product_variant_id' => isset($input['product_variant_id']) && $input['product_variant_id'] !== ''
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:54:                ? (int) $input['product_variant_id']
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:93:    /** @param array{product_id:int,buyer_name:string,buyer_phone:?string,product_variant_id:?int,quantity:int,sale_price_rsd:float,payment_method:string} $payload */
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:125:        if ((bool) $lockedProduct->variants_enabled) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:126:            if ($payload['product_variant_id'] === null) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:128:                    'product_variant_id' => 'Izaberi varijantu artikla.',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:132:            $variant = ProductVariant::query()
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:134:                ->whereKey($payload['product_variant_id'])
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:141:            if (!$variant instanceof ProductVariant) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:143:                    'product_variant_id' => 'Izabrana varijanta nije dostupna.',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:146:        } elseif ($payload['product_variant_id'] !== null) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:148:                'product_variant_id' => 'Ovaj artikal nema aktivne varijante.',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:155:            $variant instanceof ProductVariant ? $variant : $lockedProduct,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:159:        $quantityBefore = $variant instanceof ProductVariant
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:214:        if ($variant instanceof ProductVariant && (float) ($variant->purchase_price_rsd ?? 0) > 0) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:225:            'product_variant_id' => $variant?->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:233:            'variant_sku_snapshot' => $variant?->sku,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:234:            'variant_name_snapshot' => $variant?->name,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:235:            'variant_attributes_json' => $variant ? $this->variantAttributes($variant) : null,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:249:        if ($variant instanceof ProductVariant) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:255:            $parentStock = (int) ProductVariant::query()
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:275:            'product_variant_id' => $variant?->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:339:                'product_variant_id' => $variant?->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:364:    private function catalogUnitPriceRsd(Product|ProductVariant $sellable, ?float $rate): float
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:379:    private function variantAttributes(ProductVariant $variant): array
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/WarrantyService.php:59:                    'product_variant_id' => $item->product_variant_id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/WarrantyService.php:74:                    'product_sku_snapshot' => $item->variant_sku_snapshot ?: $item->product_sku,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/WarrantyService.php:75:                    'product_name_snapshot' => $item->product_name.($item->variant_name_snapshot ? ' — '.$item->variant_name_snapshot : ''),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:447:                $variantName = $this->text($item, 'variant_name_snapshot');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:448:                $variantSku = $this->text($item, 'variant_sku_snapshot');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:449:                $attributes = $item->getAttribute('variant_attributes_json');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:454:                    'variant_attributes' => is_array($attributes) ? $attributes : [],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:273:                if ($variantIds !== [] && Schema::hasColumn($table, 'product_variant_id')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:275:                        $nested->orWhereIn('product_variant_id', $variantIds);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:277:                        $nested->whereIn('product_variant_id', $variantIds);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:386:        if ($variantIds !== [] && Schema::hasColumn('products', 'default_variant_id')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:388:                ->whereIn('default_variant_id', $variantIds)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:389:                ->update(['default_variant_id' => null]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:417:            if ($variantIds !== [] && Schema::hasColumn($table, 'product_variant_id')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:418:                $count = (int) DB::table($table)->whereIn('product_variant_id', $variantIds)->count();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:421:                    $this->assertNullable($table, 'product_variant_id');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:424:                        ->whereIn('product_variant_id', $variantIds)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:425:                        ->update(['product_variant_id' => null]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:462:            ['product_sku', 'product_name', 'variant_sku_snapshot', 'variant_name_snapshot'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:474:                'variant_sku_snapshot',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:475:                'variant_name_snapshot',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:518:                                    ->where('auditable_type', 'like', '%ProductVariant%');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:558:                    if ($variantIds !== [] && Schema::hasColumn('operational_alerts', 'product_variant_id')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:560:                            $nested->orWhereIn('product_variant_id', $variantIds);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:562:                            $nested->whereIn('product_variant_id', $variantIds);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:612:        if ($variantIds !== [] && Schema::hasTable('product_variant_spec_values')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:613:            $counts['owned_rows_deleted'] += DB::table('product_variant_spec_values')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:614:                ->whereIn('product_variant_id', $variantIds)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:626:        if (Schema::hasTable('product_variants')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:627:            $counts['owned_rows_deleted'] += DB::table('product_variants')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:659:            if ($variantIds !== [] && Schema::hasColumn($table, 'product_variant_id')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:661:                    $nested->orWhereIn('product_variant_id', $variantIds);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:663:                    $nested->whereIn('product_variant_id', $variantIds);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:872:                in_array($keyLower, ['product_variant_id', 'variant_id'], true)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:156:            if (Schema::hasTable('product_variants') && Schema::hasTable('product_variant_spec_values')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:202:            if (Schema::hasTable('product_variants') && Schema::hasTable('product_variant_spec_values')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:351:        DB::table('product_variants as variants')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:358:                    if ($this->normalizeOne('product_variant_spec_values', 'product_variant_id', (int) $entity->id, $sourceId, $totalId)) $changed++;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:451:        DB::table('product_variants as variants')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:459:                        'product_variant_spec_values',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:460:                        'product_variant_id',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:9:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:27:        'product_variant_spec_values',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:28:        'product_variants',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:53:        'product_variant_spec_values',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:54:        'product_variants',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:70:        $variants = ProductVariant::query()
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:100:            ->get(['id', 'product_variant_id', 'storage_disk', 'file_path']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:171:                if ($variantIds !== [] && Schema::hasColumn('order_items', 'product_variant_id')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:172:                    $nested->orWhereIn('product_variant_id', $variantIds);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:212:                'product_variant_id' => $image->product_variant_id === null
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:214:                    : (int) $image->product_variant_id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:314:                    in_array($column, ['product_id', 'product_variant_id', 'source_product_id', 'default_variant_id'], true)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:469:                    $nested->where('k.REFERENCED_TABLE_NAME', 'product_variants')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:204:                    ->whereNull('product_images.product_variant_id');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:285:            ->select(['product_id', 'product_variant_id'])
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:288:            ->groupBy('product_id', 'product_variant_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:298:        if (!Schema::hasTable('products') || !Schema::hasTable('product_variants')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:303:            ->where('variants_enabled', true)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:306:                $query->whereNull('default_variant_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:308:                        $variant->selectRaw('1')->from('product_variants')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:309:                            ->whereColumn('product_variants.id', 'products.default_variant_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:310:                            ->whereColumn('product_variants.product_id', 'products.id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:311:                            ->whereNull('product_variants.deleted_at');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:315:        $samples = (clone $invalidDefault)->select(['id', 'sku', 'name', 'default_variant_id'])->orderBy('id')->limit($limit)->get();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:318:        $multipleDefaults = DB::table('product_variants')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:355:        if (Schema::hasTable('product_variant_spec_values') && Schema::hasTable('product_variants')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:356:            $variantQuery = DB::table('product_variant_spec_values')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:357:                ->join('product_variants', 'product_variants.id', '=', 'product_variant_spec_values.product_variant_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:358:                ->join('products', 'products.id', '=', 'product_variants.product_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:359:                ->leftJoin('specification_fields', 'specification_fields.id', '=', 'product_variant_spec_values.field_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:362:                        ->on('product_type_fields.field_id', '=', 'product_variant_spec_values.field_id');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:371:                'product_variants.id', 'product_variants.sku', 'product_variants.name', 'product_variant_spec_values.field_id',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:372:            ])->orderBy('product_variants.id')->limit($limit)->get();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:411:        if (Schema::hasTable('product_variants')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:412:            $metrics['variants_total'] = (int) DB::table('product_variants')->whereNull('deleted_at')->count();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:431:            ->select(['product_id', 'product_variant_id'])
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:433:            ->groupBy('product_id', 'product_variant_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:443:                        $group->product_variant_id === null
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:444:                            ? $query->whereNull('product_variant_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:445:                            : $query->where('product_variant_id', (int) $group->product_variant_id);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:462:        if (!Schema::hasTable('products') || !Schema::hasTable('product_variants') || !Schema::hasColumn('products', 'default_variant_id')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:468:            ->where('variants_enabled', true)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:474:                        $variants = DB::table('product_variants')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:483:                            if ($product->default_variant_id !== null) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:484:                                DB::table('products')->where('id', (int) $product->id)->update(['default_variant_id' => null]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:489:                        $chosen = $variants->firstWhere('id', (int) $product->default_variant_id) ?? $variants->first();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:491:                        if ((int) $product->default_variant_id === (int) $chosen->id && $defaultCount === 1 && (bool) $chosen->is_default) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:494:                        DB::table('product_variants')->where('product_id', (int) $product->id)->update(['is_default' => false]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:495:                        DB::table('product_variants')->where('id', (int) $chosen->id)->update(['is_default' => true]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:496:                        DB::table('products')->where('id', (int) $product->id)->update(['default_variant_id' => (int) $chosen->id]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/InventoryService.php:21:        if ((bool) $product->variants_enabled) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:12:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:586:        foreach ($order->items->sortBy(fn ($item) => sprintf('%010d:%010d', (int) $item->product_id, (int) ($item->product_variant_id ?? 0))) as $item) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:592:            if ((int) ($item->product_variant_id ?? 0) > 0) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:593:                $variant = ProductVariant::query()->where('product_id', $product->id)->lockForUpdate()->findOrFail((int) $item->product_variant_id);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:606:                'product_variant_id' => $variant?->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:615:                'metadata_json' => ['order_item_id' => $item->id, 'variant_sku' => $item->variant_sku_snapshot, 'one_time_return' => true],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:619:            $aggregate = (int) ProductVariant::query()->where('product_id', $productId)->where('status', 'active')->whereNull('deleted_at')->sum('stock_quantity');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:24:        'products' => ['id', 'sku', 'name', 'stock_quantity', 'purchase_price_rsd', 'variants_enabled'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:230:        $products = DB::table('products')->whereNull('deleted_at')->where('variants_enabled', false)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:233:        if (Schema::hasTable('product_variants')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:234:            $variants = DB::table('product_variants as v')->join('products as p', 'p.id', '=', 'v.product_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:367:                    ->orWhere($alias.'.variant_name_snapshot', 'like', $search)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:368:                    ->orWhere($alias.'.variant_sku_snapshot', 'like', $search);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:394:            ? DB::table('stock_movements')->where($kind === 'variant' ? 'product_variant_id' : 'product_id', $id)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:395:                ->when($kind === 'product', static fn ($q) => $q->whereNull('product_variant_id'))
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:399:            ? DB::table('stock_movements')->where($kind === 'variant' ? 'product_variant_id' : 'product_id', $id)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:400:                ->when($kind === 'product', static fn ($q) => $q->whereNull('product_variant_id'))
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:52:                'oi.product_variant_id',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:55:                'oi.variant_sku_snapshot',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:56:                'oi.variant_name_snapshot',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:237:        $variantId = (int) ($item->product_variant_id ?? 0);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:238:        if ($variantId > 0 && Schema::hasTable('product_variants')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:239:            $variantCost = $this->positive(DB::table('product_variants')->where('id', $variantId)->value('purchase_price_rsd'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:11:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:58:        $variantIds = array_values(array_unique(array_filter(array_map(static fn (array $item): int => (int) ($item['product_variant_id'] ?? 0), $items))));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:74:        $variants = ProductVariant::query()
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:141:            $variantId = (int) ($itemData['product_variant_id'] ?? 0);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:142:            /** @var ProductVariant|null $variant */
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:145:            if ((bool) $product->variants_enabled) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:174:                'product_variant_id' => $variant?->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:177:                'variant_sku_snapshot' => $variant?->sku,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:178:                'variant_name_snapshot' => $variant?->name,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:179:                'variant_attributes_json' => $variantAttributes,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:211:                'product_variant_id' => $variant?->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:227:            $aggregate = (int) ProductVariant::query()->where('product_id', $variantProductId)->where('status', 'active')->whereNull('deleted_at')->sum('stock_quantity');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:320:    private function priceRsd(Product|ProductVariant $product, ?float $rate): float
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:333:    /** @param array<int,array<string,mixed>> $items @return array<int,array{product_id:int,product_variant_id:?int,quantity:int}> */
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:339:            $variantId = (int) ($item['product_variant_id'] ?? 0);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:343:            if (!isset($normalized[$key])) $normalized[$key] = ['product_id' => $productId, 'product_variant_id' => $variantId > 0 ? $variantId : null, 'quantity' => 0];
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:351:    private function variantAttributes(ProductVariant $variant): array
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:10:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:30:        private readonly ProductVariantService $variants,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:106:            if ((bool) $locked->variants_enabled) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:204:            if (!empty($options['copy_variants']) && Schema::hasTable('product_variants')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:344:                return Schema::hasTable('product_variants')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:345:                    && ProductVariant::query()
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:441:                    || ProductVariant::query()->whereRaw('LOWER(sku) = ?', [mb_strtolower($candidate)])->exists(),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:449:            $variant = ProductVariant::query()->create([
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:467:                DB::table('product_variant_spec_values')->insert([
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:468:                    'product_variant_id' => $variant->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:491:            'completeness_percent','name_is_manual','source_product_id','variants_enabled','default_variant_id',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:30:        $variantCount = Schema::hasTable('product_variant_spec_values')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:31:            ? (int) DB::table('product_variant_spec_values')->where('field_id', $fieldId)->count()
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:56:            if (Schema::hasTable('product_variant_spec_values')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:57:                DB::table('product_variant_spec_values')->where('field_id', $fieldId)->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:118:            'orphan_variant_values' => $this->countOrphans('product_variant_spec_values', 'field_id', 'specification_fields', 'id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:119:                + $this->countOrphans('product_variant_spec_values', 'product_variant_id', 'product_variants', 'id'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:137:            $this->deleteOrphans('product_variant_spec_values', 'field_id', 'specification_fields', 'id');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:138:            $this->deleteOrphans('product_variant_spec_values', 'product_variant_id', 'product_variants', 'id');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:323:        if (!Schema::hasTable('product_variant_spec_values') || !Schema::hasTable('product_variants') || !Schema::hasTable('products') || !Schema::hasTable('product_type_fields')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:327:        return (int) DB::table('product_variant_spec_values as values')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:328:            ->join('product_variants as variants', 'variants.id', '=', 'values.product_variant_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:345:        DB::table('product_variant_spec_values')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:348:                    ->from('product_variants as variants')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:352:                            ->on('product_type_fields.field_id', '=', 'product_variant_spec_values.field_id');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:354:                    ->whereColumn('variants.id', 'product_variant_spec_values.product_variant_id');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:13:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:30:        private readonly ProductVariantService $variants,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:106:                    'product_variant_id' => $caseItem->product_variant_id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:291:        $variantIds = $effectItems->pluck('product_variant_id')->filter()->map(static fn (mixed $id): int => (int) $id)->unique()->sort()->values();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:292:        $variants = ProductVariant::query()->whereIn('id', $variantIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:299:            if ((bool) $product->variants_enabled && $item->product_variant_id === null) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:302:            if ($item->product_variant_id !== null) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:303:                $variant = $variants->get((int) $item->product_variant_id);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:310:        $required = $effectItems->where('stock_effect', 'decrease')->groupBy(static fn ($item): string => $item->product_variant_id ? 'v:'.$item->product_variant_id : 'p:'.$item->product_id)->map(static fn ($rows): int => (int) $rows->sum('quantity'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:313:                /** @var ProductVariant $variant */
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:328:        foreach ($effectItems->sortBy(static fn ($item): string => str_pad((string) $item->product_id, 20, '0', STR_PAD_LEFT).'-'.str_pad((string) ($item->product_variant_id ?? 0), 20, '0', STR_PAD_LEFT).'-'.str_pad((string) $item->id, 20, '0', STR_PAD_LEFT)) as $item) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:338:            /** @var ProductVariant|null $variant */
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:339:            $variant = $item->product_variant_id !== null ? $variants->get((int) $item->product_variant_id) : null;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:352:                'product_variant_id' => $variant?->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:364:                    'product_variant_id' => $variant?->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductDeletionService.php:123:                if (Schema::hasColumn('products', 'default_variant_id')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductDeletionService.php:124:                    $locked->forceFill(['default_variant_id' => null])->saveQuietly();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:14:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:202:                ->where('variants_enabled', false)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:241:            if (Schema::hasTable('product_variants')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:242:                $lowVariants = ProductVariant::query()
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:260:                        'action_url' => route('admin.products.variants.index', $variant->product_id).'#variant-'.$variant->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:261:                        'metadata_json' => ['product_variant_id' => $variant->id, 'stock' => $variant->stock_quantity, 'threshold' => $variant->low_stock_threshold],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailService.php:61:        $this->loadMany($order, 'items', 'order_items', ['id', 'order_id'], $warnings, ['product' => ['products', ['id']], 'variant' => ['product_variants', ['id']]]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:9:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:27:    public function uploadVariant(Product $product, ProductVariant $variant, array $files): int
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:34:    private function uploadInternal(Product $product, ?ProductVariant $variant, array $files): int
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:45:            $variant ? $scope->where('product_variant_id', $variant->id) : $scope->whereNull('product_variant_id');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:49:                'product_variant_id' => $variant?->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:106:    public function cloneVariantImages(ProductVariant $source, ProductVariant $target): int
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:118:                'product_variant_id' => $target->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:138:        abort_if($image->product_variant_id !== null, 422, 'Glavna slika artikla ne može biti slika varijante.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:143:                ->whereNull('product_variant_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:151:            ProductImage::query()->where('product_id', $product->id)->whereNull('product_variant_id')->update(['is_primary' => false]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:235:            ->whereNull('product_variant_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:258:            ->whereNull('product_variant_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:275:            ->whereNull('product_variant_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:292:        $variantId = $image->product_variant_id !== null ? (int) $image->product_variant_id : null;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:298:            $variantId !== null ? $nextQuery->where('product_variant_id', $variantId) : $nextQuery->whereNull('product_variant_id');
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/OrderItem.php:16:        'order_id', 'product_id', 'product_variant_id', 'product_sku', 'product_name', 'quantity',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/OrderItem.php:18:        'variant_sku_snapshot', 'variant_name_snapshot', 'variant_attributes_json',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/OrderItem.php:37:            'variant_attributes_json' => 'array',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/OrderItem.php:43:    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ProductImage.php:16:        'product_id', 'product_variant_id', 'file_path', 'storage_disk', 'original_filename', 'mime_type', 'file_size',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ProductImage.php:21:    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/StockMovement.php:15:        'event_key', 'product_id', 'product_variant_id', 'order_id', 'user_id', 'movement_type', 'source', 'quantity_change',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/StockMovement.php:37:        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/AfterSalesActionItem.php:13:        'after_sales_action_id', 'after_sales_case_item_id', 'product_id', 'product_variant_id', 'sku_snapshot',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/AfterSalesActionItem.php:25:    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ProductVariantSpecValue.php:10:final class ProductVariantSpecValue extends Model
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ProductVariantSpecValue.php:12:    protected $table = 'product_variant_spec_values';
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ProductVariantSpecValue.php:15:    protected $fillable = ['product_variant_id', 'field_id', 'value_text', 'value_detail', 'value_json', 'value_number', 'value_boolean'];
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ProductVariantSpecValue.php:18:    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ProductVariant.php:12:final class ProductVariant extends Model
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ProductVariant.php:37:    public function specificationValues(): HasMany { return $this->hasMany(ProductVariantSpecValue::class); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/AfterSalesCaseItem.php:12:    protected $fillable = ['after_sales_case_id', 'order_item_id', 'product_id', 'product_variant_id', 'sku_snapshot', 'product_name_snapshot', 'quantity', 'issue_description'];
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/AfterSalesCaseItem.php:16:    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Product.php:20:        'legacy_synced_at', 'locally_modified_at', 'completeness_percent', 'name_is_manual', 'source_product_id', 'variants_enabled', 'default_variant_id',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Product.php:36:            'variants_enabled' => 'boolean',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Product.php:52:    public function images(): HasMany { return $this->hasMany(ProductImage::class)->whereNull('product_variant_id')->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id'); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Product.php:54:    public function primaryImage(): HasOne { return $this->hasOne(ProductImage::class)->whereNull('product_variant_id')->where('is_primary', true)->orderBy('sort_order'); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Product.php:55:    public function variants(): HasMany { return $this->hasMany(ProductVariant::class)->whereNull('deleted_at')->orderByDesc('is_default')->orderBy('sort_order')->orderBy('id'); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Product.php:56:    public function activeVariants(): HasMany { return $this->hasMany(ProductVariant::class)->whereNull('deleted_at')->where('status', 'active')->orderByDesc('is_default')->orderBy('sort_order')->orderBy('id'); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Product.php:57:    public function defaultVariant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'default_variant_id'); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ProductWarranty.php:14:        'warranty_number', 'order_id', 'order_item_id', 'product_id', 'product_variant_id', 'user_id', 'warranty_rule_id',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ProductWarranty.php:41:    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductRequest.php:82:            if ($product && (bool) $product->variants_enabled) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreOrderRequest.php:8:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreOrderRequest.php:34:            'items.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreOrderRequest.php:45:                $variantId = (int) ($item['product_variant_id'] ?? 0);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreOrderRequest.php:52:                if ((bool) $product->variants_enabled) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreOrderRequest.php:54:                        $validator->errors()->add('items.'.$index.'.product_variant_id', 'Izaberi konfiguraciju proizvoda.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreOrderRequest.php:57:                    $valid = ProductVariant::query()->whereKey($variantId)->where('product_id', $productId)->where('status', 'active')->whereNull('deleted_at')->exists();
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreOrderRequest.php:58:                    if (!$valid) $validator->errors()->add('items.'.$index.'.product_variant_id', 'Izabrana konfiguracija nije dostupna za ovaj proizvod.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreOrderRequest.php:60:                    $validator->errors()->add('items.'.$index.'.product_variant_id', 'Ovaj proizvod nema aktivne varijante.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreOrderRequest.php:71:            $variantId = (int) ($item['product_variant_id'] ?? 0);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreOrderRequest.php:73:            if ($productId > 0 && $quantity > 0) $items[] = ['product_id' => $productId, 'product_variant_id' => $variantId > 0 ? $variantId : null, 'quantity' => $quantity];
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductVariantRequest.php:8:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductVariantRequest.php:16:final class ProductVariantRequest extends FormRequest
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductVariantRequest.php:31:        $variantId = $variant instanceof ProductVariant ? $variant->id : null;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductVariantRequest.php:33:            'sku' => ['required', 'string', 'max:100', 'regex:#^[A-Z0-9._/-]+$#', Rule::unique('product_variants', 'sku')->ignore($variantId), Rule::unique('products', 'sku')],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Resources/ProductResource.php:36:            'variants_enabled' => (bool) $this->variants_enabled,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Resources/OrderResource.php:45:                'product_variant_id' => $item->product_variant_id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Resources/OrderResource.php:46:                'sku' => $item->variant_sku_snapshot ?: $item->product_sku,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Resources/OrderResource.php:47:                'name' => $item->product_name.($item->variant_name_snapshot ? ' — '.$item->variant_name_snapshot : ''),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Resources/OrderResource.php:48:                'variant_name' => $item->variant_name_snapshot,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Resources/OrderResource.php:49:                'variant_attributes' => $item->variant_attributes_json ?? [],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/DirectSaleController.php:24:            'product_variant_id' => ['nullable', 'integer', 'min:1'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/InventoryController.php:41:                ->where('variants_enabled', false)
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/InventoryController.php:55:                'lowStock' => Product::query()->whereNull('deleted_at')->where('variants_enabled', false)->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->orderBy('stock_quantity')->limit(50)->get(),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/InventoryController.php:127:            $rows = Product::query()->whereNull('deleted_at')->where('variants_enabled', false)->orderBy('sku')->get(['sku', 'name', 'stock_quantity', 'low_stock_threshold', 'status']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/CatalogDictionaryController.php:495:            'variants' => ['product_variant_spec_values', 'field_id'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:8:use App\Http\Requests\ProductVariantRequest;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:11:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:15:use App\Services\ProductVariantService;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:21:final class ProductVariantController extends Controller
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:45:    public function store(ProductVariantRequest $request, Product $product, ProductVariantService $service): RedirectResponse
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:49:        return redirect()->route('admin.products.variants.index', $product)->with('status', 'Varijanta '.$variant->sku.' je kreirana.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:52:    public function update(ProductVariantRequest $request, Product $product, ProductVariant $variant, ProductVariantService $service): RedirectResponse
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:60:    public function adjust(Request $request, Product $product, ProductVariant $variant, ProductVariantService $service): RedirectResponse
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:73:    public function setDefault(Request $request, Product $product, ProductVariant $variant, ProductVariantService $service): RedirectResponse
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:81:    public function archive(Request $request, Product $product, ProductVariant $variant, ProductVariantService $service): RedirectResponse
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:89:    public function images(Request $request, Product $product, ProductVariant $variant, ProductImageService $images): RedirectResponse
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:98:    public function deleteImage(Product $product, ProductVariant $variant, ProductImage $image, ProductImageService $images): RedirectResponse
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:101:        abort_unless((int) $variant->product_id === (int) $product->id && (int) $image->product_variant_id === (int) $variant->id, 404);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/AfterSalesController.php:228:                'product_variant_id' => $item->product_variant_id !== null ? (int) $item->product_variant_id : null,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/AfterSalesController.php:322:                'product_variant_id' => $item->product_variant_id !== null ? (int) $item->product_variant_id : null,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/AfterSalesController.php:73:                        'product_variant_id' => $item->product_variant_id !== null ? (int) $item->product_variant_id : null,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/AfterSalesController.php:74:                        'sku' => (string) ($item->variant_sku_snapshot ?: $item->product_sku ?: ''),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/AfterSalesController.php:75:                        'name' => (string) $item->product_name.($item->variant_name_snapshot ? ' — '.$item->variant_name_snapshot : ''),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/AfterSalesController.php:203:                'product_variant_id' => $item->product_variant_id !== null ? (int) $item->product_variant_id : null,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/OrderController.php:61:            'variants_enabled',
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/OrderController.php:62:            'default_variant_id',
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/PaymentsInventoryDoctorCommand.php:35:        'stock_movements' => ['stock_receipt_id', 'inventory_count_id', 'product_variant_id', 'event_key', 'metadata_json'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/PaymentsInventoryDoctorCommand.php:103:            $lowStock = DB::table('products')->whereNull('deleted_at')->where('variants_enabled', false)->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count();
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/PaymentsInventoryDoctorCommand.php:104:            $lowVariantStock = Schema::hasTable('product_variants') ? DB::table('product_variants')->whereNull('deleted_at')->where('status', 'active')->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count() : 0;
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CatalogSettingsDoctorCommand.php:33:            'product_variant_spec_values' => ['product_variant_id', 'field_id', 'value_json'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:72:        'product_variants',
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:73:        'product_variant_spec_values',
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:91:        'order_items' => ['id', 'order_id', 'product_id', 'product_variant_id', 'variant_sku_snapshot', 'variant_name_snapshot', 'variant_attributes_json', 'quantity', 'commission_total_eur_snapshot', 'purchase_unit_rsd_snapshot', 'purchase_total_rsd_snapshot', 'cost_source_snapshot', 'brand_name_snapshot', 'product_line_name_snapshot', 'product_type_name_snapshot'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:98:        'stock_movements' => ['id', 'product_id', 'product_variant_id', 'quantity_change', 'event_key', 'source', 'metadata_json', 'stock_receipt_id', 'inventory_count_id'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:106:        'after_sales_case_items' => ['id', 'after_sales_case_id', 'order_item_id', 'product_id', 'product_variant_id', 'product_name_snapshot', 'quantity'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:111:        'after_sales_action_items' => ['id', 'after_sales_action_id', 'after_sales_case_item_id', 'product_id', 'product_variant_id', 'quantity', 'disposition', 'stock_effect'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:122:        'product_warranties' => ['id', 'warranty_number', 'order_id', 'order_item_id', 'product_variant_id', 'user_id', 'status', 'starts_at', 'expires_at', 'duration_months', 'duration_days', 'serial_numbers_json', 'next_maintenance_at'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:131:        'products' => ['id', 'product_type_id', 'brand_id', 'product_line_id', 'model_name', 'purchase_price_rsd', 'completeness_percent', 'name_is_manual', 'source_product_id', 'variants_enabled', 'default_variant_id'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:136:        'product_variants' => ['id', 'product_id', 'sku', 'name', 'price_amount', 'price_currency', 'stock_quantity', 'low_stock_threshold', 'status', 'is_default', 'warranty_rule_id', 'deleted_at'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:137:        'product_variant_spec_values' => ['product_variant_id', 'field_id', 'value_text', 'value_detail', 'value_number', 'value_boolean'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/OrderCostSnapshotsCommand.php:116:                    (string) ($row->variant_sku_snapshot ?: $row->product_sku ?: '—'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ManagementReportsDoctorCommand.php:91:                    (string) ($row->variant_sku_snapshot ?: $row->product_sku ?: '—'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/OrderCreateDoctorCommand.php:30:        foreach (['users', 'roles', 'products', 'product_variants', 'bank_accounts'] as $table) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/PerformanceDoctorCommand.php:33:            'product_variants' => ['product_variants_runtime_v216_idx'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:8:use App\Services\ProductVariantService;
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:16:final class ProductVariantsDoctorCommand extends Command
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:21:    public function handle(ProductVariantService $variants): int
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:27:                Product::query()->where('variants_enabled', true)->orderBy('id')->chunkById(100, function ($products) use ($variants): void {
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:38:            'products' => ['id', 'variants_enabled', 'default_variant_id', 'stock_quantity', 'price_amount', 'price_currency'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:39:            'product_variants' => ['id', 'product_id', 'sku', 'name', 'price_amount', 'price_currency', 'stock_quantity', 'low_stock_threshold', 'status', 'is_default', 'warranty_rule_id', 'deleted_at'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:40:            'product_variant_spec_values' => ['product_variant_id', 'field_id', 'value_text', 'value_detail', 'value_number', 'value_boolean'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:41:            'product_images' => ['product_id', 'product_variant_id'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:42:            'order_items' => ['product_id', 'product_variant_id', 'variant_sku_snapshot', 'variant_name_snapshot', 'variant_attributes_json'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:43:            'stock_movements' => ['product_id', 'product_variant_id', 'event_key'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:44:            'after_sales_case_items' => ['product_id', 'product_variant_id'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:45:            'after_sales_action_items' => ['product_id', 'product_variant_id'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:46:            'product_warranties' => ['product_id', 'product_variant_id'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:68:        $routes = ['admin.products.variants.index', 'admin.products.variants.store', 'admin.products.variants.update', 'admin.products.variants.stock', 'admin.products.variants.default'];
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:77:            $duplicateSku = DB::table('product_variants')->selectRaw('LOWER(sku) normalized, COUNT(*) total')->whereNull('deleted_at')->groupByRaw('LOWER(sku)')->havingRaw('COUNT(*) > 1')->count();
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:78:            $crossSku = DB::table('product_variants as variants')->join('products', DB::raw('LOWER(products.sku)'), '=', DB::raw('LOWER(variants.sku)'))->whereNull('variants.deleted_at')->count();
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:79:            $multipleDefaults = DB::table('product_variants')->select('product_id')->whereNull('deleted_at')->where('is_default', true)->groupBy('product_id')->havingRaw('COUNT(*) > 1')->count();
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:80:            $wrongDefault = DB::table('products')->join('product_variants', 'product_variants.id', '=', 'products.default_variant_id')->whereColumn('product_variants.product_id', '!=', 'products.id')->count();
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:81:            $wrongOrderVariant = DB::table('order_items')->join('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')->whereColumn('product_variants.product_id', '!=', 'order_items.product_id')->count();
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:82:            $wrongAfterSales = DB::table('after_sales_case_items')->join('product_variants', 'product_variants.id', '=', 'after_sales_case_items.product_variant_id')->whereColumn('product_variants.product_id', '!=', 'after_sales_case_items.product_id')->count();
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:83:            $aggregateMismatch = DB::table('products')->where('variants_enabled', true)->whereRaw('stock_quantity <> (SELECT COALESCE(SUM(stock_quantity),0) FROM product_variants WHERE product_variants.product_id=products.id AND product_variants.status=? AND product_variants.deleted_at IS NULL)', ['active'])->count();
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/orders/create.blade.php:64:                                                    @if($product->variants_enabled) · {{ $product->activeVariants->count() }} varijanti @endif
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/orders/create.blade.php:70:                                        <select name="items[{{ $index }}][product_variant_id]" data-order-variant>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/orders/create.blade.php:176:        const preselected = Number(initialItems?.[index]?.product_variant_id || (index === 0 ? selectedVariantId : 0));
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:347:                    @if($product->exists && $product->variants_enabled)
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/stock/index.blade.php:6:<section class="panel form-section"><h2>Trenutno stanje</h2><div class="admin-table-wrap flat-table"><table class="admin-table"><thead><tr><th>SKU</th><th>Artikal</th><th>Lager</th><th>Prag</th>@can('stock.adjust')<th>Korekcija</th>@endcan</tr></thead><tbody>@forelse($products as $product)<tr><td><strong>{{ $product->sku }}</strong></td><td>{{ $product->name }}</td><td><strong class="{{ $product->stock_quantity <= $product->low_stock_threshold ? 'text-danger' : 'text-success' }}">{{ $product->stock_quantity }}</strong></td><td>{{ $product->low_stock_threshold }}</td>@can('stock.adjust')<td>@if($product->variants_enabled)<a class="button button-ghost button-small" href="{{ route('admin.products.variants.index',$product) }}">Varijante ({{ $product->variants_count }})</a>@else<form class="inline-form" method="post" action="{{ route('admin.stock.adjust',$product) }}">@csrf<input type="hidden" name="idempotency_key" value="{{ $idempotencyKeys[$product->id] }}"><input type="number" name="quantity_change" required placeholder="+/-" class="ux-stock-max-width-90"><input name="note" required maxlength="1000" placeholder="Obavezan razlog"><button class="button button-ghost button-small" type="submit">Primeni</button></form>@endif</td>@endcan</tr>@empty<tr><td colspan="5">Nema artikala.</td></tr>@endforelse</tbody></table></div></section>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/catalog/index.blade.php:308:                @if($product->variants_enabled)<span class="success">{{ $product->activeVariants->count() }} varijanti</span>@endif
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/catalog/index.blade.php:326:                        @if($product->variants_enabled && $variantPrices->isNotEmpty() && $variantPrices->min() !== $variantPrices->max())
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/catalog/show.blade.php:151:        @if($product->variants_enabled && $product->activeVariants->isNotEmpty())
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/catalog/show.blade.php:253:            @if(!$product->variants_enabled)
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/catalog/show.blade.php:312:            @if($product->variants_enabled)
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/catalog/show.blade.php:315:                    <select name="product_variant_id" required>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/catalog/show.blade.php:318:                            <option value="{{ $variant->id }}" @selected((string) old('product_variant_id') === (string) $variant->id)>
/home/icaffeco/ald1n-project/apps/cms/current/config/release.php:36:            'product_variants',
/home/icaffeco/ald1n-project/apps/cms/current/config/release.php:70:            'product_variants',
/home/icaffeco/ald1n-project/apps/cms/current/config/release.php:110:            'product_variants',
/home/icaffeco/ald1n-project/apps/cms/current/config/release.php:150:            'product_variants',
/home/icaffeco/ald1n-project/apps/cms/current/config/release.php:307:        'product_variants' => [
/home/icaffeco/ald1n-project/apps/cms/current/bin/storage-capacity-total-smoke.php:39:$variantRequest = (string) file_get_contents($root.'/app/Http/Requests/ProductVariantRequest.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/storage-capacity-total-smoke.php:41:$variantService = (string) file_get_contents($root.'/app/Services/ProductVariantService.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/product-media-ux-smoke.php:33:$check('Reorder čuva sve slike i odbacuje tuđe ID-jeve', str_contains($service, '$existingLookup') && str_contains($service, 'Neposlati ID-jevi se dodaju na kraj') && str_contains($service, "whereNull('product_variant_id')"));
/home/icaffeco/ald1n-project/apps/cms/current/bin/catalog-settings-integrity-hotfix-smoke.php:25:$variantRequest = $source('app/Http/Requests/ProductVariantRequest.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/catalog-settings-integrity-hotfix-smoke.php:26:$variantService = $source('app/Services/ProductVariantService.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/catalog-settings-integrity-hotfix-smoke.php:35:$check('Trajno brisanje polja eksplicitno uklanja sve povezane reference', str_contains($fieldLifecycle, "product_variant_spec_values')->where('field_id'") && str_contains($fieldLifecycle, "product_spec_values')->where('field_id'") && str_contains($fieldLifecycle, "product_type_fields')->where('field_id'") && str_contains($fieldLifecycle, "specification_options')->where('field_id'"));
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:742:$productVariantsMigration = (string) file_get_contents($root.'/database/migrations/2026_07_31_000029_create_product_variants_beta7_20.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:743:$productVariantService = (string) file_get_contents($root.'/app/Services/ProductVariantService.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:744:$productVariantRequest = (string) file_get_contents($root.'/app/Http/Requests/ProductVariantRequest.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:745:$productVariantController = (string) file_get_contents($root.'/app/Http/Controllers/Admin/ProductVariantController.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:748:$productVariantDoctor = (string) file_get_contents($root.'/app/Console/Commands/ProductVariantsDoctorCommand.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:749:$productVariantFeature = (string) file_get_contents($root.'/tests/Feature/ProductVariantsWorkflowTest.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:750:$check('beta7.20 migracija uvodi varijante specifikacije slike i snapshot', str_contains($productVariantsMigration, 'product_variants') && str_contains($productVariantsMigration, 'product_variant_spec_values') && str_contains($productVariantsMigration, 'variant_attributes_json'));
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:754:$check('beta7.20 serverska validacija štiti SKU i korelisane specifikacije', str_contains($productVariantRequest, "Rule::unique('product_variants'") && str_contains($productVariantRequest, 'specification_option_dependencies'));
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:757:$check('beta7.20 porudžbina čuva variant snapshot i vraća isti lager', str_contains((string) file_get_contents($root.'/app/Services/OrderService.php'), 'variant_sku_snapshot') && str_contains((string) file_get_contents($root.'/app/Services/OrderWorkflowService.php'), 'product_variant_id'));
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:758:$check('beta7.20 postprodaja garancija i stock movement nose variant id', str_contains((string) file_get_contents($root.'/app/Services/AfterSalesActionService.php'), "'product_variant_id' => \$variant?->id") && str_contains((string) file_get_contents($root.'/app/Services/WarrantyService.php'), "'product_variant_id' => \$item->product_variant_id") && str_contains((string) file_get_contents($root.'/app/Models/StockMovement.php'), 'product_variant_id'));
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:759:$check('beta7.20 decommission clone vise ne nudi kopiranje varijanti', !str_contains((string) file_get_contents($root.'/resources/views/admin/products/clone.blade.php'), 'copy_variants') && !str_contains((string) file_get_contents($root.'/app/Http/Controllers/Admin/ProductController.php'), "'copy_variants'"));
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:972:$productVariantRequestRegex = (string) @file_get_contents($root.'/app/Http/Requests/ProductVariantRequest.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:974:$check('v2.1.3.3 ProductVariantRequest zadržava validan SKU regex delimiter', str_contains($productVariantRequestRegex, "'regex:#^[A-Z0-9._/-]+$#'"));
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:979:$storageVariantRequest = (string) @file_get_contents($root.'/app/Http/Requests/ProductVariantRequest.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:980:$storageVariantService = (string) @file_get_contents($root.'/app/Services/ProductVariantService.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/stable-maintenance-smoke.php:32:$variantRequest = $source('app/Http/Requests/ProductVariantRequest.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/product-variant-smoke.php:13:$migration = (string) file_get_contents($root.'/database/migrations/2026_07_31_000029_create_product_variants_beta7_20.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/product-variant-smoke.php:14:$service = (string) file_get_contents($root.'/app/Services/ProductVariantService.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/product-variant-smoke.php:24:$doctor = (string) file_get_contents($root.'/app/Console/Commands/ProductVariantsDoctorCommand.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/product-variant-smoke.php:26:$check('Migracija uvodi varijante, specifikacije i istorijske snapshot kolone', str_contains($migration, 'product_variants') && str_contains($migration, 'product_variant_spec_values') && str_contains($migration, 'variant_attributes_json'));
/home/icaffeco/ald1n-project/apps/cms/current/bin/product-variant-smoke.php:30:$check('Porudžbina zaključava varijantu i čuva snapshot', str_contains($order, 'lockForUpdate') && str_contains($order, 'variant_sku_snapshot') && str_contains($order, 'variant_attributes_json'));
/home/icaffeco/ald1n-project/apps/cms/current/bin/product-variant-smoke.php:31:$check('Otkazivanje vraća lager na istu varijantu', str_contains($workflow, 'product_variant_id') && str_contains($workflow, 'cancel-return'));
/home/icaffeco/ald1n-project/apps/cms/current/bin/product-variant-smoke.php:32:$check('Postprodajna zamena i povrat koriste konkretnu varijantu', str_contains($afterSales, 'ProductVariant::query()') && str_contains($afterSales, "'product_variant_id' => \$variant?->id"));
/home/icaffeco/ald1n-project/apps/cms/current/bin/product-variant-smoke.php:37:$check('Forma porudžbine zahteva izbor konfiguracije', str_contains($orderView, 'product_variant_id') && str_contains($orderView, 'variantMap'));
/home/icaffeco/ald1n-project/apps/cms/current/bin/cms-v2.1.6-smoke.php:40:$check('Migracija kreira indekse galerija i varijanti', str_contains($migration, 'product_images_primary_sort_v216_idx') && str_contains($migration, 'product_variants_runtime_v216_idx'));
/home/icaffeco/ald1n-project/apps/cms/current/bin/product-save-regex-hotfix-smoke.php:22:$variantRequest = $source('app/Http/Requests/ProductVariantRequest.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/product-save-regex-hotfix-smoke.php:27:$check('ProductVariantRequest koristi bezbedan SKU regex delimiter', str_contains($variantRequest, $safeRule));
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/ProductVariantsUiContractTest.php:9:final class ProductVariantsUiContractTest extends TestCase
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/ProductVariantsUiContractTest.php:18:        self::assertStringContainsString('product_variant_id', $order);
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/CatalogSettingsIntegrityHotfixContractTest.php:34:        self::assertStringContainsString("product_variant_spec_values')->where('field_id'", $service);
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/DirectSaleMaxUnitPriceContractTest.php:8:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/DirectSaleMaxUnitPriceContractTest.php:25:        $variantRsd = new ProductVariant();
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/DirectSaleMaxUnitPriceContractTest.php:33:        $variantEur = new ProductVariant();
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/DirectSaleMaxUnitPriceContractTest.php:82:        self::assertStringContainsString('$variant instanceof ProductVariant ? $variant : $lockedProduct', $direct);
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/DirectSaleMaxUnitPriceContractTest.php:90:        self::assertStringContainsString('private function priceRsd(Product|ProductVariant $product, ?float $rate): float', $orders);
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/ProductSaveRegexHotfixContractTest.php:17:        $variant = (string) file_get_contents($root.'/app/Http/Requests/ProductVariantRequest.php');
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/ProductVariantsMigrationContractTest.php:9:final class ProductVariantsMigrationContractTest extends TestCase
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/ProductVariantsMigrationContractTest.php:13:        $source = (string) file_get_contents(dirname(__DIR__, 2).'/database/migrations/2026_07_31_000029_create_product_variants_beta7_20.php');
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/ProductVariantsMigrationContractTest.php:14:        self::assertStringContainsString('product_variants', $source);
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/ProductVariantsMigrationContractTest.php:15:        self::assertStringContainsString('product_variant_spec_values', $source);
/home/icaffeco/ald1n-project/apps/cms/current/tests/Feature/ProductVariantsWorkflowTest.php:16:final class ProductVariantsWorkflowTest extends TestCase
/home/icaffeco/ald1n-project/apps/cms/current/tests/Feature/ProductVariantsWorkflowTest.php:63:        self::assertTrue($product->variants_enabled);
/home/icaffeco/ald1n-project/apps/cms/current/tests/Feature/ProductVariantsWorkflowTest.php:65:        self::assertNotNull($product->default_variant_id);
/home/icaffeco/ald1n-project/apps/cms/current/tests/Feature/CatalogDetailPageTest.php:9:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/tests/Feature/CatalogDetailPageTest.php:143:        $product->update(['variants_enabled' => true]);
/home/icaffeco/ald1n-project/apps/cms/current/tests/Feature/CatalogDetailPageTest.php:145:        ProductVariant::query()->create([
/home/icaffeco/ald1n-project/packages/api-contract/openapi.yaml:456:                      product_variant_id: { type: [integer, 'null'] }
/home/icaffeco/ald1n-project/packages/api-contract/openapi.yaml:3774:                  product_variant_id: { type: [integer, 'null'] }
/home/icaffeco/ald1n-project/packages/api-contract/openapi.yaml:3812:                  product_variant_id: { type: [integer, 'null'] }
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/after-sales-admin-api.ts:65:  product_variant_id: number | null;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/after-sales-admin-api.ts:76:  product_variant_id: number | null;
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:140:export type ProductVariant = {
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:167:  variants_enabled: boolean;
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:168:  variants?: ProductVariant[];
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:201:  product_variant_id: Nullable<number>;
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:205:  variant_attributes: Record<string, unknown> | unknown[];
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:423:    product_variant_id: Nullable<number>;
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:437:  product_variant_id: Nullable<number>;
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:692:  product_variant_id: Nullable<number>;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/checkout.tsx:140:        product_variant_id: item.variantId,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/product/[slug].tsx:36:    if (!product?.variants_enabled) {
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/product/[slug].tsx:54:  const selectedVariant = product.variants_enabled
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/product/[slug].tsx:63:  const requiresVariant = product.variants_enabled;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/product/[slug].tsx:142:      {product.variants_enabled && product.variants?.length ? <Card><Text style={styles.sectionTitle}>Izaberi konfiguraciju</Text>{product.variants.map((variant) => {
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:456:                      product_variant_id: { type: [integer, 'null'] }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3774:                  product_variant_id: { type: [integer, 'null'] }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3812:                  product_variant_id: { type: [integer, 'null'] }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/BACKEND-PREFLIGHT-20260807.txt:118:app/Http/Controllers/Admin/ProductVariantController.php:41:            'stockIdempotencyKey' => (string) Str::uuid(),
/home/icaffeco/ald1n-project/apps/mobile/current/docs/BACKEND-PREFLIGHT-20260807.txt:119:app/Http/Controllers/Admin/ProductVariantController.php:45:    public function store(ProductVariantRequest $request, Product $product, ProductVariantService $service): RedirectResponse
/home/icaffeco/ald1n-project/apps/mobile/current/docs/BACKEND-PREFLIGHT-20260807.txt:120:app/Http/Controllers/Admin/ProductVariantController.php:67:            'idempotency_key' => ['required', 'string', 'max:200'],
/home/icaffeco/ald1n-project/apps/mobile/current/docs/BACKEND-PREFLIGHT-20260807.txt:121:app/Http/Controllers/Admin/ProductVariantController.php:69:        $service->adjustStock($product, $variant, (int) $data['quantity_change'], (string) $data['note'], (string) $data['idempotency_key'], $request->user());
/home/icaffeco/ald1n-project/apps/mobile/current/docs/PHASE-3A-SCOPE.md:18:Ista kombinacija `product_id + product_variant_id` postoji samo jednom u korpi. Ponovno dodavanje povećava količinu do trenutnog lokalno poznatog lagera. Server ostaje autoritet i ponovo proverava lager/cenu/kurs unutar transakcije pri kreiranju porudžbine.
--- END REMAINING VARIANT SIGNAL MAP ---

============================================================
10. TARGETED GIT SAFETY
============================================================

FAIL: git diff --check failed for apps/cms/current/resources/views/catalog/index.blade.php

============================================================
ROLLBACK
============================================================
ROLLBACK_DB_PRODUCT_FLAGS=PASS
ROLLBACK_SOURCE=RESTORED
ROLLBACK_ROUTE_CACHE_STATE=RESTORED_BEST_EFFORT
