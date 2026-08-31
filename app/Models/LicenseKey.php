<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LicenseKey extends Model
{
    protected $fillable = ['product_id', 'package_id', 'license_key', 'status', 'assigned_order_id', 'assigned_at', 'expires_at'];

    public function product() {
        return $this->belongsTo(Product::class);
    }
    
    public function package() {
        return $this->belongsTo(Package::class);
    }
}
