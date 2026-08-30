<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\{Order, Payment, LicenseKey, OrderLicenseKey};
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class PaymentController extends Controller
{
    public function webhook(Request $request) {
        // Idempotent webhook handler
        $payload = $request->all();
        $orderId = $payload['order_id'] ?? null;
        $statusCode = $payload['status_code'] ?? null;
        $transactionStatus = $payload['transaction_status'] ?? null;
        
        if (!$orderId) return response()->json(['message' => 'Invalid payload'], 400);

        // For MVP, simulating midtrans signature validation bypass if dummy
        
        $order = Order::where('order_number', $orderId)->first();
        if (!$order) return response()->json(['message' => 'Order not found'], 404);
        
        $payment = Payment::where('order_id', $order->id)->first();
        if (!$payment) return response()->json(['message' => 'Payment not found'], 404);

        if ($payment->status === 'PAID') {
            return response()->json(['message' => 'Already processed']);
        }

        if ($transactionStatus == 'capture' || $transactionStatus == 'settlement') {
            DB::transaction(function () use ($order, $payment, $payload) {
                $payment->update([
                    'status' => 'PAID',
                    'paid_at' => now(),
                    'midtrans_transaction_id' => $payload['transaction_id'] ?? null,
                    'payment_method' => $payload['payment_type'] ?? null,
                    'raw_response' => json_encode($payload)
                ]);

                // Calculate dates
                $package = $order->package;
                $startDate = now();
                $endDate = null;
                if (!$package->is_unlimited) {
                    if ($package->duration_unit === 'MONTH') {
                        $endDate = now()->addMonths($package->duration_value);
                    } else if ($package->duration_unit === 'YEAR') {
                        $endDate = now()->addYears($package->duration_value);
                    }
                }

                $order->update([
                    'status' => 'ACTIVE',
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ]);

                // Assign License
                $license = LicenseKey::where('product_id', $order->product_id)
                    ->where('status', 'AVAILABLE')
                    ->lockForUpdate()
                    ->first();

                if ($license) {
                    $license->update([
                        'status' => 'ACTIVE',
                        'assigned_order_id' => $order->id,
                        'assigned_at' => now(),
                        'expires_at' => $endDate
                    ]);
                    
                    OrderLicenseKey::create([
                        'order_id' => $order->id,
                        'license_key_id' => $license->id
                    ]);

                    // Mock Email & WhatsApp Notifications
                    \Illuminate\Support\Facades\Log::info("MOCK EMAIL: Sent license key {$license->license_key} to {$order->customer_email}");
                    if ($order->customer_phone) {
                        \Illuminate\Support\Facades\Log::info("MOCK WHATSAPP: Sent license key {$license->license_key} to {$order->customer_phone}");
                    }
                }
            });
        } else if ($transactionStatus == 'cancel' || $transactionStatus == 'deny' || $transactionStatus == 'expire') {
            $payment->update(['status' => 'FAILED']);
        }

        return response()->json(['message' => 'OK']);
    }
}