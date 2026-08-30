<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Public\ProductController as PublicProductController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\CustomerOrderController;
use App\Http\Controllers\AffiliateController;
use App\Http\Controllers\SubscriptionController;

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AdminManagementController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\PackageController as AdminPackageController;
use App\Http\Controllers\Admin\LicenseKeyController as AdminLicenseKeyController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\AuditLogController;

Route::post('/admin/login', [AuthController::class, 'login']);

Route::get('/products', [PublicProductController::class, 'index']);
Route::get('/products/{slug}', [PublicProductController::class, 'show']);
Route::get('/packages/{id}', [PublicProductController::class, 'getPackage']);

Route::post('/orders', [CheckoutController::class, 'store']);
Route::get('/orders/{token}', [CheckoutController::class, 'show']);

Route::post('/payments/midtrans/webhook', [PaymentController::class, 'webhook']);

Route::post('/customer/register', [CustomerAuthController::class, 'register']);
Route::post('/customer/login', [CustomerAuthController::class, 'login']);
Route::post('/orders/{token}/simulate', [PaymentController::class, 'simulate']);

Route::middleware('auth:sanctum')->group(function () {
    // For customers
    Route::post('/customer/logout', [CustomerAuthController::class, 'logout']);
    Route::get('/customer/orders', [CustomerOrderController::class, 'index']);
    Route::get('/customer/affiliate', [AffiliateController::class, 'dashboard']);
    
    // Subscriptions & Renewals
    Route::post('/orders/{token}/renew', [SubscriptionController::class, 'renew']);
    Route::post('/subscriptions/cron', [SubscriptionController::class, 'cron']);
    
    // For admins
    Route::post('/admin/logout', [AuthController::class, 'logout']);
    Route::get('/admin/user', function (Request $request) {
        return $request->user();
    });
    
    Route::get('/admin/analytics', [AnalyticsController::class, 'index']);

    Route::apiResource('/admin/staff', AdminManagementController::class)->only(['index', 'store']);
    Route::apiResource('/admin/products', AdminProductController::class);
    Route::apiResource('/admin/packages', AdminPackageController::class);
    Route::apiResource('/admin/licenses', AdminLicenseKeyController::class);
    Route::post('/admin/licenses/import', [AdminLicenseKeyController::class, 'import']);
    Route::apiResource('/admin/orders', AdminOrderController::class)->only(['index', 'show']);
    Route::apiResource('/admin/customers', AdminCustomerController::class)->only(['index']);
    Route::get('/admin/audit-logs', [AuditLogController::class, 'index']);
});
