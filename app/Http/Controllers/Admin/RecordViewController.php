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
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class RecordViewController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'type' => ['sometimes', 'string', 'in:sales_transactions,expenses,production_logs,financial_summaries,current_egg_inventory,current_chicken_stock'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
            'entered_by' => ['nullable', 'integer', 'exists:users,id'],
        ]);
        
        $recordType = $filters['type'] ?? 'sales_transactions';
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
                $records = ProductionLog::with(['user', 'eggProduct'])
                    ->when($request->from_date, fn($q, $d) => $q->whereDate('log_date', '>=', $d))
                    ->when($request->to_date, fn($q, $d) => $q->whereDate('log_date', '<=', $d))
                    ->when($request->entered_by, fn($q, $u) => $q->where('user_id', $u))
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
        }

        return Inertia::render('Admin/ViewRecords', [
            'records' => $records,
            'filters' => $filters,
            'users' => User::whereIn('role', ['staff-production', 'staff-marketing', 'treasurer'])->get(['id', 'name']),
        ]);
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