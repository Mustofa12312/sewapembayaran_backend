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

        $totalRevenue = Order::whereIn('status', ['PAID', 'ACTIVE'])->sum('snapshot_price');
        $totalOrders = Order::count();
        $totalCustomers = Customer::count();
        $activeSubscriptions = Subscription::where('status', 'ACTIVE')->count();
        
        $paidOrders = Order::where('status', 'PAID')->count();
        $pendingPayments = Order::where('status', 'PENDING_PAYMENT')->count();
        $activeOrders = Order::where('status', 'ACTIVE')->count();
        $expiredOrders = Order::where('status', 'EXPIRED')->count();

        $availableLicenses = \App\Models\LicenseKey::where('status', 'AVAILABLE')->count();
        $assignedLicenses = \App\Models\LicenseKey::whereIn('status', ['ASSIGNED', 'ACTIVE'])->count();

        // Mock recent transactions
        $recentOrders = Order::orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'metrics' => [
                'revenue' => $totalRevenue,
                'orders' => $totalOrders,
                'customers' => $totalCustomers,
                'subscriptions' => $activeSubscriptions,
                'paid_orders' => $paidOrders,
                'pending_payments' => $pendingPayments,
                'active_orders' => $activeOrders,
                'expired_orders' => $expiredOrders,
                'available_licenses' => $availableLicenses,
                'assigned_licenses' => $assignedLicenses
            ],
            'recent_orders' => $recentOrders
        ]);
    }
}
