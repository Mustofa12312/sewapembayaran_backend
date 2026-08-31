<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'thumbnail', 'category', 'status'];

    public function packages() {
        return $this->hasMany(Package::class);
    }
}
