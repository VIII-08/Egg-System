<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Expense extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'description',
        'category', // <-- Add this
        'feed_quantity_kg',
        'amount',
        'expense_date',
        'receipt_image_path', // <-- And this
    ];

    protected $appends = ['receipt_image_url'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function getReceiptImageUrlAttribute(): ?string
    {
        if ($this->receipt_image_path) {
            return Storage::url($this->receipt_image_path);
        }

        return null;
    }
}
