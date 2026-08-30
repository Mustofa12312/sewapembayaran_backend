<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Order, Customer, Subscription, AffiliateCommission};

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        // Require at least admin role
        if (!in_array($request->user()->role, ['admin', 'super_admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $totalRevenue = Order::where('status', 'PAID')->sum('snapshot_price');
        $totalOrders = Order::where('status', 'PAID')->count();
        $totalCustomers = Customer::count();
        $activeSubscriptions = Subscription::where('status', 'ACTIVE')->count();
        
        // Mock recent transactions
        $recentOrders = Order::with('customer')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'metrics' => [
                'revenue' => $totalRevenue,
                'orders' => $totalOrders,
                'customers' => $totalCustomers,
                'subscriptions' => $activeSubscriptions
            ],
            'recent_orders' => $recentOrders
        ]);
    }
}
