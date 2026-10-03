#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$packageMode = in_array('--package', $_SERVER['argv'] ?? [], true);
$checks = [];
$check = static function (string $label, bool $ok) use (&$checks): void {
    $checks[] = [$label, $ok];
    fwrite(STDOUT, ($ok ? 'PASS' : 'FAIL')."  {$label}\n");
};

$required = [
    'artisan', 'composer.json', 'composer.lock', '.env.example', 'VERSION', 'RELEASE-TAG', 'UPGRADE-FROM', 'docs/UPGRADE-V2.1-BETA1.md', 'docs/UPGRADE-V2.1-BETA1.1.md', 'docs/UPGRADE-V2.1-BETA1.2.md', 'docs/UPGRADE-V2.1-BETA1.3.md', 'docs/UPGRADE-V2.1-BETA2.md', 'docs/UPGRADE-V2.1-BETA3.md', 'docs/UPGRADE-V2.1-BETA3.2.md', 'docs/UPGRADE-V2.1-BETA3.1.md', 'docs/UPGRADE-V2.1-BETA4.md', 'docs/UPGRADE-V2.1-BETA5.md', 'docs/UPGRADE-V2.1-BETA6.md', 'docs/UPGRADE-V2.1-BETA7.md', 'docs/UPGRADE-V2.1-BETA7.1.md', 'docs/UPGRADE-V2.1-BETA7.2.md', 'docs/UPGRADE-V2.1-BETA7.3.md', 'docs/UPGRADE-V2.1-BETA7.4.md', 'docs/UPGRADE-V2.1-BETA7.5.md', 'docs/UPGRADE-V2.1-BETA7.6.md', 'docs/UPGRADE-V2.1-BETA7.7.md', 'docs/UPGRADE-V2.1-BETA7.8.md', 'docs/UPGRADE-V2.1-BETA7.9.md', 'docs/UPGRADE-V2.1-BETA7.10.md', 'docs/UPGRADE-V2.1-BETA7.11.md', 'docs/UPGRADE-V2.1-BETA7.12.md', 'docs/UPGRADE-V2.1-BETA7.13.md', 'docs/UPGRADE-V2.1-BETA7.14.md', 'docs/UPGRADE-V2.1-BETA7.14.1.md', 'docs/UPGRADE-V2.1-BETA7.15.md', 'docs/UPGRADE-V2.1-BETA7.16.md', 'docs/UPGRADE-V2.1-BETA7.17.md', 'docs/UPGRADE-V2.1-BETA7.17.1.md', 'docs/UPGRADE-V2.1-BETA7.17.2.md', 'docs/UPGRADE-V2.1-BETA7.18.md', 'docs/UPGRADE-V2.1-BETA7.18.1.md', 'docs/UPGRADE-V2.1-BETA7.19.md', 'docs/UPGRADE-V2.1-BETA7.20.md', 'DATABASE-MIGRATION-REQUIRED.txt',
    'app/Models/Order.php', 'app/Models/OrderItem.php', 'app/Models/OrderDocument.php', 'app/Models/OrderCommission.php', 'app/Models/CommissionStatusHistory.php', 'app/Models/CommissionPaymentBatch.php', 'app/Models/OrderInternalNote.php', 'app/Models/OrderAssignment.php', 'app/Models/OrderStatusHistory.php', 'app/Models/StockMovement.php', 'app/Models/IdempotencyKey.php', 'app/Models/OrderPayment.php', 'app/Models/OrderDelivery.php', 'app/Models/AfterSalesCase.php', 'app/Models/AfterSalesCaseItem.php', 'app/Models/AfterSalesMessage.php', 'app/Models/AfterSalesAttachment.php', 'app/Models/AfterSalesStatusHistory.php', 'app/Models/AfterSalesAction.php', 'app/Models/AfterSalesActionItem.php', 'app/Models/FieldServiceTeam.php', 'app/Models/FieldWorkOrder.php', 'app/Models/FieldWorkOrderAttachment.php', 'app/Models/ServicePartSupplier.php', 'app/Models/ServicePart.php', 'app/Models/FieldWorkOrderPart.php', 'app/Models/ServicePartMovement.php', 'app/Models/ServicePartPurchaseRequest.php', 'app/Models/ServicePartPurchaseRequestItem.php', 'app/Models/WarrantyRule.php', 'app/Models/ProductWarranty.php', 'app/Models/WarrantyMaintenanceRecord.php', 'app/Models/OrderEmailOutbox.php', 'app/Models/StockReceipt.php', 'app/Models/StockReceiptItem.php', 'app/Models/InventoryCount.php', 'app/Models/InventoryCountItem.php', 'app/Models/AutomationRun.php', 'app/Models/OperationalAlert.php', 'app/Models/NotificationPreference.php', 'app/Models/BackupRun.php', 'app/Models/SystemHealthSnapshot.php', 'app/Models/SystemRuntimeState.php', 'app/Models/SecurityEvent.php',
    'app/Services/OrderService.php', 'app/Services/OrderWorkflowService.php', 'app/Services/InventoryService.php',
    'app/Services/IdempotencyService.php', 'app/Services/OrderPaymentService.php', 'app/Services/IpsPaymentPayloadService.php', 'app/Services/AdvancedInventoryService.php', 'app/Services/LegacyReadOnlyGuard.php', 'app/Services/OrderAccessService.php', 'app/Services/OrderReportService.php', 'app/Services/CommissionReportService.php', 'app/Services/CommissionWorkflowService.php', 'app/Services/OrderOperationalService.php', 'app/Services/OrderTimelineService.php', 'app/Services/OperationalNotificationService.php', 'app/Notifications/OperationalNotification.php', 'app/Services/OperationalAutomationService.php', 'app/Services/AutomationReadinessService.php', 'app/Services/BackupService.php', 'app/Services/SystemHealthService.php', 'app/Services/SecurityEventLogger.php', 'app/Services/SensitiveDataSanitizer.php', 'app/Services/OrderIndexService.php', 'app/Services/OrderDetailService.php', 'app/Services/OrderDetailPresenter.php', 'app/Support/ViewValue.php', 'app/Services/OrderDocumentService.php', 'app/Services/AfterSalesAccessService.php', 'app/Services/AfterSalesCaseService.php', 'app/Services/AfterSalesActionService.php', 'app/Services/FieldWorkOrderPlanner.php', 'app/Services/FieldOperationsService.php', 'app/Services/ServicePartsInventoryService.php', 'app/Services/WarrantyService.php', 'app/Services/OrderEmailOutboxService.php', 'app/Services/OrderEmailDispatcher.php', 'app/Services/NbsIpsQrService.php', 'app/Services/DocumentNumberService.php', 'app/Services/Pdf/SimplePdfWriter.php', 'app/Services/Pdf/BusinessDocumentPdfService.php', 'app/Services/Pdf/WarrantyCertificatePdfService.php',
    'app/Http/Requests/StoreOrderRequest.php', 'app/Http/Requests/AdjustStockRequest.php', 'app/Http/Requests/StoreAfterSalesCaseRequest.php', 'app/Http/Requests/StoreAfterSalesMessageRequest.php', 'app/Http/Requests/UpdateAfterSalesCaseRequest.php', 'app/Http/Requests/StoreAfterSalesActionRequest.php', 'app/Http/Requests/CompleteAfterSalesActionRequest.php', 'app/Http/Requests/CancelAfterSalesActionRequest.php', 'app/Http/Requests/StoreFieldServiceTeamRequest.php', 'app/Http/Requests/UpdateFieldServiceTeamRequest.php', 'app/Http/Requests/ScheduleFieldWorkOrderRequest.php', 'app/Http/Requests/CompleteFieldWorkOrderRequest.php', 'app/Http/Requests/CancelFieldWorkOrderRequest.php', 'app/Http/Requests/StoreServicePartRequest.php', 'app/Http/Requests/UpdateServicePartRequest.php', 'app/Http/Requests/AdjustServicePartStockRequest.php', 'app/Http/Requests/StoreServicePartSupplierRequest.php', 'app/Http/Requests/UpdateServicePartSupplierRequest.php', 'app/Http/Requests/StoreFieldWorkOrderPartRequest.php', 'app/Http/Requests/StoreServicePartPurchaseRequest.php', 'app/Http/Requests/CancelServicePartPurchaseRequest.php', 'app/Http/Requests/StoreWarrantyRuleRequest.php', 'app/Http/Requests/UpdateProductWarrantyRequest.php', 'app/Http/Requests/ScheduleWarrantyMaintenanceRequest.php', 'app/Http/Requests/CompleteWarrantyMaintenanceRequest.php',
    'app/Http/Controllers/OrderController.php', 'app/Http/Controllers/WarrantyController.php', 'app/Http/Controllers/AfterSalesController.php', 'app/Http/Controllers/AfterSalesAttachmentController.php', 'app/Http/Controllers/FieldWorkOrderAttachmentController.php', 'app/Http/Controllers/CommissionController.php', 'app/Http/Controllers/NotificationController.php', 'app/Http/Controllers/Api/V1/OrderController.php',
    'app/Http/Controllers/Admin/OrderController.php', 'app/Http/Controllers/Admin/AfterSalesController.php', 'app/Http/Controllers/Admin/AfterSalesActionController.php', 'app/Http/Controllers/Admin/FieldOperationsController.php', 'app/Http/Controllers/Admin/FieldServiceTeamController.php', 'app/Http/Controllers/Admin/ServicePartController.php', 'app/Http/Controllers/Admin/ServicePartSupplierController.php', 'app/Http/Controllers/Admin/FieldWorkOrderPartController.php', 'app/Http/Controllers/Admin/ServicePartPurchaseRequestController.php', 'app/Http/Controllers/Admin/WarrantyController.php', 'app/Http/Controllers/Admin/CommissionController.php', 'app/Http/Controllers/Admin/StockAdjustmentController.php', 'app/Http/Controllers/OrderDocumentController.php', 'app/Http/Controllers/Admin/OrderDocumentController.php', 'app/Http/Controllers/Admin/ReportController.php', 'app/Http/Controllers/Admin/DocumentSettingsController.php', 'app/Http/Controllers/OrderPaymentController.php', 'app/Http/Controllers/OrderDeliveryController.php', 'app/Http/Controllers/Admin/PaymentController.php', 'app/Http/Controllers/Admin/InventoryController.php', 'app/Http/Controllers/Admin/AutomationController.php', 'app/Http/Controllers/Admin/SystemHealthController.php', 'app/Http/Controllers/Admin/TurnstileSettingsController.php', 'app/Http/Controllers/Admin/OrderEmailSettingsController.php',
    'app/Http/Resources/OrderResource.php',
    'app/Console/Commands/OrdersDoctorCommand.php', 'app/Console/Commands/OrderCreateDoctorCommand.php', 'app/Console/Commands/CatalogOwnershipDoctorCommand.php', 'app/Console/Commands/DetailPagesDoctorCommand.php', 'app/Console/Commands/ReportsDoctorCommand.php', 'app/Console/Commands/OperationsDoctorCommand.php', 'app/Console/Commands/PaymentsInventoryDoctorCommand.php', 'app/Console/Commands/RunOperationalAutomationCommand.php', 'app/Console/Commands/AutomationDoctorCommand.php', 'app/Console/Commands/CreateBackupCommand.php', 'app/Console/Commands/BackupDoctorCommand.php', 'app/Console/Commands/SystemHealthCommand.php', 'app/Console/Commands/SchedulerHeartbeatCommand.php', 'app/Console/Commands/TestDatabaseDoctorCommand.php', 'app/Console/Commands/AfterSalesDoctorCommand.php', 'app/Console/Commands/FieldOperationsDoctorCommand.php', 'app/Console/Commands/ServicePartsDoctorCommand.php', 'app/Console/Commands/WarrantiesDoctorCommand.php', 'app/Console/Commands/WarrantiesBackfillCommand.php', 'app/Console/Commands/OrderEmailDispatchCommand.php', 'app/Console/Commands/OrderEmailsDoctorCommand.php',
    'database/migrations/2026_07_22_000006_enable_production_orders_inventory.php', 'database/migrations/2026_07_23_000007_repair_production_schema_beta5.php', 'database/migrations/2026_07_23_000008_repair_authenticated_runtime_beta6.php', 'database/migrations/2026_07_23_000009_create_reports_documents_and_supplier_assignment.php', 'database/migrations/2026_07_23_000010_repair_reports_schema_beta1_2.php', 'database/migrations/2026_07_23_000011_create_operational_orders_commissions_beta2.php', 'database/migrations/2026_07_23_000012_create_payments_advanced_inventory_beta3.php', 'database/migrations/2026_07_23_000013_create_automation_alerts_beta4.php', 'database/migrations/2026_07_23_000014_create_security_backup_health_beta6.php', 'database/migrations/2026_07_29_000015_repair_order_documents_and_payments_beta7_5.php', 'database/migrations/2026_07_30_000016_add_order_completion_beta7_7.php', 'database/migrations/2026_07_30_000017_add_delivery_workflow_beta7_8.php', 'database/migrations/2026_07_30_000018_fix_delivery_note_document_type_beta7_9.php', 'database/migrations/2026_07_30_000019_create_after_sales_cases_beta7_10.php', 'database/migrations/2026_07_30_000020_create_after_sales_actions_beta7_11.php', 'database/migrations/2026_07_30_000021_create_field_operations_beta7_12.php', 'database/migrations/2026_07_30_000022_create_service_parts_procurement_beta7_13.php', 'database/migrations/2026_07_30_000023_enable_document_revisions_beta7_14.php', 'database/migrations/2026_07_30_000024_create_warranties_preventive_maintenance_beta7_15.php', 'database/migrations/2026_07_30_000025_create_order_email_outbox_beta7_16.php',
    'resources/views/orders/index.blade.php', 'resources/views/admin/orders/show.blade.php', 'resources/views/commissions/index.blade.php', 'resources/views/notifications/index.blade.php', 'resources/views/admin/commissions/index.blade.php', 'resources/views/orders/create.blade.php', 'resources/views/orders/show.blade.php', 'resources/views/admin/reports/index.blade.php', 'resources/views/admin/settings/documents.blade.php', 'resources/views/admin/inventory/index.blade.php', 'resources/views/admin/orders/partials/payments.blade.php', 'resources/views/orders/partials/payments.blade.php', 'resources/views/after-sales/index.blade.php', 'resources/views/after-sales/create.blade.php', 'resources/views/after-sales/show.blade.php', 'resources/views/admin/after-sales/index.blade.php', 'resources/views/admin/after-sales/show.blade.php', 'resources/views/admin/field-operations/index.blade.php', 'resources/views/admin/field-operations/show.blade.php', 'resources/views/admin/field-operations/teams.blade.php', 'resources/views/admin/service-parts/index.blade.php', 'resources/views/admin/service-parts/suppliers.blade.php', 'resources/views/admin/service-parts/purchase-requests.blade.php', 'resources/views/admin/service-parts/purchase-show.blade.php', 'resources/views/admin/settings/automation.blade.php', 'resources/views/admin/settings/system-health.blade.php', 'resources/views/admin/settings/turnstile.blade.php', 'resources/views/admin/settings/order-emails.blade.php', 'resources/views/emails/order-events.blade.php',
    'tests/Feature/AdminOrdersImageRotationTest.php', 'tests/Feature/OperationalOrdersCommissionsTest.php', 'tests/Feature/OperationalAutomationTest.php', 'tests/Feature/PaymentsAdvancedInventoryTest.php', 'tests/Feature/OrderDeliveryWorkflowTest.php', 'tests/Feature/AfterSalesWorkflowTest.php', 'tests/Feature/AfterSalesActionExecutionTest.php', 'tests/Feature/FieldOperationsWorkflowTest.php', 'tests/Feature/ServicePartsWorkflowTest.php', 'tests/Feature/InventoryWorkspaceUiTest.php', 'tests/Feature/SecurityHealthBackupTest.php', 'tests/Feature/MySqlTestDatabaseSafetyTest.php', 'tests/Unit/SensitiveDataSanitizerTest.php', 'tests/Feature/ProductionOrderInventoryTest.php', 'tests/Feature/InventoryAdjustmentTest.php', 'tests/Feature/ProductionPermissionsTest.php', 'tests/Feature/DashboardLegacyDesignTest.php', 'tests/Feature/ReportsDocumentsSupplierTest.php', 'tests/Fixtures/pdf-logo.jpg', 'tests/Unit/BusinessDocumentPdfServiceTest.php', 'tests/Unit/DeliveryNoteMigrationContractTest.php', 'tests/Unit/DocumentRevisionMigrationContractTest.php', 'tests/Unit/CommissionReportPdfServiceTest.php', 'tests/Feature/OrderEmailsIpsWarrantyTest.php', 'tests/Unit/OrderEmailIpsMigrationContractTest.php',
    'tests/Unit/ReceivablesPermissionMigrationContractTest.php', 'tests/Unit/LegacyReadOnlyGuardTest.php', 'tests/Unit/OrderDetailPresenterTest.php', 'tests/Unit/ViewValueTest.php', 'tests/Feature/CatalogDetailPageTest.php', 'tests/Feature/LoginDashboardFallbackTest.php', 'resources/views/components/icon.blade.php', 'app/Http/Middleware/EnsureRuntimeDirectories.php', 'app/Http/Middleware/AttachRequestId.php', 'app/Http/Middleware/SecurityHeaders.php', 'app/Console/Commands/AuthDoctorCommand.php', '.env.testing.mysql.example', 'phpunit.mysql.xml', 'bin/php-lint.php', 'bin/autoload-check.php', 'bin/pdf-smoke.php', 'bin/delivery-note-smoke.php', 'bin/warranty-pdf-smoke.php', 'bin/ips-qr-pdf-smoke.php', 'storage/framework/cache/data/.gitignore', 'storage/framework/sessions/.gitignore', 'storage/framework/views/.gitignore', 'storage/logs/.gitignore', 'storage/app/backups/.gitignore', 'config/backup.php',
];
$required = array_merge($required, [
    'docs/UPGRADE-V2.1-BETA7.23.md', 'docs/RELEASE-CHECK.md', 'app/Console/Commands/ReleaseCheckCommand.php', 'config/release.php', 'bin/release-check-smoke.php', 'tests/Unit/ReleaseCheckContractTest.php', 'tests/Feature/ReleaseCheckCommandTest.php', 'storage/app/release-check/.gitignore',
    'docs/UPGRADE-V2.1-BETA7.22.1.md', 'bin/theme-css-smoke.php',
    'docs/UPGRADE-V2.1-BETA7.21.md',
    'database/migrations/2026_07_31_000030_create_management_reports_beta7_21.php',
    'app/Models/ReportSchedule.php',
    'app/Models/ReportDelivery.php',
    'app/Services/ManagementReportService.php',
    'app/Services/ReportScheduleService.php',
    'app/Services/Pdf/ManagementReportPdfService.php',
    'app/Http/Controllers/Admin/ManagementReportController.php',
    'app/Http/Controllers/Admin/ReportScheduleController.php',
    'app/Console/Commands/ManagementReportsDoctorCommand.php',
    'app/Console/Commands/OrderCostSnapshotsCommand.php',
    'app/Services/OrderItemCostSnapshotService.php',
    'app/Console/Commands/ManagementReportsDispatchCommand.php',
    'resources/views/admin/reports/management.blade.php',
    'resources/views/emails/management-report.blade.php',
    'tests/Feature/ManagementReportsProfitabilityTest.php',
    'tests/Unit/OrderCostSnapshotRepairContractTest.php',
    'bin/management-report-smoke.php',
    'bin/order-cost-snapshot-smoke.php',
    'docs/UPGRADE-V2.1-BETA7.19.md',
    'database/migrations/2026_07_31_000028_create_smart_product_management_beta7_19.php',
    'app/Services/ProductTemplateService.php',
    'app/Services/ProductCompletenessService.php',
    'app/Services/ProductBulkService.php',
    'app/Http/Controllers/Admin/ProductBulkController.php',
    'app/Console/Commands/SmartProductsDoctorCommand.php',
    'resources/views/admin/products/clone.blade.php',
    'resources/views/admin/products/bulk.blade.php',
    'tests/Unit/SmartProductManagementMigrationContractTest.php',
    'tests/Unit/SmartProductManagementUiContractTest.php',
    'tests/Feature/SmartProductManagementTest.php',
    'bin/smart-product-smoke.php',
    'docs/UPGRADE-V2.1-BETA7.18.md', 'docs/UPGRADE-V2.1-BETA7.19.md', 'docs/UPGRADE-V2.1-BETA7.23.1.md', 'docs/UPGRADE-V2.1-BETA7.23.2.md', 'docs/UPGRADE-V2.1-BETA7.24.md', 'docs/UPGRADE-V2.1-BETA7.24.1.md', 'docs/UPGRADE-V2.1-RC1.md', 'docs/RC-OPERATIONS.md', 'docs/UPGRADE-V2.1-STABLE.md', 'docs/UPGRADE-V2.1.1.md', 'docs/UPGRADE-V2.1.2.md', 'docs/UPGRADE-V2.1.3.md', 'docs/UPGRADE-V2.1.3.1.md', 'docs/UPGRADE-V2.1.3.2.md', 'docs/UPGRADE-V2.1.3.3.md', 'docs/STABLE-OPERATIONS.md', 'docs/BACKUP-RESTORE-DRILL.md', 'bin/rc-hardening-smoke.php', 'bin/stable-hardening-smoke.php', 'bin/stable-maintenance-smoke.php', 'bin/product-media-ux-smoke.php', 'bin/product-announcement-smoke.php', 'bin/catalog-settings-product-data-smoke.php', 'bin/catalog-settings-integrity-hotfix-smoke.php', 'bin/product-save-regex-hotfix-smoke.php', 'bin/storage-capacity-total-smoke.php', 'tests/Unit/ProductSaveRegexHotfixContractTest.php', 'tests/Unit/StorageCapacityTotalContractTest.php', 'tests/Unit/CatalogSettingsProductDataContractTest.php', 'tests/Unit/CatalogSettingsIntegrityHotfixContractTest.php', 'app/Console/Commands/CatalogSettingsDoctorCommand.php', 'app/Services/ProductTypeCategoryService.php', 'app/Services/SpecificationFieldLifecycleService.php', 'app/Services/StorageSpecificationService.php', 'database/migrations/2026_08_04_000033_create_catalog_type_layout_v2_1_3.php', 'database/migrations/2026_08_04_000034_repair_catalog_category_and_spec_integrity_v2_1_3_1.php', 'database/migrations/2026_08_04_000035_link_storage_components_and_total_capacity_v2_1_3_3.php', 'public/assets/js/dictionary-sort-manager.js', 'resources/views/admin/dictionary/product-type.blade.php', 'tests/Unit/ProductMediaUxContractTest.php', 'tests/Unit/ProductAnnouncementContractTest.php', 'app/Services/ProductAnnouncementService.php', 'tests/Unit/ReleaseCandidateHardeningContractTest.php', 'tests/Unit/StableReleaseContractTest.php', 'tests/Unit/StableMaintenanceContractTest.php', 'app/Console/Commands/SecurityHardeningDoctorCommand.php', 'app/Console/Commands/MigrationsDoctorCommand.php', 'app/Console/Commands/AccessControlDoctorCommand.php', 'app/Console/Commands/ReleaseIntegrityCommand.php', 'app/Console/Commands/BackupVerifyCommand.php', 'app/Console/Commands/ProductMediaDoctorCommand.php', 'app/Http/Controllers/ProductMediaDownloadController.php', 'public/assets/js/product-media-manager.js', 'resources/views/admin/products/partials/image-card.blade.php', 'resources/views/admin/products/partials/image-upload.blade.php', 'bin/catalog-detail-smoke.php', 'bin/detail-pages-doctor-smoke.php', 'tests/Unit/CatalogDetailBladeContractTest.php', 'tests/Unit/SystemHealthRemediationContractTest.php', 'tests/Unit/DetailPagesDoctorContractTest.php',
    'database/migrations/2026_07_31_000027_create_correlated_specifications_beta7_18.php',
    'app/Models/SpecificationOption.php',
    'app/Services/SpecificationDependencyService.php',
    'app/Services/CatalogSpecificationFilterService.php',
    'app/Console/Commands/CatalogCorrelationsDoctorCommand.php',
    'resources/views/partials/correlated-specification-filters.blade.php',
    'resources/views/partials/correlated-specification-filter-script.blade.php',
    'tests/Unit/CorrelatedSpecificationsMigrationContractTest.php',
    'tests/Unit/CorrelatedSpecificationUiContractTest.php',
    'docs/UPGRADE-V2.1.4.md',
    'bin/cms-v2.1.4-smoke.php',
    'tests/Unit/CmsV214ContractTest.php',
    'app/Console/Commands/CmsV214DoctorCommand.php',
    'app/Services/ProductDeletionService.php',
    'database/migrations/2026_08_04_000036_add_product_model_and_name_templates_v2_1_4.php',
    'docs/UPGRADE-V2.1.4.1.md',
    'bin/product-type-page-render-hotfix-smoke.php',
    'tests/Unit/ProductTypePageRenderHotfixContractTest.php',
    'docs/UPGRADE-V2.1.5.md',
    'bin/cms-v2.1.5-smoke.php',
    'tests/Unit/CmsV215ContractTest.php',
    'app/Console/Commands/CmsV215DoctorCommand.php',
    'public/assets/js/ux-runtime.js',
    'resources/views/errors/minimal.blade.php',
    'resources/views/errors/403.blade.php',
    'resources/views/errors/404.blade.php',
    'resources/views/errors/419.blade.php',
    'resources/views/errors/429.blade.php',
    'resources/views/errors/500.blade.php',
    'resources/views/errors/503.blade.php',
    'database/migrations/2026_08_05_000037_place_desktop_power_supply_field_v2_1_5.php',
    'docs/UPGRADE-V2.2.0.md',
    'docs/openapi.yaml',
    'bin/cms-v2.2.0-smoke.php',
    'tests/Feature/MobileApiFoundationTest.php',
    'tests/Unit/MobileApiFoundationContractTest.php',
    'app/Console/Commands/CmsV220DoctorCommand.php',
    'app/Http/Controllers/Api/V1/BootstrapController.php',
    'app/Http/Controllers/Api/V1/MobileDeviceController.php',
    'app/Models/MobileDevice.php',
    'database/migrations/2026_08_06_000039_create_mobile_devices_v2_2_0.php',
    'database/migrations/2026_08_06_000040_add_push_notification_preference_v2_2_0.php',
    'database/migrations/2026_08_06_000041_create_database_queue_tables_v2_2_0.php',
]);
foreach ($required as $file) $check('postoji '.$file, is_file($root.'/'.$file));

