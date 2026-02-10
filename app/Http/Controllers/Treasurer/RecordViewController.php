<?php

namespace App\Http\Controllers\Treasurer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\SalesTransaction;
use App\Models\Expense;
use App\Models\EggProduct;
use App\Models\FarmStat;
use App\Models\FinancialReport;
use App\Models\ChickenStockLog;
use App\Models\FeedUsageLog;
use App\Models\Collectible;
use Illuminate\Pagination\LengthAwarePaginator;

class RecordViewController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => [
                'nullable',
                'date',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->filled('start_date') && $value < $request->start_date) {
                        $fail('The end date must be on or after the start date.');
                    }
                },
            ],
            'feed_entry_type' => ['nullable', 'string', 'in:addition,deduction'],
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

        // --- 2.5. Fetch Chicken Stock Logs (Paginated) ---
        $chickenStockLogs = ChickenStockLog::with('user')
            ->when($request->start_date, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->end_date, fn($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest()
            ->paginate(15, ['*'], 'chicken_page')
            ->withQueryString();

        // --- 2.6. Fetch Collectibles (Paginated) ---
        $collectibles = Collectible::with(['salesTransaction', 'payments.recordedBy'])
            ->when($request->start_date, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->end_date, fn($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest()
            ->paginate(15, ['*'], 'collectibles_page')
            ->withQueryString();

        // --- 2.7. Fetch Merged Feed Transactions (Additions + Deductions) ---
        $feedUsageLogs = $this->buildFeedTransactions($request);

        // --- 3. Fetch Current Inventory Status (Not paginated) ---
        $eggInventory = EggProduct::orderBy('name')->get(['name', 'stock_quantity']);
        $chickenStock = FarmStat::where('stat_key', 'current_chicken_stock')->value('stat_value');
        $feedStock = (float) (FarmStat::where('stat_key', 'current_feed_stock_kg')->value('stat_value') ?? 0);

        // --- 4. Fetch Financial Reports (Paginated) ---
        $financialReports = FinancialReport::with('generatedBy')
            ->when($request->start_date, fn($q, $d) => $q->whereDate('start_date', '>=', $d))
            ->when($request->end_date, fn($q, $d) => $q->whereDate('end_date', '<=', $d))
            ->latest()
            ->paginate(15, ['*'], 'financial_page')
            ->withQueryString();
        
        // --- 5. Count reviewed financial reports (approved/rejected) for notification badge ---
        $reviewedReportsCount = FinancialReport::where('generated_by', $request->user()->id)
            ->whereIn('status', ['approved', 'rejected'])
            ->whereNotNull('reviewed_at')
            ->count();
        
        // Return all the separate data props to the component
        return Inertia::render('Treasurer/ViewSpecificRecords', [
            'sales' => $sales,
            'expenses' => $expenses,
            'chickenStockLogs' => $chickenStockLogs,
            'collectibles' => $collectibles,
            'feedUsageLogs' => $feedUsageLogs,
            'financialReports' => $financialReports,
            'inventory' => [
                'eggs' => $eggInventory,
                'chickens' => $chickenStock ?? 0, // Default to 0 if null
                'feeds' => $feedStock, // Feed stock in kg
            ],
            'filters' => $request->only(['start_date', 'end_date', 'feed_entry_type']),
            'reviewedReportsCount' => $reviewedReportsCount,
        ]);
    }

    /**
     * Build merged feed transactions (additions from Feeds expenses + deductions from FeedUsageLog)
     */
    private function buildFeedTransactions(Request $request): LengthAwarePaginator
    {
        $deductionsQuery = FeedUsageLog::query()->with('user');
        $additionsQuery = Expense::query()
            ->where('category', 'Feeds')
            ->whereNotNull('feed_quantity_kg')
            ->where('feed_quantity_kg', '>', 0)
            ->with('user');

        if ($request->filled('start_date')) {
            $deductionsQuery->whereDate('created_at', '>=', $request->start_date);
            $additionsQuery->whereDate('expense_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $deductionsQuery->whereDate('created_at', '<=', $request->end_date);
            $additionsQuery->whereDate('expense_date', '<=', $request->end_date);
        }

        $deductions = $deductionsQuery->get()->map(fn ($log) => [
            'id' => 'feed-' . $log->id,
            'reference' => 'FEED-' . $log->id,
            'date' => $log->created_at,
            'entry_type' => 'deduction',
            'quantity_kg' => (float) $log->quantity_kg,
            'recorded_by' => $log->user?->name ?? 'N/A',
            'receipt_number' => null,
            'receipt_image_url' => null,
        ]);

        $additions = $additionsQuery->get()->map(fn ($exp) => [
            'id' => 'exp-' . $exp->id,
            'reference' => 'EXP-' . $exp->id,
            'date' => $exp->expense_date ?? $exp->created_at,
            'entry_type' => 'addition',
            'quantity_kg' => (float) $exp->feed_quantity_kg,
            'recorded_by' => $exp->user?->name ?? 'N/A',
            'receipt_number' => $exp->description,
            'receipt_image_url' => $exp->receipt_image_url,
        ]);

        $merged = $deductions->concat($additions)->sortByDesc('date')->values();

        // Filter by entry type (added vs taken) if specified
        if ($request->filled('feed_entry_type') && in_array($request->feed_entry_type, ['addition', 'deduction'])) {
            $merged = $merged->filter(fn ($item) => $item['entry_type'] === $request->feed_entry_type)->values();
        }

        $perPage = 15;
        $currentPage = LengthAwarePaginator::resolveCurrentPage('feed_page');
        $paginator = new LengthAwarePaginator(
            $merged->forPage($currentPage, $perPage)->values(),
            $merged->count(),
            $perPage,
            $currentPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'pageName' => 'feed_page']
        );
        return $paginator->withQueryString();
    }
}