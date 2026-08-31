<?php
namespace App\Services;

use App\Models\LicenseKey;
use App\Models\OrderLicenseKey;
use App\Models\Order;

class LicenseService
{
    public function assignLicenseToOrder(Order $order): ?LicenseKey
    {
        $license = LicenseKey::where('product_id', $order->product_id)
            ->where('status', 'AVAILABLE')
            ->lockForUpdate()
            ->first();

        if ($license) {
            $license->update([
                'status' => 'ACTIVE',
                'assigned_order_id' => $order->id,
                'assigned_at' => now(),
                'expires_at' => $order->end_date
            ]);
            
            OrderLicenseKey::create([
                'order_id' => $order->id,
                'license_key_id' => $license->id
            ]);
            
            return $license;
        }
        
        return null;
    }
}
