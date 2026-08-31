<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderLicenseKey extends Model
{
    protected $fillable = ['order_id', 'license_key_id'];
    //
}
