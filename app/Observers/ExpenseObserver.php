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
        AuditLog::createWithRequest([
            'user_id' => $expense->user_id,
            'action' => 'expense_created',
            'log_entry' => "`{$userName}` ({$role}) logged a new expense for `{$expense->category}` totaling ₱{$expense->amount}."
        ], request());
    }

    /**
     * Handle the Expense "updated" event.
     */
    public function updated(Expense $expense): void
    {
        if ($expense->wasChanged()) {
            $userName = Auth::user() ? Auth::user()->name : 'System';
            $changes = [];
            
            if ($expense->wasChanged('amount')) {
                $changes[] = "amount from ₱{$expense->getOriginal('amount')} to ₱{$expense->amount}";
            }
            if ($expense->wasChanged('category')) {
                $changes[] = "category from `{$expense->getOriginal('category')}` to `{$expense->category}`";
            }
            if ($expense->wasChanged('description')) {
                $changes[] = "description updated";
            }
            if ($expense->wasChanged('expense_date')) {
                $changes[] = "date updated";
            }
            
            if (!empty($changes)) {
                $changesText = implode(', ', $changes);
                AuditLog::createWithRequest([
                    'user_id' => $expense->user_id,
                    'action' => 'expense_updated',
                    'log_entry' => "`{$userName}` updated expense (ID: `{$expense->id}`): {$changesText}."
                ], request());
            }
        }
    }

    /**
     * Handle the Expense "deleted" event.
     */
    public function deleted(Expense $expense): void
    {
        $userName = Auth::user() ? Auth::user()->name : 'System';
        AuditLog::createWithRequest([
            'user_id' => Auth::id(),
            'action' => 'expense_deleted',
            'log_entry' => "`{$userName}` deleted expense (ID: `{$expense->id}`) for `{$expense->category}` totaling ₱{$expense->amount}."
        ], request());
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
