<?php
namespace App\Services;

use App\Models\Order;
use App\Models\Subscription;

class SubscriptionService
{
    public function createSubscriptionForOrder(Order $order): ?Subscription
    {
        $package = $order->package;
        
        if ($package && $package->is_recurring && $order->customer_id) {
            return Subscription::create([
                'customer_id' => $order->customer_id,
                'package_id' => $package->id,
                'status' => 'ACTIVE',
                'next_billing_date' => $order->end_date ?? now()->addMonth(),
            ]);
        }
        
        return null;
    }
}