$version = trim((string) @file_get_contents($root.'/VERSION'));
$check('verzija je 2.2.0 Mobile API Foundation', $version === '2.2.0');
$check('release tag je v2.2.0', trim((string) @file_get_contents($root.'/RELEASE-TAG')) === 'v2.2.0');
$check('upgrade osnova je v2.1.6', trim((string) @file_get_contents($root.'/UPGRADE-FROM')) === 'v2.1.6');

$composer = json_decode((string) file_get_contents($root.'/composer.json'), true);
$check('composer.json validan', is_array($composer));
$check('PHP minimum 8.4', ($composer['require']['php'] ?? null) === '^8.4');
$check('Laravel 13', str_starts_with((string) ($composer['require']['laravel/framework'] ?? ''), '^13.'));
$check('Composer lint/autoload/test/release skripte postoje', isset($composer['scripts']['smoke:v2.2.0'], $composer['scripts']['doctor:v2.2.0'], $composer['scripts']['smoke:v2.1.5'], $composer['scripts']['doctor:v2.1.5'], $composer['scripts']['smoke:v2.1.4.1'], $composer['scripts']['smoke:v2.1.4'], $composer['scripts']['smoke:storage-capacity'], $composer['scripts']['smoke:product-save'], $composer['scripts']['lint'], $composer['scripts']['autoload:check'], $composer['scripts']['test:production'], $composer['scripts']['test:mysql'], $composer['scripts']['release:check'], $composer['scripts']['release:check:full'], $composer['scripts']['release:check:rc'], $composer['scripts']['release:check:stable'], $composer['scripts']['smoke:release'], $composer['scripts']['smoke:rc'], $composer['scripts']['smoke:stable'], $composer['scripts']['smoke:catalog-detail'], $composer['scripts']['smoke:order-cost'], $composer['scripts']['smoke:maintenance'], $composer['scripts']['smoke:product-media'], $composer['scripts']['smoke:product-announcements'], $composer['scripts']['smoke:catalog-settings'], $composer['scripts']['smoke:catalog-settings-hotfix']));

$appConfig = (string) file_get_contents($root.'/config/app.php');
$check('runtime verzija je 2.2.0', str_contains($appConfig, "'version' => '2.2.0'"));

$migration = (string) file_get_contents($root.'/database/migrations/2026_07_22_000006_enable_production_orders_inventory.php');
foreach (['idempotency_keys', 'source_system', 'inventory_state', 'inventory_returned_at', 'event_key', 'orders_user_idempotency_unique'] as $needle) {
    $check('migracija sadrži '.$needle, str_contains($migration, $needle));
}

$orderService = (string) file_get_contents($root.'/app/Services/OrderService.php');
$workflow = (string) file_get_contents($root.'/app/Services/OrderWorkflowService.php');
$idempotency = (string) file_get_contents($root.'/app/Services/IdempotencyService.php');
$check('porudžbina zaključava proizvode', str_contains($orderService, 'lockForUpdate()'));
$check('porudžbina umanjuje lager u transakciji', str_contains($orderService, "'quantity_change' => -".'$quantity'));
$check('povrat lagera ima jedinstveni event key', str_contains($workflow, 'cancel-return') && str_contains($workflow, "inventory_state' => 'returned"));
$check('idempotency koristi unique zapis i row lock', str_contains($idempotency, 'insertOrIgnore') && str_contains($idempotency, 'lockForUpdate()'));
$check('legacy porudžbine su blokirane', str_contains($workflow, "source_system !== 'laravel'"));

$provider = (string) file_get_contents($root.'/app/Providers/AppServiceProvider.php');
$database = (string) file_get_contents($root.'/config/database.php');
$cache = (string) file_get_contents($root.'/config/cache.php');
$queue = (string) file_get_contents($root.'/config/queue.php');
$check('legacy SQL guard je registrovan pre izvršavanja', str_contains($provider, 'beforeExecuting') && str_contains($provider, 'LegacyReadOnlyGuard'));
$check('legacy MySQL sesija je READ ONLY', str_contains($database, 'SET SESSION TRANSACTION READ ONLY'));
$check('Redis cache konekcije postoje u database konfiguraciji', str_contains($database, "'redis' => [") && str_contains($database, "'client' => env('REDIS_CLIENT', 'phpredis')") && str_contains($database, "'cache' => [") && str_contains($database, "env('REDIS_CACHE_DB', '1')"));
$check('Redis cache store postoji u cache konfiguraciji', str_contains($cache, "'redis' => [") && str_contains($cache, "'driver' => 'redis'") && str_contains($cache, "env('REDIS_CACHE_CONNECTION', 'cache')") && str_contains($cache, "env('REDIS_CACHE_LOCK_CONNECTION', 'cache')"));
$check('queue ostaje bez Redis konekcije', !str_contains($queue, "'redis' =>"));
$check('cache limiter zadržava file fallback i podržava runtime Redis override', str_contains($cache, "'limiter' => env('CACHE_LIMITER', 'file')"));

$seeder = (string) file_get_contents($root.'/database/seeders/CoreAccessSeeder.php');
foreach (['orders.create', 'orders.view_own', 'orders.cancel_own', 'orders.manage', 'stock.view', 'stock.adjust', 'reports.view', 'reports.export', 'invoices.manage', 'invoices.view_own'] as $permission) {
    $check('dozvola '.$permission, str_contains($seeder, "'slug' => '{$permission}'"));
}

$web = (string) file_get_contents($root.'/routes/web.php');
$api = (string) file_get_contents($root.'/routes/api.php');
foreach (['orders.store', 'orders.cancel', 'admin.orders.status', 'admin.orders.payment', 'admin.orders.tracking', 'admin.stock.adjust'] as $routeName) {
    $check('web ruta '.$routeName, str_contains($web, "name('".str_replace('admin.', '', $routeName)."')") || str_contains($web, "name('{$routeName}')") || str_contains($web, "->name('".substr($routeName, strrpos($routeName, '.') + 1)."')"));
}
$check('API porudžbine postoje', str_contains($api, "'/orders'") && str_contains($api, "orders/{order}/cancel"));

$reportsService = (string) file_get_contents($root.'/app/Services/OrderReportService.php');
$documentService = (string) file_get_contents($root.'/app/Services/OrderDocumentService.php');
$pdfWriter = (string) file_get_contents($root.'/app/Services/Pdf/SimplePdfWriter.php');
$documentsMigration = (string) file_get_contents($root.'/database/migrations/2026_07_23_000009_create_reports_documents_and_supplier_assignment.php');
$check('porudžbina ima dodeljenog SuperAdmin/Admin dobavljača', str_contains($orderService, 'resolveSupplier') && str_contains($orderService, "'supplier_user_id' => ".'$supplier->id') && str_contains($documentsMigration, 'supplier_name_snapshot'));
$check('admin scope vidi samo njemu dodeljene porudžbine', str_contains((string) file_get_contents($root.'/app/Services/OrderAccessService.php'), "where('supplier_user_id', ".'$user->id'.")"));
$check('izveštaji podržavaju filtere i CSV/PDF', str_contains($reportsService, 'function csv') && str_contains($reportsService, 'function pdf') && str_contains($reportsService, 'date_from'));
$check('poslovni dokumenti koriste nepromenljivi snapshot', str_contains($documentService, 'company_name') && str_contains($documentService, 'customer_name') && str_contains($documentService, 'supplier_name'));
$check('PDF renderer je lokalni, embedded TrueType i ToUnicode', str_contains($pdfWriter, '%PDF-1.4') && str_contains($pdfWriter, '/Subtype /TrueType') && str_contains($pdfWriter, '/ToUnicode') && str_contains($pdfWriter, 'DejaVuSans-Bold.ttf'));
$check('brojevi dokumenata su transakcioni i jedinstveni', str_contains((string) file_get_contents($root.'/app/Services/DocumentNumberService.php'), 'lockForUpdate') && str_contains($documentsMigration, "document_number', 50)->unique"));
$check('web rute imaju reports CSV/PDF i dokumente', str_contains($web, "name('reports.index')") && str_contains($web, "name('reports.orders.csv')") && str_contains($web, "name('reports.orders.pdf')") && str_contains($web, "name('orders.documents.store')"));
$reportController = (string) file_get_contents($root.'/app/Http/Controllers/Admin/ReportController.php');
$reportsDoctor = (string) file_get_contents($root.'/app/Console/Commands/ReportsDoctorCommand.php');
$reportsRepairMigration = (string) file_get_contents($root.'/database/migrations/2026_07_23_000010_repair_reports_schema_beta1_2.php');
$reportsView = (string) file_get_contents($root.'/resources/views/admin/reports/index.blade.php');
$check('reports stranica ima schema fallback umesto 500', str_contains($reportController, 'readinessIssues') && str_contains($reportController, 'fallbackData') && str_contains($reportsView, 'Izveštaji trenutno nisu spremni'));
$check('reports render je unutar zaštićenog controller toka', str_contains($reportController, 'renderProtected') && str_contains($reportController, "view('admin.reports.index', \$data)->render()") && str_contains($reportController, 'emergencyHtml'));
$check('reports view ima render marker i bezbedne URL-ove', str_contains($reportsView, 'reports-page-ready') && str_contains($reportsView, "url('/admin/reports')") && !str_contains($reportsView, "route('admin.settings.documents.index')"));
$check('reports export vraća kontrolisani 503', str_contains($reportController, 'unavailableExport') && str_contains($reportController, '503'));
$check('reports doctor izvršava repair i stvarne SQL upite', str_contains($reportsDoctor, "app:reports-doctor") && str_contains($reportsDoctor, "Artisan::call('migrate'") && str_contains($reportsDoctor, 'Reports SQL upiti su uspešni'));
$check('reports doctor renderuje controller Blade i layout', str_contains($reportsDoctor, '--render') && str_contains($reportsDoctor, 'renderReports') && str_contains($reportsDoctor, 'reports-page-ready') && str_contains($reportsDoctor, 'kompletan authenticated layout'));
$check('reports logging je best-effort', str_contains($reportController, 'safeLog') && str_contains($reportController, 'Logging must never replace'));
$check('beta1.2 repair migracija je nedestruktivna', str_contains($reportsRepairMigration, 'repairOrderDocuments') && str_contains($reportsRepairMigration, 'repairReportPermissions') && str_contains($reportsRepairMigration, 'namerno nedestruktivna'));

$layout = (string) file_get_contents($root.'/resources/views/layouts/app.blade.php');
$css = (string) file_get_contents($root.'/public/assets/css/app.css');
$check('hamburger dugme postoji', str_contains($layout, 'data-mobile-menu-toggle') && str_contains($layout, 'aria-controls="siteHeaderMenu"'));
$check('mobilni meni ima kontrolni JavaScript', str_contains($layout, 'setMobileMenu') && str_contains($layout, "matchMedia('(max-width: 1440px)')"));
$check('mobilni meni nema horizontalni scroll', str_contains($css, '.site-header.menu-open .header-secondary-row') && str_contains($css, 'overflow-y:auto') && str_contains($css, 'overflow-x:hidden'));
$check('direktne mobilne stavke koriste zajednički levi wrapper', str_contains($layout, 'class="nav-link-content"') && substr_count($layout, 'class="nav-link-content"') >= 3);
$check('Početna Provizije i Izveštaji su poravnati ulevo', str_contains($css, '.header-secondary-row .main-nav>a{justify-content:flex-start!important;text-align:left!important}') && str_contains($css, '.header-secondary-row .main-nav>a>.nav-link-content{width:100%;justify-content:flex-start;text-align:left}'));
$check('CSS ima pouzdan cache busting', str_contains($layout, "filemtime(public_path('assets/css/app.css'))"));
$check('legacy desktop header ima dva reda', str_contains($layout, 'header-primary-row') && str_contains($layout, 'header-secondary-row') && str_contains($layout, 'header-logout'));
$check('legacy mobilni header zadržava kurs temu nalog i hamburger', str_contains($layout, 'header-quick-actions') && str_contains($layout, 'data-theme-toggle') && str_contains($layout, 'data-mobile-menu-toggle'));
$check('dashboard ima moderni hero KPI prioritete i module', str_contains((string) file_get_contents($root.'/resources/views/dashboard/index.blade.php'), 'modern-dashboard-hero') && substr_count((string) file_get_contents($root.'/resources/views/dashboard/index.blade.php'), 'dashboard-kpi-card') >= 4 && str_contains((string) file_get_contents($root.'/resources/views/dashboard/index.blade.php'), 'dashboard-priority-list') && str_contains((string) file_get_contents($root.'/resources/views/dashboard/index.blade.php'), 'dashboard-module-grid'));
$check('dashboard CSS ima 4 desktop i 2 mobilne kolone', str_contains($css, '.legacy-metrics-grid{display:grid;grid-template-columns:repeat(4') && str_contains($css, '.legacy-metrics-grid{grid-template-columns:repeat(2'));
$check('admin gridovi su poravnati na vrh', str_contains($css, '.settings-grid,.admin-form-grid{align-items:start}'));
$check('forme koriste sadržajnu visinu', str_contains($css, 'grid-auto-rows:max-content') && str_contains($css, 'height:auto;min-height:46px'));

$deploymentCheck = (string) file_get_contents($root.'/app/Console/Commands/DeploymentCheckCommand.php');
$check('deployment check ima bezbedan repair režim', str_contains($deploymentCheck, "{--repair") && str_contains($deploymentCheck, "Artisan::call('migrate'") && str_contains($deploymentCheck, 'CoreAccessSeeder::class'));
$check('deployment check razlikuje runtime zaštitu i grant warning', str_contains($deploymentCheck, 'legacy session read-only') && str_contains($deploymentCheck, 'legacy SQL guard') && str_contains($deploymentCheck, 'strict-legacy-grants'));
$check('deployment check proverava i operativne kolone', str_contains($deploymentCheck, 'OPERATIONS_COLUMNS') && str_contains($deploymentCheck, 'missingOperationColumns'));
$check('dashboard koristi DB fallback umesto 500', str_contains((string) file_get_contents($root.'/app/Http/Controllers/DashboardController.php'), 'fallback values were used') && str_contains((string) file_get_contents($root.'/app/Http/Controllers/DashboardController.php'), 'tableHasColumns'));
$loginController = (string) file_get_contents($root.'/app/Http/Controllers/Auth/AuthenticatedSessionController.php');
$dashboardController = (string) file_get_contents($root.'/app/Http/Controllers/DashboardController.php');
$runtimeMiddleware = (string) file_get_contents($root.'/app/Http/Middleware/EnsureRuntimeDirectories.php');
$bootstrapApp = (string) file_get_contents($root.'/bootstrap/app.php');
$authDoctor = (string) file_get_contents($root.'/app/Console/Commands/AuthDoctorCommand.php');
$turnstile = (string) file_get_contents($root.'/app/Services/TurnstileService.php');
$turnstileController = (string) file_get_contents($root.'/app/Http/Controllers/Admin/TurnstileSettingsController.php');
$turnstileView = (string) file_get_contents($root.'/resources/views/admin/settings/turnstile.blade.php');
$settingsService = (string) file_get_contents($root.'/app/Services/SettingsService.php');
$repairMigration = (string) file_get_contents($root.'/database/migrations/2026_07_23_000008_repair_authenticated_runtime_beta6.php');
$check('login telemetry je best-effort', str_contains($loginController, 'Login succeeded, but last_login_at could not be updated'));
$check('login hvata session i remember-token probleme', str_contains($loginController, 'rememberTokenAvailable') && str_contains($loginController, 'serverska sesija nije mogla da se sačuva'));
$check('authenticated layout nema direktan SettingsService upit', !str_contains($layout, 'app(\App\Services\SettingsService::class)') && !str_contains($layout, '->renderTemplate('));
$check('authenticated layout koristi bezbedne user helper metode', str_contains($layout, 'displayInitial()') && str_contains($layout, 'roleName()') && str_contains($layout, 'catch (\Throwable)'));
$check('dashboard logging ne može da obori fallback', str_contains($dashboardController, 'catch (Throwable)') && str_contains($dashboardController, 'log direktorijum nije upisiv'));
$check('runtime middleware prethodi session/cache middleware-u', str_contains($bootstrapApp, 'prepend(EnsureRuntimeDirectories::class)') && str_contains($runtimeMiddleware, 'storage/framework/sessions') && str_contains($runtimeMiddleware, 'HTTP_SERVICE_UNAVAILABLE'));
$check('deployment repair kreira runtime direktorijume i kompajlira Blade', str_contains($deploymentCheck, 'repairRuntimeDirectories') && str_contains($deploymentCheck, "Artisan::call('view:cache')") && str_contains($deploymentCheck, 'post-login core schema'));
$check('auth doctor može da renderuje kompletan dashboard', str_contains($authDoctor, '--render-dashboard') && str_contains($authDoctor, 'setLaravelSession') && str_contains($authDoctor, 'ViewErrorBag') && str_contains($authDoctor, 'Post-login dashboard i kompletan authenticated layout'));
$check('Turnstile hvata sve transportne/JSON greške', str_contains($turnstile, 'catch (Throwable)') && str_contains($turnstile, 'is_array($payload)'));
$check('Turnstile podešavanja imaju DB prioritet i env fallback', str_contains($turnstile, "storedSetting('turnstile_site_key')") && str_contains($turnstile, "config('services.turnstile.site_key')"));
$check('Turnstile secret se čuva šifrovano i ne izlaže kroz all', str_contains($settingsService, 'Crypt::encryptString') && str_contains($settingsService, 'Crypt::decryptString') && str_contains($settingsService, 'SENSITIVE_KEYS'));
$check('Turnstile admin ekran i ruta postoje', str_contains($web, 'TurnstileSettingsController') && str_contains($turnstileView, 'Cloudflare Turnstile') && str_contains($turnstileController, 'settings.turnstile_updated'));
$check('beta6 repair migracija popravlja core login šemu', str_contains($repairMigration, 'ensureUsersRuntimeColumns') && str_contains($repairMigration, "remember_token") && str_contains($repairMigration, 'ensureSettings'));
$check('static check razdvaja runtime i ZIP režim', str_contains((string) file_get_contents($root.'/bin/static-check.php'), "--package") && str_contains((string) file_get_contents($root.'/bin/static-check.php'), 'ZIP hygiene provere su preskočene'));


