<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\SalesTransaction;
use App\Models\Expense;
use App\Models\ProductionLog;
use App\Models\FinancialReport;
use App\Models\EggProduct;
use App\Models\FarmStat;
use App\Models\User;
use App\Models\ChickenStockLog;
use App\Models\FeedUsageLog;
use App\Models\AuditLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;

class RecordViewController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'type' => ['sometimes', 'string', 'in:sales_transactions,expenses,production_logs,collectibles,financial_summaries,current_egg_inventory,current_chicken_stock,chicken_stock_logs,feed_usage_logs,current_feed_stock'],
            'from_date' => ['nullable', 'date'],
            'to_date' => [
                'nullable',
                'date',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->filled('from_date') && $value < $request->from_date) {
                        $fail('The end date must be on or after the start date.');
                    }
                },
            ],
            'entered_by' => ['nullable', 'integer', 'exists:users,id'],
            'production_view' => ['nullable', 'string', 'in:by_size,by_batch'],
            'feed_entry_type' => ['nullable', 'string', 'in:addition,deduction'],
        ]);
        
        $recordType = $filters['type'] ?? 'sales_transactions';
        $productionView = $filters['production_view'] ?? 'by_size';
        $records = null; // Start with a null value

        switch ($recordType) {
            case 'sales_transactions':
                $records = SalesTransaction::with('user')
                    ->when($request->from_date, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
                    ->when($request->to_date, fn($q, $d) => $q->whereDate('created_at', '<=', $d))
                    ->when($request->entered_by, fn($q, $u) => $q->where('user_id', $u))
                    ->latest()
                    ->paginate(15)
                    ->withQueryString();
                break;

            case 'expenses':
                $records = Expense::with('user')
                    ->when($request->from_date, fn($q, $d) => $q->whereDate('expense_date', '>=', $d))
                    ->when($request->to_date, fn($q, $d) => $q->whereDate('expense_date', '<=', $d))
                    ->when($request->entered_by, fn($q, $u) => $q->where('user_id', $u))
                    ->latest()
                    ->paginate(15)
                    ->withQueryString();
                break;
            
            case 'production_logs':
                if ($productionView === 'by_batch') {
                    $logs = ProductionLog::with(['user', 'eggProduct'])
                        ->when($request->from_date, fn($q, $d) => $q->whereDate('log_date', '>=', $d))
                        ->when($request->to_date, fn($q, $d) => $q->whereDate('log_date', '<=', $d))
                        ->when($request->entered_by, fn($q, $u) => $q->where('user_id', $u))
                        ->whereNotNull('batch_reference')
                        ->latest()
                        ->get();

                    $records = $logs->groupBy('batch_reference')->map(function ($group) {
                        $first = $group->first();
                        return [
                            'batch_reference' => $first->batch_reference,
                            'log_date' => $first->log_date,
                            'created_at' => $first->created_at,
                            'logged_by' => $first->user?->name,
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
                    $records = ProductionLog::with(['user', 'eggProduct'])
                        ->when($request->from_date, fn($q, $d) => $q->whereDate('log_date', '>=', $d))
                        ->when($request->to_date, fn($q, $d) => $q->whereDate('log_date', '<=', $d))
                        ->when($request->entered_by, fn($q, $u) => $q->where('user_id', $u))
                        ->latest()
                        ->paginate(15)
                        ->withQueryString();
                }
                break;

            case 'collectibles':
                $records = \App\Models\Collectible::with(['salesTransaction', 'payments.recordedBy'])
                    ->when($request->from_date, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
                    ->when($request->to_date, fn($q, $d) => $q->whereDate('created_at', '<=', $d))
                    ->latest()
                    ->paginate(15)
                    ->withQueryString();
                break;
                
            case 'financial_summaries':
                $records = FinancialReport::with('generatedBy')
                    ->latest()
                    ->paginate(15)
                    ->withQueryString();
                break;

            case 'current_egg_inventory':
                $records = EggProduct::orderBy('name')->get()->map(fn ($p) => [
                    'egg_size' => $p->name,
                    'current_stock' => $p->stock_quantity,
                    'value_est' => $p->stock_quantity * $p->price,
                ]);
                break;

            case 'current_chicken_stock':
                 $records = FarmStat::where('stat_key', 'current_chicken_stock')->get(['stat_key', 'stat_value as quantity', 'updated_at']);
                break;

            case 'chicken_stock_logs':
                $records = ChickenStockLog::with('user')
                    ->when($request->from_date, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
                    ->when($request->to_date, fn($q, $d) => $q->whereDate('created_at', '<=', $d))
                    ->when($request->entered_by, fn($q, $u) => $q->where('user_id', $u))
                    ->latest()
                    ->paginate(15)
                    ->withQueryString();
                break;

            case 'feed_usage_logs':
                $records = $this->buildFeedTransactions($request);
                break;

            case 'current_feed_stock':
                $records = FarmStat::where('stat_key', 'current_feed_stock_kg')->get(['stat_key', 'stat_value as quantity', 'updated_at']);
                break;
        }

        // Get current feed stock for display (when viewing feed-related records)
        $currentFeedStock = null;
        if (in_array($recordType, ['feed_usage_logs', 'current_feed_stock'])) {
            $feedStat = FarmStat::where('stat_key', 'current_feed_stock_kg')->first();
            $currentFeedStock = $feedStat ? $feedStat->stat_value : 0;
        }

        return Inertia::render('Admin/ViewRecords', [
            'records' => $records,
            'filters' => $filters,
            'users' => User::whereIn('role', ['staff-production', 'staff-marketing', 'treasurer'])->get(['id', 'name']),
            'currentFeedStock' => $currentFeedStock,
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

        if ($request->filled('from_date')) {
            $deductionsQuery->whereDate('created_at', '>=', $request->from_date);
            $additionsQuery->whereDate('expense_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $deductionsQuery->whereDate('created_at', '<=', $request->to_date);
            $additionsQuery->whereDate('expense_date', '<=', $request->to_date);
        }
        if ($request->filled('entered_by')) {
            $deductionsQuery->where('user_id', $request->entered_by);
            $additionsQuery->where('user_id', $request->entered_by);
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
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $paginator = new LengthAwarePaginator(
            $merged->forPage($currentPage, $perPage)->values(),
            $merged->count(),
            $perPage,
            $currentPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
        return $paginator->withQueryString();
    }

    public function viewFinancialReport($id)
    {
        try {
            $report = FinancialReport::with('generatedBy')->findOrFail($id);
            
            // Security: Explicit admin check (defense in depth)
            // Route is already protected by middleware, but explicit check adds extra security
            if (Auth::user()->role !== 'admin') {
                abort(403, 'Only administrators can view financial reports.');
            }
            
            return Inertia::render('Admin/ViewFinancialReport', [
                'report' => $report,
                'reportData' => $report->report_data,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('View Financial Report Error: Report not found - ' . $id);
            abort(404, 'Report not found.');
        } catch (\Exception $e) {
            Log::error('View Financial Report Error: ' . $e->getMessage(), [
                'report_id' => $id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            abort(500, 'An error occurred while loading the report. Please try again later.');
        }
    }

    public function downloadFinancialReport($id)
    {
        try {
            $report = FinancialReport::findOrFail($id);
            
            // Security: Explicit admin check (defense in depth)
            if (Auth::user()->role !== 'admin') {
                abort(403, 'Only administrators can download financial reports.');
            }
            
            // Generate PDF using DomPDF with UTF-8 encoding
            $pdf = Pdf::loadView('financial-report-pdf', [
                'report' => $report,
                'reportData' => $report->report_data
            ])->setOption('isHtml5ParserEnabled', true)
              ->setOption('isRemoteEnabled', true)
              ->setPaper('a4', 'portrait');

            $filename = "financial_report_{$report->start_date}_to_{$report->end_date}.pdf";
            
            // Log report download
            $userName = Auth::user() ? Auth::user()->name : 'System';
            AuditLog::createWithRequest([
                'user_id' => Auth::id(),
                'action' => 'financial_report_downloaded',
                'log_entry' => "`{$userName}` downloaded financial report (ID: `{$report->id}`) for period `{$report->start_date}` to `{$report->end_date}`.",
            ], request());
            
            // Use DomPDF's download method which sets proper headers
            return $pdf->download($filename);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('PDF Download Error: Report not found - ' . $id);
            abort(404, 'Report not found.');
        } catch (\Exception $e) {
            // Log detailed error for debugging, but return generic message
            Log::error('PDF Download Error: ' . $e->getMessage(), [
                'report_id' => $id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            abort(500, 'An error occurred while generating the PDF. Please try again later.');
        }
    }
}