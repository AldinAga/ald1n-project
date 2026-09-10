<?php

declare(strict_types=1);

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountSessionController;
use App\Http\Controllers\AfterSalesAttachmentController;
use App\Http\Controllers\AfterSalesController;
use App\Http\Controllers\Admin\AfterSalesController as AdminAfterSalesController;
use App\Http\Controllers\Admin\AfterSalesActionController as AdminAfterSalesActionController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\AutomationController;
use App\Http\Controllers\Admin\BankAccountController;
use App\Http\Controllers\Admin\CatalogDictionaryController;
use App\Http\Controllers\Admin\BrandManagerController;
use App\Http\Controllers\Admin\CommissionController;
use App\Http\Controllers\Admin\CustomerPortalController as AdminCustomerPortalController;
use App\Http\Controllers\Admin\DataQualityController;
use App\Http\Controllers\Admin\ExchangeRateController;
use App\Http\Controllers\Admin\FieldOperationsController;
use App\Http\Controllers\Admin\FieldServiceTeamController;
use App\Http\Controllers\Admin\FieldWorkOrderPartController;
use App\Http\Controllers\Admin\ServicePartController;
use App\Http\Controllers\Admin\ServicePartSupplierController;
use App\Http\Controllers\Admin\ServicePartPurchaseRequestController;
use App\Http\Controllers\Admin\CourierServiceController;
use App\Http\Controllers\Admin\DirectSaleController;
use App\Http\Controllers\Admin\DocumentSettingsController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\OrderDocumentController as AdminOrderDocumentController;
use App\Http\Controllers\Admin\OrderEmailSettingsController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ManagementReportController;
use App\Http\Controllers\Admin\ReportScheduleController;
use App\Http\Controllers\Admin\ReceivablesController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\OrderShipmentController as AdminOrderShipmentController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProductBulkController;
use App\Http\Controllers\Admin\ProductPurchaseCostController;
use App\Http\Controllers\Admin\ProductImageController;
use App\Http\Controllers\Admin\PortalConversationController as AdminPortalConversationController;
use App\Http\Controllers\Admin\SiteAppearanceController;
use App\Http\Controllers\Admin\StockAdjustmentController;
use App\Http\Controllers\Admin\StockMovementController;
use App\Http\Controllers\Admin\SystemHealthController;
use App\Http\Controllers\Admin\TurnstileSettingsController;
use App\Http\Controllers\Admin\SettingsHubController;
use App\Http\Controllers\Admin\ModuleSettingsController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\UserGroupController;
use App\Http\Controllers\Admin\WarrantyController as AdminWarrantyController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\GoogleWebAuthController;
use App\Http\Controllers\Auth\CustomerActivationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CommissionController as UserCommissionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderDeliveryController;
use App\Http\Controllers\OrderDocumentController;
use App\Http\Controllers\OrderPaymentController;
use App\Http\Controllers\OrderShipmentProofController;
use App\Http\Controllers\PortalConversationController;
use App\Http\Controllers\ProductMediaController;
use App\Http\Controllers\ProductMediaDownloadController;
use App\Http\Controllers\FieldWorkOrderAttachmentController;
use App\Http\Controllers\WarrantyController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login')->name('login.store');
    Route::get('/auth/google', [GoogleWebAuthController::class, 'redirect'])
        ->middleware('throttle:20,1')
        ->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleWebAuthController::class, 'callback'])
        ->middleware('throttle:20,1')
        ->name('auth.google.callback');

    Route::get('/forgot-password', [PasswordResetController::class, 'createLinkRequest'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'storeLinkRequest'])
        ->middleware('throttle:password-reset-link')
        ->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'create'])
        ->where('token', '[A-Za-z0-9]{80}')
        ->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'store'])
        ->middleware('throttle:password-reset')
        ->name('password.update');

    Route::get('/activate-account/{token}', [CustomerActivationController::class, 'show'])
        ->where('token', '[A-Za-z0-9]{80}')
        ->name('customer-activation.show');
    Route::post('/activate-account', [CustomerActivationController::class, 'store'])
        ->middleware('throttle:customer-activation')
        ->name('customer-activation.store');
});
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'active', 'tracked-session'])->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::permanentRedirect('/portal', '/');
    Route::get('/account', [AccountController::class, 'show'])->name('account.show');
    Route::get('/media/product/{image}', ProductMediaController::class)->whereNumber('image')->name('media.product');
    Route::get('/media/product/{image}/download', ProductMediaDownloadController::class)->whereNumber('image')->name('media.product.download');
    Route::put('/account/profile', [AccountController::class, 'profile'])->name('account.profile');
    Route::put('/account/password', [AccountController::class, 'password'])->name('account.password');
    Route::put('/account/notifications', [AccountController::class, 'notifications'])->name('account.notifications');
    Route::delete('/account/sessions/{loginSession}', [AccountSessionController::class, 'destroy'])->whereNumber('loginSession')->name('account.sessions.destroy');
    Route::delete('/account/sessions', [AccountSessionController::class, 'destroyOthers'])->name('account.sessions.destroy-others');

    Route::middleware('permission:orders.view_own')->group(function (): void {
        Route::get('/portal/messages', [PortalConversationController::class, 'index'])->name('portal.messages.index');
        Route::post('/portal/messages', [PortalConversationController::class, 'store'])->middleware('throttle:portal-messages')->name('portal.messages.store');
        Route::get('/portal/messages/{conversation}', [PortalConversationController::class, 'show'])->whereNumber('conversation')->name('portal.messages.show');
        Route::post('/portal/messages/{conversation}', [PortalConversationController::class, 'reply'])->whereNumber('conversation')->middleware('throttle:portal-messages')->name('portal.messages.reply');
    });

    Route::middleware('permission:catalog.view')->group(function (): void {
        Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
        Route::get('/catalog/quick-search', [CatalogController::class, 'quickSearch'])
            ->middleware('throttle:120,1')
            ->name('catalog.quick-search');
        Route::get('/catalog/{slug}', [CatalogController::class, 'show'])->name('catalog.show');
    });

    Route::middleware('permission:orders.create')->group(function (): void {
        Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
        Route::post('/cart/items/{product}', [CartController::class, 'store'])
            ->whereNumber('product')
            ->name('cart.items.store');
        Route::patch('/cart/items/{product}', [CartController::class, 'update'])
            ->whereNumber('product')
            ->name('cart.items.update');
        Route::delete('/cart/items/{product}', [CartController::class, 'destroy'])
            ->whereNumber('product')
            ->name('cart.items.destroy');
        Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');
    });

    Route::get('/global-search', [\App\Http\Controllers\GlobalCommandSearchController::class, 'search'])
        ->middleware('throttle:120,1')
        ->name('global-search.quick');

    Route::middleware('permission:orders.view_own')->group(function (): void {
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->whereNumber('order')->name('orders.show');
    });
    Route::middleware('permission:orders.create')->group(function (): void {
        Route::get('/order/new', [OrderController::class, 'create'])->name('orders.create');
        Route::post('/orders', [OrderController::class, 'store'])->middleware('throttle:orders')->name('orders.store');
    });
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])
        ->whereNumber('order')
        ->middleware('permission:orders.cancel_own')
        ->name('orders.cancel');

    Route::middleware('permission:invoices.view_own')->group(function (): void {
        Route::get('/orders/{order}/documents/confirmation', [OrderDocumentController::class, 'confirmation'])->whereNumber('order')->name('orders.documents.confirmation');
        Route::get('/orders/{order}/documents/{document}.pdf', [OrderDocumentController::class, 'show'])->whereNumber('order')->whereNumber('document')->name('orders.documents.show');
    });

    Route::middleware('permission:payments.view_own')->group(function (): void {
        Route::get('/orders/{order}/payments/{payment}/proof', [OrderPaymentController::class, 'proof'])
            ->whereNumber('order')->whereNumber('payment')->name('orders.payments.proof');
    });
    Route::post('/orders/{order}/payments/proof', [OrderPaymentController::class, 'storeProof'])
        ->whereNumber('order')->middleware(['permission:payments.upload_proof', 'throttle:uploads'])->name('orders.payments.proof.store');
    Route::get('/orders/{order}/delivery-proof', [OrderDeliveryController::class, 'proof'])
        ->whereNumber('order')->name('orders.delivery.proof');
    Route::get('/orders/{order}/shipment-proof', OrderShipmentProofController::class)
        ->whereNumber('order')->name('orders.shipment.proof');

    Route::middleware('permission:after_sales.view_own')->group(function (): void {
        Route::get('/after-sales', [AfterSalesController::class, 'index'])->name('after-sales.index');
        Route::get('/after-sales/{case}', [AfterSalesController::class, 'show'])->whereNumber('case')->name('after-sales.show');
        Route::post('/after-sales/{case}/messages', [AfterSalesController::class, 'message'])
            ->whereNumber('case')->middleware('throttle:uploads')->name('after-sales.messages.store');
    });
    Route::middleware('permission:after_sales.create')->group(function (): void {
        Route::get('/orders/{order}/after-sales/create', [AfterSalesController::class, 'create'])
            ->whereNumber('order')->name('after-sales.create');
        Route::post('/orders/{order}/after-sales', [AfterSalesController::class, 'store'])
            ->whereNumber('order')->middleware('throttle:uploads')->name('after-sales.store');
    });
    Route::get('/after-sales/attachments/{attachment}', AfterSalesAttachmentController::class)
        ->whereNumber('attachment')->name('after-sales.attachments.show');
    Route::get('/field-work-order-attachments/{attachment}', FieldWorkOrderAttachmentController::class)
        ->whereNumber('attachment')->name('field-work-order-attachments.show');

    Route::middleware('permission:warranties.view_own')->group(function (): void {
        Route::get('/warranties', [WarrantyController::class, 'index'])->name('warranties.index');
        Route::get('/warranties/{warranty}', [WarrantyController::class, 'show'])->whereNumber('warranty')->name('warranties.show');
    });
    Route::get('/warranties/{warranty}.pdf', [WarrantyController::class, 'pdf'])
        ->whereNumber('warranty')->name('warranties.pdf');

    Route::get('/commissions', [UserCommissionController::class, 'index'])
        ->middleware('permission:commissions.view_own')
        ->name('commissions.index');

    Route::middleware('permission:notifications.view')->group(function (): void {
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    });

    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::middleware('permission:catalog.manage_products')->group(function (): void {
            Route::get('/catalog', [AdminProductController::class, 'index'])->name('products.index');
            Route::get('/catalog/archived', [AdminProductController::class, 'archived'])->name('products.archived');
            Route::get('/catalog/create', [AdminProductController::class, 'create'])->name('products.create');
            Route::post('/catalog', [AdminProductController::class, 'store'])->name('products.store');
            Route::get('/catalog/bulk', [ProductBulkController::class, 'index'])->name('products.bulk');
            Route::post('/catalog/bulk', [ProductBulkController::class, 'process'])->name('products.bulk.process');
            // MOBILE_V0_8_SUPERADMIN_PURCHASE_COST_ENTRY_BATCH3
            Route::get('/catalog/purchase-costs', [ProductPurchaseCostController::class, 'index'])->name('products.purchase-costs');
            Route::post('/catalog/purchase-costs', [ProductPurchaseCostController::class, 'update'])->middleware('throttle:admin-write')->name('products.purchase-costs.update');
            Route::post('/catalog/name-preview', [AdminProductController::class, 'namePreview'])->name('products.name-preview');
            Route::post('/catalog/{product}/direct-sale', DirectSaleController::class)
                ->whereNumber('product')
                ->middleware('throttle:admin-write')
                ->name('products.direct-sale');
            Route::get('/catalog/{product}', [AdminProductController::class, 'edit'])
                ->whereNumber('product')
                ->name('products.manage');
            Route::get('/catalog/{product}/edit', [AdminProductController::class, 'edit'])->name('products.edit');
            Route::get('/catalog/{product}/clone', [AdminProductController::class, 'cloneForm'])->name('products.clone');
            Route::post('/catalog/{product}/clone', [AdminProductController::class, 'cloneStore'])->name('products.clone.store');
            Route::post('/catalog/{product}/regenerate-name', [AdminProductController::class, 'regenerateName'])->name('products.regenerate-name');
            Route::put('/catalog/{product}', [AdminProductController::class, 'update'])->name('products.update');
            // V0_8_PRODUCT_STATUS_LIGHTWEIGHT_CONTROL_BATCH2
            Route::patch('/catalog/{product}/status', \App\Http\Controllers\Admin\ProductStatusController::class)->whereNumber('product')->middleware('throttle:admin-write')->name('products.status');
            Route::delete('/catalog/{product}', [AdminProductController::class, 'archive'])->name('products.archive');
            Route::post('/catalog/{product}/restore', [AdminProductController::class, 'restore'])->name('products.restore');
            Route::delete('/catalog/{product}/purge', [AdminProductController::class, 'purge'])->name('products.purge');
            Route::delete('/catalog/{product}/total-purge', [AdminProductController::class, 'totalPurge'])->name('products.total-purge');
        });
        Route::middleware('permission:catalog.manage_images')->group(function (): void {
            Route::get('/catalog/{product}/images', [ProductImageController::class, 'index'])->name('products.images.index');
            Route::post('/catalog/{product}/images', [ProductImageController::class, 'store'])->middleware('throttle:uploads')->name('products.images.store');
            Route::patch('/catalog/{product}/images/{image}/primary', [ProductImageController::class, 'primary'])->name('products.images.primary');
            Route::patch('/catalog/{product}/images/{image}/rotate', [ProductImageController::class, 'rotate'])->name('products.images.rotate');
            Route::put('/catalog/{product}/images/reorder', [ProductImageController::class, 'reorder'])->name('products.images.reorder');
            Route::delete('/catalog/{product}/images/{image}', [ProductImageController::class, 'destroy'])->name('products.images.destroy');
        });
        Route::middleware('permission:catalog.audit')->group(function (): void {
            Route::get('/data-quality', [DataQualityController::class, 'index'])->name('data-quality.index');
            Route::post('/data-quality/repair', [DataQualityController::class, 'repair'])->name('data-quality.repair');
            Route::get('/data-quality/export', [DataQualityController::class, 'export'])->name('data-quality.export');
        });
        Route::middleware('permission:catalog.manage_taxonomy')->group(function (): void {
            // MOBILE_V0_9_GLOBAL_BRAND_MANAGER_BATCH3
            Route::get('/catalog-settings/brands', [BrandManagerController::class, 'index'])->name('brand-manager.index');
            Route::post('/catalog-settings/brands', [BrandManagerController::class, 'store'])->middleware('throttle:admin-write')->name('brand-manager.store');
            Route::put('/catalog-settings/brands/{brand}', [BrandManagerController::class, 'update'])->whereNumber('brand')->middleware('throttle:admin-write')->name('brand-manager.update');
            Route::get('/catalog-settings/product-type/{productType:slug}', [CatalogDictionaryController::class, 'productType'])->name('dictionary.product-type');
            Route::patch('/catalog-settings/product-type/{productType:slug}/fields/reorder', [CatalogDictionaryController::class, 'reorderTypeFields'])->name('dictionary.product-type.fields.reorder');
            Route::patch('/catalog-settings/{resource}/reorder', [CatalogDictionaryController::class, 'reorder'])->where('resource', 'categories|brands|product-lines|product-types|specification-fields')->name('dictionary.reorder');
            Route::delete('/catalog-settings/{resource}/{item}/purge', [CatalogDictionaryController::class, 'purge'])->where('resource', 'specification-fields')->name('dictionary.purge');
            Route::get('/catalog-settings/{resource}', [CatalogDictionaryController::class, 'index'])->where('resource', 'categories|brands|product-lines|product-types|specification-fields')->name('dictionary.index');
            Route::post('/catalog-settings/{resource}', [CatalogDictionaryController::class, 'store'])->where('resource', 'categories|brands|product-lines|product-types|specification-fields')->name('dictionary.store');
            Route::put('/catalog-settings/{resource}/{item}', [CatalogDictionaryController::class, 'update'])->where('resource', 'categories|brands|product-lines|product-types|specification-fields')->name('dictionary.update');
            Route::delete('/catalog-settings/{resource}/{item}', [CatalogDictionaryController::class, 'destroy'])->where('resource', 'categories|brands|product-lines|product-types|specification-fields')->name('dictionary.destroy');
        });
        Route::middleware('permission:orders.manage')->group(function (): void {
            Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
            Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->whereNumber('order')->name('orders.show');
            Route::get('/orders/archived', [AdminOrderController::class, 'archived'])->name('orders.archived');
            Route::post('/orders/{order}/archive', [AdminOrderController::class, 'archive'])->whereNumber('order')->name('orders.archive');
            Route::post('/orders/archived/{orderId}/restore', [AdminOrderController::class, 'restore'])->whereNumber('orderId')->name('orders.restore');
            Route::delete('/orders/archived/{orderId}/purge', [AdminOrderController::class, 'purge'])->whereNumber('orderId')->name('orders.purge');
            Route::patch('/orders/{order}/status', [AdminOrderController::class, 'status'])->whereNumber('order')->name('orders.status');
            Route::post('/orders/{order}/complete', [AdminOrderController::class, 'complete'])
                ->whereNumber('order')->middleware('permission:orders.confirm_delivery')->name('orders.complete');
            Route::post('/orders/{order}/reopen', [AdminOrderController::class, 'reopen'])
                ->whereNumber('order')->middleware('permission:orders.reopen')->name('orders.reopen');
            Route::patch('/orders/{order}/payment', [AdminOrderController::class, 'payment'])->whereNumber('order')->name('orders.payment');
            Route::patch('/orders/{order}/tracking', [AdminOrderController::class, 'tracking'])->whereNumber('order')->name('orders.tracking');
            Route::post('/orders/{order}/shipment', [AdminOrderShipmentController::class, 'store'])
                ->whereNumber('order')->middleware('throttle:admin-write')->name('orders.shipment.store');
            Route::post('/orders/{order}/accept', [AdminOrderController::class, 'accept'])->whereNumber('order')->name('orders.accept');
            Route::patch('/orders/{order}/deadlines', [AdminOrderController::class, 'deadlines'])->whereNumber('order')->name('orders.deadlines');
        });
        Route::middleware('permission:receivables.manage')->group(function (): void {
            Route::get('/receivables', [ReceivablesController::class, 'index'])->name('receivables.index');
            Route::get('/receivables/export.csv', [ReceivablesController::class, 'csv'])->middleware('throttle:exports')->name('receivables.csv');
            Route::put('/receivables/settings', [ReceivablesController::class, 'updateSettings'])->name('receivables.settings.update');
            Route::post('/receivables/scan', [ReceivablesController::class, 'scan'])->name('receivables.scan');
            Route::get('/receivables/{receivable}', [ReceivablesController::class, 'show'])->whereNumber('receivable')->name('receivables.show');
            Route::patch('/receivables/{receivable}', [ReceivablesController::class, 'update'])->whereNumber('receivable')->name('receivables.update');
            Route::put('/receivables/{receivable}/plan', [ReceivablesController::class, 'plan'])->whereNumber('receivable')->name('receivables.plan');
            Route::post('/receivables/{receivable}/payments', [ReceivablesController::class, 'payment'])->whereNumber('receivable')->middleware(['permission:payments.manage', 'throttle:admin-write'])->name('receivables.payments.store');
            Route::post('/receivables/{receivable}/contacts', [ReceivablesController::class, 'contact'])->whereNumber('receivable')->name('receivables.contacts.store');
            Route::post('/receivables/{receivable}/reminder', [ReceivablesController::class, 'reminder'])->whereNumber('receivable')->name('receivables.reminder');
        });
        Route::middleware('permission:after_sales.manage')->group(function (): void {
            Route::get('/after-sales', [AdminAfterSalesController::class, 'index'])->name('after-sales.index');
            Route::get('/after-sales/{case}', [AdminAfterSalesController::class, 'show'])->whereNumber('case')->name('after-sales.show');
            Route::patch('/after-sales/{case}', [AdminAfterSalesController::class, 'update'])->whereNumber('case')->name('after-sales.update');
            Route::post('/after-sales/{case}/messages', [AdminAfterSalesController::class, 'message'])
                ->whereNumber('case')->middleware('throttle:uploads')->name('after-sales.messages.store');
        });
        Route::middleware('permission:after_sales.execute')->group(function (): void {
            Route::post('/after-sales/{case}/actions', [AdminAfterSalesActionController::class, 'store'])
                ->whereNumber('case')->name('after-sales.actions.store');
            Route::post('/after-sales/{case}/actions/{action}/start', [AdminAfterSalesActionController::class, 'start'])
                ->whereNumber('case')->whereNumber('action')->name('after-sales.actions.start');
            Route::post('/after-sales/{case}/actions/{action}/complete', [AdminAfterSalesActionController::class, 'complete'])
                ->whereNumber('case')->whereNumber('action')->name('after-sales.actions.complete');
            Route::post('/after-sales/{case}/actions/{action}/cancel', [AdminAfterSalesActionController::class, 'cancel'])
                ->whereNumber('case')->whereNumber('action')->name('after-sales.actions.cancel');
        });
        Route::middleware('permission:field_operations.view')->group(function (): void {
            Route::get('/field-operations', [FieldOperationsController::class, 'index'])->name('field-operations.index');
            Route::get('/field-operations/{workOrder}', [FieldOperationsController::class, 'show'])->whereNumber('workOrder')->name('field-operations.show');
        });
        Route::middleware('permission:field_operations.manage')->group(function (): void {
            Route::patch('/field-operations/{workOrder}/schedule', [FieldOperationsController::class, 'schedule'])->whereNumber('workOrder')->name('field-operations.schedule');
            Route::post('/field-operations/{workOrder}/en-route', [FieldOperationsController::class, 'enRoute'])->whereNumber('workOrder')->name('field-operations.en-route');
            Route::post('/field-operations/{workOrder}/on-site', [FieldOperationsController::class, 'onSite'])->whereNumber('workOrder')->name('field-operations.on-site');
            Route::post('/field-operations/{workOrder}/complete', [FieldOperationsController::class, 'complete'])->whereNumber('workOrder')->middleware('throttle:uploads')->name('field-operations.complete');
            Route::post('/field-operations/{workOrder}/cancel', [FieldOperationsController::class, 'cancel'])->whereNumber('workOrder')->name('field-operations.cancel');
            Route::get('/field-service-teams', [FieldServiceTeamController::class, 'index'])->name('field-service-teams.index');
            Route::post('/field-service-teams', [FieldServiceTeamController::class, 'store'])->name('field-service-teams.store');
            Route::put('/field-service-teams/{team}', [FieldServiceTeamController::class, 'update'])->whereNumber('team')->name('field-service-teams.update');
            Route::delete('/field-service-teams/{team}', [FieldServiceTeamController::class, 'destroy'])->whereNumber('team')->name('field-service-teams.destroy');
        });

        Route::get('/service-parts', [ServicePartController::class, 'index'])->middleware('permission:service_parts.view')->name('service-parts.index');
        Route::middleware('permission:service_parts.manage')->group(function (): void {
            Route::post('/service-parts', [ServicePartController::class, 'store'])->name('service-parts.store');
            Route::put('/service-parts/{part}', [ServicePartController::class, 'update'])->whereNumber('part')->name('service-parts.update');
            Route::post('/service-parts/{part}/adjust', [ServicePartController::class, 'adjust'])->whereNumber('part')->name('service-parts.adjust');
            Route::post('/field-operations/{workOrder}/parts', [FieldWorkOrderPartController::class, 'store'])->whereNumber('workOrder')->name('field-operations.parts.store');
            Route::post('/field-operations/{workOrder}/parts/reserve', [FieldWorkOrderPartController::class, 'reserve'])->whereNumber('workOrder')->name('field-operations.parts.reserve');
            Route::delete('/field-operations/{workOrder}/parts/{line}', [FieldWorkOrderPartController::class, 'destroy'])->whereNumber('workOrder')->whereNumber('line')->name('field-operations.parts.destroy');
        });
        Route::middleware('permission:warranties.manage')->group(function (): void {
            Route::get('/warranties', [AdminWarrantyController::class, 'index'])->name('warranties.index');
            Route::post('/warranties/rules', [AdminWarrantyController::class, 'storeRule'])->name('warranties.rules.store');
            Route::put('/warranties/rules/{rule}', [AdminWarrantyController::class, 'updateRule'])->whereNumber('rule')->name('warranties.rules.update');
            Route::post('/warranties/backfill', [AdminWarrantyController::class, 'backfill'])->middleware('throttle:admin-write')->name('warranties.backfill');
            Route::get('/warranties/{warranty}', [AdminWarrantyController::class, 'show'])->whereNumber('warranty')->name('warranties.show');
            Route::put('/warranties/{warranty}', [AdminWarrantyController::class, 'update'])->whereNumber('warranty')->name('warranties.update');
            Route::post('/warranties/{warranty}/void', [AdminWarrantyController::class, 'void'])->whereNumber('warranty')->name('warranties.void');
            Route::post('/warranties/{warranty}/maintenance/{record}/schedule', [AdminWarrantyController::class, 'schedule'])->whereNumber('warranty')->whereNumber('record')->name('warranties.maintenance.schedule');
            Route::post('/warranties/{warranty}/maintenance/{record}/complete', [AdminWarrantyController::class, 'complete'])->whereNumber('warranty')->whereNumber('record')->name('warranties.maintenance.complete');
        });

        Route::middleware('permission:service_parts.procurement')->group(function (): void {
            Route::get('/service-part-suppliers', [ServicePartSupplierController::class, 'index'])->name('service-part-suppliers.index');
            Route::post('/service-part-suppliers', [ServicePartSupplierController::class, 'store'])->name('service-part-suppliers.store');
            Route::put('/service-part-suppliers/{supplier}', [ServicePartSupplierController::class, 'update'])->whereNumber('supplier')->name('service-part-suppliers.update');
            Route::get('/service-part-purchases', [ServicePartPurchaseRequestController::class, 'index'])->name('service-part-purchases.index');
            Route::post('/service-part-purchases', [ServicePartPurchaseRequestController::class, 'store'])->name('service-part-purchases.store');
            Route::get('/service-part-purchases/{purchaseRequest}', [ServicePartPurchaseRequestController::class, 'show'])->whereNumber('purchaseRequest')->name('service-part-purchases.show');
            Route::post('/service-part-purchases/{purchaseRequest}/submit', [ServicePartPurchaseRequestController::class, 'submit'])->whereNumber('purchaseRequest')->name('service-part-purchases.submit');
            Route::post('/service-part-purchases/{purchaseRequest}/order', [ServicePartPurchaseRequestController::class, 'order'])->whereNumber('purchaseRequest')->name('service-part-purchases.order');
            Route::post('/service-part-purchases/{purchaseRequest}/receive', [ServicePartPurchaseRequestController::class, 'receive'])->whereNumber('purchaseRequest')->name('service-part-purchases.receive');
            Route::post('/service-part-purchases/{purchaseRequest}/cancel', [ServicePartPurchaseRequestController::class, 'cancel'])->whereNumber('purchaseRequest')->name('service-part-purchases.cancel');
        });

        Route::post('/orders/{order}/internal-notes', [AdminOrderController::class, 'note'])
            ->whereNumber('order')->middleware('permission:orders.internal_notes')->name('orders.notes.store');
        Route::patch('/orders/{order}/reassign', [AdminOrderController::class, 'reassign'])
            ->whereNumber('order')->middleware('permission:orders.reassign')->name('orders.reassign');

        Route::middleware('permission:commissions.manage')->group(function (): void {
            Route::get('/commissions', [CommissionController::class, 'index'])->name('commissions.index');
            Route::patch('/commissions/{commission}/status', [CommissionController::class, 'transition'])->name('commissions.transition');
            Route::post('/commissions/bulk-pay', [CommissionController::class, 'bulkPay'])->name('commissions.bulk-pay');
            Route::get('/commissions.csv', [CommissionController::class, 'csv'])->middleware('throttle:exports')->name('commissions.csv');
            Route::get('/commissions.pdf', [CommissionController::class, 'pdf'])->middleware('throttle:exports')->name('commissions.pdf');
        });
        Route::middleware('permission:reports.view')->group(function (): void {
            Route::get('/reports', [ManagementReportController::class, 'index'])->name('reports.index');
            Route::get('/reports/management.pdf', [ManagementReportController::class, 'pdf'])->middleware(['permission:reports.export','throttle:exports'])->name('reports.management.pdf');
            Route::get('/reports/management.csv', [ManagementReportController::class, 'csv'])->middleware(['permission:reports.export','throttle:exports'])->name('reports.management.csv');
            Route::get('/reports/orders.pdf', [ReportController::class, 'pdf'])->middleware(['permission:reports.export','throttle:exports'])->name('reports.orders.pdf');
            Route::get('/reports/orders.csv', [ReportController::class, 'csv'])->middleware(['permission:reports.export','throttle:exports'])->name('reports.orders.csv');
            Route::get('/reports/payments.csv', [ReportController::class, 'paymentsCsv'])->middleware(['permission:reports.export','throttle:exports'])->name('reports.payments.csv');
            Route::get('/reports/inventory.csv', [ReportController::class, 'inventoryCsv'])->middleware(['permission:inventory.export','throttle:exports'])->name('reports.inventory.csv');
        });
        Route::middleware('permission:reports.manage')->group(function (): void {
            Route::post('/reports/schedules', [ReportScheduleController::class, 'store'])->name('report-schedules.store');
            Route::put('/reports/schedules/{schedule}', [ReportScheduleController::class, 'update'])->whereNumber('schedule')->name('report-schedules.update');
            Route::patch('/reports/schedules/{schedule}/toggle', [ReportScheduleController::class, 'toggle'])->whereNumber('schedule')->name('report-schedules.toggle');
            Route::post('/reports/schedules/{schedule}/run', [ReportScheduleController::class, 'run'])->whereNumber('schedule')->middleware('throttle:admin-write')->name('report-schedules.run');
            Route::delete('/reports/schedules/{schedule}', [ReportScheduleController::class, 'destroy'])->whereNumber('schedule')->name('report-schedules.destroy');
            Route::post('/reports/deliveries/{delivery}/retry', [ReportScheduleController::class, 'retry'])->whereNumber('delivery')->middleware('throttle:admin-write')->name('report-deliveries.retry');
        });
        Route::middleware('permission:invoices.manage')->group(function (): void {
            Route::post('/orders/{order}/documents', [AdminOrderDocumentController::class, 'store'])->whereNumber('order')->name('orders.documents.store');
            Route::post('/orders/{order}/invoice.pdf', [AdminOrderDocumentController::class, 'invoicePdf'])
                ->whereNumber('order')
                ->middleware('throttle:admin-write')
                ->name('orders.invoice.pdf');
            Route::post('/orders/{order}/documents/{document}/cancel', [AdminOrderDocumentController::class, 'cancel'])->whereNumber('order')->whereNumber('document')->name('orders.documents.cancel');
        });

        Route::middleware('permission:payments.manage')->group(function (): void {
            Route::post('/orders/{order}/payments', [AdminPaymentController::class, 'store'])->whereNumber('order')->name('orders.payments.store');
            Route::post('/orders/{order}/payments/{payment}/verify', [AdminPaymentController::class, 'verify'])->whereNumber('order')->whereNumber('payment')->name('orders.payments.verify');
            Route::post('/orders/{order}/payments/{payment}/reject', [AdminPaymentController::class, 'reject'])->whereNumber('order')->whereNumber('payment')->name('orders.payments.reject');
            Route::post('/orders/{order}/payments/{payment}/void', [AdminPaymentController::class, 'void'])->whereNumber('order')->whereNumber('payment')->name('orders.payments.void');
        });
        Route::get('/stock-movements', StockMovementController::class)->middleware('permission:stock.view')->name('stock.index');
        Route::post('/stock/{product}/adjust', StockAdjustmentController::class)->middleware('permission:stock.adjust')->name('stock.adjust');

        Route::get('/inventory', [InventoryController::class, 'index'])->middleware('permission:stock.view')->name('inventory.index');
        Route::post('/inventory/receipts', [InventoryController::class, 'receive'])->middleware('permission:inventory.receive')->name('inventory.receive');
        Route::post('/inventory/counts', [InventoryController::class, 'count'])->middleware('permission:inventory.count')->name('inventory.count');
        Route::get('/inventory.csv', [InventoryController::class, 'csv'])->middleware(['permission:inventory.export','throttle:exports'])->name('inventory.csv');

        Route::middleware('permission:system.manage_users')->group(function (): void {
            Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
            Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
            Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
            Route::get('/customer-portal', [AdminCustomerPortalController::class, 'index'])->name('customer-portal.index');
            Route::post('/customer-portal/users', [AdminCustomerPortalController::class, 'store'])->middleware('throttle:admin-write')->name('customer-portal.users.store');
            Route::get('/customer-portal/users/{user}', [AdminCustomerPortalController::class, 'show'])->whereNumber('user')->name('customer-portal.users.show');
            Route::post('/customer-portal/users/{user}/invite', [AdminCustomerPortalController::class, 'invite'])->whereNumber('user')->middleware('throttle:admin-write')->name('customer-portal.users.invite');
            Route::post('/customer-portal/users/{user}/orders/link', [AdminCustomerPortalController::class, 'linkOrder'])->whereNumber('user')->middleware('throttle:admin-write')->name('customer-portal.users.orders.link');
            Route::delete('/customer-portal/users/{user}/sessions', [AdminCustomerPortalController::class, 'revokeSessions'])->whereNumber('user')->middleware('throttle:admin-write')->name('customer-portal.users.sessions.revoke');
            Route::get('/customer-portal/conversations/{conversation}', [AdminPortalConversationController::class, 'show'])->whereNumber('conversation')->name('customer-portal.conversations.show');
            Route::post('/customer-portal/conversations/{conversation}/reply', [AdminPortalConversationController::class, 'reply'])->whereNumber('conversation')->middleware('throttle:portal-messages')->name('customer-portal.conversations.reply');
            Route::patch('/customer-portal/conversations/{conversation}', [AdminPortalConversationController::class, 'update'])->whereNumber('conversation')->middleware('throttle:admin-write')->name('customer-portal.conversations.update');
            Route::get('/user-groups', [UserGroupController::class, 'index'])->name('user-groups.index');
            Route::post('/user-groups', [UserGroupController::class, 'store'])->name('user-groups.store');
            Route::put('/user-groups/{userGroup}', [UserGroupController::class, 'update'])->name('user-groups.update');
            Route::delete('/user-groups/{userGroup}', [UserGroupController::class, 'destroy'])->name('user-groups.destroy');
        });

        Route::get('/settings', [SettingsHubController::class, 'index'])->name('settings.index');

        Route::prefix('settings')->name('settings.')->middleware('permission:system.manage_settings')->group(function (): void {
            Route::get('/modules', [ModuleSettingsController::class, 'index'])->name('modules.index');
            Route::put('/modules', [ModuleSettingsController::class, 'update'])->middleware('throttle:admin-write')->name('modules.update');
            Route::get('/automation', [AutomationController::class, 'index'])->middleware('permission:automation.manage')->name('automation.index');
            Route::put('/automation', [AutomationController::class, 'update'])->middleware('permission:automation.manage')->name('automation.update');
            Route::post('/automation/run', [AutomationController::class, 'run'])->middleware('permission:automation.manage')->name('automation.run');
            Route::post('/automation/alerts/{alert}/resolve', [AutomationController::class, 'resolve'])->middleware('permission:automation.manage')->name('automation.alerts.resolve');
            Route::get('/system-health', [SystemHealthController::class, 'index'])->middleware('permission:system.health')->name('system-health.index');
            Route::post('/system-health/run', [SystemHealthController::class, 'run'])->middleware(['permission:system.health','throttle:admin-write'])->name('system-health.run');
            Route::post('/system-health/backup', [SystemHealthController::class, 'backup'])->middleware(['permission:backups.manage','throttle:backup'])->name('system-health.backup');
            Route::post('/system-health/prune', [SystemHealthController::class, 'prune'])->middleware(['permission:backups.manage','throttle:admin-write'])->name('system-health.prune');
            Route::get('/turnstile', [TurnstileSettingsController::class, 'index'])->name('turnstile.index');
            Route::put('/turnstile', [TurnstileSettingsController::class, 'update'])->middleware('throttle:admin-write')->name('turnstile.update');
            Route::get('/appearance', [SiteAppearanceController::class, 'index'])->name('appearance');
            Route::put('/appearance', [SiteAppearanceController::class, 'update'])->name('appearance.update');
            Route::delete('/appearance/assets/{asset}', [SiteAppearanceController::class, 'removeAsset'])->name('appearance.asset');
            Route::get('/exchange-rate', [ExchangeRateController::class, 'index'])->name('exchange.index');
            Route::post('/exchange-rate/manual', [ExchangeRateController::class, 'manual'])->name('exchange.manual');
            Route::post('/exchange-rate/automatic', [ExchangeRateController::class, 'automatic'])->name('exchange.automatic');
            Route::post('/exchange-rate/refresh', [ExchangeRateController::class, 'refresh'])->name('exchange.refresh');
            Route::get('/couriers', [CourierServiceController::class, 'index'])->name('couriers.index');
            Route::post('/couriers', [CourierServiceController::class, 'store'])->middleware('throttle:admin-write')->name('couriers.store');
            Route::put('/couriers/{courier}', [CourierServiceController::class, 'update'])->whereNumber('courier')->middleware('throttle:admin-write')->name('couriers.update');
            Route::get('/order-emails', [OrderEmailSettingsController::class, 'index'])->name('order-emails.index');
            Route::put('/order-emails', [OrderEmailSettingsController::class, 'update'])->middleware('throttle:admin-write')->name('order-emails.update');
            Route::post('/order-emails/dispatch', [OrderEmailSettingsController::class, 'dispatch'])->middleware('throttle:admin-write')->name('order-emails.dispatch');
            Route::post('/order-emails/retry', [OrderEmailSettingsController::class, 'retry'])->middleware('throttle:admin-write')->name('order-emails.retry');
            Route::get('/documents', [DocumentSettingsController::class, 'index'])->name('documents.index');
            Route::put('/documents', [DocumentSettingsController::class, 'update'])->name('documents.update');
            Route::delete('/documents/logo', [DocumentSettingsController::class, 'removeLogo'])->name('documents.logo.destroy');
            Route::get('/bank-accounts', [BankAccountController::class, 'index'])->name('bank-accounts.index');
            Route::post('/bank-accounts', [BankAccountController::class, 'store'])->name('bank-accounts.store');
            Route::put('/bank-accounts/{bankAccount}', [BankAccountController::class, 'update'])->name('bank-accounts.update');
            Route::delete('/bank-accounts/{bankAccount}', [BankAccountController::class, 'destroy'])->name('bank-accounts.destroy');
        });

        Route::get('/audit-log', AuditLogController::class)->middleware('permission:catalog.audit')->name('audit.index');
        Route::get('/audit-log.csv', [AuditLogController::class, 'csv'])->middleware(['permission:audit.export','throttle:exports'])->name('audit.csv');
    });
});
