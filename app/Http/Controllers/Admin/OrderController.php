<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index() { return Order::with('product', 'package', 'payments')->latest()->paginate(50); }
    public function show($id) { return Order::with('product', 'package', 'payments', 'licenseKeys')->findOrFail($id); }

    public function refund(Request $request, $id) {
        $request->validate(['reason' => 'required|string']);
        
        $order = Order::with('payments')->findOrFail($id);
        $payment = $order->payments()->where('status', 'PAID')->first();

        if (!$payment) {
            return response()->json(['message' => 'No paid payment found for this order.'], 400);
        }

        try {
            DB::transaction(function () use ($order, $payment, $request) {
                $paymentService = new \App\Services\PaymentService();
                $paymentService->processRefund($payment, $request->reason);

                $order->update(['status' => 'REFUNDED']);

                // Revoke licenses
                foreach ($order->licenseKeys as $license) {
                    $license->update([
                        'status' => 'AVAILABLE',
                        'assigned_order_id' => null,
                        'assigned_at' => null,
                        'expires_at' => null
                    ]);
                }
                
                // Note: Realistically, you'd also cancel active subscriptions here
            });
            return response()->json(['message' => 'Order refunded successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function markAsPaid(Request $request, $id) {
        $order = Order::with('payments')->findOrFail($id);
        
        if ($order->status !== 'PENDING_PAYMENT') {
            return response()->json(['message' => 'Order is not in pending status.'], 400);
        }

        $payment = $order->payments()->where('status', 'PENDING')->first();
        if (!$payment) {
            return response()->json(['message' => 'No pending payment found.'], 400);
        }

        try {
            DB::transaction(function () use ($order, $payment) {
                // Update statuses
                $payment->update(['status' => 'PAID']);
                $order->update(['status' => 'ACTIVE']);
                
                // Assign license
                $licenseService = new \App\Services\LicenseService();
                $licenseService->assignLicense($order);

                // Reactivate Grace Period subscription if any
                $customer = \App\Models\Customer::where('email', $order->customer_email)->first();
                if ($customer) {
                    $sub = \App\Models\Subscription::where('customer_id', $customer->id)
                        ->where('package_id', $order->package_id)
                        ->where('status', 'GRACE_PERIOD')
                        ->first();
                        
                    if ($sub) {
                        $sub->update([
                            'status' => 'ACTIVE',
                            'next_billing_date' => now()->addMonth()
                        ]);
                    }
                }

                // Log manual event
                $paymentService = new \App\Services\PaymentService();
                $paymentService->logEvent($payment, 'manual_fulfillment', ['source' => 'admin_dashboard']);
                
                // Send Paid Email
                \Illuminate\Support\Facades\Mail::to($order->customer_email)->send(new \App\Mail\OrderPaidMail($order));
            });
            return response()->json(['message' => 'Order manually fulfilled successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}