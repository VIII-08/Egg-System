<?php

namespace App\Observers;

use App\Models\ProductionLog;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProductionLogObserver
{
    /**
     * Handle the ProductionLog "created" event.
     */
    public function created(ProductionLog $log) {
        $userName = $log->user->name ?? 'System';
        $productName = $log->eggProduct->name ?? 'Unknown Product';
        $logEntry = "`{$userName}` logged a production of `{$log->quantity}` pcs for `{$productName}`.";
        AuditLog::createWithRequest(['user_id' => $log->user_id, 'action' => 'production_logged', 'log_entry' => $logEntry], request());
        
        // Set the "data_changed" flag for the scheduler to pick up
        // The scheduler will check this flag every minute and run forecasting if needed
        // This is safe for Hostinger shared hosting (no exec/popen required)
        Cache::put('data_changed', true, now()->addDay());
        
        Log::info('Data change flag set after production log. Scheduler will trigger forecasting.');
    }

    /**
     * Handle the ProductionLog "updated" event.
     */
    public function updated(ProductionLog $productionLog): void
    {
        if ($productionLog->wasChanged()) {
            $userName = Auth::user() ? Auth::user()->name : ($productionLog->user->name ?? 'System');
            $productName = $productionLog->eggProduct->name ?? 'Unknown Product';
            $changes = [];
            
            if ($productionLog->wasChanged('quantity')) {
                $changes[] = "quantity from `{$productionLog->getOriginal('quantity')}` to `{$productionLog->quantity}`";
            }
            if ($productionLog->wasChanged('log_date')) {
                $changes[] = "date updated";
            }
            if ($productionLog->wasChanged('egg_product_id')) {
                $changes[] = "product changed";
            }
            
            if (!empty($changes)) {
                $changesText = implode(', ', $changes);
                AuditLog::createWithRequest([
                    'user_id' => Auth::id() ?? $productionLog->user_id,
                    'action' => 'production_log_updated',
                    'log_entry' => "`{$userName}` updated production log (ID: `{$productionLog->id}`) for `{$productName}`: {$changesText}."
                ], request());
            }
        }
    }

    /**
     * Handle the ProductionLog "deleted" event.
     */
    public function deleted(ProductionLog $productionLog): void
    {
        $userName = Auth::user() ? Auth::user()->name : ($productionLog->user->name ?? 'System');
        $productName = $productionLog->eggProduct->name ?? 'Unknown Product';
        AuditLog::createWithRequest([
            'user_id' => Auth::id() ?? $productionLog->user_id,
            'action' => 'production_log_deleted',
            'log_entry' => "`{$userName}` deleted production log (ID: `{$productionLog->id}`) for `{$productName}` with quantity `{$productionLog->quantity}` pcs."
        ], request());
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
