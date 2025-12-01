<?php

namespace App\Observers;

use App\Models\Expense;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class ExpenseObserver
{
    /**
     * Handle the Expense "created" event.
     */
    public function created(Expense $expense): void
    {
        $userName = Auth::user() ? Auth::user()->name : 'System';
        $role = Auth::user() ? Auth::user()->role : '';
        AuditLog::create([
            'user_id' => $expense->user_id,
            'action' => 'expense_created',
            'log_entry' => "{$userName} ({$role}) logged a new expense for '{$expense->category}' totaling ₱{$expense->amount}."
        ]);
    
    }

    /**
     * Handle the Expense "updated" event.
     */
    public function updated(Expense $expense): void
    {
        //
    }

    /**
     * Handle the Expense "deleted" event.
     */
    public function deleted(Expense $expense): void
    {
        //
    }

    /**
     * Handle the Expense "restored" event.
     */
    public function restored(Expense $expense): void
    {
        //
    }

    /**
     * Handle the Expense "force deleted" event.
     */
    public function forceDeleted(Expense $expense): void
    {
        //
    }
}