$operationsMigration = (string) file_get_contents($root.'/database/migrations/2026_07_23_000011_create_operational_orders_commissions_beta2.php');
$commissionWorkflow = (string) file_get_contents($root.'/app/Services/CommissionWorkflowService.php');
$orderOperations = (string) file_get_contents($root.'/app/Services/OrderOperationalService.php');
$timelineService = (string) file_get_contents($root.'/app/Services/OrderTimelineService.php');
$notificationService = (string) file_get_contents($root.'/app/Services/OperationalNotificationService.php');
$operationsDoctor = (string) file_get_contents($root.'/app/Console/Commands/OperationsDoctorCommand.php');
$commissionReport = (string) file_get_contents($root.'/app/Services/CommissionReportService.php');
$adminCommissionView = (string) file_get_contents($root.'/resources/views/admin/commissions/index.blade.php');
$ownCommissionView = (string) file_get_contents($root.'/resources/views/commissions/index.blade.php');
$orderAdminView = (string) file_get_contents($root.'/resources/views/admin/orders/show.blade.php');
$notificationView = (string) file_get_contents($root.'/resources/views/notifications/index.blade.php');
foreach (['order_internal_notes', 'order_assignments', 'commission_payment_batches', 'notifications', 'payment_batch_id', 'status_updated_at', 'last_internal_note_at'] as $needle) {
    $check('operativna migracija sadrži '.$needle, str_contains($operationsMigration, $needle));
}
foreach (['commissions.view_own', 'orders.reassign', 'orders.internal_notes', 'notifications.view'] as $permission) {
    $check('beta2 dozvola '.$permission, str_contains($seeder, "'slug' => '{$permission}'") && str_contains($deploymentCheck, "'{$permission}'"));
}
$check('provizije imaju odobravanje isplatu storniranje i istoriju', str_contains($commissionWorkflow, 'assertTransition') && str_contains($commissionWorkflow, 'markPaidBulk') && str_contains($commissionWorkflow, 'commission_status_history'));
$check('masovna isplata koristi transakciju row lock i batch', str_contains($commissionWorkflow, 'DB::transaction') && str_contains($commissionWorkflow, 'lockForUpdate') && str_contains($commissionWorkflow, 'CommissionPaymentBatch::query()->create'));
$check('korisnik vidi samo svoje provizije i podrazumevanih 10 procenata', str_contains($commissionReport, "where('user_id', \$user->id)") && str_contains($ownCommissionView, 'Podrazumevana provizija je 10% vrednosti artikla po komadu.') && !str_contains($ownCommissionView, '20 EUR') && !str_contains($ownCommissionView, '50 EUR'));
$check('interne napomene nisu u javnom timeline-u', str_contains($timelineService, 'if ($includeInternal)') && str_contains($orderOperations, 'addInternalNote'));
$check('ponovna dodela je ograničena na SuperAdministratora', str_contains($orderOperations, "abort_unless(\$actor->hasRole('superadmin')") && str_contains($orderOperations, 'OrderAssignment::query()->create'));
$check('preuzimanje i rokovi porudžbine imaju audit i obaveštenja', str_contains($orderOperations, 'order.accepted') && str_contains($orderOperations, 'order.deadlines_changed') && str_contains($orderOperations, 'Ažurirani su rokovi porudžbine'));
$check('database notifikacije su neblokirajuće i mail je opcioni', str_contains($notificationService, 'OperationalNotification') && str_contains((string) file_get_contents($root.'/app/Notifications/OperationalNotification.php'), "\$channels[] = 'database'") && str_contains($notificationService, 'Notifications are non-blocking'));
$check('operativni doctor proverava šemu SQL i render', str_contains($operationsDoctor, '--repair') && str_contains($operationsDoctor, '--render') && str_contains($operationsDoctor, 'commission_payment_batches'));
$check('admin provizije imaju filtere CSV PDF i masovnu isplatu', str_contains($adminCommissionView, 'bulkPayForm') && str_contains($web, 'commissions.bulk-pay') && str_contains($commissionReport, 'function csv') && str_contains($commissionReport, 'function pdf'));
$check('commission tabela nema unutrašnji vertikalni scroll pri obradi', str_contains($adminCommissionView, 'commission-table-wrap') && str_contains($css, '.commission-table-wrap{overflow-x:auto;overflow-y:hidden}'));
$check('obrada provizije koristi veliki viewport modal', str_contains($css, '.table-action-menu[open]::before') && str_contains($css, 'width:min(620px,calc(100vw - 32px))') && str_contains($css, 'max-height:calc(100dvh - 32px)') && !str_contains($css, '.table-action-popover{position:absolute'));
$check('commission modal ima naslov i eksplicitno zatvaranje', str_contains($adminCommissionView, 'table-action-popover-head') && str_contains($adminCommissionView, 'commission-popover-close') && str_contains($adminCommissionView, "event.key !== 'Escape'"));
$check('otvaranje commission modala zatvara prethodni', str_contains($adminCommissionView, "if (other !== menu) other.removeAttribute('open')"));
$check('porudžbina ima timeline interne napomene preuzimanje rokove i reassignment UI', str_contains($orderAdminView, 'timeline') && str_contains($orderAdminView, 'internal') && str_contains($orderAdminView, 'reassign') && str_contains($orderAdminView, 'expected_processing_at'));
$check('inbox obaveštenja podržava read i read-all', str_contains($web, 'notifications.read-all') && str_contains($notificationView, 'notifications.read') && str_contains((string) file_get_contents($root.'/app/Http/Controllers/NotificationController.php'), 'markAsRead'));
$check('operativni feature testovi postoje', str_contains((string) file_get_contents($root.'/tests/Feature/OperationalOrdersCommissionsTest.php'), 'test_commission_can_be_approved_paid_audited_and_seen_by_owner') && str_contains((string) file_get_contents($root.'/tests/Feature/OperationalOrdersCommissionsTest.php'), 'test_internal_notes_are_private_and_superadmin_can_reassign_order'));
$check('commission modal regresioni feature test postoji', str_contains((string) file_get_contents($root.'/tests/Feature/OperationalOrdersCommissionsTest.php'), 'test_commission_action_uses_large_viewport_modal_without_table_scroll'));


$paymentsInventoryMigration = (string) file_get_contents($root.'/database/migrations/2026_07_23_000012_create_payments_advanced_inventory_beta3.php');
$phpLintScript = (string) file_get_contents($root.'/bin/php-lint.php');
$check('beta3.1 migracija nema globalni use Throwable', !preg_match('/^use\s+Throwable\s*;/m', $paymentsInventoryMigration));
$check('beta3.1 migracija koristi potpuno kvalifikovani Throwable', str_contains($paymentsInventoryMigration, 'catch (\\Throwable)'));
$check('PHP lint odbija warning deprecated i notice izlaz', str_contains($phpLintScript, 'PHP (Warning|Deprecated|Notice|Parse error|Fatal error)') && str_contains($phpLintScript, '$hasDiagnostic'));
$paymentService = (string) file_get_contents($root.'/app/Services/OrderPaymentService.php');
$inventoryService = (string) file_get_contents($root.'/app/Services/AdvancedInventoryService.php');
$ipsService = (string) file_get_contents($root.'/app/Services/IpsPaymentPayloadService.php');
$paymentsInventoryDoctor = (string) file_get_contents($root.'/app/Console/Commands/PaymentsInventoryDoctorCommand.php');
$inventoryController = (string) file_get_contents($root.'/app/Http/Controllers/Admin/InventoryController.php');
$inventoryView = (string) file_get_contents($root.'/resources/views/admin/inventory/index.blade.php');
$paymentFeatureTest = (string) file_get_contents($root.'/tests/Feature/PaymentsAdvancedInventoryTest.php');
foreach (['order_payments', 'stock_receipts', 'stock_receipt_items', 'inventory_counts', 'inventory_count_items', 'payment_state', 'paid_total_rsd', 'payment_due_at'] as $needle) {
    $check('beta3 migracija sadrži '.$needle, str_contains($paymentsInventoryMigration, $needle));
}
foreach (['payments.manage', 'payments.upload_proof', 'payments.view_own', 'inventory.receive', 'inventory.count', 'inventory.export'] as $permission) {
    $check('beta3 dozvola '.$permission, str_contains($seeder, "'slug' => '{$permission}'") && str_contains($deploymentCheck, "'{$permission}'"));
}
$check('uplate koriste transakciju row lock audit i saldo', str_contains($paymentService, 'DB::transaction') && str_contains($paymentService, 'lockForUpdate') && str_contains($paymentService, 'recalculateLocked') && str_contains($paymentService, 'order.payment_verified'));
$check('potvrde uplate su privatne i autorizovane', str_contains($paymentService, "storeAs(") && str_contains($paymentService, "'local'") && str_contains($web, 'orders.payments.proof') && str_contains((string) file_get_contents($root.'/app/Http/Controllers/OrderPaymentController.php'), 'authorizeView'));
$check('IPS podaci koriste snapshot porudžbine', str_contains($ipsService, 'bank_account_number_snapshot') && str_contains($ipsService, 'payment_reference_snapshot') && str_contains($ipsService, 'order_ips_qr'));
$check('predračun i račun postavljaju dospeće porudžbine', str_contains($documentService, "'payment_due_at' =>") && str_contains($documentService, 'endOfDay()'));
$check('ulaz robe i popis koriste idempotency transakciju i row lock', str_contains($inventoryService, "'inventory.receive'") && str_contains($inventoryService, "'inventory.count'") && str_contains($inventoryService, 'lockForUpdate') && str_contains($inventoryService, 'StockMovement::query()->create'));
$check('napredni lager ima readiness fallback umesto 500', str_contains($inventoryController, 'readinessIssues') && str_contains($inventoryController, 'fallbackData') && str_contains($inventoryView, 'Napredni lager trenutno nije spreman'));
$check('reports beta3 sažeci i izvozi su zaštićeni', str_contains($reportController, 'safePaymentSummary') && str_contains($reportController, 'safeInventorySummary') && str_contains($reportController, 'payments-inventory-doctor --repair'));
$check('beta3 doctor proverava repair SQL i render', str_contains($paymentsInventoryDoctor, 'app:payments-inventory-doctor') && str_contains($paymentsInventoryDoctor, '--repair') && str_contains($paymentsInventoryDoctor, '--render') && str_contains($paymentsInventoryDoctor, 'order_payments'));
$check('beta3 feature testovi pokrivaju uplate ulaz i popis', str_contains($paymentFeatureTest, 'test_customer_proof_can_be_verified_and_updates_order_balance') && str_contains($paymentFeatureTest, 'test_stock_receipt_is_idempotent_and_inventory_count_creates_variance'));

$financialRepairMigration = (string) file_get_contents($root.'/database/migrations/2026_07_29_000015_repair_order_documents_and_payments_beta7_5.php');
$documentFeatureTest = (string) file_get_contents($root.'/tests/Feature/ReportsDocumentsSupplierTest.php');
$check('beta7.5 repair migracija obnavlja PDF i payment šemu', str_contains($financialRepairMigration, 'repairDocumentCounters') && str_contains($financialRepairMigration, 'repairOrderDocuments') && str_contains($financialRepairMigration, 'repairOrderPayments') && str_contains($financialRepairMigration, 'repairOrderPaymentColumns'));
$check('beta7.5 repair migracija je nedestruktivna', str_contains($financialRepairMigration, 'Recovery migracija je namerno nedestruktivna') && !str_contains($financialRepairMigration, 'dropIfExists'));
$check('beta7.5 potvrda koristi site name fallback', str_contains($documentService, "settings['site_name']") && str_contains($documentFeatureTest, 'test_order_confirmation_works_without_separate_document_company_settings'));
$check('beta7.5 ručno evidentiranje uplate ima regresioni test', str_contains($paymentFeatureTest, 'test_assigned_admin_can_record_payment_from_order_workspace') && str_contains($paymentFeatureTest, "'/admin/orders/'.\$order->id.'/payments'"));
$check('beta7.5 doctor proverava dokument i payment tabele', str_contains($paymentsInventoryDoctor, "'document_counters'") && str_contains($paymentsInventoryDoctor, "'order_documents'") && str_contains($paymentsInventoryDoctor, "'order_payments'"));
$check('beta7.6 PDF dozvoljava lokalno uvezene porudžbine', !str_contains($documentService, "source_system !== 'laravel'") && str_contains($documentFeatureTest, 'test_confirmation_pdf_can_be_issued_for_imported_completed_order'));
$check('beta7.6 uplate dozvoljavaju lokalno uvezene porudžbine', !str_contains($paymentService, "source_system !== 'laravel'") && str_contains($paymentFeatureTest, 'test_assigned_admin_can_record_payment_for_imported_completed_order'));
$check('beta7.6 legacy lager zaštita ostaje aktivna', str_contains($workflow, "source_system !== 'laravel'") && str_contains((string) file_get_contents($root.'/app/Services/OrderOperationalService.php'), "source_system !== 'laravel'"));

$completionMigration = (string) file_get_contents($root.'/database/migrations/2026_07_30_000016_add_order_completion_beta7_7.php');
$documentSettingsController = (string) file_get_contents($root.'/app/Http/Controllers/Admin/DocumentSettingsController.php');
$documentSettingsView = (string) file_get_contents($root.'/resources/views/admin/settings/documents.blade.php');
$businessPdf = (string) file_get_contents($root.'/app/Services/Pdf/BusinessDocumentPdfService.php');
$adminOrderView = (string) file_get_contents($root.'/resources/views/admin/orders/show.blade.php');
$adminOrderIndex = (string) file_get_contents($root.'/resources/views/admin/orders/index.blade.php');
$check('beta7.7 migracija dodaje terminalno stanje porudžbine', str_contains($completionMigration, 'completed_at') && str_contains($completionMigration, 'completed_by') && str_contains($completionMigration, 'completion_note') && str_contains($completionMigration, 'orders_completion_state_index'));
$check('beta7.7 PDF podešavanja imaju upload pregled i uklanjanje logotipa', str_contains($documentSettingsController, 'storePdfLogo') && str_contains($documentSettingsController, 'removeLogo') && str_contains($documentSettingsView, 'Logo na PDF dokumentima') && str_contains($web, "name('documents.logo.destroy')"));
$check('beta7.7 PDF logo se ugrađuje kao lokalni JPEG', str_contains($documentSettingsController, 'imagejpeg') && str_contains($documentService, 'localLogoPath') && str_contains($businessPdf, 'imageJpeg'));
$check('beta7.7 PDF ne prikazuje subagent email kupca', str_contains($documentService, "'customer_email' => null") && !str_contains($businessPdf, "document['customer_email']") && str_contains($documentFeatureTest, 'assertStringNotContainsString($customer->email'));
$check('beta7.7 kompletiranje COD porudžbine evidentira preostali saldo', str_contains($workflow, 'public function complete') && str_contains($workflow, "'payment_method' => 'cash_on_delivery'") && str_contains($workflow, "'completed_at' => now()") && str_contains($paymentFeatureTest, 'test_admin_can_complete_cod_order_and_lock_all_further_financial_actions'));
$check('beta7.7 kompletirana porudžbina zaključava dalje izmene', str_contains($workflow, 'assertNotCompleted') && str_contains($paymentService, 'assertOrderOpen') && str_contains($adminOrderView, 'Porudžbina i isporuka su kompletirane'));
$check('beta7.7 kompletiranje je jasno dostupno u detalju i listi', str_contains($adminOrderView, 'Evidentiraj isporuku i kompletiraj') && str_contains($adminOrderIndex, 'Evidentiraj isporuku') && str_contains($web, "name('orders.complete')"));


$deliveryMigration = (string) file_get_contents($root.'/database/migrations/2026_07_30_000017_add_delivery_workflow_beta7_8.php');
$deliveryModel = (string) file_get_contents($root.'/app/Models/OrderDelivery.php');
$deliveryController = (string) file_get_contents($root.'/app/Http/Controllers/OrderDeliveryController.php');
$deliveryFeatureTest = (string) file_get_contents($root.'/tests/Feature/OrderDeliveryWorkflowTest.php');
$orderPresenter = (string) file_get_contents($root.'/app/Services/OrderDetailPresenter.php');
$deploymentDoctor = (string) file_get_contents($root.'/app/Console/Commands/DeploymentCheckCommand.php');
$check('beta7.8 migracija dodaje evidenciju isporuke i reopening stanje', str_contains($deliveryMigration, 'order_deliveries') && str_contains($deliveryMigration, 'reopened_at') && str_contains($deliveryMigration, 'delivery_method_snapshot') && str_contains($deliveryMigration, 'orders.confirm_delivery') && str_contains($deliveryMigration, 'orders.reopen'));
$check('beta7.8 kompletiranje čuva dokaz isporuke privatno', str_contains($workflow, 'storeDeliveryProof') && str_contains($workflow, "'proof_disk' => 'local'") && str_contains($deliveryController, 'authorizeView') && str_contains($deliveryController, 'Cache-Control'));
$check('beta7.8 otpremnica koristi OTP broj i delivery snapshot', str_contains($documentService, "'delivery_note'") && str_contains((string) file_get_contents($root.'/app/Services/DocumentNumberService.php'), "'delivery_note' => 'OTP'") && str_contains($businessPdf, 'OTPREMNICA'));
$check('beta7.8 ponovno otvaranje je superadmin-only i auditovano', str_contains($workflow, 'public function reopen') && str_contains($workflow, "hasRole('superadmin')") && str_contains($workflow, 'order.reopened') && str_contains($adminOrderView, 'Ponovo otvori porudžbinu'));
$check('beta7.8 detalj prikazuje strukturiranu evidenciju isporuke', str_contains($orderPresenter, "'delivery' =>") && str_contains($adminOrderView, 'Evidencija isporuke') && str_contains((string) file_get_contents($root.'/resources/views/orders/show.blade.php'), 'Potvrđena isporuka'));
$check('beta7.8 doctor proverava novu šemu i dozvole', str_contains($deploymentDoctor, "'order_deliveries'") && str_contains($deploymentDoctor, "'orders.confirm_delivery'") && str_contains($deploymentDoctor, "'orders.reopen'"));
$check('beta7.8 feature testovi pokrivaju dokaz otpremnicu i reopening', str_contains($deliveryFeatureTest, 'test_completion_records_private_delivery_proof_cod_balance_and_delivery_note') && str_contains($deliveryFeatureTest, 'test_only_superadmin_can_reopen_completed_order_and_history_is_preserved'));
$check('beta7.8 UI ima delivery workflow responsive stilove', str_contains($css, 'v2.1.0-beta7.8 - Evidence-backed delivery workflow') && str_contains($css, '.delivery-completion-form') && str_contains($css, '.reopen-order-panel'));

$deliveryNoteHotfixMigration = (string) file_get_contents($root.'/database/migrations/2026_07_30_000018_fix_delivery_note_document_type_beta7_9.php');
$adminDocumentController = (string) file_get_contents($root.'/app/Http/Controllers/Admin/OrderDocumentController.php');
$userDocumentController = (string) file_get_contents($root.'/app/Http/Controllers/OrderDocumentController.php');
$paymentsInventoryDoctor = (string) file_get_contents($root.'/app/Console/Commands/PaymentsInventoryDoctorCommand.php');
$deliveryMigrationTest = (string) file_get_contents($root.'/tests/Unit/DeliveryNoteMigrationContractTest.php');
$deliverySmoke = (string) file_get_contents($root.'/bin/delivery-note-smoke.php');
$check('beta7.9 migracija uklanja legacy ENUM blokadu za delivery_note', str_contains($deliveryNoteHotfixMigration, 'ALTER TABLE `order_documents` MODIFY `document_type` VARCHAR(40) NOT NULL') && str_contains($deliveryNoteHotfixMigration, 'delivery_note'));
$check('beta7.9 servis radi schema preflight pre izdavanja otpremnice', str_contains($documentService, 'assertIssueSchemaReady') && str_contains($documentService, 'SHOW COLUMNS FROM `order_documents`') && str_contains($documentService, 'php artisan migrate --force'));
$check('beta7.9 pomoćni notification kvar ne obara izdat dokument, a IPS važi samo za finansijske dokumente', str_contains($documentService, 'Document notification failed after successful issuance') && str_contains((string) file_get_contents($root.'/app/Services/NbsIpsQrService.php'), "['proforma', 'invoice']"));
$check('beta7.9 kontroleri vraćaju incident poruku umesto Error 500', str_contains($adminDocumentController, 'Order document issuance failed') && str_contains($adminDocumentController, 'Incident: ') && str_contains($userDocumentController, 'Order document PDF rendering failed') && str_contains($userDocumentController, '503'));
$check('beta7.9 doctor proverava stvarni MySQL tip dokumenta', str_contains($deploymentDoctor, 'delivery note document type') && str_contains($paymentsInventoryDoctor, 'PASS Tip dokumenta podržava PDF otpremnicu.'));
$check('beta7.9 ima migration contract i delivery note PDF smoke test', str_contains($deliveryMigrationTest, 'VARCHAR(40) NOT NULL') && str_contains($deliverySmoke, "'document_type' => 'delivery_note'") && str_contains($deliverySmoke, 'PASS delivery note PDF'));

$catalogController = (string) file_get_contents($root.'/app/Http/Controllers/CatalogController.php');
$catalogQuery = (string) file_get_contents($root.'/app/Services/CatalogQueryService.php');
$productImage = (string) file_get_contents($root.'/app/Models/ProductImage.php');
$catalogView = (string) file_get_contents($root.'/resources/views/catalog/show.blade.php');
$catalogIndex = (string) file_get_contents($root.'/resources/views/catalog/index.blade.php');
$check('detail koristi eksplicitan slug upit', str_contains($web, "'/catalog/{slug}'") && str_contains($catalogController, 'findVisibleBySlug'));
$check('slug upit primenjuje objedinjeni visibility scope', str_contains($catalogQuery, 'applyVisibleCatalog($query, $user)'));
$check('API detail koristi isti slug upit', str_contains($api, "'/products/{slug}'") && str_contains((string) file_get_contents($root.'/app/Http/Controllers/Api/V1/CatalogController.php'), 'findVisibleBySlug'));
$check('neispravna slika ne obara detail', str_contains($productImage, 'catch (Throwable)') && str_contains($productImage, "in_array('..', explode('/', \$path), true)"));
$check('detail filtrira slike bez validnog URL-a', str_contains($catalogView, '$displayImages') && str_contains($catalogView, "is_string(\$image['url'])"));
$check('katalog generiše eksplicitan slug link', str_contains($catalogIndex, "['slug' => \$product->slug]"));
$check('detail ima interaktivnu thumbnail galeriju', str_contains($catalogView, 'data-product-gallery') && str_contains($catalogView, 'data-gallery-thumbnail') && str_contains($catalogView, 'data-gallery-counter'));
$check('detail ima fullscreen lightbox i zoom kontrole', str_contains($catalogView, 'data-product-lightbox') && str_contains($catalogView, 'data-zoom-in') && str_contains($catalogView, 'data-zoom-out') && str_contains($catalogView, 'data-zoom-reset'));
$check('gallery podržava tastaturu swipe i preload', str_contains($catalogView, "event.key === 'ArrowRight'") && str_contains($catalogView, "addEventListener('pointerup'") && str_contains($catalogView, 'preloadNeighbours'));
$check('gallery radi i sa jednom slikom', str_contains($catalogView, 'thumbnails.length ? thumbnails.map') && str_contains($catalogView, 'mainButton.dataset.gallerySrc'));
$check('gallery CSS ima fullscreen viewport i responsive mobile', str_contains($css, '.product-lightbox{position:fixed;inset:0') && str_contains($css, 'height:100dvh') && str_contains($css, '.product-gallery-thumbnails{display:flex'));
$check('gallery feature testovi postoje', str_contains((string) file_get_contents($root.'/tests/Feature/CatalogDetailPageTest.php'), 'test_product_detail_renders_accessible_gallery_lightbox_zoom_and_swipe_controls') && str_contains((string) file_get_contents($root.'/tests/Feature/CatalogDetailPageTest.php'), 'test_single_product_image_still_has_fullscreen_gallery'));


