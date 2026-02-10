<?php

namespace App\Observers;

use App\Models\ChickenStockLog;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class ChickenStockLogObserver
{
    /**
     * Handle the ChickenStockLog "created" event.
     */
    public function created(ChickenStockLog $chickenStockLog): void
    {
        $userName = Auth::user() ? Auth::user()->name : 'A user';
        
        // Customize the message based on the adjustment type
        $actionVerb = $chickenStockLog->adjustment_type === 'addition' ? 'added' : 'removed';
        
        $logEntry = "`{$userName}` `{$actionVerb}` `{$chickenStockLog->quantity}` chickens due to '`{$chickenStockLog->reason}`'.";

        AuditLog::createWithRequest([
            'user_id' => $chickenStockLog->user_id,
            'action' => 'chicken_stock_adjusted',
            'log_entry' => $logEntry,
        ], request());
    
    }

    /**
     * Handle the ChickenStockLog "updated" event.
     */
    public function updated(ChickenStockLog $chickenStockLog): void
    {
        if ($chickenStockLog->wasChanged()) {
            $userName = Auth::user() ? Auth::user()->name : 'System';
            $changes = [];
            
            if ($chickenStockLog->wasChanged('quantity')) {
                $changes[] = "quantity from `{$chickenStockLog->getOriginal('quantity')}` to `{$chickenStockLog->quantity}`";
            }
            if ($chickenStockLog->wasChanged('adjustment_type')) {
                $changes[] = "adjustment type from `{$chickenStockLog->getOriginal('adjustment_type')}` to `{$chickenStockLog->adjustment_type}`";
            }
            if ($chickenStockLog->wasChanged('reason')) {
                $changes[] = "reason updated";
            }
            
            if (!empty($changes)) {
                $changesText = implode(', ', $changes);
                AuditLog::createWithRequest([
                    'user_id' => Auth::id() ?? $chickenStockLog->user_id,
                    'action' => 'chicken_stock_log_updated',
                    'log_entry' => "`{$userName}` updated chicken stock log (ID: `{$chickenStockLog->id}`): {$changesText}."
                ], request());
            }
        }
    }

    /**
     * Handle the ChickenStockLog "deleted" event.
     */
    public function deleted(ChickenStockLog $chickenStockLog): void
    {
        $userName = Auth::user() ? Auth::user()->name : 'System';
        AuditLog::createWithRequest([
            'user_id' => Auth::id() ?? $chickenStockLog->user_id,
            'action' => 'chicken_stock_log_deleted',
            'log_entry' => "`{$userName}` deleted chicken stock log (ID: `{$chickenStockLog->id}`) - {$chickenStockLog->adjustment_type} of `{$chickenStockLog->quantity}` chickens."
        ], request());
    }

    /**
     * Handle the ChickenStockLog "restored" event.
     */
    public function restored(ChickenStockLog $chickenStockLog): void
    {
        //
    }

    /**
     * Handle the ChickenStockLog "force deleted" event.
     */
    public function forceDeleted(ChickenStockLog $chickenStockLog): void
    {
        //
    }
}
