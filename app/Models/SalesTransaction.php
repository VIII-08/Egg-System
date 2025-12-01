<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Import BelongsTo
use Illuminate\Database\Eloquent\Relations\HasMany;   // Import HasMany

class SalesTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_amount',
        'customer_name',
    ];

    /**
     * Get the user that recorded the transaction.
     * THIS IS THE FUNCTION WE ARE ADDING TO FIX THE ERROR.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the items included in the sale.
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}