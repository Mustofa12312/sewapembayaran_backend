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

                    // Phase 2: Send Mock Notifications
                    $licenseKeyStr = $license->license_key;
                    \Illuminate\Support\Facades\Log::info("MOCK NOTIFICATION: Email Receipt sent to {$order->customer_email}");
                    \Illuminate\Support\Facades\Log::info("MOCK NOTIFICATION: WhatsApp License Key [{$licenseKeyStr}] sent to {$order->customer_phone}");
                    
                    // Phase 5: Handle Affiliate Commission
                    if ($order->customer_id) {
                        $customer = \App\Models\Customer::find($order->customer_id);
                        if ($customer && $customer->referrer_id) {
                            // Grant 10% commission
                            $commissionAmount = $order->snapshot_price * 0.10;
                            \App\Models\AffiliateCommission::create([
                                'customer_id' => $customer->referrer_id,
                                'order_id' => $order->id,
                                'amount' => $commissionAmount,
                                'status' => 'PENDING'
                            ]);
                            \Illuminate\Support\Facades\Log::info("AFFILIATE: Granted Rp{$commissionAmount} commission to Customer ID {$customer->referrer_id}");
                        }
                    }
                }
            });
        } else if ($transactionStatus == 'cancel' || $transactionStatus == 'deny' || $transactionStatus == 'expire') {
            $payment->update(['status' => 'FAILED']);
        }

        return response()->json(['message' => 'OK']);
    }

    public function simulate(Request $request, $token) {
        $order = Order::where('secure_token', $token)->firstOrFail();
        
        // Construct mock payload
        $payload = [
            'order_id' => $order->order_number,
            'status_code' => '200',
            'transaction_status' => 'settlement',
            'transaction_id' => 'mock_transaction_' . \Illuminate\Support\Str::random(10),
            'payment_type' => 'mock_qris'
        ];
        
        // Just call webhook method internally
        $mockRequest = Request::create('/api/webhook/midtrans', 'POST', $payload);
        return $this->webhook($mockRequest);
    }
}