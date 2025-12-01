<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Models\ProductionLog;
use App\Models\Expense;
use App\Models\ChickenStockLog;
use App\Models\SalesTransaction; 

class RecordViewController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Start with the basic data that is always present
        $props = [
            'filters' => $request->only(['start_date', 'end_date']),
            'userRole' => $user->role,
        ];
    
        // Define the base queries for each record type,
        // applying the user_id filter from the start.
        $productionQuery = ProductionLog::query()->where('user_id', $user->id);
        $expenseQuery = Expense::query()->where('user_id', $user->id);
        $chickenQuery = ChickenStockLog::query()->where('user_id', $user->id);
        $salesQuery = SalesTransaction::query()->where('user_id', $user->id);
    
        // Apply date filters if they exist. This is the crucial logic.
        if ($request->filled('start_date')) {
            $productionQuery->whereDate('log_date', '>=', $request->input('start_date'));
            $expenseQuery->whereDate('expense_date', '>=', $request->input('start_date'));
            $chickenQuery->whereDate('created_at', '>=', $request->input('start_date'));
            $salesQuery->whereDate('created_at', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $productionQuery->whereDate('log_date', '<=', $request->input('end_date'));
            $expenseQuery->whereDate('expense_date', '<=', $request->input('end_date'));
            $chickenQuery->whereDate('created_at', '<=', $request->input('end_date'));
            $salesQuery->whereDate('created_at', '<=', $request->input('end_date'));
        }
    
        // Now, execute the queries and paginate the results based on the user's role.
        if ($user->role === 'staff-production') {
            $props['productionLogs'] = $productionQuery->with('eggProduct')->latest()->paginate(15)->withQueryString();
            $props['chickenStockLogs'] = $chickenQuery->latest()->paginate(15)->withQueryString();
        } elseif ($user->role === 'staff-marketing') {
            $props['salesTransactions'] = $salesQuery->latest()->paginate(15)->withQueryString();
        }
        
        // Expenses are fetched for both roles.
        $props['expenses'] = $expenseQuery->latest()->paginate(15)->withQueryString();
    
        return Inertia::render('Staff/ViewMyRecords', $props);
    }
}