$automationMigration = (string) file_get_contents($root.'/database/migrations/2026_07_23_000013_create_automation_alerts_beta4.php');
$automationService = (string) file_get_contents($root.'/app/Services/OperationalAutomationService.php');
$automationDoctor = (string) file_get_contents($root.'/app/Console/Commands/AutomationDoctorCommand.php');
$automationView = (string) file_get_contents($root.'/resources/views/admin/settings/automation.blade.php');
$consoleRoutes = (string) file_get_contents($root.'/routes/console.php');
$notificationService = (string) file_get_contents($root.'/app/Services/OperationalNotificationService.php');
foreach (['automation_runs', 'operational_alerts', 'notification_preferences'] as $needle) {
    $check('beta4 migracija sadrži '.$needle, str_contains($automationMigration, $needle));
}
$check('beta4 nema Redis i koristi scheduler/file lock', str_contains($automationService, 'Cache::lock') && str_contains($consoleRoutes, "app:automation-run") && str_contains($consoleRoutes, "app:automation-run --digest"));
$check('beta4 detektuje nepreuzete porudžbine dospele obaveze i nizak lager', str_contains($automationService, 'order_unaccepted') && str_contains($automationService, 'payment_overdue') && str_contains($automationService, 'low_stock'));
$check('beta4 upozorenja su deduplikovana i razrešavaju se', str_contains($automationService, 'alert_key') && str_contains($automationService, "'status' => 'resolved'"));
$check('notification preferences upravljaju kanalima i kategorijama', str_contains($notificationService, 'notificationPreference') && str_contains($notificationService, 'categoryEnabled') && str_contains((string) file_get_contents($root.'/app/Notifications/OperationalNotification.php'), "['_in_app']"));
$check('automation settings UI i ručno pokretanje postoje', str_contains($automationView, 'Pokreni scan sada') && str_contains($web, "name('automation.run')"));
$check('automation doctor proverava repair scheduler i run', str_contains($automationDoctor, '--repair') && str_contains($automationDoctor, '--run') && str_contains($automationDoctor, 'scheduler definicije'));
$check('beta4 dozvola automation.manage postoji', str_contains($seeder, "'slug' => 'automation.manage'") && str_contains($deploymentCheck, "'automation.manage'"));
$check('beta4 feature testovi pokrivaju deduplikaciju i preference', str_contains((string) file_get_contents($root.'/tests/Feature/OperationalAutomationTest.php'), 'test_scan_creates_deduplicated_order_and_stock_alerts') && str_contains((string) file_get_contents($root.'/tests/Feature/OperationalAutomationTest.php'), 'test_user_can_save_notification_preferences'));



$inventoryWorkspaceTest = (string) file_get_contents($root.'/tests/Feature/InventoryWorkspaceUiTest.php');
$check('beta5 inventory koristi jednu aktivnu operaciju', str_contains($inventoryView, 'inventory-operation-tabs') && str_contains($inventoryView, "\$mode === 'receive'") && str_contains($inventoryView, "\$mode === 'count'"));
$check('beta5 inventory čuva filter i limit nakon knjiženja', str_contains($inventoryView, '_return_q') && str_contains($inventoryController, 'PAGE_LIMITS') && str_contains($inventoryController, 'returnQuery'));
$check('beta5 inventory nema unutrašnji vertikalni scrollbar', str_contains($css, 'v2.1.0-beta5 - inventory workspace responsive redesign') && str_contains($css, 'max-height:none!important') && str_contains($css, 'overflow-y:visible!important'));
$check('beta5 inventory responsive tabela koristi data-label kartice', str_contains($inventoryView, 'data-label="Artikal"') && str_contains($css, '.inventory-data-table td{display:grid!important'));
$check('beta5 feature test pokriva inventory workspace', str_contains($inventoryWorkspaceTest, 'test_inventory_workspace_uses_single_active_operation_and_preserves_filters'));



$beta6Migration = (string) file_get_contents($root.'/database/migrations/2026_07_23_000014_create_security_backup_health_beta6.php');
$backupService = (string) file_get_contents($root.'/app/Services/BackupService.php');
$healthService = (string) file_get_contents($root.'/app/Services/SystemHealthService.php');
$securityHeaders = (string) file_get_contents($root.'/app/Http/Middleware/SecurityHeaders.php');
$requestIdMiddleware = (string) file_get_contents($root.'/app/Http/Middleware/AttachRequestId.php');
$auditLogger = (string) file_get_contents($root.'/app/Services/AuditLogger.php');
$sensitiveSanitizer = (string) file_get_contents($root.'/app/Services/SensitiveDataSanitizer.php');
$testDbDoctor = (string) file_get_contents($root.'/app/Console/Commands/TestDatabaseDoctorCommand.php');
$healthView = (string) file_get_contents($root.'/resources/views/admin/settings/system-health.blade.php');
foreach (['backup_runs', 'system_health_snapshots', 'system_runtime_states', 'security_events', 'system.health', 'backups.manage', 'audit.export', 'security.view'] as $needle) {
    $check('beta6 migracija sadrži '.$needle, str_contains($beta6Migration, $needle));
}
$check('beta6 backup koristi mysqldump bez lozinke u argumentima', str_contains($backupService, "['MYSQL_PWD'") && !str_contains($backupService, '--password='));
$check('beta6 backup odbija public putanju i pravi SHA-256 manifest', str_contains($backupService, 'public_path()') && str_contains($backupService, 'manifest.json') && str_contains($backupService, "hash_file('sha256'"));
$check('beta6 system health proverava scheduler backup migracije i legacy', str_contains($healthService, 'scheduler_heartbeat') && str_contains($healthService, 'pendingMigrationCount') && str_contains($healthService, 'backup_runs') && str_contains($healthService, 'transaction_read_only'));
$check('beta6 security header-i i request ID postoje', str_contains($securityHeaders, 'Content-Security-Policy') && str_contains($securityHeaders, 'X-Content-Type-Options') && str_contains($requestIdMiddleware, 'X-Request-ID'));
$check('beta6 audit koristi rekurzivnu sanitizaciju i request ID', str_contains($auditLogger, 'SensitiveDataSanitizer') && str_contains($auditLogger, "'request_id'") && str_contains($sensitiveSanitizer, '[REDACTED]'));
$check('beta6 rate limiter-i pokrivaju upload export admin i backup', str_contains($provider, "RateLimiter::for('uploads'") && str_contains($provider, "RateLimiter::for('exports'") && str_contains($provider, "RateLimiter::for('admin-write'") && str_contains($provider, "RateLimiter::for('backup'"));
$check('beta6 test DB doctor ima višestruku zaštitu', str_contains($testDbDoctor, "str_ends_with") && str_contains($testDbDoctor, 'TEST_DB_CONFIRM_DATABASE') && str_contains($testDbDoctor, 'ALLOW_TEST_DATABASE_RESET') && str_contains($testDbDoctor, "environment('testing')"));
$check('beta6 system health UI i backup akcije postoje', str_contains($healthView, 'System Health & Backup') && str_contains($healthView, 'Kreiraj kompletan backup') && str_contains($web, "system-health/backup"));
$check('beta6 scheduler ima heartbeat backup i health snapshot', str_contains($consoleRoutes, 'app:scheduler-heartbeat') && str_contains($consoleRoutes, 'app:backup-create --type=daily') && str_contains($consoleRoutes, 'app:backup-create --type=weekly') && str_contains($consoleRoutes, 'app:system-health --snapshot'));
$check('beta6 feature i unit testovi postoje', str_contains((string) file_get_contents($root.'/tests/Feature/SecurityHealthBackupTest.php'), 'test_security_headers_and_request_id_are_present') && str_contains((string) file_get_contents($root.'/tests/Unit/SensitiveDataSanitizerTest.php'), 'test_nested_secrets_are_redacted'));


$orderIndexService = (string) file_get_contents($root.'/app/Services/OrderIndexService.php');
$orderListController = (string) file_get_contents($root.'/app/Http/Controllers/Admin/OrderController.php');
$orderListView = (string) file_get_contents($root.'/resources/views/admin/orders/index.blade.php');
$ordersDoctor = (string) file_get_contents($root.'/app/Console/Commands/OrdersDoctorCommand.php');
$productImageService = (string) file_get_contents($root.'/app/Services/ProductImageService.php');
$productEditView = (string) file_get_contents($root.'/resources/views/admin/products/form.blade.php');
$productImagesView = (string) file_get_contents($root.'/resources/views/admin/products/images.blade.php');
$ordersImageTest = (string) file_get_contents($root.'/tests/Feature/AdminOrdersImageRotationTest.php');
$check('beta7.1 orders ima readiness SQL i render zaštitu', str_contains($orderIndexService, 'readinessIssues') && str_contains($orderListController, 'renderIndexProtected') && str_contains($orderListController, 'Orders Blade/layout render nije uspeo'));
$check('beta7.1 orders doctor proverava isti browser render', str_contains($ordersDoctor, 'app:orders-doctor') && str_contains($ordersDoctor, 'data-orders-page-ready') && str_contains($orderListView, 'data-orders-page-ready="1"'));
$check('beta7.1 orders recovery ne završava generičkim 500', str_contains($orderListController, 'Porudžbine privremeno nisu dostupne') && str_contains($orderListController, '503'));
$productImageCardView = (string) file_get_contents($root.'/resources/views/admin/products/partials/image-card.blade.php');
$check('beta7.1 edit artikla ima rotaciju ulevo i udesno', str_contains($productEditView, 'rotate-left-') && str_contains($productEditView, 'rotate-right-') && str_contains($productImageCardView, '↶ 90°') && str_contains($productImageCardView, '↷ 90°'));
$check('beta7.1 legacy rotacija koristi copy-on-write', str_contains($productImageService, 'legacy_copy_on_write') && str_contains($productImageService, 'sourceAbsolutePath') && str_contains($productImageService, "storage_disk = 'public'"));
$check('beta7.1 rotacija koristi privremeni fajl i kontrolisani Imagick/GD fallback', str_contains($productImageService, 'temporaryCopy') && str_contains($productImageService, "extension_loaded('gd')") && str_contains($productImageService, 'rotateWithImagick') && str_contains($productImageCardView, 'Rotiraj ulevo 90°'));
$check('beta7.1 feature testovi postoje', str_contains($ordersImageTest, 'test_admin_orders_index_renders_complete_protected_page') && str_contains($ordersImageTest, 'test_product_edit_exposes_left_and_right_rotation_for_legacy_image'));

$orderDetailService = (string) file_get_contents($root.'/app/Services/OrderDetailService.php');
$orderTimelineService = (string) file_get_contents($root.'/app/Services/OrderTimelineService.php');
$ipsService = (string) file_get_contents($root.'/app/Services/IpsPaymentPayloadService.php');
$adminOrderDetailView = (string) file_get_contents($root.'/resources/views/admin/orders/show.blade.php');
$userOrderDetailView = (string) file_get_contents($root.'/resources/views/orders/show.blade.php');
$detailPagesDoctor = (string) file_get_contents($root.'/app/Console/Commands/DetailPagesDoctorCommand.php');
$check('beta7.2 order detail koristi opcioni schema-aware loader', str_contains($orderDetailService, 'final class OrderDetailService') && str_contains($orderDetailService, 'loadCommission') && str_contains($orderDetailService, 'setRelation'));
$check('beta7.2 admin i user detail imaju protected render', str_contains($orderListController, 'renderShowProtected') && str_contains((string) file_get_contents($root.'/app/Http/Controllers/OrderController.php'), 'User order detail render nije uspeo'));
$check('beta7.2 admin i user detail imaju readiness markere', str_contains($adminOrderDetailView, 'data-order-detail-ready="1"') && str_contains($userOrderDetailView, 'data-order-user-detail-ready="1"'));
$check('beta7.2 timeline i IPS ne mogu oboriti detalj', str_contains($orderTimelineService, 'appendAuditEvents') && str_contains($orderTimelineService, 'relationLoaded') && str_contains($ipsService, 'IPS payload je generisan, ali nije sačuvan'));
$check('beta7.2 orders doctor renderuje oba detalja', str_contains($ordersDoctor, '--order-id=') && str_contains($ordersDoctor, 'renderAdminDetail') && str_contains($ordersDoctor, 'renderUserDetail'));
$check('beta7.2 detail-pages doctor proverava ključne detail stranice', str_contains($detailPagesDoctor, 'app:detail-pages-doctor') && str_contains($detailPagesDoctor, 'data-catalog-detail-ready') && str_contains($detailPagesDoctor, 'data-product-edit-ready') && str_contains($detailPagesDoctor, 'data-product-images-ready'));
$check('beta7.2 feature testovi pokrivaju detail i opcione tabele', str_contains($ordersImageTest, 'test_admin_and_customer_order_details_render_complete_pages') && str_contains($ordersImageTest, 'test_order_detail_survives_missing_optional_operational_tables'));


$viewValue = (string) file_get_contents($root.'/app/Support/ViewValue.php');
$orderDetailPresenter = (string) file_get_contents($root.'/app/Services/OrderDetailPresenter.php');
$userOrderController = (string) file_get_contents($root.'/app/Http/Controllers/OrderController.php');
$presenterTest = (string) file_get_contents($root.'/tests/Unit/OrderDetailPresenterTest.php');
$viewValueTest = (string) file_get_contents($root.'/tests/Unit/ViewValueTest.php');
$adminPaymentDetailView = (string) file_get_contents($root.'/resources/views/admin/orders/partials/payments.blade.php');
$userPaymentDetailView = (string) file_get_contents($root.'/resources/views/orders/partials/payments.blade.php');
$detailTemplates = $adminOrderDetailView.$adminPaymentDetailView.$userOrderDetailView.$userPaymentDetailView;
$check('beta7.3 detail koristi scalar presenter umesto Eloquent objekata u Blade-u', str_contains($orderDetailPresenter, 'final class OrderDetailPresenter') && str_contains($orderListController, 'OrderDetailPresenter $presenter') && str_contains($userOrderController, 'OrderDetailPresenter $presenter') && substr_count($detailTemplates, '$detail[') >= 4 && !str_contains($detailTemplates, '$order->'));
$check('beta7.3 presenter bezbedno obrađuje raw i zero datume', str_contains($orderDetailPresenter, 'getAttributes()') && str_contains($orderDetailPresenter, "str_starts_with((string) \$value, '0000-00-00')") && str_contains($orderDetailPresenter, 'Carbon::parse'));
$check('beta7.3 presenter bezbedno generiše named rute', str_contains($orderDetailPresenter, 'Route::has') && str_contains($orderDetailPresenter, 'catch (Throwable)') && !str_contains($detailTemplates, 'route('));
$check('beta7.3 detail view nema direktne auth, relation ili datetime pozive', !str_contains($detailTemplates, 'auth()->') && !str_contains($detailTemplates, '?->format(') && !str_contains($detailTemplates, 'relationLoaded('));
$check('beta7.3 admin i user detail imaju ne-503 read-only fallback', str_contains($orderListController, "'X-Ald1n-Detail-Fallback' => '1'") && str_contains($orderListController, '$this->fallbackDetailHtml') && str_contains($userOrderController, "'X-Ald1n-Detail-Fallback' => '1'") && str_contains($userOrderController, '$this->fallbackDetailHtml'));
$check('beta7.3 orders doctor prikazuje tačan exception uzrok za oba detaila', substr_count($ordersDoctor, '<fg=yellow>UZROK</>') >= 2 && substr_count($ordersDoctor, 'lastDetailRenderException()') >= 2 && str_contains($ordersDoctor, 'getFile()'));
$check('beta7.3 orders doctor nastavlja admin i user audit', str_contains($ordersDoctor, '$failed = false;') && str_contains($ordersDoctor, 'return $failed ? self::FAILURE : self::SUCCESS;'));
$check('beta7.3 payment i inventory Gates su definisani', str_contains($provider, "Gate::define('payments.manage'") && str_contains($provider, "Gate::define('payments.upload_proof'") && str_contains($provider, "Gate::define('inventory.receive'") && str_contains($provider, "Gate::define('inventory.count'"));
$check('beta7.3 presenter i ViewValue regresioni testovi postoje', str_contains($presenterTest, 'test_presenter_survives_zero_dates_and_uses_only_scalar_view_data') && str_contains($viewValue, 'getRawOriginal') && str_contains($viewValueTest, 'test_zero_and_invalid_database_dates_use_fallback_without_casting_exception') && str_contains($viewValueTest, 'test_missing_named_route_is_reported_as_null_instead_of_throwing_from_view'));


$afterSalesMigration = (string) file_get_contents($root.'/database/migrations/2026_07_30_000019_create_after_sales_cases_beta7_10.php');
$afterSalesService = (string) file_get_contents($root.'/app/Services/AfterSalesCaseService.php');
$afterSalesAccess = (string) file_get_contents($root.'/app/Services/AfterSalesAccessService.php');
$afterSalesUserController = (string) file_get_contents($root.'/app/Http/Controllers/AfterSalesController.php');
$afterSalesAdminController = (string) file_get_contents($root.'/app/Http/Controllers/Admin/AfterSalesController.php');
$afterSalesAttachmentController = (string) file_get_contents($root.'/app/Http/Controllers/AfterSalesAttachmentController.php');
$afterSalesFeatureTest = (string) file_get_contents($root.'/tests/Feature/AfterSalesWorkflowTest.php');
$afterSalesDoctor = (string) file_get_contents($root.'/app/Console/Commands/AfterSalesDoctorCommand.php');
$dashboardController = (string) file_get_contents($root.'/app/Http/Controllers/DashboardController.php');
$dashboardView = (string) file_get_contents($root.'/resources/views/dashboard/index.blade.php');
$afterSalesViews = (string) file_get_contents($root.'/resources/views/after-sales/create.blade.php')
    .(string) file_get_contents($root.'/resources/views/after-sales/show.blade.php')
    .(string) file_get_contents($root.'/resources/views/admin/after-sales/index.blade.php')
    .(string) file_get_contents($root.'/resources/views/admin/after-sales/show.blade.php');
foreach (['after_sales_cases', 'after_sales_case_items', 'after_sales_messages', 'after_sales_attachments', 'after_sales_status_history'] as $needle) {
    $check('beta7.10 migracija sadrži '.$needle, str_contains($afterSalesMigration, $needle));
}
$check('beta7.10 ima tri postprodajne dozvole', str_contains($seeder, "'slug' => 'after_sales.create'") && str_contains($seeder, "'slug' => 'after_sales.view_own'") && str_contains($seeder, "'slug' => 'after_sales.manage'"));
$check('beta7.10 pristup poštuje vlasnika dodeljenog admina i superadmin scope', str_contains($afterSalesAccess, 'applyVisibleScope') && str_contains($afterSalesAccess, "hasRole('superadmin')") && str_contains($afterSalesAccess, "supplier_user_id"));
$check('beta7.10 slučaj zahteva isporučenu ili kompletiranu porudžbinu', str_contains($afterSalesAccess, 'completed_at') && str_contains($afterSalesAccess, 'delivery()->exists()'));
$check('beta7.10 čuva pogođene stavke snapshot i SLA rok', str_contains($afterSalesService, 'product_name_snapshot') && str_contains($afterSalesService, 'slaDays') && str_contains($afterSalesService, "'due_at'"));
$check('beta7.10 privatni prilozi proveravaju MIME veličinu i autorizaciju', str_contains($afterSalesService, 'getMimeType') && str_contains($afterSalesService, '10 * 1024 * 1024') && str_contains($afterSalesAttachmentController, 'authorizeView') && str_contains($afterSalesAttachmentController, "visibility === 'internal'") && str_contains($afterSalesAttachmentController, 'Storage::disk'));
$check('beta7.10 javne i interne poruke su odvojene', str_contains($afterSalesService, "'internal'") && str_contains($afterSalesUserController, "where('visibility', 'public')") && str_contains($afterSalesAdminController, "visibility"));
$check('beta7.10 statusni tok zahteva obrazloženje konačne odluke', str_contains($afterSalesService, 'assertTransition') && str_contains($afterSalesService, 'resolution_summary') && str_contains($afterSalesService, 'Prelaz iz trenutnog'));
$check('beta7.10 automatizacija upozorava na probijene rokove slučaja', str_contains($automationService, 'after_sales_overdue') && str_contains($automationService, "route('admin.after-sales.show'"));
$check('beta7.10 UI ima korisnički i administratorski postprodajni tok', str_contains($afterSalesViews, 'Otvori reklamaciju ili servis') && str_contains($afterSalesViews, 'Komunikacija i interne napomene') && str_contains($css, 'v2.1.0-beta7.10 - After-sales cases'));
$check('beta7.10 doctor proverava šemu dozvole i SQL', str_contains($afterSalesDoctor, 'app:after-sales-doctor') && str_contains($afterSalesDoctor, '--repair') && str_contains($afterSalesDoctor, 'Osnovni SQL upiti'));
$check('beta7.10 feature test pokriva privatni prilog i obradu', str_contains($afterSalesFeatureTest, 'test_customer_opens_case_admin_processes_it_and_attachment_stays_private') && str_contains($afterSalesFeatureTest, 'assertNotFound'));
$check('beta7.10 privatni download zabranjuje browser cache', str_contains($afterSalesAttachmentController, 'private, no-store, max-age=0') && str_contains($afterSalesAttachmentController, 'nosniff'));
$check('beta7.10 konkurentno zatvaranje ne propušta novu poruku', substr_count($afterSalesService, 'lockForUpdate()') >= 3 && str_contains($afterSalesService, '$locked->isClosed()'));
$check('beta7.10 reopening zahteva razlog i čuva vreme prethodnog rešenja', str_contains($afterSalesService, 'Za ponovno otvaranje slučaja obavezno unesite razlog') && str_contains($afterSalesFeatureTest, 'test_closing_preserves_resolution_time_and_reopening_requires_a_reason'));
$check('beta7.10 nedodeljeni slučajevi obaveštavaju superadministratore', str_contains($afterSalesService, "where('slug', 'superadmin')") && str_contains($afterSalesService, '$recipients->unique'));
$check('beta7.10 dashboard prikazuje aktivne probijene i waiting slučajeve', str_contains($dashboardController, 'afterSalesStats') && str_contains($dashboardController, "'awaiting_customer'") && str_contains($dashboardView, 'Reklamacije i servisi') && str_contains($dashboardView, '$afterSalesStats[\'overdue\']'));

