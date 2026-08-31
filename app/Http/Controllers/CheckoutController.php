<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\PaymentService;
use Exception;

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
        
        $customerId = auth('sanctum')->check() ? auth('sanctum')->id() : null;

        try {
            $orderService = new OrderService();
            $order = $orderService->createOrder($validated, $customerId);

            $paymentService = new PaymentService();
            $finalPrice = $order->snapshot_price - $order->discount_amount;
            $paymentResult = $paymentService->createPaymentAttempt($order, $finalPrice);
            
            return response()->json([
                'order' => $order,
                'snap_token' => $paymentResult['snap_token'],
                'final_price' => $finalPrice,
                'discount_amount' => $order->discount_amount
            ]);
        } catch (Exception $e) {
            $status = $e->getCode() ?: 400;
            if ($status < 400 || $status > 500) $status = 400;
            return response()->json(['message' => $e->getMessage()], $status);
        }
    }
    
    public function show($token) {
        return Order::where('secure_token', $token)->with('product', 'package', 'licenseKeys', 'coupon')->firstOrFail();
    }

    public function pay($token) {
        $order = Order::where('secure_token', $token)->firstOrFail();
        
        if ($order->status !== 'PENDING_PAYMENT') {
            return response()->json(['message' => 'Order is not pending payment.'], 400);
        }

        try {
            $paymentService = new PaymentService();
            $finalPrice = $order->snapshot_price - $order->discount_amount;
            $paymentResult = $paymentService->createPaymentAttempt($order, $finalPrice);
            
            return response()->json([
                'order' => $order,
                'snap_token' => $paymentResult['snap_token'],
                'final_price' => $finalPrice,
                'discount_amount' => $order->discount_amount
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}