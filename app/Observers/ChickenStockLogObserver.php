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

        AuditLog::create([
            'user_id' => $chickenStockLog->user_id,
            'action' => 'chicken_stock_adjusted',
            'log_entry' => $logEntry,
        ]);
    
    }

    /**
     * Handle the ChickenStockLog "updated" event.
     */
    public function updated(ChickenStockLog $chickenStockLog): void
    {
        //
    }

    /**
     * Handle the ChickenStockLog "deleted" event.
     */
    public function deleted(ChickenStockLog $chickenStockLog): void
    {
        //
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
