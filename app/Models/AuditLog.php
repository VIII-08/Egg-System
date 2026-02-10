<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = ['user_id', 'action', 'log_entry', 'ip_address', 'user_agent'];

    public function user() {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Helper method to create audit log with request context
     */
    public static function createWithRequest(array $data, $request = null): self
    {
        if ($request) {
            $data['ip_address'] = $request->ip();
            $data['user_agent'] = $request->userAgent();
        }
        return self::create($data);
    }
}
