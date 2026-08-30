<?php

$controllers = [
    'Admin/PackageController.php' => <<<PHP
<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Package;
class PackageController extends Controller
{
    public function index() { return Package::with('features', 'product')->get(); }
    public function store(Request \$request) {
        \$validated = \$request->validate([
            'product_id' => 'required|exists:products,id',
            'name' => 'required|string',
            'price' => 'required|numeric',
            'duration_value' => 'nullable|integer',
            'duration_unit' => 'nullable|in:MONTH,YEAR',
            'is_unlimited' => 'boolean',
            'status' => 'required|in:ACTIVE,INACTIVE,ARCHIVED'
        ]);
        \$package = Package::create(\$validated);
        if (\$request->has('features')) {
            foreach (\$request->features as \$f) {
                \$package->features()->create(['feature_name' => \$f]);
            }
        }
        return response()->json(\$package->load('features'), 201);
    }
    public function show(\$id) { return Package::with('features')->findOrFail(\$id); }
    public function update(Request \$request, \$id) {
        \$package = Package::findOrFail(\$id);
        \$package->update(\$request->all());
        return response()->json(\$package);
    }
    public function destroy(\$id) {
        Package::findOrFail(\$id)->update(['status' => 'ARCHIVED']);
        return response()->json(['message' => 'Archived']);
    }
}
PHP,
    'Admin/LicenseKeyController.php' => <<<PHP
<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LicenseKey;
class LicenseKeyController extends Controller
{
    public function index() { return LicenseKey::with('product', 'package')->latest()->paginate(50); }
    public function store(Request \$request) {
        \$validated = \$request->validate([
            'product_id' => 'required|exists:products,id',
            'package_id' => 'nullable|exists:packages,id',
            'license_keys' => 'required|array',
            'license_keys.*' => 'string|unique:license_keys,license_key'
        ]);
        \$inserted = [];
        foreach (\$validated['license_keys'] as \$key) {
            \$inserted[] = LicenseKey::create([
                'product_id' => \$validated['product_id'],
                'package_id' => \$validated['package_id'] ?? null,
                'license_key' => \$key,
                'status' => 'AVAILABLE'
            ]);
        }
        return response()->json(\$inserted, 201);
    }
    public function show(\$id) { return LicenseKey::findOrFail(\$id); }
    public function update(Request \$request, \$id) {
        \$license = LicenseKey::findOrFail(\$id);
        \$license->update(\$request->only('status'));
        return response()->json(\$license);
    }
    public function destroy(\$id) {}
}
PHP,
    'Admin/OrderController.php' => <<<PHP
<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Order;
class OrderController extends Controller
{
    public function index() { return Order::with('product', 'package', 'payment')->latest()->paginate(50); }
    public function show(\$id) { return Order::with('product', 'package', 'payment', 'licenseKeys')->findOrFail(\$id); }
}
PHP,
    'Public/ProductController.php' => <<<PHP
<?php
namespace App\Http\Controllers\Public;
use App\Http\Controllers\Controller;
use App\Models\Product;
class ProductController extends Controller
{
    public function index() {
        return Product::where('status', 'ACTIVE')->with(['packages' => function(\$q) {
            \$q->where('status', 'ACTIVE')->with('features');
        }])->get();
    }
    public function show(\$slug) {
        return Product::where('slug', \$slug)->where('status', 'ACTIVE')->with(['packages' => function(\$q) {
            \$q->where('status', 'ACTIVE')->with('features');
        }])->firstOrFail();
    }
}
PHP,
    'CheckoutController.php' => <<<PHP
<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\{Package, Order, Payment};
use Illuminate\Support\Str;
class CheckoutController extends Controller
{
    public function store(Request \$request) {
        \$validated = \$request->validate([
            'package_id' => 'required|exists:packages,id',
            'customer_name' => 'required|string',
            'customer_email' => 'required|email',
            'customer_phone' => 'nullable|string'
        ]);
        \$package = Package::with('product')->findOrFail(\$validated['package_id']);
        
        // Setup Midtrans
        // Assuming env MIDTRANS_SERVER_KEY is set
        \Midtrans\Config::\$serverKey = env('MIDTRANS_SERVER_KEY', 'dummy');
        \Midtrans\Config::\$isProduction = false;
        \Midtrans\Config::\$isSanitized = true;
        \Midtrans\Config::\$is3ds = true;

        \$orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(6));
        \$token = Str::random(32);
        
        \$order = Order::create([
            'order_number' => \$orderNumber,
            'secure_token' => \$token,
            'package_id' => \$package->id,
            'product_id' => \$package->product_id,
            'customer_name' => \$validated['customer_name'],
            'customer_email' => \$validated['customer_email'],
            'customer_phone' => \$validated['customer_phone'],
            'snapshot_price' => \$package->price,
            'status' => 'PENDING_PAYMENT'
        ]);
        
        \$params = [
            'transaction_details' => [
                'order_id' => \$orderNumber,
                'gross_amount' => \$package->price,
            ],
            'customer_details' => [
                'first_name' => \$validated['customer_name'],
                'email' => \$validated['customer_email'],
                'phone' => \$validated['customer_phone'] ?? '',
            ]
        ];
        
        try {
            // For MVP and without real midtrans creds, we might mock this.
            // If dummy key, simulate token:
            \$snapToken = env('MIDTRANS_SERVER_KEY') === 'dummy' ? 'mock_snap_token_' . Str::random(10) : \Midtrans\Snap::getSnapToken(\$params);
        } catch (\Exception \$e) {
            \$snapToken = 'mock_snap_token_' . Str::random(10);
        }
        
        Payment::create([
            'order_id' => \$order->id,
            'amount' => \$package->price,
            'status' => 'PENDING'
        ]);

        return response()->json([
            'order' => \$order,
            'snap_token' => \$snapToken
        ]);
    }
    
    public function show(\$token) {
        return Order::where('secure_token', \$token)->with('product', 'package', 'licenseKeys')->firstOrFail();
    }
}
PHP,
    'PaymentController.php' => <<<PHP
