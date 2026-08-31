<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Package;

class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_invalid_signature()
    {
        $response = $this->postJson('/api/payments/midtrans/webhook', [
            'order_id' => 'ORD-123-1',
            'status_code' => '200',
            'gross_amount' => '100000.00',
            'signature_key' => 'invalid_signature'
        ]);

        $response->assertStatus(403);
    }

    public function test_processes_valid_settlement_webhook()
    {
        // Setup Order and Payment
        $product = Product::create(['name' => 'Prod', 'slug' => 'prod', 'status' => 'ACTIVE']);
        $package = Package::create([
            'product_id' => $product->id, 'name' => 'Basic', 'price' => 100000, 
            'duration_value' => 1, 'duration_unit' => 'MONTH', 'is_unlimited' => false, 'status' => 'ACTIVE', 'is_recurring' => false
        ]);
        
        $order = Order::create([
            'order_number' => 'ORD-TEST',
            'secure_token' => 'token123',
            'package_id' => $package->id,
            'product_id' => $product->id,
            'customer_name' => 'Test',
            'customer_email' => 'test@test.com',
            'snapshot_price' => 100000,
            'discount_amount' => 0,
            'status' => 'PENDING_PAYMENT'
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'amount' => 100000,
            'status' => 'PENDING'
        ]);

        $midtransOrderId = $order->order_number . '-' . $payment->id;
        $statusCode = '200';
        $grossAmount = '100000.00';
        // 'dummy' is the default env value for MIDTRANS_SERVER_KEY in tests unless changed
        $signature = hash('sha512', $midtransOrderId . $statusCode . $grossAmount . 'dummy');

        $response = $this->postJson('/api/payments/midtrans/webhook', [
            'order_id' => $midtransOrderId,
            'status_code' => $statusCode,
            'transaction_status' => 'settlement',
            'gross_amount' => $grossAmount,
            'signature_key' => $signature,
            'transaction_id' => 'midtrans-123',
            'payment_type' => 'credit_card'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'PAID',
            'midtrans_transaction_id' => 'midtrans-123'
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'ACTIVE'
        ]);
        
        $this->assertDatabaseHas('payment_events', [
            'payment_id' => $payment->id,
            'status' => 'settlement'
        ]);
    }
}