$afterSalesActionMigration = (string) file_get_contents($root.'/database/migrations/2026_07_30_000020_create_after_sales_actions_beta7_11.php');
$afterSalesActionService = (string) file_get_contents($root.'/app/Services/AfterSalesActionService.php');
$afterSalesActionController = (string) file_get_contents($root.'/app/Http/Controllers/Admin/AfterSalesActionController.php');
$afterSalesActionTest = (string) file_get_contents($root.'/tests/Feature/AfterSalesActionExecutionTest.php');
foreach (['after_sales_actions', 'after_sales_action_items', 'after_sales_action_id', 'after_sales.execute'] as $needle) {
    $check('beta7.11 migracija sadrži '.$needle, str_contains($afterSalesActionMigration, $needle));
}
$check('beta7.11 podržava četiri izvršne radnje', str_contains($afterSalesActionService, "'service_visit'") && str_contains($afterSalesActionService, "'replacement_dispatch'") && str_contains($afterSalesActionService, "'return_receipt'") && str_contains($afterSalesActionService, "'refund'"));
$check('beta7.11 lager efekti su zaključani i idempotentni', str_contains($afterSalesActionService, 'lockForUpdate()') && str_contains($afterSalesActionService, 'after-sales-action:') && str_contains($afterSalesActionService, "where('event_key'"));
$check('beta7.11 refundacija je vezana za radnju i ograničena neto uplatom', str_contains($paymentService, 'recordAfterSalesRefundLocked') && str_contains($paymentService, 'after_sales_action_id') && str_contains($paymentService, 'neto uplaćenog iznosa'));
$check('beta7.11 slučaj čeka završetak aktivnih radnji', str_contains($afterSalesService, 'Slučaj ne može biti završen dok postoje planirane ili aktivne izvršne radnje') && str_contains($afterSalesService, 'prvo evidentirajte i izvršite'));
$check('beta7.11 UI ima planiranje pokretanje izvršenje i otkazivanje', str_contains($afterSalesViews, 'Planiraj novu radnju') && str_contains($afterSalesViews, 'Pokreni radnju') && str_contains($afterSalesViews, 'Označi kao izvršenu') && str_contains($afterSalesViews, 'Otkaži radnju'));
$check('beta7.11 Gate i permission middleware štite izvršne kontrole', str_contains($provider, "Gate::define('after_sales.execute'") && str_contains($web, "permission:after_sales.execute"));
$check('beta7.11 controller ima sve izvršne endpoint-e', str_contains($afterSalesActionController, 'function store') && str_contains($afterSalesActionController, 'function start') && str_contains($afterSalesActionController, 'function complete') && str_contains($afterSalesActionController, 'function cancel'));
$check('beta7.11 automatizacija prati rok izvršne radnje', str_contains($automationService, 'after_sales_action_overdue'));
$check('beta7.11 dashboard prikazuje radnje za izvršenje', str_contains($dashboardController, 'pending_actions') && str_contains($dashboardView, '$afterSalesStats[\'pending_actions\']'));
$check('beta7.11 testovi pokrivaju idempotentni lager povrat i refundaciju', str_contains($afterSalesActionTest, 'test_replacement_action_changes_stock_exactly_once') && str_contains($afterSalesActionTest, 'test_return_receipt_can_restock_the_returned_item') && str_contains($afterSalesActionTest, 'test_refund_action_works_on_completed_order'));


$fieldMigration = (string) file_get_contents($root.'/database/migrations/2026_07_30_000021_create_field_operations_beta7_12.php');
$fieldPlanner = (string) file_get_contents($root.'/app/Services/FieldWorkOrderPlanner.php');
$fieldService = (string) file_get_contents($root.'/app/Services/FieldOperationsService.php');
$fieldController = (string) file_get_contents($root.'/app/Http/Controllers/Admin/FieldOperationsController.php');
$fieldDoctor = (string) file_get_contents($root.'/app/Console/Commands/FieldOperationsDoctorCommand.php');
$fieldViews = (string) file_get_contents($root.'/resources/views/admin/field-operations/index.blade.php')
    .(string) file_get_contents($root.'/resources/views/admin/field-operations/show.blade.php')
    .(string) file_get_contents($root.'/resources/views/admin/field-operations/teams.blade.php');
$fieldTest = (string) file_get_contents($root.'/tests/Feature/FieldOperationsWorkflowTest.php');
foreach (['field_service_teams', 'field_work_orders', 'field_work_order_attachments', 'field_operations.view', 'field_operations.manage'] as $needle) {
    $check('beta7.12 migracija sadrži '.$needle, str_contains($fieldMigration, $needle));
}
$check('beta7.12 fizičke radnje automatski dobijaju radni nalog', str_contains($fieldPlanner, 'PHYSICAL_ACTIONS') && str_contains($fieldPlanner, 'ensureForAction'));
$check('beta7.12 sprečava preklapanje termina iste ekipe', str_contains($fieldPlanner, 'assertNoConflict') && str_contains($fieldPlanner, 'where(\'planned_start_at\', \'<\', $end)') && str_contains($fieldPlanner, 'lockForUpdate()'));
$check('beta7.12 završetak zahteva dolazak na lokaciju', str_contains($fieldService, "status !== 'on_site'") && str_contains($fieldService, 'Pre završetka evidentirajte'));
$check('beta7.12 radni nalog čuva troškove kilometražu i privatne dokaze', str_contains($fieldService, 'travel_cost_rsd') && str_contains($fieldService, 'labor_cost_rsd') && str_contains($fieldService, 'parts_cost_rsd') && str_contains($fieldService, "Storage::disk('local')"));
$check('beta7.12 UI ima kalendar ekipe i operativne statuse', str_contains($fieldViews, 'Operativni kalendar') && str_contains($fieldViews, 'Ekipa je krenula') && str_contains($fieldViews, 'Na lokaciji') && str_contains($css, 'v2.1.0-beta7.12 - field operations'));
$check('beta7.12 rute i Gate štite terenske operacije', str_contains($provider, "Gate::define('field_operations.view'") && str_contains($provider, "Gate::define('field_operations.manage'") && str_contains($web, 'permission:field_operations.manage'));
$check('beta7.12 automatizacija prati neplanirane i probijene radne naloge', str_contains($automationService, 'field_work_order_overdue') && str_contains($automationService, 'field_work_order_unscheduled'));
$check('beta7.12 doctor proverava tabele dozvole rute i SQL', str_contains($fieldDoctor, 'app:field-operations-doctor') && str_contains($fieldDoctor, 'Osnovni SQL upiti i relacije'));
$check('beta7.12 test pokriva auto nalog konflikt i on-site završetak', str_contains($fieldTest, 'test_physical_actions_create_work_orders_prevent_team_overlap_and_require_on_site_completion') && str_contains($fieldTest, "assertSessionHasErrors('field_service_team_id')") && str_contains($fieldTest, "assertSessionHasErrors('work_order')"));


$servicePartsMigration = (string) file_get_contents($root.'/database/migrations/2026_07_30_000022_create_service_parts_procurement_beta7_13.php');
$servicePartsService = (string) file_get_contents($root.'/app/Services/ServicePartsInventoryService.php');
$servicePartsDoctor = (string) file_get_contents($root.'/app/Console/Commands/ServicePartsDoctorCommand.php');
$servicePartsViews = (string) file_get_contents($root.'/resources/views/admin/service-parts/index.blade.php')
    .(string) file_get_contents($root.'/resources/views/admin/service-parts/suppliers.blade.php')
    .(string) file_get_contents($root.'/resources/views/admin/service-parts/purchase-requests.blade.php')
    .(string) file_get_contents($root.'/resources/views/admin/service-parts/purchase-show.blade.php')
    .(string) file_get_contents($root.'/resources/views/admin/field-operations/show.blade.php');
$servicePartsTest = (string) file_get_contents($root.'/tests/Feature/ServicePartsWorkflowTest.php');
foreach (['service_part_suppliers', 'service_parts', 'field_work_order_parts', 'service_part_movements', 'service_part_purchase_requests', 'service_part_purchase_request_items', 'service_parts.view', 'service_parts.manage', 'service_parts.procurement'] as $needle) {
    $check('beta7.13 migracija sadrži '.$needle, str_contains($servicePartsMigration, $needle));
}
$check('beta7.13 početno stanje ulazi u movement ledger', str_contains($servicePartsService, 'createPart') && str_contains($servicePartsService, "'opening_balance'") && str_contains($servicePartsService, 'service-part-opening:'));
$check('beta7.13 rezervacija ne umanjuje fizičko stanje', str_contains($servicePartsService, 'reserveWorkOrderParts') && str_contains($servicePartsService, '\'reserved_quantity\' => $reservedAfter') && str_contains($servicePartsService, "'reservation'"));
$check('beta7.13 završetak skida stvarni utrošak i oslobađa ostatak', str_contains($servicePartsService, 'finalizeWorkOrderParts') && str_contains($servicePartsService, "'consumption'") && str_contains($servicePartsService, "'release-final:'") && str_contains($fieldService, 'finalizeWorkOrderParts'));
$check('beta7.13 otkazivanje oslobađa sve rezervacije', str_contains($servicePartsService, 'releaseWorkOrderReservations') && str_contains($fieldService, 'releaseWorkOrderReservations'));
$check('beta7.13 kretanja servisnog lagera su idempotentna i ponovo proverena pod lockom', str_contains($servicePartsService, 'firstOrCreate([\'event_key\' => $eventKey]') && substr_count($servicePartsService, 'where(\'event_key\', $eventKey)->first()') >= 2 && str_contains($servicePartsService, 'service-part-purchase-receive:'));
$check('beta7.13 nacrt nabavke koristi konkurentno bezbedan privremeni broj', str_contains($servicePartsService, "'PENDING-'.Str::lower(Str::random(24))"));
$check('beta7.13 prijem nabavke računa ponderisanu prosečnu cenu', str_contains($servicePartsService, '$oldValue = $stockBefore *') && str_contains($servicePartsService, '$newValue = $qty *') && str_contains($servicePartsService, '\'average_cost_rsd\' => $average'));
$check('beta7.13 UI ima servisni lager dobavljače nabavku i utrošak', str_contains($servicePartsViews, 'Servisni lager') && str_contains($servicePartsViews, 'Dobavljači rezervnih delova') && str_contains($servicePartsViews, 'Zahtevi za nabavku') && str_contains($servicePartsViews, 'Rezerviši sve lokalne delove'));
$check('beta7.13 Gate i rute štite lager i nabavku', str_contains($provider, "Gate::define('service_parts.view'") && str_contains($provider, "Gate::define('service_parts.manage'") && str_contains($provider, "Gate::define('service_parts.procurement'") && str_contains($web, 'permission:service_parts.procurement'));
$check('beta7.13 automatizacija prati nizak lager i kašnjenje nabavke', str_contains($automationService, 'service_part_low') && str_contains($automationService, 'service_part_purchase_overdue'));
$check('beta7.13 doctor proverava tabele dozvole rute i SQL', str_contains($servicePartsDoctor, 'app:service-parts-doctor') && str_contains($servicePartsDoctor, 'SQL upiti i relacije servisnog lagera rade'));
$check('beta7.13 testovi pokrivaju ledger rezervaciju utrošak i ponderisanu cenu', str_contains($servicePartsTest, 'test_part_creation_records_opening_balance_and_manual_adjustment_is_idempotent') && str_contains($servicePartsTest, 'test_work_order_reserves_consumes_and_releases_unused_service_parts_exactly_once') && str_contains($servicePartsTest, 'test_purchase_receipt_updates_stock_and_weighted_average_once'));
$check('beta7.13 UI ima responsive stilove servisnog lagera', str_contains($css, 'v2.1.0-beta7.13 - servisni lager i nabavka'));


$documentRevisionMigration = (string) file_get_contents($root.'/database/migrations/2026_07_30_000023_enable_document_revisions_beta7_14.php');
$documentRevisionTest = (string) file_get_contents($root.'/tests/Unit/DocumentRevisionMigrationContractTest.php');
$orderDocumentController = (string) file_get_contents($root.'/app/Http/Controllers/Admin/OrderDocumentController.php');
$orderDocumentModel = (string) file_get_contents($root.'/app/Models/OrderDocument.php');
$orderShowView = (string) file_get_contents($root.'/resources/views/admin/orders/show.blade.php');
$check('beta7.14.1 migracija prvo obezbeđuje FK indeks', str_contains($documentRevisionMigration, 'ensureOrderIdForeignKeySupportIndex') && str_contains($documentRevisionMigration, 'order_documents_order_id_fk_index') && strpos($documentRevisionMigration, 'ensureOrderIdForeignKeySupportIndex();') < strpos($documentRevisionMigration, 'dropOrderTypeUniqueIndexes();'));
$check('beta7.14 migracija uklanja unique order/type ograničenje', str_contains($documentRevisionMigration, 'dropOrderTypeUniqueIndexes') && str_contains($documentRevisionMigration, "['order_id', 'document_type', 'status']"));
$check('beta7.14 migracija uvodi revizije i vezu sa prethodnim dokumentom', str_contains($documentRevisionMigration, 'revision_number') && str_contains($documentRevisionMigration, 'supersedes_document_id') && str_contains($documentRevisionMigration, 'backfillRevisionChain'));
$check('beta7.14 servis vraća samo aktivan dokument ili izdaje novu reviziju', str_contains($documentService, "where('status', 'issued')") && str_contains($documentService, '$latestRevision') && str_contains($documentService, '$revisionNumber'));
$check('beta7.14 storniranje zahteva razlog i čuva audit podatak', str_contains($orderDocumentController, 'cancellation_reason') && str_contains($documentService, "'cancellation_reason' => \$reason"));
$check('beta7.14 model podržava supersedes relaciju', str_contains($orderDocumentModel, 'function supersedes') && str_contains($orderDocumentModel, 'function revisions'));
$check('beta7.14 UI razlikuje aktivan dokument i novu reviziju', str_contains($orderShowView, 'Otvori aktivni predračun') && str_contains($orderShowView, 'Izdaj novi račun') && str_contains($orderShowView, 'Razlog storniranja'));
$check('beta7.14 PDF prikazuje broj revizije', str_contains((string) file_get_contents($root.'/app/Services/Pdf/BusinessDocumentPdfService.php'), "' · revizija '"));
$check('beta7.14 regresioni test pokriva ponovno izdavanje', str_contains((string) file_get_contents($root.'/tests/Feature/ReportsDocumentsSupplierTest.php'), 'test_cancelled_proforma_and_invoice_can_be_reissued_as_new_revisions') && str_contains($documentRevisionTest, 'dropOrderTypeUniqueIndexes'));



$warrantyMigration = (string) file_get_contents($root.'/database/migrations/2026_07_30_000024_create_warranties_preventive_maintenance_beta7_15.php');
$warrantyService = (string) file_get_contents($root.'/app/Services/WarrantyService.php');
$warrantyPdf = (string) file_get_contents($root.'/app/Services/Pdf/WarrantyCertificatePdfService.php');
$warrantyAdminController = (string) file_get_contents($root.'/app/Http/Controllers/Admin/WarrantyController.php');
$warrantyUserController = (string) file_get_contents($root.'/app/Http/Controllers/WarrantyController.php');
$warrantyDoctor = (string) file_get_contents($root.'/app/Console/Commands/WarrantiesDoctorCommand.php');
$warrantyBackfill = (string) file_get_contents($root.'/app/Console/Commands/WarrantiesBackfillCommand.php');
$warrantyFeatureTest = (string) file_get_contents($root.'/tests/Feature/WarrantiesPreventiveMaintenanceTest.php');
$warrantySmoke = (string) file_get_contents($root.'/bin/warranty-pdf-smoke.php');
$warrantyViews = (string) file_get_contents($root.'/resources/views/admin/warranties/index.blade.php')
    .(string) file_get_contents($root.'/resources/views/admin/warranties/show.blade.php')
    .(string) file_get_contents($root.'/resources/views/warranties/index.blade.php')
    .(string) file_get_contents($root.'/resources/views/warranties/show.blade.php');
foreach (['warranty_rules', 'product_warranties', 'warranty_maintenance_records', 'warranties.view_own', 'warranties.manage'] as $needle) {
    $check('beta7.15 migracija sadrži '.$needle, str_contains($warrantyMigration, $needle));
}
$check('beta7.15 pravila imaju product category global prioritet', str_contains($warrantyService, "scope_type', 'product'") && str_contains($warrantyService, "scope_type', 'category'") && str_contains($warrantyService, "scope_type', 'global'") && str_contains($warrantyService, 'orderByDesc(\'priority\')'));
$check('beta7.15 kompletiranje automatski izdaje garanciju bez obaranja porudžbine', str_contains($workflow, 'ensureForOrder') && str_contains($workflow, 'Automatic warranty issuance failed.'));
$check('beta7.15 otkazivanje poništava aktivne garancije', str_contains($workflow, 'voidForOrder') && str_contains($warrantyService, 'warranty.voided'));
$check('beta7.15 GAR poslovni broj je registrovan', str_contains((string) file_get_contents($root.'/app/Services/DocumentNumberService.php'), "'warranty' => 'GAR'"));
$check('beta7.15 garancija čuva snapshot kupca artikla uslova i serijskih brojeva', str_contains($warrantyService, 'customer_name_snapshot') && str_contains($warrantyService, 'product_name_snapshot') && str_contains($warrantyService, 'terms_snapshot') && str_contains($warrantyService, 'serial_numbers_json'));
$check('beta7.15 preventivno održavanje generiše sledeći termin', str_contains($warrantyService, 'maintenance_completed') && str_contains($warrantyService, 'next_maintenance_at') && str_contains($warrantyService, "'status' => 'due'"));
$check('beta7.15 zakazivanje ne menja vreme tokom provere datuma', str_contains($warrantyService, '$scheduledAt->copy()->startOfDay()'));
// ALD1N WARRANTY BACKFILL STATIC CHECK V2
// Canonical duplicate-safe backfill query moved to WarrantyAdminService in Mobile v0.6 Warranties Admin Batch 2A V3.
$check('beta7.15 backfill bira samo stavke bez garancije',
    is_file(__DIR__.'/../app/Services/WarrantyAdminService.php')
    && str_contains(
        (string) file_get_contents(__DIR__.'/../app/Services/WarrantyAdminService.php'),
        "whereDoesntHave('warranty')"
    )
);
$check('beta7.15 PDF garantni list prikazuje ključne snapshot podatke', str_contains($warrantyPdf, 'Garantni list') && str_contains($warrantyPdf, 'Serijski broj') && str_contains($warrantyPdf, 'Uslovi garancije') && str_contains($warrantyPdf, 'Preventivno održavanje'));
$check('beta7.15 korisnički i administratorski prikazi postoje', str_contains($warrantyViews, 'Moje garancije') && str_contains($warrantyViews, 'Novo pravilo garancije') && str_contains($warrantyViews, 'Preventivno održavanje'));
$check('beta7.15 rute Gates i administratorski scope štite garancije', str_contains($web, 'permission:warranties.view_own') && str_contains($web, 'permission:warranties.manage') && str_contains($provider, "Gate::define('warranties.view_own'") && str_contains($provider, "Gate::define('warranties.manage'") && str_contains($warrantyUserController, "supplier_user_id") && str_contains($warrantyFeatureTest, 'warranty-other-admin'));
$check('beta7.15 automatizacija prati istek i održavanje', str_contains($automationService, 'warranty_expiring') && str_contains($automationService, 'warranty_maintenance_due'));
$check('beta7.15 dashboard prikazuje garancije', str_contains($dashboardController, 'warrantyStats') && str_contains($dashboardView, 'Garancije') && str_contains($dashboardView, '$warrantyStats[\'active\']') && str_contains($dashboardView, '$warrantyStats[\'maintenance_due\']'));
$check('beta7.15 doctor i backfill komande postoje', str_contains($warrantyDoctor, 'app:warranties-doctor') && str_contains($warrantyDoctor, '--backfill') && str_contains($warrantyBackfill, 'app:warranties-backfill'));
$check('beta7.15 feature test pokriva automatsko izdavanje i prioritet pravila', str_contains($warrantyFeatureTest, 'test_completed_delivery_issues_warranty_and_customer_can_open_pdf') && str_contains($warrantyFeatureTest, 'test_product_rule_has_priority_and_maintenance_completion_creates_next_due_record'));
$check('beta7.15 warranty PDF smoke postoji', str_contains($warrantySmoke, 'PASS warranty PDF') && str_contains($warrantySmoke, 'WarrantyCertificatePdfService'));



