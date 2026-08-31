<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $fillable = ['product_id', 'name', 'description', 'price', 'duration_value', 'duration_unit', 'is_unlimited', 'status', 'is_recurring'];

    public function product() {
        return $this->belongsTo(Product::class);
    }

    public function features() {
        return $this->hasMany(PackageFeature::class);
    }
}
