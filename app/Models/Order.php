<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['order_number', 'secure_token', 'package_id', 'product_id', 'customer_name', 'customer_email', 'customer_phone', 'snapshot_price', 'start_date', 'end_date', 'status', 'customer_id', 'coupon_id', 'discount_amount'];

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
    
    public function payments() {
        return $this->hasMany(Payment::class);
    }
}
