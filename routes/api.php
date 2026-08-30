<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::post('/admin/login', [AuthController::class, 'login']);

use App\Http\Controllers\Public\ProductController as PublicProductController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\PaymentController;

Route::get('/products', [PublicProductController::class, 'index']);
Route::get('/products/{slug}', [PublicProductController::class, 'show']);

Route::post('/orders', [CheckoutController::class, 'store']);
Route::get('/orders/{token}', [CheckoutController::class, 'show']);

Route::post('/payments/midtrans/webhook', [PaymentController::class, 'webhook']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/admin/logout', [AuthController::class, 'logout']);
    Route::get('/admin/user', function (Request $request) {
        return $request->user();
    });

    Route::apiResource('/admin/products', App\Http\Controllers\Admin\ProductController::class);
    Route::apiResource('/admin/packages', App\Http\Controllers\Admin\PackageController::class);
    Route::apiResource('/admin/licenses', App\Http\Controllers\Admin\LicenseKeyController::class);
    Route::apiResource('/admin/orders', App\Http\Controllers\Admin\OrderController::class)->only(['index', 'show']);
});
