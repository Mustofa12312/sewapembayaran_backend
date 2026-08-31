<?php
namespace App\Services;

use App\Models\Order;
use App\Jobs\ProcessAffiliateCommissionJob;

class AffiliateService
{
    public function processCommission(Order $order): void
    {
        ProcessAffiliateCommissionJob::dispatch($order);
    }
}
