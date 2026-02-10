<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChickenStockLog extends Model
{
    protected $fillable = [ 'user_id', 'adjustment_type', 'quantity', 'reason', 'notes', ];

    /**
     * Get the user who made this adjustment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
