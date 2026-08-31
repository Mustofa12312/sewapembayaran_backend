<?php
namespace App\Services;

use App\Models\Order;
use App\Jobs\SendOrderNotificationJob;

class NotificationService
{
    public function sendOrderReceipt(Order $order): void
    {
        SendOrderNotificationJob::dispatch($order);
    }
    
    public function sendLicenseKey(Order $order, string $licenseKeyStr): void
    {
        SendOrderNotificationJob::dispatch($order, $licenseKeyStr);
    }
}
