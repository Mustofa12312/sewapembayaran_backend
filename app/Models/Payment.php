<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['order_id', 'midtrans_transaction_id', 'gateway', 'amount', 'status', 'payment_method', 'paid_at', 'raw_response'];
    
    public function order() {
        return $this->belongsTo(Order::class);
    }
    
    public function paymentEvents() {
        return $this->hasMany(PaymentEvent::class);
    }
}
