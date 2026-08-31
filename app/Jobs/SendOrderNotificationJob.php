<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class SendOrderNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;
    protected $licenseKeyStr;

    /**
     * Create a new job instance.
     */
    public function __construct(Order $order, ?string $licenseKeyStr = null)
    {
        $this->order = $order;
        $this->licenseKeyStr = $licenseKeyStr;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("MOCK NOTIFICATION: Email Receipt sent to {$this->order->customer_email}");
        if ($this->licenseKeyStr) {
            Log::info("MOCK NOTIFICATION: WhatsApp License Key [{$this->licenseKeyStr}] sent to {$this->order->customer_phone}");
        }
    }
}
