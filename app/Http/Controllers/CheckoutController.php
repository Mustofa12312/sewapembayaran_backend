<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\{Package, Order, Payment};
use Illuminate\Support\Str;
class CheckoutController extends Controller
{
    public function store(Request $request) {
        $validated = $request->validate([
            'package_id' => 'required|exists:packages,id',
            'customer_name' => 'required|string',
            'customer_email' => 'required|email',
            'customer_phone' => 'nullable|string'
        ]);
        $package = Package::with('product')->findOrFail($validated['package_id']);
        
        // Setup Midtrans
        // Assuming env MIDTRANS_SERVER_KEY is set
        \Midtrans\Config::$serverKey = env('MIDTRANS_SERVER_KEY', 'dummy');
        \Midtrans\Config::$isProduction = false;
        \Midtrans\Config::$isSanitized = true;
        \Midtrans\Config::$is3ds = true;

        $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(6));
        $token = Str::random(32);
        
        $order = Order::create([
            'order_number' => $orderNumber,
            'secure_token' => $token,
            'package_id' => $package->id,
            'product_id' => $package->product_id,
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'customer_phone' => $validated['customer_phone'],
            'snapshot_price' => $package->price,
            'status' => 'PENDING_PAYMENT'
        ]);
        
        $params = [
            'transaction_details' => [
                'order_id' => $orderNumber,
                'gross_amount' => $package->price,
            ],
            'customer_details' => [
                'first_name' => $validated['customer_name'],
                'email' => $validated['customer_email'],
                'phone' => $validated['customer_phone'] ?? '',
            ]
        ];
        
        try {
            // For MVP and without real midtrans creds, we might mock this.
            // If dummy key, simulate token:
            $snapToken = env('MIDTRANS_SERVER_KEY') === 'dummy' ? 'mock_snap_token_' . Str::random(10) : \Midtrans\Snap::getSnapToken($params);
        } catch (\Exception $e) {
            $snapToken = 'mock_snap_token_' . Str::random(10);
        }
        
        Payment::create([
            'order_id' => $order->id,
            'amount' => $package->price,
            'status' => 'PENDING'
        ]);

        return response()->json([
            'order' => $order,
            'snap_token' => $snapToken
        ]);
    }
    
    public function show($token) {
        return Order::where('secure_token', $token)->with('product', 'package', 'licenseKeys')->firstOrFail();
    }
}