<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Order;

class CustomerOrderController extends Controller
{
    public function index(Request $request)
    {
        return Order::where('customer_id', $request->user()->id)
            ->with('product', 'package', 'payment')
            ->latest()
            ->get();
    }
}