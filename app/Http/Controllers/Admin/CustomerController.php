<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;

class CustomerController extends Controller
{
    public function index()
    {
        return Customer::withCount('orders')
            ->withSum('orders as total_spent', 'snapshot_price')
            ->latest()
            ->paginate(50);
    }
}
