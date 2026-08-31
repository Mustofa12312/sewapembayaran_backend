<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\{Package, Order, Payment, Coupon};
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function store(Request $request) {
        $validated = $request->validate([
            'package_id' => 'required|exists:packages,id',
            'customer_name' => 'required|string',
            'customer_email' => 'required|email',
            'customer_phone' => 'nullable|string',
            'coupon_code' => 'nullable|string'
        ]);
        
        $package = Package::with('product')->findOrFail($validated['package_id']);
        $price = $package->price;
        $discountAmount = 0;
        $couponId = null;

        if (!empty($validated['coupon_code'])) {
            $coupon = Coupon::where('code', $validated['coupon_code'])
                ->where('status', 'ACTIVE')
                ->where(function($q) {
                    $q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
                })
                ->where(function($q) {
                    $q->whereNull('max_uses')->orWhereRaw('used_count < max_uses');
                })
                ->first();
            
            if (!$coupon) {
                return response()->json(['message' => 'Invalid or expired coupon'], 400);
            }

            $couponId = $coupon->id;
            if ($coupon->type === 'percent') {
                $discountAmount = ($price * $coupon->value) / 100;
            } else {
                $discountAmount = $coupon->value;
            }
            if ($discountAmount > $price) $discountAmount = $price;
        }

        $finalPrice = $price - $discountAmount;

        \Midtrans\Config::$serverKey = env('MIDTRANS_SERVER_KEY', 'dummy');
        \Midtrans\Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', false);
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
            'customer_phone' => $validated['customer_phone'] ?? null,
            'customer_id' => auth('sanctum')->check() ? auth('sanctum')->id() : null,
            'snapshot_price' => $price,
            'coupon_id' => $couponId,
            'discount_amount' => $discountAmount,
            'status' => 'PENDING_PAYMENT'
        ]);
        
        // Note: Subscription will be created upon successful payment settlement in Webhook
        
        $params = [
            'transaction_details' => [
                'order_id' => $orderNumber,
                'gross_amount' => $finalPrice,
            ],
            'customer_details' => [
                'first_name' => $validated['customer_name'],
                'email' => $validated['customer_email'],
                'phone' => $validated['customer_phone'] ?? '',
            ]
        ];
        
        try {
            $snapToken = env('MIDTRANS_SERVER_KEY') === 'dummy' ? 'mock_snap_token_' . Str::random(10) : \Midtrans\Snap::getSnapToken($params);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Midtrans Snap Error: ' . $e->getMessage());
            $snapToken = 'mock_snap_token_' . Str::random(10);
        }
        
        Payment::create([
            'order_id' => $order->id,
            'amount' => $finalPrice,
            'status' => 'PENDING'
        ]);

        return response()->json([
            'order' => $order,
            'snap_token' => $snapToken,
            'final_price' => $finalPrice,
            'discount_amount' => $discountAmount
        ]);
    }
    
    public function show($token) {
        return Order::where('secure_token', $token)->with('product', 'package', 'licenseKeys', 'coupon')->firstOrFail();
    }
}