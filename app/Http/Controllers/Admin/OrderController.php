<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Order;
class OrderController extends Controller
{
    public function index() { return Order::with('product', 'package', 'payment', 'user')->latest()->paginate(50); }
    public function show($id) { return Order::with('product', 'package', 'payment', 'licenseKeys')->findOrFail($id); }
}