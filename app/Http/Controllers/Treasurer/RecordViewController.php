<?php

namespace App\Http\Controllers\Treasurer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\SalesTransaction;
use App\Models\Expense;
use App\Models\EggProduct;
use App\Models\FarmStat;
use App\Models\User;
use App\Models\FinancialReport;

class RecordViewController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        // --- 1. Fetch Sales Transactions (Paginated) ---
        $sales = SalesTransaction::with('user')
            ->when($request->start_date, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->end_date, fn($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest()
            ->paginate(15, ['*'], 'sales_page') // Use named pagination for multiple paginators
            ->withQueryString();

        // --- 2. Fetch Expenses (Paginated) ---
        $expenses = Expense::with('user')
            ->when($request->start_date, fn($q, $d) => $q->whereDate('expense_date', '>=', $d))
            ->when($request->end_date, fn($q, $d) => $q->whereDate('expense_date', '<=', $d))
            ->latest()
            ->paginate(15, ['*'], 'expenses_page') // Use named pagination
            ->withQueryString();

        // --- 3. Fetch Current Inventory Status (Not paginated) ---
        $eggInventory = EggProduct::orderBy('name')->get(['name', 'stock_quantity']);
        $chickenStock = FarmStat::where('stat_key', 'current_chicken_stock')->value('stat_value');

        // --- 4. Fetch Staff Activity Audit (NOW FILTERED BY DATE) ---
        $staffAudit = User::whereIn('role', ['staff-production', 'staff-marketing'])
                            ->withCount([
                                'productionLogs' => function ($query) use ($request) {
                                    $query->when($request->start_date, fn($q, $d) => $q->whereDate('log_date', '>=', $d))
                                          ->when($request->end_date, fn($q, $d) => $q->whereDate('log_date', '<=', $d));
                                },
                                'expenses' => function ($query) use ($request) {
                                    $query->when($request->start_date, fn($q, $d) => $q->whereDate('expense_date', '>=', $d))
                                          ->when($request->end_date, fn($q, $d) => $q->whereDate('expense_date', '<=', $d));
                                },
                                'salesTransactions' => function ($query) use ($request) {
                                    $query->when($request->start_date, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
                                          ->when($request->end_date, fn($q, $d) => $q->whereDate('created_at', '<=', $d));
                                },
                            ])
                            ->get();

        // --- 5. Fetch Financial Reports (Paginated) ---
        $financialReports = FinancialReport::with('generatedBy')
            ->when($request->start_date, fn($q, $d) => $q->whereDate('start_date', '>=', $d))
            ->when($request->end_date, fn($q, $d) => $q->whereDate('end_date', '<=', $d))
            ->latest()
            ->paginate(15, ['*'], 'financial_page')
            ->withQueryString();
        
        // --- 6. Count reviewed financial reports (approved/rejected) for notification badge ---
        $reviewedReportsCount = FinancialReport::where('generated_by', $request->user()->id)
            ->whereIn('status', ['approved', 'rejected'])
            ->whereNotNull('reviewed_at')
            ->count();
        
        // Return all the separate data props to the component
        return Inertia::render('Treasurer/ViewSpecificRecords', [
            'sales' => $sales,
            'expenses' => $expenses,
            'financialReports' => $financialReports,
            'inventory' => [
                'eggs' => $eggInventory,
                'chickens' => $chickenStock ?? 0, // Default to 0 if null
            ],
            'staffAudit' => $staffAudit,
            'filters' => $request->only(['start_date', 'end_date']),
            'reviewedReportsCount' => $reviewedReportsCount,
        ]);
    }
}