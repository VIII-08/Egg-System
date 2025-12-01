<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; 

class DataCorrectionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'request_type',
        'reference_id',
        'description_of_error',
        'proposed_correction',
        'receipt_image_path',
    ];

    /**
     * Get the user who submitted the correction request.
     * THIS IS THE MISSING FUNCTION THAT FIXES THE ERROR.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who reviewed the request (optional).
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}