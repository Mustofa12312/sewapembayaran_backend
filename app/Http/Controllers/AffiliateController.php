<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\{Customer, AffiliateCommission};

class AffiliateController extends Controller
{
    public function dashboard(Request $request)
    {
        $customer = $request->user();
        
        $referralsCount = Customer::where('referrer_id', $customer->id)->count();
        $totalCommission = AffiliateCommission::where('customer_id', $customer->id)->sum('amount');
        
        return response()->json([
            'referral_code' => $customer->referral_code,
            'total_referrals' => $referralsCount,
            'total_commission' => $totalCommission
        ]);
    }
}