$orderEmailMigration = (string) file_get_contents($root.'/database/migrations/2026_07_30_000025_create_order_email_outbox_beta7_16.php');
$orderEmailOutbox = (string) file_get_contents($root.'/app/Services/OrderEmailOutboxService.php');
$orderEmailDispatcher = (string) file_get_contents($root.'/app/Services/OrderEmailDispatcher.php');
$orderEmailSettingsController = (string) file_get_contents($root.'/app/Http/Controllers/Admin/OrderEmailSettingsController.php');
$orderEmailSettingsView = (string) file_get_contents($root.'/resources/views/admin/settings/order-emails.blade.php');
$orderEmailDoctor = (string) file_get_contents($root.'/app/Console/Commands/OrderEmailsDoctorCommand.php');
$nbsService = (string) file_get_contents($root.'/app/Services/NbsIpsQrService.php');
$ipsPayloadService = (string) file_get_contents($root.'/app/Services/IpsPaymentPayloadService.php');
$simplePdfWriter = (string) file_get_contents($root.'/app/Services/Pdf/SimplePdfWriter.php');
$businessPdf = (string) file_get_contents($root.'/app/Services/Pdf/BusinessDocumentPdfService.php');
$orderEmailFeatureTest = (string) file_get_contents($root.'/tests/Feature/OrderEmailsIpsWarrantyTest.php');
$ipsSmoke = (string) file_get_contents($root.'/bin/ips-qr-pdf-smoke.php');
$orderEmailView = (string) file_get_contents($root.'/resources/views/emails/order-events.blade.php');
$check('beta7.16 migracija uvodi outbox QR snapshot i dane garancije', str_contains($orderEmailMigration, 'order_email_outbox') && str_contains($orderEmailMigration, 'ips_payload_snapshot') && str_contains($orderEmailMigration, 'duration_days'));
$check('beta7.16 migracija ima recovery putanju za delimičan MariaDB DDL', str_contains($orderEmailMigration, 'createOrRepairOrderEmailOutbox') && str_contains($orderEmailMigration, 'Schema::getIndexes') && str_contains($orderEmailMigration, 'Schema::getForeignKeys'));
$check('beta7.16 e-mail outbox ima dedupe intervale i pojedinačne primaoce', str_contains($orderEmailOutbox, 'dedupe_key') && str_contains($orderEmailOutbox, 'scheduledFor') && str_contains($orderEmailOutbox, 'recipient_email'));
$check('beta7.16 e-mail prima autor odgovorno lice i dodatne adrese', str_contains($orderEmailOutbox, 'order_email_send_creator') && str_contains($orderEmailOutbox, 'order_email_send_supplier') && str_contains($orderEmailOutbox, 'order_email_custom_recipients'));
$check('beta7.16 workflow šalje status tracking plaćanje i dokumente', str_contains($workflow, 'order_status_changed') && str_contains($workflow, 'order_tracking_changed') && str_contains((string) file_get_contents($root.'/app/Services/OrderPaymentService.php'), 'order_payment_changed') && str_contains($documentService, 'documentIssued'));
$check('beta7.16 dispatcher ima retry stuck recovery i zaštitu storniranog priloga', str_contains($orderEmailDispatcher, "where('status', 'sending')") && str_contains($orderEmailDispatcher, 'attempt_count + 1') && str_contains($orderEmailDispatcher, 'removeStaleDocumentRows'));
$check('beta7.16 scheduler šalje outbox svake minute', str_contains((string) file_get_contents($root.'/routes/console.php'), "Schedule::command('app:order-email-dispatch')") && str_contains((string) file_get_contents($root.'/routes/console.php'), '->everyMinute()'));
$check('beta7.16 admin podešava intervale događaje i dokumente', str_contains($orderEmailSettingsController, 'order_email_creation_interval_minutes') && str_contains($orderEmailSettingsView, 'Dokumenti koji se šalju') && str_contains($orderEmailSettingsView, 'Dodatne adrese'));
$check('beta7.16 e-mail šablon ima događaje i bezbedan action link', str_contains($orderEmailView, '@foreach($events as $event)') && str_contains($orderEmailView, '$event->action_url'));
$check('beta7.16 NBS payload + batch511 v2 terminal cache guard', str_contains($ipsPayloadService, "'K:PR'") && str_contains($ipsPayloadService, "'V:01'") && str_contains($ipsPayloadService, "'I:RSD'.number_format") && str_contains($ipsPayloadService, "'SF:'") && str_contains($ipsPayloadService, 'BATCH511_V2_PAYMENT_IPS_CACHE_GUARD') && str_contains($ipsPayloadService, 'shouldClearCachedPayload($order, $amountRsd)') && str_contains($ipsPayloadService, 'clearCachedPayload') && str_contains($ipsPayloadService, "['paid', 'overpaid', 'cancelled', 'refunded']") && str_contains($ipsPayloadService, 'outstandingAmount($order, $amountRsd) <= 0.004'));
$check('beta7.16 NBS servis koristi zvanični HTTPS endpoint i čuva privatni PNG snapshot', str_contains($nbsService, 'https://nbs.rs/QRcode/api/qr/v1/generate/320') && str_contains($nbsService, "Storage::disk('local')->put") && str_contains($nbsService, 'ips_qr_generated_at'));
$check('beta7.16 stornirani istorijski dokument ostaje pregledljiv bez ponovnog NBS poziva', str_contains($nbsService, "(string) \$document->status !== 'issued'"));
$check('beta7.16 finansijski dokument trazi NBS QR samo za pozitivan neplaceni saldo', str_contains($nbsService, 'Dokument nije izdat') && str_contains($nbsService, "['paid', 'overpaid', 'cancelled', 'refunded']") && str_contains($nbsService, 'outstandingAmount') && str_contains($documentService, '$this->nbsIpsQr->generate($order)') && str_contains($nbsService, '$this->generate($order)'));
$check('beta7.16 PDF crta PNG bez GD i prikazuje NBS IPS QR oznaku', str_contains($simplePdfWriter, 'function imagePng') && str_contains($simplePdfWriter, 'FlateDecode') && str_contains($businessPdf, 'NBS IPS QR'));
$check('beta7.16 IPS QR smoke potvrđuje sliku oznaku i tačan RSD iznos', str_contains($ipsSmoke, 'PASS NBS IPS QR PDF') && str_contains($ipsSmoke, '/Subtype /Image') && str_contains($ipsSmoke, '31.583,29 RSD'));
$check('beta7.16 garancija podržava kombinaciju meseci i dana', str_contains($warrantyService, 'addMonthsNoOverflow($durationMonths)->addDays($durationDays)') && str_contains($warrantyPdf, 'duration_days') && str_contains($warrantyViews, 'Dodatni dani'));
$check('beta7.16 admin može kreirati porudžbinu', str_contains((string) file_get_contents($root.'/app/Models/User.php'), "hasRole('admin', 'superadmin')") && !str_contains((string) file_get_contents($root.'/app/Models/User.php'), "return $permission !== 'orders.create'"));
$check('beta7.16 doctor proverava outbox SMTP scheduler NBS i garancijske dane', str_contains($orderEmailDoctor, 'app:order-emails-doctor') && str_contains($orderEmailDoctor, 'NBS IPS QR endpoint') && str_contains($orderEmailDoctor, 'duration_days'));
$check('beta7.16 feature test pokriva admin porudžbinu događaje NBS QR i dane garancije', str_contains($orderEmailFeatureTest, 'test_admin_can_create_order') && str_contains($orderEmailFeatureTest, 'test_bank_transfer_invoice_snapshots_official_nbs_payload_png_and_exact_rsd_total') && str_contains($orderEmailFeatureTest, 'test_warranty_duration_combines_months_and_days'));

$receivablesMigration = (string) file_get_contents($root.'/database/migrations/2026_07_31_000026_create_receivables_collection_beta7_17.php');
$receivableAllocationMigration = (string) file_get_contents($root.'/database/migrations/2026_09_09_000147_create_receivable_payment_allocations_batch147.php');
$receivableAllocationModel = (string) file_get_contents($root.'/app/Models/ReceivablePaymentAllocation.php');
$receivablesService = (string) file_get_contents($root.'/app/Services/ReceivablesService.php');
$receivablesController = (string) file_get_contents($root.'/app/Http/Controllers/Admin/ReceivablesController.php');
$receivablesIndex = (string) file_get_contents($root.'/resources/views/admin/receivables/index.blade.php');
$receivablesShow = (string) file_get_contents($root.'/resources/views/admin/receivables/show.blade.php');
$receivablesDoctor = (string) file_get_contents($root.'/app/Console/Commands/ReceivablesDoctorCommand.php');
$receivablesTest = (string) file_get_contents($root.'/tests/Feature/ReceivablesCollectionTest.php');
$layoutShell = (string) file_get_contents($root.'/resources/views/layouts/app.blade.php');
$check('beta7.17 migracija uvodi predmete rate i komunikaciju naplate', str_contains($receivablesMigration, 'receivable_cases') && str_contains($receivablesMigration, 'receivable_installments') && str_contains($receivablesMigration, 'receivable_contacts'));
$check('beta7.17 migracija je recovery-safe za delimičan DDL', str_contains($receivablesMigration, 'createOrRepairCases') && str_contains($receivablesMigration, 'ensureColumns') && str_contains($receivablesMigration, 'down(): void'));
$check('beta7.17 servis automatski otvara zatvara i usklađuje predmete', str_contains($receivablesService, 'ensureForOrder') && str_contains($receivablesService, 'syncForOrder') && str_contains($receivablesService, "'status' => 'closed'"));
$check('beta7.17 rate se raspoređuju prema stvarno plaćenom iznosu', str_contains($receivablesService, 'plan_paid_baseline_rsd') && str_contains($receivablesService, 'paid_amount_rsd') && str_contains($receivablesService, '$allocatable') && str_contains($receivableAllocationMigration, 'receivable_payment_allocations') && str_contains($receivableAllocationModel, 'ReceivablePaymentAllocation') && str_contains($receivablesService, 'plan_payment_high_water_id') && str_contains($receivablesService, 'syncForOrderWithPaymentLedger') && str_contains((string) file_get_contents($root.'/app/Http/Controllers/Api/V1/Admin/ReceivablesController.php'), 'admin.receivables.payment.record') && str_contains((string) file_get_contents($root.'/app/Http/Controllers/Api/V1/Admin/ReceivablesController.php'), "'idempotency_key' => ['required', 'string', 'max:200']"));
$check('beta7.17 automatske opomene koriste faze dedupe i outbox', str_contains($receivablesService, 'eligibleReminderStage') && str_contains($receivablesService, 'event_key') && str_contains($orderEmailOutbox, 'receivableReminder'));
$check('beta7.17 admin ima aging pregled plan evidenciju komunikacije i direktnu uplatu', str_contains($receivablesIndex, 'aging-grid') && str_contains($receivablesIndex, '#evidentiraj-uplatu') && str_contains($receivablesIndex, '$canManagePayments') && !str_contains($receivablesIndex, "@can('payments.manage')@if") && str_contains($receivablesShow, 'Plan otplate') && str_contains($receivablesShow, 'Komunikacija i opomene') && str_contains($receivablesShow, 'Evidentiraj uplatu') && str_contains($receivablesShow, 'data-ux-allow-multiple-submit') && str_contains($receivablesShow, '$canManagePayments') && !str_contains($receivablesShow, "@can('payments.manage')@if") && str_contains($receivablesController, 'admin.receivables.payment.record') && str_contains((string) file_get_contents($root.'/routes/web.php'), "name('receivables.payments.store')") && str_contains($layoutShell, '<span>Potraživanja</span>') && str_contains((string) file_get_contents($root.'/public/assets/js/ux-runtime.js'), 'data-ux-allow-multiple-submit'));
$check('beta7.17 podmeni se zatvara klikom van escape i izborom stavke', str_contains($layoutShell, 'closeDropdowns') && str_contains($layoutShell, "event.key !== 'Escape'") && str_contains($layoutShell, "target.closest?.('.nav-dropdown')"));
$check('beta7.17 checkbox i radio imaju normalnu globalnu veličinu', str_contains($css, 'input[type="checkbox"],input[type="radio"]') && str_contains($css, 'max-width:17px!important'));
$check('beta7.17 doctor proverava šemu dozvolu i scheduler', str_contains($receivablesDoctor, 'app:receivables-doctor') && str_contains($receivablesDoctor, 'receivables.manage') && str_contains($receivablesDoctor, 'app:automation-run'));
$receivablesMigration = (string) file_get_contents($root.'/database/migrations/2026_07_31_000026_create_receivables_collection_beta7_17.php');
$check('beta7.17.1 permission seed je schema-aware', str_contains($receivablesMigration, 'schemaAwareUpdateOrInsert') && str_contains($receivablesMigration, "Schema::hasColumn(\$table, 'updated_at')"));
$check('beta7.17.1 seeder ne zahteva permissions.updated_at', str_contains($seeder, "Schema::hasColumn('permissions', 'updated_at')") && !str_contains($seeder, "\$permission + ['created_at' => now()]"));
$check('beta7.17.2 hover podmeni ima grace period i click pin', str_contains($layoutShell, 'hoverCloseTimers') && str_contains($layoutShell, 'clickPinnedDropdowns') && str_contains($layoutShell, 'scheduleHoverClose') && str_contains($layoutShell, '280'));
$check('beta7.17.2 CSS premošćava razmak do podmenija', str_contains($css, '.nav-dropdown-menu:before') && str_contains($css, 'top:-12px') && str_contains($css, 'height:12px'));


$correlationMigration = (string) file_get_contents($root.'/database/migrations/2026_07_31_000027_create_correlated_specifications_beta7_18.php');
$correlationService = (string) file_get_contents($root.'/app/Services/SpecificationDependencyService.php');
$catalogFilterService = (string) file_get_contents($root.'/app/Services/CatalogSpecificationFilterService.php');
$productRequest = (string) file_get_contents($root.'/app/Http/Requests/ProductRequest.php');
$productForm = (string) file_get_contents($root.'/resources/views/admin/products/form.blade.php');
$filterPartial = (string) file_get_contents($root.'/resources/views/partials/correlated-specification-filters.blade.php');
$filterScript = (string) file_get_contents($root.'/resources/views/partials/correlated-specification-filter-script.blade.php');
$correlationDoctor = (string) file_get_contents($root.'/app/Console/Commands/CatalogCorrelationsDoctorCommand.php');
$check('beta7.18 migracija uvodi strukturirane opcije i korelacije', str_contains($correlationMigration, 'specification_options') && str_contains($correlationMigration, 'specification_option_dependencies') && str_contains($correlationMigration, 'value_detail'));
$check('beta7.18 migracija je recovery-safe za MariaDB', str_contains($correlationMigration, 'Schema::getIndexes') && str_contains($correlationMigration, 'Schema::getForeignKeys') && str_contains($correlationMigration, 'repairProductTypeParentAssignments'));
$check('beta7.18 procesor ima porodicu i tačan model', str_contains($correlationMigration, 'Intel Core Ultra 9') && str_contains($correlationMigration, 'AMD Ryzen AI Max PRO') && str_contains($correlationMigration, 'Qualcomm Snapdragon X2 Elite Extreme') && str_contains($correlationMigration, 'Apple M5 Pro') && str_contains($correlationMigration, 'Tačan model procesora'));
$check('beta7.18 brend filtrira samo sopstvene linije', str_contains($productForm, 'data-brand-select') && str_contains($productForm, 'data-line-select') && str_contains($productRequest, 'Izabrana linija ne pripada izabranom brendu.'));
$check('beta7.18 generičke zavisnosti imaju server validaciju i zaštitu ciklusa', str_contains($correlationService, 'assertNoCycle') && str_contains($correlationService, 'clearParentDependenciesForField') && str_contains($productRequest, 'Izabrana opcija nije povezana sa roditeljskim izborom.'));
$check('beta7.18 forma skriva nepovezane opcije i čuva detalj', str_contains($productForm, 'data-parent-option-ids') && str_contains($productForm, 'spec_details[') && str_contains($productForm, 'is-dependency-empty'));
$check('beta7.18 kataloški filteri podržavaju select range boolean text i detalj', str_contains($catalogFilterService, "filter_type === 'select'") && str_contains($catalogFilterService, "filter_type === 'range'") && str_contains($catalogFilterService, "filter_type === 'boolean'") && str_contains($catalogFilterService, 'value_detail'));
$check('beta7.18 oba kataloga koriste korelisane filtere', str_contains($filterPartial, 'data-filter-spec-wrapper') && str_contains($filterScript, 'data-correlated-spec-filter-form') && str_contains((string) file_get_contents($root.'/resources/views/catalog/index.blade.php'), 'correlated-specification-filters') && str_contains((string) file_get_contents($root.'/resources/views/admin/products/index.blade.php'), 'correlated-specification-filters'));
$check('beta7.18 doctor proverava procesor linije veze i tipove', str_contains($correlationDoctor, 'app:catalog-correlations-doctor') && str_contains($correlationDoctor, 'Svi artikli imaju usklađen brend i liniju') && str_contains($correlationDoctor, 'Nema kružnih veza'));

$productController = (string) file_get_contents($root.'/app/Http/Controllers/Admin/ProductController.php');
$productFormMethod = (string) strstr($productController, 'private function formView');
$catalogControllerSource = (string) file_get_contents($root.'/app/Http/Controllers/CatalogController.php');
$dictionaryControllerSource = (string) file_get_contents($root.'/app/Http/Controllers/Admin/CatalogDictionaryController.php');
$check('beta7.18.1 forma artikla ne koristi nedostupni index filter servis', !str_contains($productFormMethod, '$specificationFilters') && substr_count($productFormMethod, "'types' =>") === 1);
$check('beta7.18.1 jedinstveni katalog dobija podatke za korelisane filtere', str_contains($catalogControllerSource, "'filterFields' => \$referenceOptions['filterFields']") && str_contains($productController, "redirect()->route('catalog.index'"));
$check('beta7.18.1 šifarnici dobijaju podatke za roditelje i mape zavisnosti', str_contains($dictionaryControllerSource, "'selectableFields' =>") && str_contains($dictionaryControllerSource, "'dependencyMaps' =>"));


$smartMigration = (string) file_get_contents($root.'/database/migrations/2026_07_31_000028_create_smart_product_management_beta7_19.php');
$templateService = (string) file_get_contents($root.'/app/Services/ProductTemplateService.php');
$completenessService = (string) file_get_contents($root.'/app/Services/ProductCompletenessService.php');
$bulkService = (string) file_get_contents($root.'/app/Services/ProductBulkService.php');
$bulkController = (string) file_get_contents($root.'/app/Http/Controllers/Admin/ProductBulkController.php');
$cloneView = (string) file_get_contents($root.'/resources/views/admin/products/clone.blade.php');
$bulkView = (string) file_get_contents($root.'/resources/views/admin/products/bulk.blade.php');
$smartDoctor = (string) file_get_contents($root.'/app/Console/Commands/SmartProductsDoctorCommand.php');
$smartFeature = (string) file_get_contents($root.'/tests/Feature/SmartProductManagementTest.php');
$check('beta7.19 migracija uvodi šablone kompletnost i poreklo klona', str_contains($smartMigration, 'name_template') && str_contains($smartMigration, 'completeness_percent') && str_contains($smartMigration, 'source_product_id'));
$check('beta7.19 migracija je recovery-safe i obračunava postojeći katalog', str_contains($smartMigration, 'Schema::hasColumn') && str_contains($smartMigration, 'backfillCompleteness') && str_contains($smartMigration, 'chunkById'));
$check('beta7.19 template servis podržava alias placeholdere', str_contains($templateService, 'cpu_family') && str_contains($templateService, 'cpu_detail') && str_contains($templateService, 'storage'));
$check('beta7.19 completeness servis vraća nepotpun aktivan artikal u nacrt', str_contains($completenessService, "['status'] = 'draft'") && str_contains($completenessService, 'minimum_completeness_percent'));
$check('beta7.19 kloniranje čuva novi SKU i nulti lager', str_contains((string) file_get_contents($root.'/app/Services/ProductAdminService.php'), 'source_product_id') && str_contains((string) file_get_contents($root.'/app/Services/ProductAdminService.php'), "'stock_quantity' => 0"));
$check('beta7.19 clone checkboxi eksplicitno šalju nulu', str_contains($cloneView, 'type="hidden" name="{{ $name }}" value="0"') && str_contains($cloneView, 'copy_images'));
$check('beta7.19 bulk zahteva pregled i blokira praznu operaciju', str_contains($bulkService, 'assertHasChanges') && str_contains($bulkView, 'Pregled promena') && str_contains($bulkController, 'preview'));
$check('beta7.19 bulk promena brenda čisti neusklađenu liniju', str_contains($bulkService, 'product_line_id') && str_contains($bulkService, 'brand_id'));
$check('beta7.19 preview naziva uklanja method spoof', str_contains($productForm, "requestData.delete('_method')"));
$check('beta7.19 doctor proverava šemu rute i kompletnost', str_contains($smartDoctor, 'app:smart-products-doctor') && str_contains($smartDoctor, 'specification_options') && str_contains($smartDoctor, 'completeness_percent'));
$check('beta7.19 feature test pokriva naziv klon i bulk', str_contains($smartFeature, 'test_template_generates_name_defaults_and_completeness') && str_contains($smartFeature, 'test_clone_has_new_sku_zero_stock_and_does_not_copy_unchecked_sections') && str_contains($smartFeature, 'test_bulk_brand_change_clears_line_from_previous_brand'));


$productVariantsHistoricalMigration = (string) file_get_contents($root.'/database/migrations/2026_07_31_000029_create_product_variants_beta7_20.php');
$productVariantsDecommissionMigrations = glob($root.'/database/migrations/*decommission_product_variants.php') ?: [];
$productVariantsDecommissionMigration = count($productVariantsDecommissionMigrations) === 1 ? (string) file_get_contents($productVariantsDecommissionMigrations[0]) : '';
$productVariantFeature = (string) file_get_contents($root.'/tests/Feature/ProductVariantsWorkflowTest.php');
$productVariantUiContract = (string) file_get_contents($root.'/tests/Unit/ProductVariantsUiContractTest.php');
$check('beta7.20 istorijska migracija ostaje sačuvana kao migration history', str_contains($productVariantsHistoricalMigration, 'product_variants') && str_contains($productVariantsHistoricalMigration, 'product_variant_spec_values') && str_contains($productVariantsHistoricalMigration, 'intentionally preserved'));
$check('Product Variants forward decommission migracija postoji jednom', count($productVariantsDecommissionMigrations) === 1 && str_contains($productVariantsDecommissionMigration, 'assertPurgeSafety') && str_contains($productVariantsDecommissionMigration, 'dropVariantSchema'));
$check('Product Variants decommission migracija ima recovery-safe rollback rekonstrukciju', str_contains($productVariantsDecommissionMigration, 'restoreVariantSchema') && str_contains($productVariantsDecommissionMigration, 'restoreExternalColumns') && str_contains($productVariantsDecommissionMigration, 'restoreForeignKeys'));
$check('Product Variants runtime klase su fizički uklonjene', !is_file($root.'/app/Models/ProductVariant.php') && !is_file($root.'/app/Models/ProductVariantSpecValue.php') && !is_file($root.'/app/Services/ProductVariantService.php') && !is_file($root.'/app/Http/Requests/ProductVariantRequest.php') && !is_file($root.'/app/Http/Controllers/Admin/ProductVariantController.php') && !is_file($root.'/app/Console/Commands/ProductVariantsDoctorCommand.php'));
$check('Product Variants admin UI fajlovi su fizički uklonjeni', !is_file($root.'/resources/views/admin/products/variants.blade.php') && !is_file($root.'/resources/views/admin/products/partials/variant-fields.blade.php'));
$check('Porudžbine su product-only bez variant identiteta i snapshotova', !str_contains((string) file_get_contents($root.'/app/Services/OrderService.php'), 'variant') && !str_contains((string) file_get_contents($root.'/app/Services/OrderWorkflowService.php'), 'variant') && !str_contains((string) file_get_contents($root.'/app/Http/Requests/StoreOrderRequest.php'), 'variant') && !str_contains((string) file_get_contents($root.'/resources/views/orders/create.blade.php'), 'variant'));
$check('Postprodaja garancija i stock movement su product-only', !str_contains((string) file_get_contents($root.'/app/Services/AfterSalesActionService.php'), 'variant') && !str_contains((string) file_get_contents($root.'/app/Services/WarrantyService.php'), 'variant') && !str_contains((string) file_get_contents($root.'/app/Models/StockMovement.php'), 'variant'));
$check('Inventory je product-only bez variants_enabled grane', !str_contains((string) file_get_contents($root.'/app/Services/InventoryService.php'), 'variant') && !str_contains((string) file_get_contents($root.'/app/Services/AdvancedInventoryService.php'), 'variant'));
$check('Kataloški query filter i detalj su product-only', !str_contains((string) file_get_contents($root.'/app/Services/CatalogSpecificationFilterService.php'), 'variant') && !str_contains((string) file_get_contents($root.'/app/Services/CatalogQueryService.php'), 'variant') && !str_contains((string) file_get_contents($root.'/app/Http/Controllers/CatalogController.php'), 'variant') && !str_contains((string) file_get_contents($root.'/resources/views/catalog/show.blade.php'), 'variant'));
$check('Product slike i model su product-only', !str_contains((string) file_get_contents($root.'/app/Services/ProductImageService.php'), 'variant') && !str_contains((string) file_get_contents($root.'/app/Models/Product.php'), 'variant') && !str_contains((string) file_get_contents($root.'/app/Models/ProductImage.php'), 'variant'));
$check('Clone vise ne nudi niti obrađuje kopiranje varijanti', !str_contains((string) file_get_contents($root.'/resources/views/admin/products/clone.blade.php'), 'copy_variants') && !str_contains((string) file_get_contents($root.'/app/Http/Controllers/Admin/ProductController.php'), "'copy_variants'") && !str_contains((string) file_get_contents($root.'/app/Services/ProductAdminService.php'), 'copy_variants'));
$check('Product Variants Feature test sada proverava retired route i uklonjenu šemu', str_contains($productVariantFeature, 'test_retired_variant_route_is_not_available_and_schema_is_removed') && str_contains($productVariantFeature, "assertFalse(Schema::hasTable('product_variants'))"));
$check('Product Variants UI contract sada zahteva potpuno uklonjen variant UI', str_contains($productVariantUiContract, 'assertFileDoesNotExist') && str_contains($productVariantUiContract, "assertStringNotContainsString('product_variant_id'"));
$check('Product Variants decommission smoke postoji kao završni regresioni guard', str_contains((string) file_get_contents($root.'/bin/product-variant-smoke.php'), 'Product Variants Decommission smoke') && str_contains((string) file_get_contents($root.'/bin/product-variant-smoke.php'), 'Aktivni CMS runtime nema Product Variants signal'));

