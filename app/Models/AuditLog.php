<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = ['action', 'entity', 'entity_id', 'before_data', 'after_data', 'ip_address', 'user_agent'];
    //
}
