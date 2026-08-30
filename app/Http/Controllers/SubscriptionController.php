<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\{Subscription, Order, Payment, LicenseKey, OrderLicenseKey};
use Illuminate\Support\Str;

class SubscriptionController extends Controller
{
    public function renew(Request $request, $token)
    {
        $order = Order::where('secure_token', $token)->with('package')->firstOrFail();
        
        $orderLicense = OrderLicenseKey::where('order_id', $order->id)->first();
        if (!$orderLicense) {
            return response()->json(['message' => 'No active license to renew.'], 400);
        }

        $price = $order->package->price;
        $newOrderNumber = 'RNW-' . date('Ymd') . '-' . strtoupper(Str::random(6));
        
        $payment = Payment::create([
            'order_id' => $order->id,
            'amount' => $price,
            'status' => 'PENDING'
        ]);

        return response()->json([
            'message' => 'Renewal payment initiated.',
            'payment_id' => $payment->id,
            'amount' => $price,
            'mock_pay_url' => '/api/payments/midtrans/webhook?order_id=' . $order->order_number . '&transaction_status=settlement&gross_amount=' . $price
        ]);
    }

    public function cron(Request $request)
    {
        $dueSubscriptions = Subscription::where('status', 'ACTIVE')
            ->whereDate('next_billing_date', '<=', now())
            ->get();

        $renewed = 0;
        foreach ($dueSubscriptions as $sub) {
            $sub->next_billing_date = now()->addMonth();
            $sub->save();
            
            \Illuminate\Support\Facades\Log::info("MOCK RECURRING: Auto-charged Subscription {$sub->id} for Package {$sub->package_id}. Next billing: {$sub->next_billing_date}");
            $renewed++;
        }

        return response()->json(['message' => 'Cron executed', 'renewed_count' => $renewed]);
    }
}
