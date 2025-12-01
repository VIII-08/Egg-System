<?php

namespace App\Observers;

use App\Models\ProductionLog;
use App\Models\AuditLog;

class ProductionLogObserver
{
    /**
     * Handle the ProductionLog "created" event.
     */
    public function created(ProductionLog $log) {
        $userName = $log->user->name ?? 'System';
        $productName = $log->eggProduct->name ?? 'Unknown Product';
        $logEntry = "`{$userName}` logged a production of `{$log->quantity}` pcs for `{$productName}`.";
        AuditLog::create(['user_id' => $log->user_id, 'action' => 'production_logged', 'log_entry' => $logEntry]);
    }

    /**
     * Handle the ProductionLog "updated" event.
     */
    public function updated(ProductionLog $productionLog): void
    {
        //
    }

    /**
     * Handle the ProductionLog "deleted" event.
     */
    public function deleted(ProductionLog $productionLog): void
    {
        //
    }

    /**
     * Handle the ProductionLog "restored" event.
     */
    public function restored(ProductionLog $productionLog): void
    {
        //
    }

    /**
     * Handle the ProductionLog "force deleted" event.
     */
    public function forceDeleted(ProductionLog $productionLog): void
    {
        //
    }
}
