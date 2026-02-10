<?php
namespace App\Http\Controllers\Treasurer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Models\SalesTransaction;
use App\Models\Expense;
use App\Models\ProductionLog;
use App\Models\FinancialReport;
use App\Models\Collectible;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FinancialReportController extends Controller
{
    // This method shows the page and handles data previewing
    public function index(Request $request)
    {
        $reportData = null;

        if ($request->has(['start_date', 'end_date'])) {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);

            // 1. Calculate Sales & Revenue Breakdown
            $totalRevenue = SalesTransaction::whereBetween('created_at', [$startDate, $endDate])->sum('total_amount');
            
            // Calculate revenue breakdown by product
            $revenueBreakdown = \App\Models\SaleItem::whereHas('transaction', fn($q) => $q->whereBetween('created_at', [
                $startDate->copy()->startOfDay()->toDateTimeString(),
                $endDate->copy()->endOfDay()->toDateTimeString()
            ]))
            ->with('product')
            ->get()
            ->groupBy(function($item) {
                return $item->product->name ?? 'Unknown';
            })
            ->map(function($items) {
                return $items->sum(fn($i) => $i->quantity * $i->price);
            })
            ->sortKeys();
            
            // 2. Calculate Expenses & Breakdown
            $expenses = Expense::whereBetween('expense_date', [$startDate, $endDate])->get();
            $totalExpenses = $expenses->sum('amount');
            $expenseBreakdown = $expenses->groupBy('category')->map->sum('amount');

            // 3. Calculate Production
            $productionBreakdown = ProductionLog::with('eggProduct')
                ->whereBetween('log_date', [$startDate, $endDate])
                ->get()
                ->groupBy('eggProduct.name')
                ->map->sum('quantity');

            $amountReceivables = Collectible::sum('balance');
            $cashCollected = max(0, $totalRevenue - $amountReceivables);

            $reportData = [
                'startDateFormatted' => $startDate->format('F j, Y'),
                'endDateFormatted' => $endDate->format('F j, Y'),
                'totalRevenue' => (float) $totalRevenue,
                'totalExpenses' => (float) $totalExpenses,
                'netIncome' => (float) $totalRevenue - $totalExpenses,
                'amountReceivables' => (float) $amountReceivables,
                'cashCollected' => (float) $cashCollected,
                'revenueBreakdown' => $revenueBreakdown,
                'expenseBreakdown' => $expenseBreakdown,
                'productionBreakdown' => $productionBreakdown,
            ];
        }

        return Inertia::render('Treasurer/GenerateFinancialReport', [
            'reportData' => $reportData,
            'filters' => $request->only(['start_date', 'end_date']),
        ]);
    }
    
    // This method saves the generated report
    public function store(Request $request)
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'report_data' => 'required|array',
            'report_data.totalRevenue' => 'required|numeric|min:0',
            'report_data.totalExpenses' => 'required|numeric|min:0',
            'report_data.netIncome' => 'required|numeric',
        ]);
        
        // Security: Ensure user is authenticated and is a treasurer
        // (Already protected by middleware, but explicit check for defense in depth)
        if (Auth::user()->role !== 'treasurer') {
            abort(403, 'Only treasurers can generate financial reports.');
        }
        
        FinancialReport::create([
            'generated_by' => Auth::id(),
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'total_revenue' => $data['report_data']['totalRevenue'],
            'total_expenses' => $data['report_data']['totalExpenses'],
            'net_income' => $data['report_data']['netIncome'],
            'report_data' => $data['report_data'],
            'status' => 'submitted',
        ]);

        return to_route('treasurer.reports.index')->with('success', 'Financial report has been generated and submitted for admin review.');
    }

    public function testDownload($id)
    {
        return response()->json([
            'message' => 'Test download route working',
            'report_id' => $id,
            'url' => route('treasurer.reports.download', $id)
        ]);
    }

    public function download($id)
    {
        try {
            $report = FinancialReport::findOrFail($id);
            
            // Security: Explicit ownership check (defense in depth)
            if ($report->generated_by !== Auth::id()) {
                abort(403, 'You do not have permission to download this report.');
            }
            
            // Only allow download if report is approved
            if ($report->status !== 'approved') {
                abort(403, 'This report is not approved for download.');
            }

            // Generate PDF using DomPDF
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('financial-report-pdf', [
                'report' => $report,
                'reportData' => $report->report_data
            ]);

            $filename = "financial_report_{$report->start_date}_to_{$report->end_date}.pdf";
            
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

    public function print($id)
    {
        try {
            $report = FinancialReport::findOrFail($id);
            
            // Security: Explicit ownership check (defense in depth)
            if ($report->generated_by !== Auth::id()) {
                abort(403, 'You do not have permission to print this report.');
            }
            
            // Only allow print if report is approved
            if ($report->status !== 'approved') {
                abort(403, 'This report is not approved for printing.');
            }

            return Inertia::render('Treasurer/PrintFinancialReport', [
                'report' => $report,
                'reportData' => $report->report_data
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Print View Error: Report not found - ' . $id);
            abort(404, 'Report not found.');
        } catch (\Exception $e) {
            // Log detailed error for debugging, but return generic message
            Log::error('Print View Error: ' . $e->getMessage(), [
                'report_id' => $id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            abort(500, 'An error occurred while loading the print view. Please try again later.');
        }
    }

    public function view($id)
    {
        try {
            $report = FinancialReport::with('generatedBy')->findOrFail($id);
            
            // Security: Explicit ownership check (defense in depth)
            if ($report->generated_by !== Auth::id()) {
                abort(403, 'You do not have permission to view this report.');
            }

            return Inertia::render('Treasurer/ViewFinancialReport', [
                'report' => $report,
                'reportData' => $report->report_data,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('View Report Error: Report not found - ' . $id);
            abort(404, 'Report not found.');
        } catch (\Exception $e) {
            // Log detailed error for debugging, but return generic message
            Log::error('View Report Error: ' . $e->getMessage(), [
                'report_id' => $id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            abort(500, 'An error occurred while loading the report. Please try again later.');
        }
    }
}