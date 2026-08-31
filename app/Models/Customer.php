<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = ['name', 'email', 'password', 'phone', 'remember_token', 'referral_code', 'referrer_id'];

    public function orders() {
        return $this->hasMany(Order::class);
    }

    public function subscriptions() {
        return $this->hasMany(Subscription::class);
    }

    public function affiliateCommissions() {
        return $this->hasMany(AffiliateCommission::class, 'customer_id');
    }
}
