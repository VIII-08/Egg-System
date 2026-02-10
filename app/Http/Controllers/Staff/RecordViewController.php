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
use App\Models\Collectible;
use App\Models\FeedUsageLog;
use App\Models\FarmStat;
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
            'production_view' => ['nullable', 'string', 'in:by_size,by_batch'],
            'feed_entry_type' => ['nullable', 'string', 'in:addition,deduction'],
        ]);

        $user = Auth::user();

        // Start with the basic data that is always present
        $props = [
            'filters' => $request->only(['start_date', 'end_date', 'production_view', 'feed_entry_type']),
            'userRole' => $user->role,
        ];
        
        $productionView = $request->input('production_view', 'by_size');
    
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
            // Handle production logs based on view type
            if ($productionView === 'by_batch') {
                $logs = $productionQuery->with(['eggProduct', 'user'])
                    ->whereNotNull('batch_reference')
                    ->latest()
                    ->get();

                $props['productionLogs'] = $logs->groupBy('batch_reference')->map(function ($group) {
                    $first = $group->first();
                    return [
                        'batch_reference' => $first->batch_reference,
                        'log_date' => $first->log_date,
                        'created_at' => $first->created_at,
                        'logged_by' => $first->user?->name ?? 'Me',
                        'total_quantity' => $group->sum('quantity'),
                        'items' => $group->map(function ($log) {
                            return [
                                'egg_size' => $log->eggProduct->name ?? 'Unknown',
                                'quantity' => (int) $log->quantity,
                            ];
                        })->values(),
                    ];
                })->values();
            } else {
                $props['productionLogs'] = $productionQuery->with('eggProduct')->latest()->paginate(15)->withQueryString();
            }
            $props['chickenStockLogs'] = $chickenQuery->latest()->paginate(15)->withQueryString();
        } elseif ($user->role === 'staff-marketing') {
            $props['salesTransactions'] = $salesQuery->with(['items.product'])->latest()->paginate(15)->withQueryString();
            
            // Fetch collectibles for sales transactions created by this user
            $collectiblesQuery = Collectible::query()
                ->whereHas('salesTransaction', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            
            // Apply date filters to collectibles (based on sales transaction date)
            if ($request->filled('start_date')) {
                $collectiblesQuery->whereHas('salesTransaction', function ($q) use ($request) {
                    $q->whereDate('created_at', '>=', $request->input('start_date'));
                });
            }
            if ($request->filled('end_date')) {
                $collectiblesQuery->whereHas('salesTransaction', function ($q) use ($request) {
                    $q->whereDate('created_at', '<=', $request->input('end_date'));
                });
            }
            
            $props['collectibles'] = $collectiblesQuery->with(['salesTransaction', 'payments.recordedBy'])->latest()->paginate(15)->withQueryString();
        }
        
        // Expenses are fetched for both roles.
        $props['expenses'] = $expenseQuery->latest()->paginate(15)->withQueryString();
        
        // Feed data for production staff
        if ($user->role === 'staff-production') {
            // Get current feed stock
            $feedStat = FarmStat::firstOrCreate(
                ['stat_key' => 'current_feed_stock_kg'],
                ['stat_value' => 0]
            );
            $props['currentFeedStock'] = $feedStat->stat_value;
            
            // Build combined feed transactions (additions from Expenses + deductions from FeedUsageLog)
            $feedTransactions = $this->buildFeedTransactions($user->id, $request);
            $props['feedUsageLogs'] = $feedTransactions;
        }
    
        return Inertia::render('Staff/ViewMyRecords', $props);
    }

    /**
     * Build merged feed transactions (additions from Feeds expenses + deductions from FeedUsageLog)
     */
    private function buildFeedTransactions(int $userId, Request $request): LengthAwarePaginator
    {
        $deductionsQuery = FeedUsageLog::query()->where('user_id', $userId);
        $additionsQuery = Expense::query()
            ->where('user_id', $userId)
            ->where('category', 'Feeds')
            ->whereNotNull('feed_quantity_kg')
            ->where('feed_quantity_kg', '>', 0);

        if ($request->filled('start_date')) {
            $deductionsQuery->whereDate('created_at', '>=', $request->input('start_date'));
            $additionsQuery->whereDate('expense_date', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $deductionsQuery->whereDate('created_at', '<=', $request->input('end_date'));
            $additionsQuery->whereDate('expense_date', '<=', $request->input('end_date'));
        }

        $deductions = $deductionsQuery->with('user')->get()->map(fn ($log) => [
            'id' => 'feed-' . $log->id,
            'reference' => 'FEED-' . $log->id,
            'date' => $log->created_at,
            'entry_type' => 'deduction',
            'quantity_kg' => (float) $log->quantity_kg,
            'recorded_by' => $log->user?->name ?? 'N/A',
            'receipt_number' => null,
            'receipt_image_url' => null,
        ]);

        $additions = $additionsQuery->with('user')->get()->map(fn ($exp) => [
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
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $path = $request->url();

        $paginator = new LengthAwarePaginator(
            $merged->forPage($currentPage, $perPage)->values(),
            $merged->count(),
            $perPage,
            $currentPage,
            ['path' => $path]
        );
        return $paginator->withQueryString();
    }
}
