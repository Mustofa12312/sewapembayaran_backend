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
}