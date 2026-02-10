<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Remittance extends Model
{
    use HasFactory;

    protected $fillable = [
        'marketing_user_id',
        'treasurer_user_id',
        'total_amount',
        'status',
    ];

    public function marketingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marketing_user_id');
    }

    public function treasurerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'treasurer_user_id');
    }
}