<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\{Order, Payment, LicenseKey, OrderLicenseKey};
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class PaymentController extends Controller
{
    public function webhook(Request \$request) {
        // Idempotent webhook handler
        \$payload = \$request->all();
        \$orderId = \$payload['order_id'] ?? null;
        \$statusCode = \$payload['status_code'] ?? null;
        \$transactionStatus = \$payload['transaction_status'] ?? null;
        
        if (!\$orderId) return response()->json(['message' => 'Invalid payload'], 400);

        // For MVP, simulating midtrans signature validation bypass if dummy
        
        \$order = Order::where('order_number', \$orderId)->first();
        if (!\$order) return response()->json(['message' => 'Order not found'], 404);
        
        \$payment = Payment::where('order_id', \$order->id)->first();
        if (!\$payment) return response()->json(['message' => 'Payment not found'], 404);

        if (\$payment->status === 'PAID') {
            return response()->json(['message' => 'Already processed']);
        }

        if (\$transactionStatus == 'capture' || \$transactionStatus == 'settlement') {
            DB::transaction(function () use (\$order, \$payment, \$payload) {
                \$payment->update([
                    'status' => 'PAID',
                    'paid_at' => now(),
                    'midtrans_transaction_id' => \$payload['transaction_id'] ?? null,
                    'payment_method' => \$payload['payment_type'] ?? null,
                    'raw_response' => json_encode(\$payload)
                ]);

                // Calculate dates
                \$package = \$order->package;
                \$startDate = now();
                \$endDate = null;
                if (!\$package->is_unlimited) {
                    if (\$package->duration_unit === 'MONTH') {
                        \$endDate = now()->addMonths(\$package->duration_value);
                    } else if (\$package->duration_unit === 'YEAR') {
                        \$endDate = now()->addYears(\$package->duration_value);
                    }
                }

                \$order->update([
                    'status' => 'ACTIVE',
                    'start_date' => \$startDate,
                    'end_date' => \$endDate
                ]);

                // Assign License
                \$license = LicenseKey::where('product_id', \$order->product_id)
                    ->where('status', 'AVAILABLE')
                    ->lockForUpdate()
                    ->first();

                if (\$license) {
                    \$license->update([
                        'status' => 'ACTIVE',
                        'assigned_order_id' => \$order->id,
                        'assigned_at' => now(),
                        'expires_at' => \$endDate
                    ]);
                    
                    OrderLicenseKey::create([
                        'order_id' => \$order->id,
                        'license_key_id' => \$license->id
                    ]);
                }
            });
        } else if (\$transactionStatus == 'cancel' || \$transactionStatus == 'deny' || \$transactionStatus == 'expire') {
            \$payment->update(['status' => 'FAILED']);
        }

        return response()->json(['message' => 'OK']);
    }
}
PHP
];

foreach ($controllers as $path => $content) {
    file_put_contents(__DIR__ . '/app/Http/Controllers/' . $path, $content);
    echo "Updated $path\n";
}
