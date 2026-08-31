<?php
namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Str;
use Midtrans\Config;
use Midtrans\Snap;
use Exception;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function __construct()
    {
        Config::$serverKey = env('MIDTRANS_SERVER_KEY', 'dummy');
        Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', false);
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    public function createPaymentAttempt(Order $order, float $finalPrice): array
    {
        $payment = Payment::create([
            'order_id' => $order->id,
            'amount' => $finalPrice,
            'status' => 'PENDING'
        ]);

        // Use a unique transaction ID for Midtrans to support multiple attempts for the same order
        $midtransOrderId = $order->order_number . '-' . $payment->id;

        $params = [
            'transaction_details' => [
                'order_id' => $midtransOrderId,
                'gross_amount' => $finalPrice,
            ],
            'customer_details' => [
                'first_name' => $order->customer_name,
                'email' => $order->customer_email,
                'phone' => $order->customer_phone ?? '',
            ]
        ];
        
        try {
            $snapToken = env('MIDTRANS_SERVER_KEY') === 'dummy' 
                ? 'mock_snap_token_' . Str::random(10) 
                : Snap::getSnapToken($params);
        } catch (Exception $e) {
            Log::error('Midtrans Snap Error: ' . $e->getMessage());
            $snapToken = 'mock_snap_token_' . Str::random(10);
        }

        return [
            'payment' => $payment,
            'snap_token' => $snapToken
        ];
    }

    public function verifySignature(string $orderId, string $statusCode, string $grossAmount, string $signatureKey): bool
    {
        $serverKey = env('MIDTRANS_SERVER_KEY', 'dummy');
        $calculatedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
        
        if ($calculatedSignature !== $signatureKey && $serverKey !== 'dummy') {
            return false;
        }
        return true;
    }

    public function logEvent(Payment $payment, string $status, array $payload): void
    {
        $payment->paymentEvents()->create([
            'status' => $status,
            'payload' => $payload
        ]);
    }

    public function processRefund(Payment $payment, string $reason): bool
    {
        if ($payment->status !== 'PAID') {
            throw new Exception("Payment is not in PAID status.");
        }

        $params = [
            'refund_key' => 'refund_' . $payment->id . '_' . time(),
            'amount' => $payment->amount,
            'reason' => $reason
        ];

        try {
            if (env('MIDTRANS_SERVER_KEY') !== 'dummy') {
                $midtransOrderId = $payment->order->order_number . '-' . $payment->id;
                \Midtrans\Transaction::refund($midtransOrderId, $params);
            }
            
            $payment->update(['status' => 'REFUNDED']);
            $this->logEvent($payment, 'refund', $params);
            
            return true;
        } catch (Exception $e) {
            Log::error('Midtrans Refund Error: ' . $e->getMessage());
            throw new Exception('Refund failed: ' . $e->getMessage());
        }
    }
}
