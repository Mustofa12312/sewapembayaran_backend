<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = ['customer_id', 'package_id', 'status', 'next_billing_date', 'midtrans_token'];

    public function customer() {
        return $this->belongsTo(Customer::class);
    }

    public function package() {
        return $this->belongsTo(Package::class);
    }
}
