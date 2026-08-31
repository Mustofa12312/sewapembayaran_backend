<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;

#[Signature('app:process-subscriptions')]
#[Description('Process recurring subscriptions and simulate charging for MVP.')]
class ProcessSubscriptions extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting subscription processing...');
        
        $dueSubscriptions = Subscription::where('status', 'ACTIVE')
            ->whereDate('next_billing_date', '<=', now())
            ->get();

        if ($dueSubscriptions->isEmpty()) {
            $this->info('No due subscriptions found.');
            return;
        }

        $renewed = 0;
        foreach ($dueSubscriptions as $sub) {
            // Put subscription in grace period
            $sub->update([
                'status' => 'GRACE_PERIOD'
            ]);
            
            // Create Renewal Order
            $orderService = new \App\Services\OrderService();
            // Need a way to fetch the customer data. Subscription belongs to Customer.
            $customer = \App\Models\Customer::find($sub->customer_id);
            if (!$customer) {
                Log::error("Customer not found for Subscription {$sub->id}");
                continue;
            }

            $order = $orderService->createOrder([
                'package_id' => $sub->package_id,
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => $customer->phone,
            ], null);

            // Record the subscription ID in the order metadata to link them (using a new column or just matching later).
            // Actually, we don't have subscription_id in Order table. We can rely on customer_id + package_id in PaymentService.
            
            Log::info("Generated renewal Order {$order->id} for Subscription {$sub->id}. Status set to GRACE_PERIOD.");
            $renewed++;
        }

        $this->info("Successfully processed {$renewed} subscriptions into Grace Period.");
    }
}
