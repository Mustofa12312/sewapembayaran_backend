<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckGracePeriod extends Command
{
    protected $signature = 'app:check-grace-period';

    protected $description = 'Check subscriptions in grace period and expire them if 7 days have passed.';

    public function handle()
    {
        $this->info('Starting grace period check...');
        
        // 7 days ago
        $cutoffDate = now()->subDays(7);
        
        $expiredSubscriptions = Subscription::where('status', 'GRACE_PERIOD')
            ->whereDate('next_billing_date', '<=', $cutoffDate)
            ->get();

        if ($expiredSubscriptions->isEmpty()) {
            $this->info('No expired subscriptions found in grace period.');
            return;
        }

        $revoked = 0;
        foreach ($expiredSubscriptions as $sub) {
            $sub->update([
                'status' => 'EXPIRED'
            ]);
            
            // Revoke associated license keys if they belong to this package and customer
            \App\Models\LicenseKey::where('customer_id', $sub->customer_id)
                ->where('package_id', $sub->package_id)
                ->where('status', 'ACTIVE')
                ->update(['status' => 'EXPIRED']);
                
            Log::info("Revoked Subscription {$sub->id} and associated licenses (Grace Period Expired).");
            $revoked++;
        }

        $this->info("Successfully expired {$revoked} subscriptions.");
    }
}