$check('beta7.17 feature test pokriva dedupe rate zatvaranje i UI regresiju', str_contains($receivablesTest, 'test_automation_creates_case_and_deduplicated_due_reminder') && str_contains($receivablesTest, 'test_verified_payments_allocate_oldest_installments_and_close_case') && str_contains($receivablesTest, 'test_random_dated_payments_split_across_installments_with_exact_completion_date') && str_contains($receivablesTest, 'test_dropdown_and_checkbox_regression_markers_are_present') && str_contains($receivablesTest, 'test_direct_sale_custom_web_plan_records_first_installment_immediately_and_is_idempotent') && str_contains($receivablesTest, 'test_direct_sale_legacy_deferred_input_keeps_equal_plan_without_initial_payment') && str_contains((string) file_get_contents($root.'/resources/views/catalog/show.blade.php'), 'data-direct-sale-deferred-panel') && str_contains((string) file_get_contents($root.'/resources/views/catalog/show.blade.php'), 'data-ux-allow-multiple-submit'));


$managementMigration = (string) file_get_contents($root.'/database/migrations/2026_07_31_000030_create_management_reports_beta7_21.php');
$managementService = (string) file_get_contents($root.'/app/Services/ManagementReportService.php');
$managementPdf = (string) file_get_contents($root.'/app/Services/Pdf/ManagementReportPdfService.php');
$reportScheduleService = (string) file_get_contents($root.'/app/Services/ReportScheduleService.php');
$managementController = (string) file_get_contents($root.'/app/Http/Controllers/Admin/ManagementReportController.php');
$managementView = (string) file_get_contents($root.'/resources/views/admin/reports/management.blade.php');
$managementFeature = (string) file_get_contents($root.'/tests/Feature/ManagementReportsProfitabilityTest.php');
$check('beta7.21 migracija uvodi nabavne snapshotove i rasporede', str_contains($managementMigration, 'purchase_total_rsd_snapshot') && str_contains($managementMigration, 'report_schedules') && str_contains($managementMigration, 'report_deliveries'));
$check('beta7.21 migracija je recovery-safe i permission schema-aware', str_contains($managementMigration, 'addColumn') && str_contains($managementMigration, 'onlyExistingColumns') && str_contains($managementMigration, "'reports.manage'"));
$check('beta7.21 marža koristi snapshot i prikazuje pokrivenost troška', str_contains($managementService, 'known_revenue_rsd') && str_contains($managementService, 'cost_coverage_percent') && str_contains($managementService, 'purchase_total_rsd_snapshot'));
$check('beta7.21 filteri važe za KPI trend i segmente', substr_count($managementService, 'applyItemFilters') >= 4);
$check('beta7.21 dashboard pokriva lager potraživanja postprodaju i tim', str_contains($managementService, 'inventory()') && str_contains($managementService, 'receivables(') && str_contains($managementService, 'afterSales(') && str_contains($managementService, 'teamPerformance('));
$check('beta7.21 PDF upravljačkog izveštaja postoji', str_contains($managementPdf, 'Upravljački izveštaj profitabilnosti') && is_file($root.'/bin/management-report-smoke.php'));
$check('beta7.21 raspored ima retry dedupe i zasebne primaoce', str_contains($reportScheduleService, 'dedupe_key') && str_contains($reportScheduleService, "status' => 'retry'") && str_contains($reportScheduleService, 'recipient_email'));
$check('beta7.21 ekran je bezbedan pre migracije', str_contains($managementController, "Schema::hasTable('report_schedules')") && str_contains($managementController, "Schema::hasColumn('order_items'"));
$check('beta7.21 UI ima CSV PDF rasporede i cost coverage', str_contains($managementView, 'reports.management.csv') && str_contains($managementView, 'report-schedules.store') && str_contains($managementView, 'Pokrivenost nabavne cene'));
$phase7ManagementThemeCss = (string) file_get_contents(
    dirname(__DIR__).'/public/assets/css/ald1n-ui-v2.css',
);
$check(
    'beta7.22.1 management analytics koristi aktivnu temu bez belog fallback-a',
    preg_match('/\.analytics-card\s*\{[^}]*background\s*:\s*var\(--panel\)/s', $phase7ManagementThemeCss) === 1
        && preg_match('/\.analytics-bar\s*\{[^}]*background\s*:\s*var\(--panel-2\)/s', $phase7ManagementThemeCss) === 1
        && !str_contains($phase7ManagementThemeCss, 'background:var(--panel-bg)')
);
$check('beta7.22.1 CSS kompatibilni aliasi postoje', str_contains($css, '--accent:var(--primary)') && str_contains($css, '--border:var(--line)') && str_contains($css, '--panel-bg:var(--panel)') && str_contains($css, '--surface-soft:var(--panel-2)'));
$check('beta7.21 feature test pokriva ekran export i raspored', str_contains($managementFeature, 'test_superadministrator_can_open_management_dashboard_and_exports') && str_contains($managementFeature, 'test_schedule_is_created_and_manual_run_is_deduplicated_per_request'));

$env = (string) file_get_contents($root.'/.env.example');
$portalService = (string) file_get_contents($root.'/app/Services/CustomerPortalService.php');
$portalView = (string) file_get_contents($root.'/resources/views/dashboard/partials/customer-center.blade.php');
$dashboardControllerSource = (string) file_get_contents($root.'/app/Http/Controllers/DashboardController.php');
$reportService = (string) file_get_contents($root.'/app/Services/ManagementReportService.php');
$check('beta7.22 portal servis i fallback podaci postoje', str_contains($dashboardControllerSource, 'private function portalData') && str_contains($dashboardControllerSource, "reportDashboardWarning('customer_portal'"));
$check('beta7.22 portal objedinjuje porudžbine dokumente uplate garancije i servis', str_contains($portalService, 'private function recentOrders') && str_contains($portalService, 'private function documents') && str_contains($portalService, 'private function payments') && str_contains($portalService, 'private function warranties') && str_contains($portalService, 'private function serviceAppointments') && str_contains($portalView, 'Jedinstveni korisnički centar'));
$check('beta7.22 report grouping je kompatibilan sa ONLY_FULL_GROUP_BY', str_contains($reportService, "fromSub(\$segmentRows, 'segment_rows')") && str_contains($reportService, "->groupBy('segment_label')") && !str_contains($reportService, '->groupByRaw($label)'));
$check('beta7.22 dashboard ima trend prioritete brze akcije i operativne module', str_contains($dashboardView, 'Trend prodaje') && str_contains($dashboardView, 'Prioritetne aktivnosti') && str_contains($dashboardView, 'Brze akcije') && str_contains($dashboardView, 'Svi dostupni moduli na jednom mestu'));
$customerPortalDoctor = (string) file_get_contents($root.'/app/Console/Commands/CustomerPortalDoctorCommand.php');
$check('beta7.22.1 portal doctor prosleđuje ViewErrorBag', str_contains($customerPortalDoctor, 'ViewErrorBag') && str_contains($customerPortalDoctor, "->with('errors', new ViewErrorBag())"));
$check('beta7.22.1 layout bezbedno proverava errors bag', str_contains($layout, 'isset($errors) && $errors->any()'));

$releaseCommand = (string) file_get_contents($root.'/app/Console/Commands/ReleaseCheckCommand.php');
$releaseConfig = (string) file_get_contents($root.'/config/release.php');
$releaseSmoke = (string) file_get_contents($root.'/bin/release-check-smoke.php');
$releaseDocs = (string) file_get_contents($root.'/docs/RELEASE-CHECK.md');
$check('beta7.23 release-check komanda ima profile i kontrolisane režime', str_contains($releaseCommand, 'app:release-check') && str_contains($releaseCommand, '--profile=standard') && str_contains($releaseCommand, '--repair') && str_contains($releaseCommand, '--render') && str_contains($releaseCommand, '--snapshot') && str_contains($releaseCommand, '--strict') && str_contains($releaseCommand, '--list'));
$check('beta7.23 release registry ima quick standard i full profile', str_contains($releaseConfig, "'quick' =>") && str_contains($releaseConfig, "'standard' =>") && str_contains($releaseConfig, "'full' =>"));
$check('beta7.23 release plan ne dispatchuje poslovne akcije', !str_contains($releaseConfig, "'--dispatch'") && !str_contains($releaseConfig, "'--create-test'") && !str_contains($releaseConfig, "'--backfill'") && !str_contains($releaseConfig, "'--run'"));
$check('beta7.23 release metadata i atomski JSON report postoje', str_contains($releaseCommand, "base_path('RELEASE-TAG')") && str_contains($releaseCommand, "base_path('VERSION')") && str_contains($releaseCommand, 'rename($temporary, $target)') && str_contains($releaseCommand, 'rename($latestTemporary, $latest)'));
$check('beta7.23 release rezultat ima READY i NOT READY ugovor', str_contains($releaseCommand, 'RELEASE CHECK: READY FOR PRODUCTION') && str_contains($releaseCommand, 'RELEASE CHECK: NOT READY'));
$check('beta7.23 smoke i dokumentacija postoje', str_contains($releaseSmoke, 'Release check smoke') && str_contains($releaseDocs, 'storage/app/release-check/latest.json'));

$catalogDetailView = (string) file_get_contents($root.'/resources/views/catalog/show.blade.php');
$catalogDetailFeature = (string) file_get_contents($root.'/tests/Feature/CatalogDetailPageTest.php');
$catalogDetailSmoke = (string) file_get_contents($root.'/bin/catalog-detail-smoke.php');
$systemHealthService = (string) file_get_contents($root.'/app/Services/SystemHealthService.php');
$check('beta7.23.1 catalog detail je product-only i nema retired variant Blade markere', !str_contains($catalogDetailView, 'variant') && !str_contains($catalogDetailView, "@can('orders.create')@if"));
$check('beta7.23.1 catalog detail Blade direktive su izbalansirane', substr_count($catalogDetailView, '@foreach') === substr_count($catalogDetailView, '@endforeach') && substr_count($catalogDetailView, '@if') === substr_count($catalogDetailView, '@endif') && substr_count($catalogDetailView, '@can') === substr_count($catalogDetailView, '@endcan'));
$check('beta7.23.1 product-only detail Feature i smoke regresija postoje', str_contains($catalogDetailFeature, 'test_product_detail_is_product_only_after_variant_decommission') && str_contains($catalogDetailSmoke, 'Catalog detail smoke'));
$check('beta7.23.1 health daje čitljive runtime remediation komande', str_contains($systemHealthService, '(int) floor(abs($heartbeat->recorded_at->diffInMinutes(now())))') && str_contains($systemHealthService, 'app:scheduler-heartbeat') && str_contains($systemHealthService, 'app:automation-run') && str_contains($systemHealthService, 'app:backup-create --type=manual'));

$detailPagesDoctorSmoke = (string) file_get_contents($root.'/bin/detail-pages-doctor-smoke.php');
$detailPagesDoctorContract = (string) file_get_contents($root.'/tests/Unit/DetailPagesDoctorContractTest.php');
$check('beta7.23.2 detail doctor rešava controller zavisnosti kroz container', str_contains($detailPagesDoctor, "app()->call([app(AdminProductController::class), 'edit']") && str_contains($detailPagesDoctor, "app()->call([app(ProductImageController::class), 'index']"));
$check('beta7.23.2 detail doctor nema direktan edit poziv sa jednim argumentom', !str_contains($detailPagesDoctor, 'app(AdminProductController::class)->edit('));
$check('beta7.23.2 detail doctor smoke i contract regresija postoje', str_contains($detailPagesDoctorSmoke, 'Detail pages doctor smoke') && str_contains($detailPagesDoctorContract, 'test_product_edit_dependencies_are_resolved_by_the_container'));


$portal2Migration = (string) file_get_contents($root.'/database/migrations/2026_08_01_000032_create_customer_portal_2_beta7_24.php');
$portal2Activation = (string) file_get_contents($root.'/app/Services/CustomerActivationService.php');
$portal2Sessions = (string) file_get_contents($root.'/app/Services/PortalSessionService.php');
$portal2Middleware = (string) file_get_contents($root.'/app/Http/Middleware/EnsureTrackedPortalSession.php');
$portal2Conversations = (string) file_get_contents($root.'/app/Services/PortalConversationService.php');
$portal2Routes = (string) file_get_contents($root.'/routes/web.php');
$portal2CustomerView = (string) file_get_contents($root.'/resources/views/portal/messages/show.blade.php');
$portal2AdminView = (string) file_get_contents($root.'/resources/views/admin/customer-portal/conversation.blade.php');
$portal2Smoke = (string) file_get_contents($root.'/bin/customer-portal-2-smoke.php');
$portal2Maintenance = (string) file_get_contents($root.'/app/Console/Commands/CustomerPortalMaintenanceCommand.php');
$portal2Schedule = (string) file_get_contents($root.'/routes/console.php');
$check('beta7.24 migracija uvodi aktivacije sesije komunikaciju i order-link audit', str_contains($portal2Migration, 'user_activation_tokens') && str_contains($portal2Migration, 'user_login_sessions') && str_contains($portal2Migration, 'portal_conversations') && str_contains($portal2Migration, 'portal_messages') && str_contains($portal2Migration, 'portal_order_link_history'));
$check('beta7.24 aktivacioni token je hashiran jednokratan i vremenski ograničen', str_contains($portal2Activation, "hash('sha256', \$plainToken)") && str_contains($portal2Activation, "whereNull('accepted_at')") && str_contains($portal2Activation, "where('expires_at', '>', now())"));
$check('beta7.24 session registry koristi hash i podržava revoke', str_contains($portal2Sessions, "hash('sha256', \$id)") && str_contains($portal2Sessions, 'revokeOthers') && str_contains($portal2Middleware, 'validateAndTouch'));
$check('beta7.24 kupac vidi samo javne poruke a admin interne', str_contains($portal2CustomerView, '$conversation->publicMessages') && !str_contains($portal2CustomerView, '$conversation->messages as $message') && str_contains($portal2AdminView, "\$message->visibility === 'internal'"));
$check('beta7.24 portal rute aktivacija i admin centar postoje', str_contains($portal2Routes, "name('customer-activation.show')") && str_contains($portal2Routes, "name('portal.messages.index')") && str_contains($portal2Routes, "name('customer-portal.index')"));
$check('beta7.24 komunikacija razdvaja public i internal', str_contains($portal2Conversations, "\$visibility === 'internal'") && str_contains($portal2Conversations, "'visibility' => \$visibility"));
$check('beta7.24 smoke i PHPUnit regresije postoje', str_contains($portal2Smoke, 'Customer Portal 2.0 smoke') && is_file($root.'/tests/Unit/CustomerPortal2ContractTest.php') && is_file($root.'/tests/Feature/CustomerPortal2Test.php'));
$check('beta7.24 maintenance čisti tokene i stare session evidencije', str_contains($portal2Maintenance, 'purgeExpired') && str_contains($portal2Maintenance, 'expireStale') && str_contains($portal2Schedule, "app:customer-portal-maintenance"));
$check('beta7.24 reinvite ne deaktivira aktivnog kupca i aktivacija nije cache-ovana', str_contains($portal2Activation, "!in_array(\$user->status, ['active', 'blocked'], true)") && str_contains((string) file_get_contents($root.'/app/Http/Middleware/SecurityHeaders.php'), "'customer-activation.*'"));


$orderCostService = (string) file_get_contents($root.'/app/Services/OrderItemCostSnapshotService.php');
$orderCostCommand = (string) file_get_contents($root.'/app/Console/Commands/OrderCostSnapshotsCommand.php');
$orderCostSmoke = (string) file_get_contents($root.'/bin/order-cost-snapshot-smoke.php');
$orderCostContract = (string) file_get_contents($root.'/tests/Unit/OrderCostSnapshotRepairContractTest.php');
$managementReportsDoctor = (string) file_get_contents($root.'/app/Console/Commands/ManagementReportsDoctorCommand.php');
$check('beta7.24.1 management repair obrađuje missing snapshotove', str_contains($managementReportsDoctor, '$costSnapshots->repairMissing()') && str_contains($managementReportsDoctor, '$costSnapshots->missingCount()'));
$check('beta7.24.1 repair ne prepisuje kompletne snapshotove', str_contains($orderCostService, 'only touches incomplete snapshots') && str_contains($orderCostService, 'isMissing($item)'));
$check('beta7.24.1 repair je transakcioni i koristi row lock', str_contains($orderCostService, 'DB::transaction') && str_contains($orderCostService, 'lockForUpdate()'));
$check('beta7.24.1 kandidati imaju transparentan product-only izvor', !str_contains($orderCostService, 'repair_variant_current') && str_contains($orderCostService, 'repair_receipt_historical') && str_contains($orderCostService, 'repair_product_current'));
$check('beta7.24.1 ručna finansijska promena zahteva razlog i audit', str_contains($orderCostService, 'mb_strlen($reason) < 5') && str_contains($orderCostService, "Schema::hasTable('audit_logs')") && str_contains($orderCostService, 'order_item.cost_snapshot.manual'));
$check('beta7.24.1 audit/repair komanda i regresije postoje', str_contains($orderCostCommand, 'app:order-cost-snapshots') && str_contains($orderCostSmoke, 'Order cost snapshot smoke') && str_contains($orderCostContract, 'test_repair_is_conservative_and_uses_audited_sources'));

$rcConfig = require $root.'/config/release.php';
$rcProfile = (array) ($rcConfig['profiles']['rc'] ?? []);
$rcSecurity = (string) file_get_contents($root.'/app/Console/Commands/SecurityHardeningDoctorCommand.php');
$rcMigrations = (string) file_get_contents($root.'/app/Console/Commands/MigrationsDoctorCommand.php');
$rcAccess = (string) file_get_contents($root.'/app/Console/Commands/AccessControlDoctorCommand.php');
$rcIntegrity = (string) file_get_contents($root.'/app/Console/Commands/ReleaseIntegrityCommand.php');
$rcBackup = (string) file_get_contents($root.'/app/Console/Commands/BackupVerifyCommand.php');
$rcSmoke = (string) file_get_contents($root.'/bin/rc-hardening-smoke.php');
$check('rc1 profil sadrzi final hardening provere', ($rcProfile[0] ?? null) === 'deployment' && ($rcProfile[count($rcProfile) - 1] ?? null) === 'system_health' && in_array('release_integrity', $rcProfile, true) && in_array('security_hardening', $rcProfile, true) && in_array('migrations', $rcProfile, true) && in_array('access_control', $rcProfile, true) && in_array('backup_verify', $rcProfile, true));
$check('rc1 security doctor proverava production debug HTTPS session i public fajlove', str_contains($rcSecurity, 'APP_DEBUG') && str_contains($rcSecurity, 'APP_URL') && str_contains($rcSecurity, 'SESSION_SECURE_COOKIE') && str_contains($rcSecurity, 'publicSensitiveFiles'));
$check('rc1 migration doctor proverava pending SQL mode i foreign keys', str_contains($rcMigrations, 'Migration nije primenjena') && str_contains($rcMigrations, 'ONLY_FULL_GROUP_BY') && str_contains($rcMigrations, 'foreign_key_checks'));
$check('rc1 access doctor proverava route permission i superadmin', str_contains($rcAccess, 'permission:') && str_contains($rcAccess, 'SuperAdministrator') && str_contains($rcAccess, 'unprotected_admin'));
$check('rc1 release integrity proverava SHA-256 i path traversal', str_contains($rcIntegrity, "hash_file('sha256', \$path)") && str_contains($rcIntegrity, "str_contains('/'.\$relative.'/', '/../')"));
$check('rc1 backup verify je read-only i proverava SQL gzip i file hash', str_contains($rcBackup, 'scanSqlGzip') && str_contains($rcBackup, 'database_sha256') && str_contains($rcBackup, "hash_file('sha256', \$targetPath)") && !str_contains($rcBackup, '->create('));
$check('rc1 smoke i contract regresije postoje', str_contains($rcSmoke, 'RC hardening smoke') && is_file($root.'/tests/Unit/ReleaseCandidateHardeningContractTest.php'));
$check('rc1 nema novu migration datoteku', (glob($root.'/database/migrations/*rc1*.php') ?: []) === []);

$stableProfile = (array) ($rcConfig['profiles']['stable'] ?? []);
$stableSmoke = (string) file_get_contents($root.'/bin/stable-hardening-smoke.php');
$stableDashboardPartial = (string) file_get_contents($root.'/resources/views/dashboard/partials/customer-center.blade.php');
$stableRoutes = (string) file_get_contents($root.'/routes/web.php');
$stableLayout = (string) file_get_contents($root.'/resources/views/layouts/app.blade.php');
$check('stable profil je identican potvrdenom rc profilu', $stableProfile === $rcProfile && $stableProfile !== []);
$check('stable smoke i contract regresije postoje', str_contains($stableSmoke, 'Stable hardening smoke') && is_file($root.'/tests/Unit/StableReleaseContractTest.php'));
$check('stable početna je univerzalni dashboard sa integrisanim korisničkim centrom', str_contains($dashboardView, 'data-universal-dashboard-ready="1"') && str_contains($stableDashboardPartial, 'data-customer-center-ready="1"') && str_contains($dashboardControllerSource, 'CustomerPortalService $portalService'));
$check('stable nema zasebnu Moj portal stranicu ni stavku menija', !is_file($root.'/resources/views/portal/index.blade.php') && !is_file($root.'/app/Http/Controllers/CustomerPortalController.php') && str_contains($stableRoutes, "Route::permanentRedirect('/portal', '/')") && !str_contains($stableRoutes, "name('portal.index')") && !str_contains($stableLayout, "route('portal.index')"));
$check('stable nema novu migration datoteku', (glob($root.'/database/migrations/*stable*.php') ?: []) === []);

