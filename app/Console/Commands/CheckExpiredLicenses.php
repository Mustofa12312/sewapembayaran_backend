<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Models\{Order, LicenseKey, AuditLog};
use Illuminate\Support\Facades\DB;

#[Signature('app:check-expired-licenses')]
#[Description('Mark orders and licenses as expired if their end_date has passed.')]
class CheckExpiredLicenses extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting expired license check...');

        $expiredOrders = Order::where('status', 'ACTIVE')
            ->whereNotNull('end_date')
            ->where('end_date', '<', now())
            ->get();

        if ($expiredOrders->isEmpty()) {
            $this->info('No expired orders found.');
            return;
        }

        $count = 0;
        foreach ($expiredOrders as $order) {
            DB::transaction(function () use ($order, &$count) {
                $order->update(['status' => 'EXPIRED']);
                
                $licenses = LicenseKey::where('assigned_order_id', $order->id)->get();
                foreach ($licenses as $license) {
                    $license->update(['status' => 'EXPIRED']);
                }

                // Log this action for Audit Logs
                AuditLog::create([
                    'action' => 'EXPIRE_ORDER',
                    'entity' => 'ORDER',
                    'entity_id' => $order->id,
                    'before' => 'ACTIVE',
                    'after' => 'EXPIRED',
                    'ip_address' => 'SYSTEM',
                    'user_agent' => 'CRON'
                ]);
                $count++;
            });
        }

        $this->info("Successfully expired {$count} orders.");
    }
}
