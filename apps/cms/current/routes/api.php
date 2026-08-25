<?php

declare(strict_types=1);

use App\Http\Controllers\AfterSalesAttachmentController;
use App\Http\Controllers\FieldWorkOrderAttachmentController;
use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AuthRecoveryController;
use App\Http\Controllers\Api\V1\AfterSalesController;
use App\Http\Controllers\Api\V1\AuthTokenController;
use App\Http\Controllers\Api\V1\BootstrapController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\Admin\CatalogProductController as AdminCatalogProductController;
use App\Http\Controllers\Api\V1\Admin\ProductDeletionController as AdminProductDeletionController;
use App\Http\Controllers\Api\V1\Admin\ProductPurchaseCostController as AdminProductPurchaseCostController;
use App\Http\Controllers\Api\V1\Admin\CatalogBrandController as AdminCatalogBrandController;
use App\Http\Controllers\Api\V1\Admin\CatalogDictionaryController as AdminCatalogDictionaryController;
use App\Http\Controllers\Api\V1\Admin\FoundationController as AdminFoundationController;
use App\Http\Controllers\Api\V1\Admin\CommissionController as AdminCommissionController;
use App\Http\Controllers\Api\V1\Admin\WarrantyController as AdminWarrantyController;
use App\Http\Controllers\Api\V1\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Api\V1\Admin\ReportScheduleController as AdminReportScheduleController;
use App\Http\Controllers\Api\V1\Admin\SystemHealthController as AdminSystemHealthController;
use App\Http\Controllers\Api\V1\Admin\AuditEventController as AdminAuditEventController;
use App\Http\Controllers\Api\V1\Admin\ModuleSettingsController as AdminModuleSettingsController;
use App\Http\Controllers\Api\V1\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\V1\Admin\ExchangeRateController as AdminExchangeRateController;
use App\Http\Controllers\Api\V1\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\V1\Admin\OrderMutationController as AdminOrderMutationController;
use App\Http\Controllers\Api\V1\Admin\OrderShipmentController as AdminOrderShipmentController;
use App\Http\Controllers\Api\V1\Admin\OrderDocumentController as AdminOrderDocumentController;
use App\Http\Controllers\Api\V1\Admin\CourierServiceController as AdminCourierServiceController;
use App\Http\Controllers\Api\V1\Admin\AfterSalesActionController as AdminAfterSalesActionController;
use App\Http\Controllers\Api\V1\Admin\AfterSalesAttachmentController as AdminAfterSalesAttachmentController;
use App\Http\Controllers\Api\V1\Admin\AfterSalesController as AdminAfterSalesController;
use App\Http\Controllers\Admin\ReceivablesController as WebReceivablesController;
use App\Http\Controllers\Api\V1\Admin\ReceivablesController as AdminReceivablesController;
use App\Http\Controllers\Api\V1\Admin\ServicePartsController as AdminServicePartsController;
use App\Http\Controllers\Api\V1\Admin\FieldOperationsController as AdminFieldOperationsController;
use App\Http\Controllers\Api\V1\Admin\FieldServiceTeamController as AdminFieldServiceTeamController;
use App\Http\Controllers\Api\V1\Admin\FieldWorkOrderPartController as AdminFieldWorkOrderPartController;
use App\Http\Controllers\Api\V1\CatalogOptionsController;
use App\Http\Controllers\Api\V1\GoogleAuthController;
use App\Http\Controllers\Api\V1\MobileDeviceController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\OrderOptionsController;
use App\Http\Controllers\Api\V1\CommissionController;
use App\Http\Controllers\Api\V1\WarrantyController;
use App\Http\Controllers\Api\V1\Admin\InventoryController as AdminInventoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::post('/auth/token', [AuthTokenController::class, 'store'])
        ->middleware('throttle:api-login')
        ->name('auth.token');

    Route::post('/auth/google', [GoogleAuthController::class, 'store'])
        ->middleware('throttle:api-google-auth')
        ->name('auth.google');

    // MOBILE_V1_0_AUTH_ACCOUNT_SECURITY_PARITY_BATCH32
    Route::post('/auth/password/forgot', [AuthRecoveryController::class, 'forgot'])
        ->middleware('throttle:password-reset-link')
        ->name('auth.password.forgot');
    Route::post('/auth/password/reset', [AuthRecoveryController::class, 'reset'])
        ->middleware('throttle:password-reset')
        ->name('auth.password.reset');
    Route::get('/auth/customer-activation', [AuthRecoveryController::class, 'activationState'])
        ->middleware('throttle:customer-activation')
        ->name('auth.customer-activation.state');
    Route::post('/auth/customer-activation', [AuthRecoveryController::class, 'activate'])
        ->middleware('throttle:customer-activation')
        ->name('auth.customer-activation.store');

    Route::middleware(['auth:sanctum', 'active'])->group(function (): void {
        Route::get('/bootstrap', [BootstrapController::class, 'show'])->name('bootstrap');
        Route::prefix('admin')
            ->name('admin.')
            ->group(function (): void {
                Route::get('/foundation', [AdminFoundationController::class, 'show'])->name('foundation');
                // MOBILE_V1_0_ADMIN_PURCHASE_COST_PARITY_BATCH19_V4
                Route::prefix('catalog/purchase-costs')
                    ->middleware('permission:catalog.manage_products')
                    ->name('catalog.purchase-costs.')
                    ->group(function (): void {
                        Route::get('/', [AdminProductPurchaseCostController::class, 'index'])->name('index');
                        Route::put('/', [AdminProductPurchaseCostController::class, 'update'])
                            ->middleware('throttle:admin-write')->name('update');
                    });
                Route::middleware('permission:commissions.manage')->group(function (): void {
                    Route::get('/commissions', [AdminCommissionController::class, 'index'])->name('commissions.index');
                    Route::post('/commissions/bulk-pay', [AdminCommissionController::class, 'bulkPay'])->name('commissions.bulk-pay');
                    Route::get('/commissions.csv', [AdminCommissionController::class, 'csv'])->middleware('throttle:exports')->name('commissions.csv');
                    Route::get('/commissions.pdf', [AdminCommissionController::class, 'pdf'])->middleware('throttle:exports')->name('commissions.pdf');
                    Route::get('/commissions/{commission}', [AdminCommissionController::class, 'show'])->whereNumber('commission')->name('commissions.show');
                    Route::patch('/commissions/{commission}/status', [AdminCommissionController::class, 'transition'])->whereNumber('commission')->name('commissions.transition');
                });
                Route::middleware('permission:warranties.manage')->group(function (): void {
                    Route::get('/warranties', [AdminWarrantyController::class, 'index'])->name('warranties.index');
                    Route::get('/warranties/rules', [AdminWarrantyController::class, 'rules'])->name('warranties.rules.index');
                    Route::post('/warranties/rules', [AdminWarrantyController::class, 'storeRule'])->middleware('throttle:admin-write')->name('warranties.rules.store');
                    Route::put('/warranties/rules/{rule}', [AdminWarrantyController::class, 'updateRule'])->whereNumber('rule')->middleware('throttle:admin-write')->name('warranties.rules.update');
                    Route::post('/warranties/backfill', [AdminWarrantyController::class, 'backfill'])->middleware('throttle:admin-write')->name('warranties.backfill');
                    Route::get('/warranties/{warranty}.pdf', [AdminWarrantyController::class, 'pdf'])->whereNumber('warranty')->name('warranties.pdf');
                    Route::get('/warranties/{warranty}', [AdminWarrantyController::class, 'show'])->whereNumber('warranty')->name('warranties.show');
                    Route::put('/warranties/{warranty}', [AdminWarrantyController::class, 'update'])->whereNumber('warranty')->name('warranties.update');
                    Route::post('/warranties/{warranty}/void', [AdminWarrantyController::class, 'void'])->whereNumber('warranty')->name('warranties.void');
                    Route::post('/warranties/{warranty}/maintenance/{record}/schedule', [AdminWarrantyController::class, 'schedule'])->whereNumber('warranty')->whereNumber('record')->name('warranties.maintenance.schedule');
                    Route::post('/warranties/{warranty}/maintenance/{record}/complete', [AdminWarrantyController::class, 'complete'])->whereNumber('warranty')->whereNumber('record')->name('warranties.maintenance.complete');
                });
                Route::middleware('permission:reports.view')->group(function (): void {
                    Route::get('/reports/management', [AdminReportController::class, 'management'])->name('reports.management');
                    Route::get('/reports/management.csv', [AdminReportController::class, 'managementCsv'])->middleware(['permission:reports.export', 'throttle:exports'])->name('reports.management.csv');
                    Route::get('/reports/management.pdf', [AdminReportController::class, 'managementPdf'])->middleware(['permission:reports.export', 'throttle:exports'])->name('reports.management.pdf');
                });
                Route::middleware('permission:reports.manage')->group(function (): void {
                    Route::get('/report-schedules', [AdminReportScheduleController::class, 'index'])->name('report-schedules.index');
                    Route::post('/report-schedules', [AdminReportScheduleController::class, 'store'])->middleware('throttle:admin-write')->name('report-schedules.store');
                    Route::patch('/report-schedules/{schedule}', [AdminReportScheduleController::class, 'update'])->whereNumber('schedule')->middleware('throttle:admin-write')->name('report-schedules.update');
                    Route::patch('/report-schedules/{schedule}/toggle', [AdminReportScheduleController::class, 'toggle'])->whereNumber('schedule')->middleware('throttle:admin-write')->name('report-schedules.toggle');
                    Route::post('/report-schedules/{schedule}/run', [AdminReportScheduleController::class, 'run'])->whereNumber('schedule')->middleware('throttle:admin-write')->name('report-schedules.run');
                    Route::delete('/report-schedules/{schedule}', [AdminReportScheduleController::class, 'destroy'])->whereNumber('schedule')->middleware('throttle:admin-write')->name('report-schedules.destroy');
                    Route::post('/report-deliveries/{delivery}/retry', [AdminReportScheduleController::class, 'retry'])->whereNumber('delivery')->middleware('throttle:admin-write')->name('report-deliveries.retry');
                });
                // MOBILE_V1_0_SYSTEM_HEALTH_MUTATIONS_PARITY_BATCH25
                Route::prefix('system-health')
                    ->middleware('permission:system.health')
                    ->name('system-health.')
                    ->group(function (): void {
                        Route::get('/', [AdminSystemHealthController::class, 'index'])->name('index');
                        Route::post('/run', [AdminSystemHealthController::class, 'run'])
                            ->middleware('throttle:admin-write')->name('run');
                        Route::middleware('permission:backups.manage')->group(function (): void {
                            Route::post('/backup', [AdminSystemHealthController::class, 'backup'])
                                ->middleware('throttle:backup')->name('backup');
                            Route::post('/prune', [AdminSystemHealthController::class, 'prune'])
                                ->middleware('throttle:admin-write')->name('prune');
                        });
                    });
                // MOBILE_V1_0_AUDIT_CSV_EXPORT_PARITY_BATCH29
                Route::middleware('permission:security.view')->group(function (): void {
                    Route::get('/audit-events', [AdminAuditEventController::class, 'index'])->name('audit-events.index');
                    Route::get('/audit-events.csv', [AdminAuditEventController::class, 'csv'])
                        ->middleware(['permission:audit.export', 'throttle:exports'])->name('audit-events.csv');
                    Route::get('/audit-events/{event}', [AdminAuditEventController::class, 'show'])->whereNumber('event')->name('audit-events.show');
                });
                // MOBILE_V0_8_SHIPMENT_COURIER_DIRECTORY_BATCH11
                Route::get('/couriers', [AdminCourierServiceController::class, 'index'])->name('couriers.index');
                Route::post('/couriers', [AdminCourierServiceController::class, 'store'])->middleware('throttle:admin-write')->name('couriers.store');
                Route::put('/couriers/{courier}', [AdminCourierServiceController::class, 'update'])->whereNumber('courier')->middleware('throttle:admin-write')->name('couriers.update');

                // MOBILE_V0_8_COMPLETE_USER_MANAGEMENT_BATCH12
                // MOBILE_V0_8_EUR_RSD_EXCHANGE_RATE_BATCH13
                Route::middleware('permission:system.manage_settings')->group(function (): void {
                    // MOBILE_V1_0_SET_01_MODULE_SETTINGS_PARITY_BATCH30
                    Route::prefix('settings/modules')->name('settings.modules.')->group(function (): void {
                        Route::get('/', [AdminModuleSettingsController::class, 'index'])->name('index');
                        Route::put('/', [AdminModuleSettingsController::class, 'update'])
                            ->middleware('throttle:admin-write')->name('update');
                    });
                    Route::get('/exchange-rate', [AdminExchangeRateController::class, 'index'])->name('exchange-rate.index');
                    Route::put('/exchange-rate/manual', [AdminExchangeRateController::class, 'manual'])->middleware('throttle:admin-write')->name('exchange-rate.manual');
                    Route::put('/exchange-rate/automatic', [AdminExchangeRateController::class, 'automatic'])->middleware('throttle:admin-write')->name('exchange-rate.automatic');
                    Route::post('/exchange-rate/refresh', [AdminExchangeRateController::class, 'refresh'])->middleware('throttle:admin-write')->name('exchange-rate.refresh');
                });
                Route::middleware('permission:system.manage_users')->group(function (): void {
                    Route::get('/users/options', [AdminUserController::class, 'options'])->name('users.options');
                    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
                    Route::post('/users', [AdminUserController::class, 'store'])->middleware('throttle:admin-write')->name('users.store');
                    Route::get('/users/{user}', [AdminUserController::class, 'show'])->whereNumber('user')->name('users.show');
                    Route::put('/users/{user}', [AdminUserController::class, 'update'])->whereNumber('user')->middleware('throttle:admin-write')->name('users.update');
                });
                Route::middleware('permission:orders.manage')->group(function (): void {
                    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
                    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->whereNumber('order')->name('orders.show');
                    Route::get('/orders/{order}/shipment-proof', [AdminOrderShipmentController::class, 'proof'])->whereNumber('order')->name('orders.shipment.proof');
                    // MOBILE_V1_0_ADMIN_ORDER_DOCUMENTS_INVOICE_PARITY_BATCH23
                    Route::middleware('permission:invoices.manage')->group(function (): void {
                        Route::post('/orders/{order}/documents', [AdminOrderDocumentController::class, 'store'])->whereNumber('order')->middleware('throttle:admin-write')->name('orders.documents.store');
                        Route::get('/orders/{order}/documents/confirmation.pdf', [AdminOrderDocumentController::class, 'confirmation'])->whereNumber('order')->name('orders.documents.confirmation');
                        Route::get('/orders/{order}/documents/{document}.pdf', [AdminOrderDocumentController::class, 'pdf'])->whereNumber('order')->whereNumber('document')->name('orders.documents.pdf');
                        Route::post('/orders/{order}/documents/{document}/cancel', [AdminOrderDocumentController::class, 'cancel'])->whereNumber('order')->whereNumber('document')->middleware('throttle:admin-write')->name('orders.documents.cancel');
                    });
                    Route::middleware('throttle:admin-write')->group(function (): void {
                        Route::patch('/orders/{order}/status', [AdminOrderMutationController::class, 'status'])->whereNumber('order')->name('orders.status');
                        Route::post('/orders/{order}/accept', [AdminOrderMutationController::class, 'accept'])->whereNumber('order')->name('orders.accept');
                        Route::post('/orders/{order}/internal-notes', [AdminOrderMutationController::class, 'internalNote'])->whereNumber('order')->middleware('permission:orders.internal_notes')->name('orders.internal-notes.store');
                        Route::patch('/orders/{order}/reassign', [AdminOrderMutationController::class, 'reassign'])->whereNumber('order')->middleware('permission:orders.reassign')->name('orders.reassign');
                        Route::patch('/orders/{order}/deadlines', [AdminOrderMutationController::class, 'deadlines'])->whereNumber('order')->name('orders.deadlines');
                        Route::patch('/orders/{order}/payment-status', [AdminOrderMutationController::class, 'paymentStatus'])->whereNumber('order')->name('orders.payment-status');
                        Route::post('/orders/{order}/complete', [AdminOrderMutationController::class, 'complete'])->whereNumber('order')->middleware('permission:orders.confirm_delivery')->name('orders.complete');
                        Route::post('/orders/{order}/reopen', [AdminOrderMutationController::class, 'reopen'])->whereNumber('order')->middleware('permission:orders.reopen')->name('orders.reopen');
                        Route::post('/orders/{order}/shipment', [AdminOrderShipmentController::class, 'store'])->whereNumber('order')->name('orders.shipment.store');
                        Route::middleware('permission:payments.manage')->group(function (): void {
                            Route::post('/orders/{order}/payments', [AdminOrderMutationController::class, 'paymentStore'])->whereNumber('order')->name('orders.payments.store');
                            Route::post('/orders/{order}/payments/{payment}/verify', [AdminOrderMutationController::class, 'paymentVerify'])->whereNumber('order')->whereNumber('payment')->name('orders.payments.verify');
                            Route::post('/orders/{order}/payments/{payment}/reject', [AdminOrderMutationController::class, 'paymentReject'])->whereNumber('order')->whereNumber('payment')->name('orders.payments.reject');
                            Route::post('/orders/{order}/payments/{payment}/void', [AdminOrderMutationController::class, 'paymentVoid'])->whereNumber('order')->whereNumber('payment')->name('orders.payments.void');
                        });
                    });
                });
            });
        Route::prefix('admin/service-parts')->name('admin.service-parts.')->group(function (): void {
            Route::get('/', [AdminServicePartsController::class, 'partsIndex'])
                ->middleware('permission:service_parts.view')->name('index');
            Route::middleware(['permission:service_parts.manage', 'throttle:admin-write'])->group(function (): void {
                Route::post('/', [AdminServicePartsController::class, 'partStore'])->name('store');
                Route::put('/{part}', [AdminServicePartsController::class, 'partUpdate'])->whereNumber('part')->name('update');
                Route::post('/{part}/adjust', [AdminServicePartsController::class, 'partAdjust'])->whereNumber('part')->name('adjust');
            });
        });
        Route::prefix('admin/service-part-suppliers')->name('admin.service-part-suppliers.')->middleware('permission:service_parts.procurement')->group(function (): void {
            Route::get('/', [AdminServicePartsController::class, 'suppliersIndex'])->name('index');
            Route::middleware('throttle:admin-write')->group(function (): void {
                Route::post('/', [AdminServicePartsController::class, 'supplierStore'])->name('store');
                Route::put('/{supplier}', [AdminServicePartsController::class, 'supplierUpdate'])->whereNumber('supplier')->name('update');
            });
        });
        Route::prefix('admin/service-part-purchases')->name('admin.service-part-purchases.')->middleware('permission:service_parts.procurement')->group(function (): void {
            Route::get('/', [AdminServicePartsController::class, 'purchasesIndex'])->name('index');
            Route::get('/{purchaseRequest}', [AdminServicePartsController::class, 'purchaseShow'])->whereNumber('purchaseRequest')->name('show');
            Route::middleware('throttle:admin-write')->group(function (): void {
                Route::post('/', [AdminServicePartsController::class, 'purchaseStore'])->name('store');
                Route::post('/{purchaseRequest}/submit', [AdminServicePartsController::class, 'purchaseSubmit'])->whereNumber('purchaseRequest')->name('submit');
                Route::post('/{purchaseRequest}/order', [AdminServicePartsController::class, 'purchaseOrder'])->whereNumber('purchaseRequest')->name('order');
                Route::post('/{purchaseRequest}/receive', [AdminServicePartsController::class, 'purchaseReceive'])->whereNumber('purchaseRequest')->name('receive');
                Route::post('/{purchaseRequest}/cancel', [AdminServicePartsController::class, 'purchaseCancel'])->whereNumber('purchaseRequest')->name('cancel');
            });
        });
        Route::prefix('admin/receivables')
            ->middleware('permission:receivables.manage')
            ->name('admin.receivables.')
            ->group(function (): void {
                Route::get('/', [AdminReceivablesController::class, 'index'])->name('index');
                Route::get('export.csv', [WebReceivablesController::class, 'csv'])
                    ->middleware('throttle:exports')->name('csv');
                Route::put('settings', [AdminReceivablesController::class, 'updateSettings'])
                    ->middleware('throttle:admin-write')->name('settings.update');
                Route::post('scan', [AdminReceivablesController::class, 'scan'])
                    ->middleware('throttle:admin-write')->name('scan');
                Route::get('{receivable}', [AdminReceivablesController::class, 'show'])
                    ->whereNumber('receivable')->name('show');
                Route::patch('{receivable}', [AdminReceivablesController::class, 'update'])
                    ->whereNumber('receivable')->middleware('throttle:admin-write')->name('update');
                Route::put('{receivable}/plan', [AdminReceivablesController::class, 'plan'])
                    ->whereNumber('receivable')->middleware('throttle:admin-write')->name('plan');
                Route::post('{receivable}/contacts', [AdminReceivablesController::class, 'contact'])
                    ->whereNumber('receivable')->middleware('throttle:admin-write')->name('contacts.store');
                Route::post('{receivable}/reminder', [AdminReceivablesController::class, 'reminder'])
                    ->whereNumber('receivable')->middleware('throttle:admin-write')->name('reminder');
            });
        Route::prefix('admin/field-work')->name('admin.field-work.')->group(function (): void {
            Route::middleware('permission:field_operations.view')->group(function (): void {
                Route::get('/', [AdminFieldOperationsController::class, 'index'])->name('index');
                Route::get('/{workOrder}', [AdminFieldOperationsController::class, 'show'])
                    ->whereNumber('workOrder')->name('show');
            });
            Route::middleware(['permission:field_operations.manage', 'throttle:admin-write'])->group(function (): void {
                Route::patch('/{workOrder}/schedule', [AdminFieldOperationsController::class, 'schedule'])
                    ->whereNumber('workOrder')->name('schedule');
                Route::post('/{workOrder}/en-route', [AdminFieldOperationsController::class, 'enRoute'])
                    ->whereNumber('workOrder')->name('en-route');
                Route::post('/{workOrder}/on-site', [AdminFieldOperationsController::class, 'onSite'])
                    ->whereNumber('workOrder')->name('on-site');
                Route::post('/{workOrder}/complete', [AdminFieldOperationsController::class, 'complete'])
                    ->whereNumber('workOrder')->middleware('throttle:uploads')->name('complete');
                Route::post('/{workOrder}/cancel', [AdminFieldOperationsController::class, 'cancel'])
                    ->whereNumber('workOrder')->name('cancel');
            });
            Route::middleware(['permission:service_parts.manage', 'throttle:admin-write'])->group(function (): void {
                Route::post('/{workOrder}/parts', [AdminFieldWorkOrderPartController::class, 'store'])
                    ->whereNumber('workOrder')->name('parts.store');
                Route::post('/{workOrder}/parts/reserve', [AdminFieldWorkOrderPartController::class, 'reserve'])
                    ->whereNumber('workOrder')->name('parts.reserve');
                Route::delete('/{workOrder}/parts/{line}', [AdminFieldWorkOrderPartController::class, 'destroy'])
                    ->whereNumber('workOrder')->whereNumber('line')->name('parts.destroy');
            });
        });
        Route::get('admin/field-service-teams', [AdminFieldServiceTeamController::class, 'index'])
            ->middleware('permission:field_operations.manage')->name('admin.field-service-teams.index');
        Route::prefix('admin/after-sales')
            ->name('admin.after-sales.')
            ->middleware('permission:after_sales.manage')
            ->group(function (): void {
                Route::get('/', [AdminAfterSalesController::class, 'index'])->name('index');
                Route::get('/attachments/{attachment}', AdminAfterSalesAttachmentController::class)
                    ->whereNumber('attachment')->name('attachments.show');
                Route::get('/{case}', [AdminAfterSalesController::class, 'show'])->whereNumber('case')->name('show');
                Route::patch('/{case}', [AdminAfterSalesController::class, 'update'])
                    ->whereNumber('case')->middleware('throttle:admin-write')->name('update');
                Route::post('/{case}/messages', [AdminAfterSalesController::class, 'message'])
                    ->whereNumber('case')->middleware(['throttle:uploads', 'throttle:admin-write'])->name('messages.store');
                Route::middleware(['permission:after_sales.execute', 'throttle:admin-write'])->group(function (): void {
                    Route::post('/{case}/actions', [AdminAfterSalesActionController::class, 'store'])
                        ->whereNumber('case')->name('actions.store');
                    Route::post('/{case}/actions/{action}/start', [AdminAfterSalesActionController::class, 'start'])
                        ->whereNumber('case')->whereNumber('action')->name('actions.start');
                    Route::post('/{case}/actions/{action}/complete', [AdminAfterSalesActionController::class, 'complete'])
                        ->whereNumber('case')->whereNumber('action')->name('actions.complete');
                    Route::post('/{case}/actions/{action}/cancel', [AdminAfterSalesActionController::class, 'cancel'])
                        ->whereNumber('case')->whereNumber('action')->name('actions.cancel');
                });
            });
        // MOBILE_V0_8_PRODUCT_EDIT_ARCHIVE_RESTORE_BATCH8
        // MOBILE_V1_0_ADMIN_CATALOG_DICTIONARIES_BATCH22
        Route::prefix('admin/catalog/dictionaries')
            ->middleware('permission:catalog.manage_taxonomy')
            ->name('admin.catalog.dictionaries.')
            ->group(function (): void {
                Route::patch('/brands/reorder', [AdminCatalogDictionaryController::class, 'reorderBrands'])
                    ->middleware('throttle:admin-write')->name('brands.reorder');
                Route::get('/product-types/{productType}', [AdminCatalogDictionaryController::class, 'productType'])
                    ->whereNumber('productType')->name('product-types.show');
                Route::patch('/product-types/{productType}/fields/reorder', [AdminCatalogDictionaryController::class, 'reorderTypeFields'])
                    ->whereNumber('productType')->middleware('throttle:admin-write')->name('product-types.fields.reorder');
                Route::delete('/specification-fields/{item}/purge', [AdminCatalogDictionaryController::class, 'purgeSpecificationField'])
                    ->whereNumber('item')->middleware('throttle:admin-write')->name('specification-fields.purge');
                Route::patch('/{resource}/reorder', [AdminCatalogDictionaryController::class, 'reorder'])
                    ->where('resource', 'categories|product-lines|product-types|specification-fields')
                    ->middleware('throttle:admin-write')->name('reorder');
                Route::get('/{resource}', [AdminCatalogDictionaryController::class, 'index'])
                    ->where('resource', 'categories|product-lines|product-types|specification-fields')->name('index');
                Route::post('/{resource}', [AdminCatalogDictionaryController::class, 'store'])
                    ->where('resource', 'categories|product-lines|product-types|specification-fields')
                    ->middleware('throttle:admin-write')->name('store');
                Route::put('/{resource}/{item}', [AdminCatalogDictionaryController::class, 'update'])
                    ->where('resource', 'categories|product-lines|product-types|specification-fields')->whereNumber('item')
                    ->middleware('throttle:admin-write')->name('update');
                Route::delete('/{resource}/{item}', [AdminCatalogDictionaryController::class, 'destroy'])
                    ->where('resource', 'categories|product-lines|product-types|specification-fields')->whereNumber('item')
                    ->middleware('throttle:admin-write')->name('destroy');
            });

        // MOBILE_V0_9_GLOBAL_BRAND_MANAGER_BATCH3
        Route::prefix('admin/catalog/brands')
            ->middleware('permission:catalog.manage_taxonomy')
            ->name('admin.catalog.brands.')
            ->group(function (): void {
                Route::get('/', [AdminCatalogBrandController::class, 'index'])->name('index');
                Route::get('/options', [AdminCatalogBrandController::class, 'options'])->name('options');
                Route::post('/', [AdminCatalogBrandController::class, 'store'])->middleware('throttle:admin-write')->name('store');
                Route::put('/{brand}', [AdminCatalogBrandController::class, 'update'])->whereNumber('brand')->middleware('throttle:admin-write')->name('update');
            });

        Route::prefix('admin/catalog')
            ->middleware('permission:catalog.manage_products')
            ->name('admin.catalog.')
            ->group(function (): void {
                Route::get('/options', [AdminCatalogProductController::class, 'options'])->name('options');
                Route::get('/products', [AdminCatalogProductController::class, 'index'])->name('products.index');
                Route::get('/products/archived', [AdminCatalogProductController::class, 'archived'])->name('products.archived');
                Route::post('/products', [AdminCatalogProductController::class, 'store'])->name('products.store');
                Route::get('/products/{product}', [AdminCatalogProductController::class, 'show'])
                    ->whereNumber('product')->name('products.show');
                Route::put('/products/{product}', [AdminCatalogProductController::class, 'update'])
                    ->whereNumber('product')->middleware('throttle:admin-write')->name('products.update');
                Route::post('/products/{product}/archive', [AdminCatalogProductController::class, 'archive'])
                    ->whereNumber('product')->middleware('throttle:admin-write')->name('products.archive');
                Route::post('/products/{product}/restore', [AdminCatalogProductController::class, 'restore'])
                    ->whereNumber('product')->middleware('throttle:admin-write')->name('products.restore');
                // MOBILE_V1_0_ADMIN_CATALOG_PURGE_TOTAL_PURGE_BATCH24
                Route::get('/products/{product}/deletion', [AdminProductDeletionController::class, 'show'])
                    ->whereNumber('product')->name('products.deletion');
                Route::delete('/products/{product}/purge', [AdminProductDeletionController::class, 'purge'])
                    ->whereNumber('product')->middleware('throttle:admin-write')->name('products.purge');
                Route::delete('/products/{product}/total-purge', [AdminProductDeletionController::class, 'totalPurge'])
                    ->whereNumber('product')->middleware('throttle:admin-write')->name('products.total-purge');
                // MOBILE_V0_8_SUPERADMIN_DIRECT_SALE_BATCH10
                Route::get('/products/{product}/direct-sale/options', [AdminCatalogProductController::class, 'directSaleOptions'])
                    ->whereNumber('product')
                    ->name('products.direct-sale.options');
                Route::post('/products/{product}/direct-sale', [AdminCatalogProductController::class, 'directSale'])
                    ->whereNumber('product')
                    ->middleware('throttle:admin-write')
                    ->name('products.direct-sale.store');
                // MOBILE_V0_8_SHARED_PRODUCT_IMAGE_MANAGER_BATCH9
                Route::get('/products/{product}/images', [AdminCatalogProductController::class, 'imageIndex'])
                    ->whereNumber('product')
                    ->middleware('permission:catalog.manage_images')
                    ->name('products.images.index');
                Route::post('/products/{product}/images', [AdminCatalogProductController::class, 'images'])
                    ->whereNumber('product')
                    ->middleware(['permission:catalog.manage_images', 'throttle:uploads'])
                    ->name('products.images.store');
                Route::post('/products/{product}/images/reorder', [AdminCatalogProductController::class, 'imageReorder'])
                    ->whereNumber('product')
                    ->middleware(['permission:catalog.manage_images', 'throttle:admin-write'])
                    ->name('products.images.reorder');
                Route::post('/products/{product}/images/{image}/primary', [AdminCatalogProductController::class, 'imagePrimary'])
                    ->whereNumber('product')->whereNumber('image')
                    ->middleware(['permission:catalog.manage_images', 'throttle:admin-write'])
                    ->name('products.images.primary');
                Route::post('/products/{product}/images/{image}/rotate', [AdminCatalogProductController::class, 'imageRotate'])
                    ->whereNumber('product')->whereNumber('image')
                    ->middleware(['permission:catalog.manage_images', 'throttle:admin-write'])
                    ->name('products.images.rotate');
                Route::delete('/products/{product}/images/{image}', [AdminCatalogProductController::class, 'imageDestroy'])
                    ->whereNumber('product')->whereNumber('image')
                    ->middleware(['permission:catalog.manage_images', 'throttle:admin-write'])
                    ->name('products.images.destroy');
            });
        Route::get('/me', [AuthTokenController::class, 'me'])->name('me');
        Route::patch('/me', [AccountController::class, 'profile'])->name('me.update');
        Route::put('/me/password', [AccountController::class, 'password'])
            ->middleware('throttle:api-sensitive')
            ->name('me.password');
        Route::put('/me/notification-preferences', [AccountController::class, 'notifications'])->name('me.notification-preferences');
        // MOBILE_V1_0_ACCOUNT_LOGIN_SESSIONS_PARITY_BATCH32
        Route::get('/me/sessions', [AccountController::class, 'sessions'])->name('me.sessions.index');
        Route::delete('/me/sessions/others', [AccountController::class, 'revokeOtherSessions'])
            ->middleware('throttle:api-sensitive')->name('me.sessions.others');
        Route::delete('/me/sessions/{kind}/{session}', [AccountController::class, 'revokeSession'])
            ->where('kind', 'api|web')->whereNumber('session')
            ->middleware('throttle:api-sensitive')->name('me.sessions.destroy');
        Route::delete('/auth/token', [AuthTokenController::class, 'destroy'])->name('auth.token.destroy');

        Route::get('/devices', [MobileDeviceController::class, 'index'])->name('devices.index');
        Route::post('/devices', [MobileDeviceController::class, 'store'])->middleware('throttle:api-devices')->name('devices.store');
        Route::patch('/devices/{mobileDevice}', [MobileDeviceController::class, 'update'])->whereNumber('mobileDevice')->middleware('throttle:api-devices')->name('devices.update');
        Route::delete('/devices/{mobileDevice}', [MobileDeviceController::class, 'destroy'])->whereNumber('mobileDevice')->middleware('throttle:api-devices')->name('devices.destroy');

        Route::middleware('permission:catalog.view')->group(function (): void {
            Route::get('/catalog/filters', [CatalogOptionsController::class, 'show'])->name('catalog.filters');
            Route::get('/products', [CatalogController::class, 'index'])->name('products.index');
            Route::get('/products/{slug}', [CatalogController::class, 'show'])->name('products.show');
        });

        Route::get('/orders/options', [OrderOptionsController::class, 'show'])
            ->middleware('permission:orders.create')
            ->name('orders.options');
        Route::middleware('permission:orders.manage')->group(function (): void {
            Route::get('/orders/assigned', [OrderController::class, 'assigned'])->name('orders.assigned.index');
            Route::get('/orders/assigned/{order}', [OrderController::class, 'assignedShow'])->whereNumber('order')->name('orders.assigned.show');
        });
        Route::middleware('permission:orders.view_own')->group(function (): void {
            Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
            Route::get('/orders/{order}/post-create', [OrderController::class, 'postCreate'])->whereNumber('order')->name('orders.post-create');
            Route::get('/orders/{order}/delivery-proof', [OrderController::class, 'deliveryProof'])->whereNumber('order')->name('orders.delivery.proof');
            Route::get('/orders/{order}', [OrderController::class, 'show'])->whereNumber('order')->name('orders.show');
        });
        Route::post('/orders', [OrderController::class, 'store'])
            ->middleware(['permission:orders.create', 'throttle:orders'])
            ->name('orders.store');
        Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])
            ->whereNumber('order')
            ->middleware('permission:orders.cancel_own')
            ->name('orders.cancel');

        Route::middleware('permission:payments.view_own')->group(function (): void {
            Route::get('/orders/{order}/payments/{payment}/proof', [OrderController::class, 'paymentProof'])
                ->whereNumber('order')->whereNumber('payment')->name('orders.payments.proof');
        });
        Route::post('/orders/{order}/payments/proof', [OrderController::class, 'storePaymentProof'])
            ->whereNumber('order')
            ->middleware(['permission:payments.upload_proof', 'throttle:uploads'])
            ->name('orders.payments.proof.store');

        Route::middleware('permission:invoices.view_own')->group(function (): void {
            Route::get('/orders/{order}/documents/confirmation.pdf', [OrderController::class, 'confirmationPdf'])
                ->whereNumber('order')->name('orders.documents.confirmation');
            Route::get('/orders/{order}/documents/{document}.pdf', [OrderController::class, 'documentPdf'])
                ->whereNumber('order')->whereNumber('document')->name('orders.documents.show');
        });

        Route::middleware('permission:after_sales.view_own')->group(function (): void {
            Route::get('/after-sales', [AfterSalesController::class, 'index'])->name('after-sales.index');
            Route::get('/after-sales/{case}', [AfterSalesController::class, 'show'])->whereNumber('case')->name('after-sales.show');
            Route::post('/after-sales/{case}/messages', [AfterSalesController::class, 'message'])
                ->whereNumber('case')->middleware('throttle:uploads')->name('after-sales.messages.store');
        });
        Route::middleware('permission:after_sales.create')->group(function (): void {
            Route::get('/orders/{order}/after-sales/options', [AfterSalesController::class, 'options'])
                ->whereNumber('order')->name('after-sales.options');
            Route::post('/orders/{order}/after-sales', [AfterSalesController::class, 'store'])
                ->whereNumber('order')->middleware('throttle:uploads')->name('after-sales.store');
        });
        Route::get('/after-sales/attachments/{attachment}', AfterSalesAttachmentController::class)
            ->whereNumber('attachment')->name('after-sales.attachments.show');
        Route::get('/field-work-order-attachments/{attachment}', FieldWorkOrderAttachmentController::class)
            ->whereNumber('attachment')->name('field-work-order-attachments.show');

        Route::middleware('permission:warranties.view_own')->group(function (): void {
            Route::get('/warranties', [WarrantyController::class, 'index'])->name('warranties.index');
            Route::get('/warranties/{warranty}.pdf', [WarrantyController::class, 'pdf'])->whereNumber('warranty')->name('warranties.pdf');
            Route::get('/warranties/{warranty}', [WarrantyController::class, 'show'])->whereNumber('warranty')->name('warranties.show');
        });

        Route::middleware('permission:commissions.view_own')->group(function (): void {
            Route::get('/commissions', [CommissionController::class, 'index'])->name('commissions.index');
            Route::get('/commissions/{commission}', [CommissionController::class, 'show'])->whereNumber('commission')->name('commissions.show');
        });

        Route::middleware('permission:notifications.view')->group(function (): void {
            Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
            Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
            Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
        });
    });
});

