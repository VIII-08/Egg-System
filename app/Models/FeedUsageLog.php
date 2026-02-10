<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedUsageLog extends Model
{
    protected $fillable = [
        'user_id',
        'quantity_kg',
        'notes',
    ];

    /**
     * Get the user who recorded this feed usage.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
