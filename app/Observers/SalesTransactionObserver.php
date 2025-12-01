<?php

namespace App\Observers;

use App\Models\SalesTransaction;
use App\Models\AuditLog;

class SalesTransactionObserver
{
    /**
     * Handle the SalesTransaction "created" event.
     */
    public function created(SalesTransaction $sale): void
    {
        $userName = $sale->user->name ?? 'System';
        $logEntry = "`{$userName}` recorded a new sale (ID: `{$sale->id}`) with a total of `₱{$sale->total_amount}`.";
        AuditLog::create(['user_id' => $sale->user_id, 'action' => 'sale_created', 'log_entry' => $logEntry]);
    }

    /**
     * Handle the SalesTransaction "updated" event.
     */
    public function updated(SalesTransaction $salesTransaction): void
    {
        //
    }

    /**
     * Handle the SalesTransaction "deleted" event.
     */
    public function deleted(SalesTransaction $salesTransaction): void
    {
        //
    }

    /**
     * Handle the SalesTransaction "restored" event.
     */
    public function restored(SalesTransaction $salesTransaction): void
    {
        //
    }

    /**
     * Handle the SalesTransaction "force deleted" event.
     */
    public function forceDeleted(SalesTransaction $salesTransaction): void
    {
        //
    }
}
