<?php

declare(strict_types=1);

use App\Http\Controllers\Public\PublicRetailController;
use App\Http\Controllers\Public\PublicRetailImageController;
use Illuminate\Support\Facades\Route;

// Separate unprivileged retail transport, guarded again at service level.
Route::get('/katalog', [PublicRetailController::class, 'index'])->middleware('throttle:120,1')->name('public.catalog');
Route::get('/katalog/slika/{image}', PublicRetailImageController::class)->whereNumber('image')->middleware('throttle:120,1')->name('public.image');
Route::get('/katalog/{slug}', [PublicRetailController::class, 'product'])->middleware('throttle:120,1')->name('public.product');
Route::post('/korpa/dodaj/{product}', [PublicRetailController::class, 'add'])->whereNumber('product')->middleware('throttle:30,1')->name('public.cart.add');
Route::get('/korpa', [PublicRetailController::class, 'cart'])->middleware('throttle:120,1')->name('public.cart');
Route::post('/korpa', [PublicRetailController::class, 'updateCart'])->middleware('throttle:30,1')->name('public.cart.update');
Route::get('/porucivanje', [PublicRetailController::class, 'checkout'])->middleware('throttle:30,1')->name('public.checkout');
Route::post('/porucivanje', [PublicRetailController::class, 'place'])->middleware('throttle:10,1')->name('public.checkout.place');
Route::get('/porudzbina/{token}', [PublicRetailController::class, 'order'])->where('token', '[a-f0-9]{64}')->middleware('throttle:40,1')->name('public.order.show');
Route::get('/porudzbina/{token}/dokument/{document}', [PublicRetailController::class, 'document'])
    ->where('token', '[a-f0-9]{64}')->whereNumber('document')->middleware('throttle:20,1')->name('public.order.document');
Route::post('/porudzbina/{token}/opozovi-pristup', [PublicRetailController::class, 'revoke'])
    ->where('token', '[a-f0-9]{64}')->middleware('throttle:10,1')->name('public.order.revoke');
