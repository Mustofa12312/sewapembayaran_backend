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
            $sub->update([
                'next_billing_date' => now()->addMonth()
            ]);
            
            Log::info("MOCK RECURRING: Auto-charged Subscription {$sub->id} for Package {$sub->package_id}. Next billing: {$sub->next_billing_date}");
            $renewed++;
        }

        $this->info("Successfully processed {$renewed} subscriptions.");
    }
}
