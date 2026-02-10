<?php

namespace App\Observers;

use App\Models\SalesTransaction;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SalesTransactionObserver
{
    /**
     * Handle the SalesTransaction "created" event.
     */
    public function created(SalesTransaction $sale): void
    {
        $userName = $sale->user->name ?? 'System';
        $logEntry = "`{$userName}` recorded a new sale (ID: `{$sale->id}`) with a total of `₱{$sale->total_amount}`.";
        AuditLog::createWithRequest(['user_id' => $sale->user_id, 'action' => 'sale_created', 'log_entry' => $logEntry], request());
        
        // Set the "data_changed" flag for the scheduler to pick up
        // The scheduler will check this flag every minute and run forecasting if needed
        // This is safe for Hostinger shared hosting (no exec/popen required)
        Cache::put('data_changed', true, now()->addDay());
        
        Log::info('Data change flag set after sale transaction. Scheduler will trigger forecasting.');
    }

    /**
     * Handle the SalesTransaction "updated" event.
     */
    public function updated(SalesTransaction $salesTransaction): void
    {
        if ($salesTransaction->wasChanged()) {
            $userName = Auth::user() ? Auth::user()->name : ($salesTransaction->user->name ?? 'System');
            $changes = [];
            
            if ($salesTransaction->wasChanged('total_amount')) {
                $changes[] = "total amount from ₱{$salesTransaction->getOriginal('total_amount')} to ₱{$salesTransaction->total_amount}";
            }
            if ($salesTransaction->wasChanged('customer_name')) {
                $oldCustomer = $salesTransaction->getOriginal('customer_name') ?? 'None';
                $newCustomer = $salesTransaction->customer_name ?? 'None';
                $changes[] = "customer from `{$oldCustomer}` to `{$newCustomer}`";
            }
            
            if (!empty($changes)) {
                $changesText = implode(', ', $changes);
                AuditLog::createWithRequest([
                    'user_id' => Auth::id() ?? $salesTransaction->user_id,
                    'action' => 'sale_updated',
                    'log_entry' => "`{$userName}` updated sale (ID: `{$salesTransaction->id}`): {$changesText}."
                ], request());
            }
        }
    }

    /**
     * Handle the SalesTransaction "deleted" event.
     */
    public function deleted(SalesTransaction $salesTransaction): void
    {
        $userName = Auth::user() ? Auth::user()->name : ($salesTransaction->user->name ?? 'System');
        AuditLog::createWithRequest([
            'user_id' => Auth::id() ?? $salesTransaction->user_id,
            'action' => 'sale_deleted',
            'log_entry' => "`{$userName}` deleted sale (ID: `{$salesTransaction->id}`) with total amount ₱{$salesTransaction->total_amount}."
        ], request());
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
