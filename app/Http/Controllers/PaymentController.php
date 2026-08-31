<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use App\Services\{PaymentService, LicenseService, SubscriptionService, AffiliateService, NotificationService};
use Exception;

class PaymentController extends Controller
{
    public function webhook(Request $request) {
        $payload = $request->all();
        $midtransOrderId = $payload['order_id'] ?? null;
        $statusCode = $payload['status_code'] ?? null;
        $transactionStatus = $payload['transaction_status'] ?? null;
        $grossAmount = $payload['gross_amount'] ?? null;
        $signatureKey = $payload['signature_key'] ?? null;

        if (!$midtransOrderId || !$statusCode || !$grossAmount || !$signatureKey) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        $paymentService = new PaymentService();
        if (!$paymentService->verifySignature($midtransOrderId, $statusCode, $grossAmount, $signatureKey)) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        // Extact actual order_number and payment_id
        $parts = explode('-', $midtransOrderId);
        $paymentId = array_pop($parts);
        $orderNumber = implode('-', $parts);
        
        $order = Order::where('order_number', $orderNumber)->first();
        if (!$order) return response()->json(['message' => 'Order not found'], 404);
        
        if ($transactionStatus == 'capture' || $transactionStatus == 'settlement') {
            try {
                DB::transaction(function () use ($order, $paymentId, $payload, $grossAmount, $transactionStatus, $paymentService) {
                    $payment = Payment::where('id', $paymentId)->lockForUpdate()->first();
                    if (!$payment) throw new Exception('Payment not found');

                    $paymentService->logEvent($payment, $transactionStatus, $payload);

                    if ($payment->status === 'PAID') return;

                    if (floatval($grossAmount) != floatval($payment->amount)) {
                        throw new Exception('Gross amount mismatch');
                    }

                    $payment->update([
                        'status' => 'PAID',
                        'paid_at' => now(),
                        'midtrans_transaction_id' => $payload['transaction_id'] ?? null,
                        'payment_method' => $payload['payment_type'] ?? null,
                        'raw_response' => json_encode($payload)
                    ]);

                    $package = $order->package;
                    $startDate = now();
                    $endDate = null;
                    if (!$package->is_unlimited) {
                        $endDate = $package->duration_unit === 'MONTH' 
                            ? now()->addMonths($package->duration_value) 
                            : now()->addYears($package->duration_value);
                    }

                    $order->update([
                        'status' => 'ACTIVE',
                        'start_date' => $startDate,
                        'end_date' => $endDate
                    ]);

                    (new SubscriptionService())->createSubscriptionForOrder($order);
                    $license = (new LicenseService())->assignLicenseToOrder($order);

                    if ($license) {
                        $notificationService = new NotificationService();
                        $notificationService->sendOrderReceipt($order);
                        $notificationService->sendLicenseKey($order, $license->license_key);
                        
                        (new AffiliateService())->processCommission($order);
                    }
                });
            } catch (Exception $e) {
                return response()->json(['message' => $e->getMessage()], 400);
            }
        } else if (in_array($transactionStatus, ['cancel', 'deny', 'expire'])) {
            DB::transaction(function () use ($paymentId, $transactionStatus, $payload, $paymentService) {
                $payment = Payment::where('id', $paymentId)->lockForUpdate()->first();
                if ($payment) {
                    $paymentService->logEvent($payment, $transactionStatus, $payload);
                    if ($payment->status !== 'PAID') {
                        $payment->update(['status' => 'FAILED']);
                    }
                }
            });
        }

        return response()->json(['message' => 'OK']);
    }

    public function simulate(Request $request, $token) {
        $order = Order::where('secure_token', $token)->firstOrFail();
        
        $payload = [
            'order_id' => $order->order_number,
            'status_code' => '200',
            'transaction_status' => 'settlement',
            'gross_amount' => $order->snapshot_price - $order->discount_amount,
            'transaction_id' => 'mock_transaction_' . \Illuminate\Support\Str::random(10),
            'payment_type' => 'mock_qris'
        ];
        
        $payload['signature_key'] = hash('sha512', 
            $payload['order_id'] . 
            $payload['status_code'] . 
            $payload['gross_amount'] . 
            env('MIDTRANS_SERVER_KEY', 'dummy')
        );

        $mockRequest = Request::create('/api/webhook/midtrans', 'POST', $payload);
        return $this->webhook($mockRequest);
    }
}