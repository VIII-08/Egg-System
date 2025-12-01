<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'egg_product_id',
        'quantity',
        'log_date',
    ];

    /**
     * Get the user that owns the production log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the egg product associated with the log.
     */
    public function eggProduct(): BelongsTo
    {
        return $this->belongsTo(EggProduct::class);
    }
}