$productAnnouncementService = (string) file_get_contents($root.'/app/Services/ProductAnnouncementService.php');
$productAnnouncementSmoke = (string) file_get_contents($root.'/bin/product-announcement-smoke.php');
$mailSettingsView = (string) file_get_contents($root.'/resources/views/admin/settings/order-emails.blade.php');
$mailTemplate = (string) file_get_contents($root.'/resources/views/emails/order-events.blade.php');
$check('v2.1.2 obaveštenja o novom artiklu su opt-in i koriste outbox', str_contains($settingsService, "'product_email_new_items_enabled' => '0'") && str_contains($productAnnouncementService, 'OrderEmailOutbox') && str_contains($productAnnouncementService, "'event_type' => 'product_published'"));
$check('v2.1.2 novi artikal se šalje aktivnim registrovanim korisnicima bez duplikata', str_contains($productAnnouncementService, "where('status', 'active')") && str_contains($productAnnouncementService, 'firstOrCreate') && str_contains($productAnnouncementService, 'FILTER_VALIDATE_EMAIL'));
$check('v2.1.2 mail podešavanja i šablon podržavaju nove artikle', str_contains($mailSettingsView, 'product_email_new_items_enabled') && str_contains($mailSettingsView, 'product_email_new_items_interval_minutes') && str_contains($mailTemplate, 'Pogledaj artikal'));
$check('v2.1.2 product announcement regresije postoje', str_contains($productAnnouncementSmoke, 'Product announcement smoke') && is_file($root.'/tests/Unit/ProductAnnouncementContractTest.php'));
$check('v2.1.2 nema novu migration datoteku', (glob($root.'/database/migrations/*2_1_2*.php') ?: []) === []);

$check('APP_ENV production', str_contains($env, 'APP_ENV=production'));
$check('Redis nije obavezan za database queue', str_contains($env, 'Redis nije obavezan'));
$check('file session/cache/limiter i database queue', str_contains($env, 'SESSION_DRIVER=file') && str_contains($env, 'CACHE_STORE=file') && str_contains($env, 'CACHE_LIMITER=file') && str_contains($env, 'QUEUE_CONNECTION=database'));
$check('secret vrednosti su prazne',
    preg_match('/^DB_PASSWORD=\s*$/m', $env) === 1
    && preg_match('/^LEGACY_DB_PASSWORD=\s*$/m', $env) === 1
    && preg_match('/^TURNSTILE_SECRET_KEY=\s*$/m', $env) === 1
    && preg_match('/^MAIL_PASSWORD=\s*$/m', $env) === 1
);

$import = (string) file_get_contents($root.'/app/Console/Commands/LegacyImportCommand.php');
$check('import ne upisuje legacy konekciju', !preg_match('/DB::connection\([\'\"]legacy[\'\"]\)->'.'(?:insert|update|delete|statement|unprepared)/', $import));

if ($packageMode) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    $envFound = $backupFound = $runtimeFound = $privateBusinessFileFound = $generatedStorageFound = $secretFound = false;
    $knownPublicTurnstileKey = '0x4AAAAAAD4fRZTSSOfQu29m';
    foreach ($iterator as $file) {
        if (!$file->isFile()) continue;
        $relative = substr($file->getPathname(), strlen($root) + 1);
        if ($relative === '.env') $envFound = true;
        if (str_contains($relative, '.env.backup') || str_contains($relative, '.backup-')) $backupFound = true;
        $runtimePath = str_starts_with($relative, 'storage/logs/')
            || str_starts_with($relative, 'storage/framework/sessions/')
            || str_starts_with($relative, 'storage/framework/cache/data/')
            || str_starts_with($relative, 'storage/framework/views/');
        if ($runtimePath && basename($relative) !== '.gitignore') $runtimeFound = true;
        if (str_starts_with($relative, 'storage/app/private/') && basename($relative) !== '.gitignore') $privateBusinessFileFound = true;
        if (str_starts_with($relative, 'storage/app/') && basename($relative) !== '.gitignore') $generatedStorageFound = true;
        if ($file->getSize() <= 2_000_000) {
            $contents = (string) @file_get_contents($file->getPathname());
            if (preg_match_all('/0x4[A-Za-z0-9_-]{18,}/', $contents, $matches)) {
                foreach ($matches[0] as $candidate) if (!hash_equals($knownPublicTurnstileKey, $candidate)) $secretFound = true;
            }
        }
    }
    $check('produkcioni .env nije u paketu', !$envFound);
    $check('backup fajlovi nisu u paketu', !$backupFound);
    $check('runtime logovi i sesije nisu u paketu', !$runtimeFound);
    $check('privatni poslovni prilozi nisu u paketu', !$privateBusinessFileFound);
    $check('generisani PDF i QR snapshotovi nisu u paketu', !$generatedStorageFound);
    $check('Turnstile secret nije u paketu', !$secretFound);
} else {
    fwrite(STDOUT, "INFO  ZIP hygiene provere su preskočene na instaliranoj aplikaciji; za raspakovani sanitized ZIP koristi --package.\n");
}



$catalogSettingsController = (string) @file_get_contents($root.'/app/Http/Controllers/Admin/CatalogDictionaryController.php');
$catalogSettingsRoutes = (string) @file_get_contents($root.'/routes/web.php');
$catalogSettingsView = (string) @file_get_contents($root.'/resources/views/admin/dictionary/product-type.blade.php');
$catalogSortJs = (string) @file_get_contents($root.'/public/assets/js/dictionary-sort-manager.js');
$productRequestV213 = (string) @file_get_contents($root.'/app/Http/Requests/ProductRequest.php');
$productFormV213 = (string) @file_get_contents($root.'/resources/views/admin/products/form.blade.php');
$catalogSettingsDoctor = (string) @file_get_contents($root.'/app/Console/Commands/CatalogSettingsDoctorCommand.php');
$check('v2.1.3 tipovi proizvoda imaju posebne stranice i Drag & Drop', str_contains($catalogSettingsRoutes, '/catalog-settings/product-type/{productType:slug}') && str_contains($catalogSettingsView, 'data-sort-edit-start') && str_contains($catalogSortJs, "addEventListener('pointerdown'"));
$check('v2.1.3 specifikaciona polja mogu trajno da se obrišu', str_contains($catalogSettingsController, 'function purge(') && str_contains($catalogSettingsController, 'catalog.specification_field.deleted'));
$check('v2.1.3 tip automatski određuje kategoriju', !str_contains($productFormV213, 'name="category_ids[]"') && str_contains($productRequestV213, "'category_ids' => \$categoryId !== null ? [\$categoryId] : []"));
$check('v2.1.3 diskovi imaju pojedinačne celobrojne GB kapacitete', str_contains($productFormV213, 'spec_capacities[') && str_contains($productRequestV213, 'spec_structured') && str_contains($productRequestV213, 'ceo broj bez decimala'));
$check('v2.1.3 catalog settings doctor postoji', str_contains($catalogSettingsDoctor, 'app:catalog-settings-doctor') && str_contains($catalogSettingsDoctor, 'categoryMismatchCounts'));
$check('v2.1.3 grana ima tri kontrolisane migration datoteke', count(glob($root.'/database/migrations/*2_1_3*.php') ?: []) === 3);


$productRequestRegex = (string) @file_get_contents($root.'/app/Http/Requests/ProductRequest.php');
$check('v2.1.3.3 ProductRequest zadržava validan SKU regex delimiter', str_contains($productRequestRegex, "'regex:#^[A-Z0-9._/-]+$#'"));
$check('v2.1.3.3 ProductVariantRequest je retired a ProductRequest zadržava validan SKU regex', !is_file($root.'/app/Http/Requests/ProductVariantRequest.php') && str_contains($productRequestRegex, "'regex:#^[A-Z0-9._/-]+$#'"));

$storageService = (string) @file_get_contents($root.'/app/Services/StorageSpecificationService.php');
$storageMigration = (string) @file_get_contents($root.'/database/migrations/2026_08_04_000035_link_storage_components_and_total_capacity_v2_1_3_3.php');
$storageModel = (string) @file_get_contents($root.'/app/Models/SpecificationField.php');
$productMediaJsV2133 = (string) @file_get_contents($root.'/public/assets/js/product-media-manager.js');
$check('v2.1.3.3 migracija povezuje listu diskova i izvedeni ukupni kapacitet', str_contains($storageMigration, 'storage_role') && str_contains($storageMigration, 'storage_source_field_id') && str_contains($storageMigration, 'StorageSpecificationService::class'));
$check('v2.1.3.3 stari kapacitet se bezbedno prenosi na prvi disk', str_contains($storageService, 'normalizeRows($rows, $legacyTotal)') && str_contains($storageService, "['capacity_gb'] = ") && str_contains($storageService, 'fallbackTotal'));
$check('v2.1.3.3 backend ne veruje ručnom ukupnom zbiru', str_contains($productRequestV213, 'applyComputedTotals') && str_contains($storageService, 'totalCapacity($rows)'));
$check('v2.1.3.3 ukupni kapacitet je ispod diskova i readonly', strpos($productFormV213, 'data-repeatable-list') < strpos($productFormV213, 'data-storage-total-card') && str_contains($productFormV213, 'data-storage-total-display') && str_contains($productFormV213, 'readonly'));
$check('v2.1.3.3 frontend sabira diskove i čuva početni legacy zbir', str_contains($productMediaJsV2133, 'totalCapacity +=') && str_contains($productMediaJsV2133, 'storageInitialTotal') && str_contains($productMediaJsV2133, 'userTouchedStorage'));
$check('v2.1.3.3 product-only storage model zadržava izvedeni zbir bez variant servisa', str_contains($storageModel, 'isDerivedStorageTotalField') && !is_file($root.'/app/Http/Requests/ProductVariantRequest.php') && !is_file($root.'/app/Services/ProductVariantService.php') && !str_contains($storageService, 'product_variant_spec_values'));
$check('v2.1.3.3 storage smoke i contract test postoje', is_file($root.'/bin/storage-capacity-total-smoke.php') && is_file($root.'/tests/Unit/StorageCapacityTotalContractTest.php'));


$v214Migration = (string) @file_get_contents($root.'/database/migrations/2026_08_04_000036_add_product_model_and_name_templates_v2_1_4.php');
$v214Model = (string) @file_get_contents($root.'/app/Models/Product.php');
$v214Request = (string) @file_get_contents($root.'/app/Http/Requests/ProductRequest.php');
$v214Controller = (string) @file_get_contents($root.'/app/Http/Controllers/Admin/ProductController.php');
$v214Template = (string) @file_get_contents($root.'/app/Services/ProductTemplateService.php');
$v214Deletion = (string) @file_get_contents($root.'/app/Services/ProductDeletionService.php');
$v214Form = (string) @file_get_contents($root.'/resources/views/admin/products/form.blade.php');
$v214Css = (string) @file_get_contents($root.'/public/assets/css/app.css');
$v214Routes = (string) @file_get_contents($root.'/routes/web.php');
$v214Release = (string) @file_get_contents($root.'/config/release.php');
$check('v2.1.4 migracija dodaje model proizvoda i usklađuje šablone', str_contains($v214Migration, "string('model_name', 190)") && str_contains($v214Migration, 'ensureModelPlaceholderInNameTemplates'));
$check('v2.1.4 model se validira čuva i koristi u nazivu', str_contains($v214Model, "'model_name'") && str_contains($v214Request, "'model_name' => ['nullable', 'string', 'max:190']") && str_contains($v214Template, "'model' => trim((string) (\$data['model_name'] ?? ''))"));
$check('v2.1.4 forma ima model proizvoda posle linije', strpos($v214Form, 'data-line-select') < strpos($v214Form, 'data-product-model'));
$check('v2.1.4 trajno brisanje ima SKU potvrdu i izbor brisanja slika', str_contains($v214Controller, 'hash_equals((string) $product->sku') && str_contains($v214Form, 'name="delete_images"') && str_contains($v214Deletion, "deleteDirectory"));
$check('v2.1.4 poslovna istorija blokira destruktivno brisanje', str_contains($v214Deletion, "'Porudžbine' => 'order_items'") && str_contains($v214Deletion, "\$blockers['Promene lagera']"));
$check('v2.1.4 semantički sistem tastera pokriva sve uloge', str_contains($v214Css, '--button-height:46px') && str_contains($v214Css, '.button-secondary') && str_contains($v214Css, '.button-success') && str_contains($v214Css, '.button-warning') && str_contains($v214Css, '.button-danger'));
$check('v2.1.4 route i stable doctor postoje', str_contains($v214Routes, "name('products.purge')") && str_contains($v214Release, "'cms_v214'") && is_file($root.'/app/Console/Commands/CmsV214DoctorCommand.php'));
$check('v2.1.4 smoke i contract test postoje', is_file($root.'/bin/cms-v2.1.4-smoke.php') && is_file($root.'/tests/Unit/CmsV214ContractTest.php'));


$v2141DictionaryController = (string) @file_get_contents($root.'/app/Http/Controllers/Admin/CatalogDictionaryController.php');
$v2141FieldsView = (string) @file_get_contents($root.'/resources/views/admin/dictionary/fields.blade.php');
$v2141Doctor = (string) @file_get_contents($root.'/app/Console/Commands/CmsV214DoctorCommand.php');
$check('v2.1.4.1 controller priprema i prosledjuje orderedFields', str_contains($v2141DictionaryController, '$orderedFields = $fields->sortBy') && str_contains($v2141DictionaryController, "'orderedFields' => \$orderedFields"));
$check('v2.1.4.1 Blade bezbedno inicijalizuje orderedFields', str_contains($v2141FieldsView, '$fields = collect($fields ?? []);') && str_contains($v2141FieldsView, '$orderedFields = collect($orderedFields ?? $fields);'));
$check('v2.1.4.1 doctor renderuje formulare svih tipova', str_contains($v2141Doctor, 'renderProductTypeFieldForms') && str_contains($v2141Doctor, "view('admin.dictionary.fields'") && str_contains($v2141Doctor, "str_contains(\$html, 'template-field-table')"));
$check('v2.1.4.1 smoke i contract test postoje', is_file($root.'/bin/product-type-page-render-hotfix-smoke.php') && is_file($root.'/tests/Unit/ProductTypePageRenderHotfixContractTest.php'));



$v215Runtime = (string) @file_get_contents($root.'/public/assets/js/ux-runtime.js');
$v215Css = (string) @file_get_contents($root.'/public/assets/css/app.css');
$v215Doctor = (string) @file_get_contents($root.'/app/Console/Commands/CmsV215DoctorCommand.php');
$v215Migration = (string) @file_get_contents($root.'/database/migrations/2026_08_05_000037_place_desktop_power_supply_field_v2_1_5.php');
$v215ProductForm = (string) @file_get_contents($root.'/resources/views/admin/products/form.blade.php');
$v215OrderForm = (string) @file_get_contents($root.'/resources/views/orders/create.blade.php');
$v215Release = (string) @file_get_contents($root.'/config/release.php');
$check('v2.1.5 globalni UX runtime štiti submit i nesačuvane izmene', str_contains($v215Runtime, 'protectForms') && str_contains($v215Runtime, 'protectUnsavedChanges') && str_contains($v215Runtime, 'beforeunload'));
$check('v2.1.5 mobilni action dock koristi originalni submit', str_contains($v215Runtime, 'createMobileActionDock') && str_contains($v215Runtime, 'submit.click()') && str_contains($v215Css, '.ux-mobile-action-dock'));
$check('v2.1.5 validacija i accessibility markeri postoje', str_contains($v215Runtime, 'aria-invalid') && str_contains($v215Runtime, "document.addEventListener('invalid'") && str_contains($v215Css, '--ux-focus-ring'));
$check('v2.1.5 dugi formulari su eksplicitno označeni', str_contains($v215ProductForm, 'data-ux-sticky-actions') && str_contains($v215OrderForm, 'data-ux-sticky-actions'));
$check('v2.1.5 sistemske error stranice postoje', is_file($root.'/resources/views/errors/404.blade.php') && is_file($root.'/resources/views/errors/500.blade.php') && is_file($root.'/resources/views/errors/503.blade.php'));
$check('v2.1.5 migracija koristi postojeće snaga-napajanja polje', str_contains($v215Migration, "FIELD_SLUG = 'snaga-napajanja'") && str_contains($v215Migration, "PRODUCT_TYPE_SLUG = 'desktop-racunar'") && str_contains($v215Migration, "['status' => 'active']") && !str_contains($v215Migration, "DB::table('specification_fields')->insert"));
$check('v2.1.5 migracija postavlja napajanje u sredinu', str_contains($v215Migration, 'intdiv(count($assignedIds) + 1, 2)') && str_contains($v215Migration, 'array_splice($assignedIds, $middleIndex'));
$check('v2.1.5 doctor proverava Blade, route akcije i napajanje', str_contains($v215Doctor, 'compileAllBladeViews') && str_contains($v215Doctor, 'renderErrorPages') && str_contains($v215Doctor, 'auditRouteActions') && str_contains($v215Doctor, 'activateAndPlaceDesktopPowerSupplyField'));
$check('v2.1.5 stable release koristi render i repair', str_contains($v215Release, "'cms_v215'") && str_contains($v215Release, "'repair' => ['--repair' => true]") && str_contains($v215Release, "'render' => ['--render' => true]"));
$check('v2.1.5 smoke i contract test postoje', is_file($root.'/bin/cms-v2.1.5-smoke.php') && is_file($root.'/tests/Unit/CmsV215ContractTest.php'));



$v216Migration = (string) @file_get_contents($root.'/database/migrations/2026_08_05_000038_create_performance_data_quality_v2_1_6.php');
$v216Quality = (string) @file_get_contents($root.'/app/Services/DataQualityService.php');
$v216Performance = (string) @file_get_contents($root.'/app/Console/Commands/PerformanceDoctorCommand.php');
$v216Doctor = (string) @file_get_contents($root.'/app/Console/Commands/CmsV216DoctorCommand.php');
$v216Routes = (string) @file_get_contents($root.'/routes/web.php');
$v216View = (string) @file_get_contents($root.'/resources/views/admin/data-quality/index.blade.php');
$v216Catalog = (string) @file_get_contents($root.'/app/Services/CatalogQueryService.php');
$v216CatalogView = (string) @file_get_contents($root.'/resources/views/catalog/index.blade.php');
$v216Release = (string) @file_get_contents($root.'/config/release.php');
$check('v2.1.6 migracija kreira snapshot istoriju i ciljane indekse', str_contains($v216Migration, 'data_quality_snapshots') && str_contains($v216Migration, 'products_catalog_active_created_v216_idx') && str_contains($v216Migration, 'product_images_primary_sort_v216_idx'));
$check('v2.1.6 Data Quality audit pokriva product-only katalog slike i specifikacije', str_contains($v216Quality, 'appendCatalogIdentityIssues') && str_contains($v216Quality, 'appendImageIssues') && !str_contains($v216Quality, 'appendVariantIssues') && str_contains($v216Quality, 'appendSpecificationIssues'));
$check('v2.1.6 repair je nedestruktivan i preračunava izvedene vrednosti', str_contains($v216Quality, 'repairSafe') && str_contains($v216Quality, 'normalizeImagePrimaries') && str_contains($v216Quality, 'recalculateAll(false)'));
$check('v2.1.6 performance doctor proverava indekse cache i SQL pragove', str_contains($v216Performance, 'requiredIndexes') && str_contains($v216Performance, 'CatalogReferenceCache') && str_contains($v216Performance, 'slow_query_failure_ms'));
$check('v2.1.6 dashboard kešira schema metadata po requestu', str_contains((string) @file_get_contents($root.'/app/Http/Controllers/DashboardController.php'), 'tableColumnsCache'));
$check('v2.1.6 Data Quality Center rute i prikaz postoje', str_contains($v216Routes, "name('data-quality.index')") && str_contains($v216View, 'Data Quality Center') && str_contains($v216View, 'Bezbedna automatska popravka'));
$check('v2.1.6 katalog ima quality filtere', str_contains($v216CatalogView, 'name="quality"') && str_contains($v216Catalog, "'missing_image'") && str_contains($v216Catalog, "'incomplete'"));
$check('v2.1.6 doctor renderuje centar i pokreće performance audit', str_contains($v216Doctor, "app:performance-doctor") && str_contains($v216Doctor, "View::make('admin.data-quality.index'"));
$check('v2.1.6 Stable release uključuje render repair i strict', str_contains($v216Release, "'cms_v216'") && str_contains($v216Release, "'strict' => ['--strict' => true]") && str_contains($v216Release, "'repair' => ['--repair' => true]"));
$check('v2.1.6 smoke i contract test postoje', is_file($root.'/bin/cms-v2.1.6-smoke.php') && is_file($root.'/tests/Unit/PerformanceDataQualityContractTest.php'));

$v220Routes = (string) @file_get_contents($root.'/routes/api.php');
$v220Bootstrap = (string) @file_get_contents($root.'/app/Http/Controllers/Api/V1/BootstrapController.php');
$v220Devices = (string) @file_get_contents($root.'/app/Http/Controllers/Api/V1/MobileDeviceController.php');
$v220Exceptions = (string) @file_get_contents($root.'/bootstrap/app.php');
$v220ErrorResponse = (string) @file_get_contents($root.'/app/Support/ApiErrorResponse.php');
$v220OpenApi = (string) @file_get_contents($root.'/docs/openapi.yaml');
$v220Release = (string) @file_get_contents($root.'/config/release.php');
$check('v2.2.0 bootstrap device catalog order i notification rute postoje', str_contains($v220Routes, "Route::get('/bootstrap'") && str_contains($v220Routes, "Route::post('/devices'") && str_contains($v220Routes, "Route::get('/catalog/filters'") && str_contains($v220Routes, "Route::get('/orders/options'") && str_contains($v220Routes, "Route::get('/notifications'"));
$check('v2.2.0 bootstrap vraća permissions features i app policy', str_contains($v220Bootstrap, 'permissions') && str_contains($v220Bootstrap, 'features') && str_contains($v220Bootstrap, 'backend_version'));
$check('v2.2.0 uređaji deduplikuju push tokene i podržavaju opoziv', str_contains($v220Devices, 'revokeDuplicatePushTokens') && str_contains($v220Devices, "'revoked_at' => now()"));
$check('v2.2.0 API greške imaju stabilan envelope', str_contains($v220Exceptions, 'validation_failed') && str_contains($v220ErrorResponse, 'request_id') && str_contains($v220Exceptions, 'shouldRenderJsonWhen'));
$check('v2.2.0 OpenAPI i Stable doctor su povezani', str_contains($v220OpenApi, 'openapi: 3.1.0') && str_contains($v220Release, "'cms_v220'") && str_contains($v220Release, 'app:cms-v2-2-0-doctor'));

$failed = count(array_filter($checks, static fn (array $item): bool => !$item[1]));
fwrite(STDOUT, sprintf("\nUkupno: %d, neuspešno: %d\n", count($checks), $failed));
exit($failed === 0 ? 0 : 1);
