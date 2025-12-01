<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_transaction_id',
        'egg_product_id',
        'quantity',
        'price',
    ];

    public function transaction(): BelongsTo
    {
        // By default, Laravel will look for a 'transaction_id' column.
        // Since our column is named 'sales_transaction_id', we must specify it.
        return $this->belongsTo(SalesTransaction::class, 'sales_transaction_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(EggProduct::class, 'egg_product_id');
    }
}