// MOBILE_V0_7_INVENTORY_ADMIN_API_BATCH2
Route::prefix('v1')->name('api.v1.')->middleware(['auth:sanctum', 'active'])->group(function (): void {
    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/inventory', [AdminInventoryController::class, 'index'])
            ->middleware('permission:stock.view')
            ->name('inventory.index');
        Route::get('/stock-movements', [AdminInventoryController::class, 'movements'])
            ->middleware('permission:stock.view')
            ->name('stock-movements.index');
        Route::post('/stock/{product}/adjust', [AdminInventoryController::class, 'adjust'])
            ->whereNumber('product')
            ->middleware(['permission:stock.adjust', 'throttle:admin-write'])
            ->name('stock.adjust');
        Route::post('/inventory/receipts', [AdminInventoryController::class, 'receive'])
            ->middleware(['permission:inventory.receive', 'throttle:admin-write'])
            ->name('inventory.receipts.store');
        Route::post('/inventory/counts', [AdminInventoryController::class, 'count'])
            ->middleware(['permission:inventory.count', 'throttle:admin-write'])
            ->name('inventory.counts.store');
        Route::get('/inventory.csv', [AdminInventoryController::class, 'csv'])
            ->middleware(['permission:inventory.export', 'throttle:exports'])
            ->name('inventory.csv');
    });
});
