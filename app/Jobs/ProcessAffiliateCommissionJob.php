<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;
use App\Models\Customer;
use App\Models\AffiliateCommission;
use Illuminate\Support\Facades\Log;

class ProcessAffiliateCommissionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;

    /**
     * Create a new job instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->order->customer_id) {
            $customer = Customer::find($this->order->customer_id);
            if ($customer && $customer->referrer_id) {
                // Grant 10% commission
                $commissionAmount = $this->order->snapshot_price * 0.10;
                AffiliateCommission::create([
                    'customer_id' => $customer->referrer_id,
                    'order_id' => $this->order->id,
                    'amount' => $commissionAmount,
                    'status' => 'PENDING'
                ]);
                Log::info("AFFILIATE: Granted Rp{$commissionAmount} commission to Customer ID {$customer->referrer_id}");
            }
        }
    }
}
