<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Import this

class FinancialReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'generated_by', 'start_date', 'end_date', 'total_revenue',
        'total_expenses', 'net_income', 'report_data', 'status', 'reviewed_by', 'reviewed_at', 'admin_notes'
    ];
    
    // The $casts property automatically converts the JSON string to and from an array
    protected $casts = [
        'report_data' => 'array',
    ];

    /**
     * Get the user who generated the financial report.
     * THIS IS THE MISSING FUNCTION THAT FIXES THE ERROR.
     */
    public function generatedBy(): BelongsTo
    {
        // This tells Laravel that the 'generated_by' column on this table
        // is the foreign key that links to the 'id' of a User.
        return $this->belongsTo(User::class, 'generated_by');
    }
}