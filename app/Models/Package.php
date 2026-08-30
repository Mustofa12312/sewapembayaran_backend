<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $guarded = [];

    public function product() {
        return $this->belongsTo(Product::class);
    }

    public function features() {
        return $this->hasMany(PackageFeature::class);
    }
}
