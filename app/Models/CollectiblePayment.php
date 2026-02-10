<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectiblePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'collectible_id',
        'amount',
        'payment_date',
        'payment_method',
        'recorded_by_user_id',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
    ];

    public function collectible(): BelongsTo
    {
        return $this->belongsTo(Collectible::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}











