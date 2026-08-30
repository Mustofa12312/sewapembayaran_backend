<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = [];

    public function product() {
        return $this->belongsTo(Product::class);
    }
    
    public function package() {
        return $this->belongsTo(Package::class);
    }
    
    public function licenseKeys() {
        return $this->belongsToMany(LicenseKey::class, 'order_license_keys');
    }
    
    public function coupon() {
        return $this->belongsTo(Coupon::class);
    }
    
    public function payment() {
        return $this->hasOne(Payment::class);
    }
}
