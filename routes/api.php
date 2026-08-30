<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::post('/admin/login', [AuthController::class, 'login']);

use App\Http\Controllers\Public\ProductController as PublicProductController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\CustomerOrderController;

Route::get('/products', [PublicProductController::class, 'index']);
Route::get('/products/{slug}', [PublicProductController::class, 'show']);

Route::post('/orders', [CheckoutController::class, 'store']);
Route::get('/orders/{token}', [CheckoutController::class, 'show']);

Route::post('/payments/midtrans/webhook', [PaymentController::class, 'webhook']);

Route::post('/customer/register', [CustomerAuthController::class, 'register']);
Route::post('/customer/login', [CustomerAuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    // For customers
    Route::post('/customer/logout', [CustomerAuthController::class, 'logout']);
    Route::get('/customer/orders', [CustomerOrderController::class, 'index']);
    
    // Subscriptions & Renewals
    Route::post('/orders/{token}/renew', [\App\Http\Controllers\SubscriptionController::class, 'renew']);
    Route::post('/subscriptions/cron', [\App\Http\Controllers\SubscriptionController::class, 'cron']); // In reality, this should be protected by server-side cron auth
    
    // For admins
    Route::post('/admin/logout', [AuthController::class, 'logout']);
    Route::get('/admin/user', function (Request $request) {
        return $request->user();
    });

    Route::apiResource('/admin/products', App\Http\Controllers\Admin\ProductController::class);
    Route::apiResource('/admin/packages', App\Http\Controllers\Admin\PackageController::class);
    Route::apiResource('/admin/licenses', App\Http\Controllers\Admin\LicenseKeyController::class);
    Route::apiResource('/admin/orders', App\Http\Controllers\Admin\OrderController::class)->only(['index', 'show']);
});
