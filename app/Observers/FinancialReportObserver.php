<?php

namespace App\Observers;

use App\Models\FinancialReport;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class FinancialReportObserver
{
    /**
     * Handle the FinancialReport "created" event.
     * This fires when the Treasurer first submits the report.
     */
    public function created(FinancialReport $report): void
    {
        $userName = Auth::user() ? Auth::user()->name : 'A user';
        
        $logEntry = "`{$userName}` submitted the Financial Report for the period `{$report->start_date}` to `{$report->end_date}` for review.";

        AuditLog::create([
            'user_id' => $report->generated_by,
            'action' => 'financial_report_submitted',
            'log_entry' => $logEntry,
        ]);
    }
    
    /**
     * Handle the FinancialReport "updated" event.
     * This will fire when the Admin approves or rejects the report.
     */
    public function updated(FinancialReport $report): void
    {
        // Only log if the 'status' field has been changed.
        if ($report->isDirty('status')) {
            // Ignore the initial change from draft to submitted, as 'created' handles it.
            if ($report->getOriginal('status') === 'submitted') {
                $adminName = Auth::user() ? Auth::user()->name : 'An admin';
                $statusAction = $report->status; // 'approved' or 'rejected'

                $logEntry = "`{$adminName}` `{$statusAction}` the Financial Report for the period `{$report->start_date}` to `{$report->end_date}`.";
                
                AuditLog::create([
                    'user_id' => $report->reviewed_by,
                    'action' => "financial_report_{$statusAction}",
                    'log_entry' => $logEntry,
                ]);
            }
        }
    }
}