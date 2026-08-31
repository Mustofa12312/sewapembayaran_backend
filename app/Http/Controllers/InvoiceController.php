<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function download($token)
    {
        $order = Order::where('secure_token', $token)->with(['product', 'package'])->firstOrFail();
        
        if ($order->status !== 'ACTIVE' && $order->status !== 'EXPIRED') {
            return response()->json(['message' => 'Invoice only available for paid orders.'], 400);
        }

        $pdf = Pdf::loadView('invoice', compact('order'));
        return $pdf->download('invoice-' . $order->order_number . '.pdf');
    }
}
