<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = ['user_id', 'action', 'log_entry'];

    public function user() {
        return $this->belongsTo(\App\Models\User::class);
    }
}
