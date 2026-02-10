<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Collectible extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_transaction_id',
        'customer_name',
        'total_amount',
        'amount_paid',
        'balance',
        'status',
        'last_payment_date',
        'fully_paid_date',
    ];

    protected $casts = [
        'last_payment_date' => 'date',
        'fully_paid_date' => 'date',
    ];

    public function salesTransaction(): BelongsTo
    {
        return $this->belongsTo(SalesTransaction::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CollectiblePayment::class);
    }
}











