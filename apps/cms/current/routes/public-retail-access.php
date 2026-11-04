<?php
declare(strict_types=1);

use App\Http\Controllers\Admin\PublicStorefrontToggleController;
use Illuminate\Support\Facades\Route;

Route::post('/admin/settings/public-storefront/toggle',PublicStorefrontToggleController::class)
    ->middleware(['web','auth','active','tracked-session','permission:system.manage_settings','throttle:admin-write'])
    ->name('admin.settings.public-storefront.toggle');
