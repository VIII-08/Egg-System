<?php

namespace App\Observers;

use App\Models\DataCorrectionRequest;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class DataCorrectionRequestObserver
{
    /**
     * Handle the DataCorrectionRequest "created" event.
     */
    public function created(DataCorrectionRequest $request): void
    {
        $userName = $request->user->name ?? 'An unknown user';
        
        $logEntry = "`{$userName}` submitted a new data correction request for `{$request->request_type} #{$request->reference_id}`.";

        AuditLog::create([
            'user_id' => $request->user_id,
            'action' => 'correction_request_created',
            'log_entry' => $logEntry,
        ]);
    }

    /**
     * Handle the DataCorrectionRequest "updated" event.
     */
    public function updated(DataCorrectionRequest $request): void
    {
        // We only want to log the event when the 'status' field changes.
        // isDirty('status') checks if this specific field was part of the update.
        if ($request->isDirty('status')) {
            $adminName = Auth::user() ? Auth::user()->name : 'An admin';

            // Customize the message based on the new status
            $statusAction = $request->status; // e.g., 'approved', 'rejected'

            $logEntry = "`{$adminName}` `{$statusAction}` the data correction request for `{$request->request_type} #{$request->reference_id}`.";

            AuditLog::create([
                'user_id' => $request->reviewed_by, // The admin who did the action
                'action' => "correction_request_{$statusAction}",
                'log_entry' => $logEntry,
            ]);
        }
    }
}