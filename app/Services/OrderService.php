<?php
namespace App\Services;

use App\Models\{Package, Order, Coupon};
use Illuminate\Support\Str;
use Exception;

class OrderService
{
    public function createOrder(array $validatedData, ?int $customerId): Order
    {
        $package = Package::with('product')->findOrFail($validatedData['package_id']);
        $price = $package->price;
        $discountAmount = 0;
        $couponId = null;

        if (!empty($validatedData['coupon_code'])) {
            $coupon = Coupon::where('code', $validatedData['coupon_code'])
                ->where('status', 'ACTIVE')
                ->where(function($q) {
                    $q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
                })
                ->where(function($q) {
                    $q->whereNull('max_uses')->orWhereRaw('used_count < max_uses');
                })
                ->first();
            
            if (!$coupon) {
                throw new Exception('Invalid or expired coupon', 400);
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
        $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(6));
        $token = Str::random(32);
        
        return Order::create([
            'order_number' => $orderNumber,
            'secure_token' => $token,
            'package_id' => $package->id,
            'product_id' => $package->product_id,
            'customer_name' => $validatedData['customer_name'],
            'customer_email' => $validatedData['customer_email'],
            'customer_phone' => $validatedData['customer_phone'] ?? null,
            'customer_id' => $customerId,
            'snapshot_price' => $price,
            'coupon_id' => $couponId,
            'discount_amount' => $discountAmount,
            'status' => 'PENDING_PAYMENT'
        ]);
    }
}
