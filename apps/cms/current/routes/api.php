<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AuthTokenController;
use App\Http\Controllers\Api\V1\BootstrapController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\CatalogOptionsController;
use App\Http\Controllers\Api\V1\GoogleAuthController;
use App\Http\Controllers\Api\V1\MobileDeviceController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\OrderOptionsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::post('/auth/token', [AuthTokenController::class, 'store'])
        ->middleware('throttle:api-login')
        ->name('auth.token');

    Route::post('/auth/google', [GoogleAuthController::class, 'store'])
        ->middleware('throttle:api-google-auth')
        ->name('auth.google');

    Route::middleware(['auth:sanctum', 'active'])->group(function (): void {
        Route::get('/bootstrap', [BootstrapController::class, 'show'])->name('bootstrap');
        Route::get('/me', [AuthTokenController::class, 'me'])->name('me');
        Route::patch('/me', [AccountController::class, 'profile'])->name('me.update');
        Route::put('/me/password', [AccountController::class, 'password'])
            ->middleware('throttle:api-sensitive')
            ->name('me.password');
        Route::put('/me/notification-preferences', [AccountController::class, 'notifications'])->name('me.notification-preferences');
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
        Route::middleware('permission:orders.view_own')->group(function (): void {
            Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
            Route::get('/orders/{order}', [OrderController::class, 'show'])->whereNumber('order')->name('orders.show');
        });
        Route::post('/orders', [OrderController::class, 'store'])
            ->middleware(['permission:orders.create', 'throttle:orders'])
            ->name('orders.store');
        Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])
            ->whereNumber('order')
            ->middleware('permission:orders.cancel_own')
            ->name('orders.cancel');

        Route::middleware('permission:notifications.view')->group(function (): void {
            Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
            Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
            Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
        });
    });
});
