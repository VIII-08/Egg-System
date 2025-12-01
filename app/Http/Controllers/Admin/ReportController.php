<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Models\SaleItem;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ProductionLog;
use App\Models\EggProduct;
use App\Models\ChickenStockLog;
use App\Models\FarmStat;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ExpenseReportExport;
use App\Exports\SalesSummaryExport;
use App\Exports\InventoryReportExport;

class ReportController extends Controller
{
    // Method to show the page and generate report PREVIEWS
    public function index(Request $request)
    {
        $filters = $request->validate([
            'report_type' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);
        
        $reportData = null;
        if ($request->has(['report_type', 'start_date', 'end_date'])) {
            $reportData = $this->generateReportData($filters);
            
            // Ensure items is always an array
            if ($reportData && isset($reportData['items']) && !is_array($reportData['items'])) {
                $reportData['items'] = [];
            }
        }

        return Inertia::render('Admin/GenerateReports', [
            'reportData' => $reportData,
            'filters' => $filters,
        ]);
    }

    // Method to generate and DOWNLOAD the report as a PDF
    public function downloadPdf(Request $request)
    {
         $filters = $request->validate([
            'report_type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);
         
         $reportData = $this->generateReportData($filters);
         
         // Use the report type to determine which Blade template to render
         $viewName = 'reports.' . $filters['report_type'];

         if (!view()->exists($viewName)) {
             abort(404, 'Report template not found.');
         }

         $pdf = Pdf::loadView($viewName, $reportData);
         return $pdf->download(ucwords(str_replace('_', ' ', $filters['report_type'])) . '.pdf');
    }

    public function downloadExcel(Request $request)
    {
        $filters = $request->validate([
            'report_type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $filename = "{$filters['report_type']}-{$filters['start_date']}-to-{$filters['end_date']}.xlsx";
        
        switch ($filters['report_type']) {
            case 'expense_summary':
                return Excel::download(new ExpenseReportExport($filters['start_date'], $filters['end_date']), $filename);
            case 'sales_summary':
                return Excel::download(new SalesSummaryExport($filters['start_date'], $filters['end_date']), $filename);
            case 'inventory_report':
                return Excel::download(new InventoryReportExport($filters['start_date'], $filters['end_date']), $filename);
            default:
                return back()->with('error', 'Excel export is not available for this report type.');
        }
    }

    // This private helper contains ALL the report generation logic
    private function generateReportData(array $filters): array
    {
        $startDate = Carbon::parse($filters['start_date']);
        $endDate = Carbon::parse($filters['end_date']);
        $reportType = $filters['report_type'];
        
        $data = ['startDate' => $startDate->format('Y-m-d'), 'endDate' => $endDate->format('Y-m-d'), 'reportType' => $reportType];

        switch ($reportType) {
            case 'expense_summary':
                // Get all expenses in the date range
                $expenses = Expense::whereBetween('expense_date', [$startDate, $endDate])->get();
                
                // Get ALL expense categories from the database (not just those with expenses)
                $categoryNames = ExpenseCategory::orderBy('name')
                    ->pluck('name')
                    ->unique()
                    ->values()
                    ->toArray();
                
                // Group expenses by date and category
                $expensesByDate = [];
                foreach ($expenses as $expense) {
                    $dateKey = Carbon::parse($expense->expense_date)->format('Y-m-d');
                    $category = $expense->category ?? 'Uncategorized';
                    
                    if (!isset($expensesByDate[$dateKey])) {
                        $expensesByDate[$dateKey] = [];
                    }
                    if (!isset($expensesByDate[$dateKey][$category])) {
                        $expensesByDate[$dateKey][$category] = 0;
                    }
                    $expensesByDate[$dateKey][$category] += $expense->amount;
                }
                
                // Generate all dates in the range
                $dates = [];
                $currentDate = $startDate->copy();
                while ($currentDate <= $endDate) {
                    $dates[] = $currentDate->copy();
                    $currentDate->addDay();
                }
                
                // Build daily report
                $dailyReport = [];
                $columnTotals = [];
                
                // Initialize column totals
                foreach ($categoryNames as $categoryName) {
                    $columnTotals[$categoryName] = 0;
                }
                $columnTotals['total_expenses'] = 0;
                
                foreach ($dates as $date) {
                    $dateKey = $date->format('Y-m-d');
                    $dayNumber = $date->day;
                    
                    $row = ['day' => $dayNumber];
                    
                    // Initialize all category columns to 0 for this date
                    foreach ($categoryNames as $categoryName) {
                        $row[$categoryName] = 0;
                    }
                    $row['total_expenses'] = 0;
                    
                    // Fill in the expenses for categories on this date
                    if (isset($expensesByDate[$dateKey])) {
                        foreach ($expensesByDate[$dateKey] as $category => $amount) {
                            if (in_array($category, $categoryNames)) {
                                $row[$category] = (float)$amount;
                                $row['total_expenses'] += (float)$amount;
                                
                                // Add to column totals
                                $columnTotals[$category] += (float)$amount;
                            }
                        }
                    }
                    
                    $columnTotals['total_expenses'] += $row['total_expenses'];
                    $dailyReport[] = $row;
                }
                
                // Add totals row
                $totalsRow = ['day' => 'TOTAL'];
                foreach ($categoryNames as $categoryName) {
                    $totalsRow[$categoryName] = $columnTotals[$categoryName];
                }
                $totalsRow['total_expenses'] = $columnTotals['total_expenses'];
                $dailyReport[] = $totalsRow;
                
                $data['items'] = $dailyReport;
                $data['categoryNames'] = $categoryNames;
                $data['monthYear'] = $startDate->format('F, Y');
                $data['total'] = $columnTotals['total_expenses'];
                break;

            case 'sales_summary':
                // Get egg products in the correct order (excluding DAMAGED and BROKEN EGGS)
                $eggProducts = EggProduct::where('name', '!=', 'DAMAGED')
                    ->whereRaw('LOWER(name) != ?', ['broken eggs'])
                    ->orderByRaw("CASE 
                        WHEN name = 'SMALL' THEN 1
                        WHEN name = 'MEDIUM' THEN 2
                        WHEN name = 'LARGE' THEN 3
                        WHEN name = 'X-LARGE' THEN 4
                        WHEN name = 'JUMBO' THEN 5
                        WHEN name = 'PULLETS' THEN 6
                        ELSE 7
                    END")
                    ->get();
                
                // Get prices for each product
                $prices = [];
                $productNames = [];
                foreach ($eggProducts as $product) {
                    $productNames[] = $product->name;
                    $prices[$product->name] = $product->price;
                }
                
                // Fetch sales data grouped by date and product
                $salesData = SaleItem::with('product')
                    ->whereHas('transaction', function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('created_at', [
                            $startDate->copy()->startOfDay()->toDateTimeString(),
                            $endDate->copy()->endOfDay()->toDateTimeString()
                        ]);
                    })
                    ->join('sales_transactions', 'sale_items.sales_transaction_id', '=', 'sales_transactions.id')
                    ->selectRaw('DATE(sales_transactions.created_at) as sale_date, 
                                 sale_items.egg_product_id,
                                 SUM(sale_items.quantity * sale_items.price) as daily_revenue,
                                 SUM(sale_items.quantity) as daily_quantity')
                    ->groupBy('sale_date', 'sale_items.egg_product_id')
                    ->get();
                
                // Generate all dates in the range
                $dates = [];
                $currentDate = $startDate->copy();
                while ($currentDate <= $endDate) {
                    $dates[] = $currentDate->copy();
                    $currentDate->addDay();
                }
                
                // Group sales data by date
                $groupedByDate = $salesData->groupBy('sale_date');
                
                // Build daily report rows
                $reportRows = collect();
                $columnTotals = [];
                
                // Initialize column totals
                foreach ($productNames as $productName) {
                    $columnTotals[$productName] = ['quantity' => 0, 'revenue' => 0];
                }
                $columnTotals['total_eggs'] = 0;
                $columnTotals['total_sales'] = 0;
                
                foreach ($dates as $date) {
                    $dateKey = $date->format('Y-m-d');
                    $dayNumber = $date->day;
                    
                    $row = ['day' => $dayNumber];
                    
                    // Initialize all product columns to 0 for this date
                    foreach ($productNames as $productName) {
                        $row[$productName] = ['quantity' => 0, 'revenue' => 0];
                    }
                    $row['total_eggs'] = 0;
                    $row['total_sales'] = 0;
                    
                    // Fill in the revenue and quantity for products sold on this date
                    if (isset($groupedByDate[$dateKey])) {
                        foreach ($groupedByDate[$dateKey] as $item) {
                            $productName = $item->product->name ?? '';
                            if (in_array($productName, $productNames)) {
                                $row[$productName] = [
                                    'quantity' => (int)$item->daily_quantity,
                                    'revenue' => (float)$item->daily_revenue
                                ];
                                $row['total_eggs'] += (int)$item->daily_quantity;
                                $row['total_sales'] += (float)$item->daily_revenue;
                                
                                // Add to column totals
                                $columnTotals[$productName]['quantity'] += (int)$item->daily_quantity;
                                $columnTotals[$productName]['revenue'] += (float)$item->daily_revenue;
                            }
                        }
                    }
                    
                    $columnTotals['total_eggs'] += $row['total_eggs'];
                    $columnTotals['total_sales'] += $row['total_sales'];
                    
                    $reportRows->push($row);
                }
                
                // Add totals row
                $totalsRow = ['day' => 'TOTAL'];
                foreach ($productNames as $productName) {
                    $totalsRow[$productName] = $columnTotals[$productName];
                }
                $totalsRow['total_eggs'] = $columnTotals['total_eggs'];
                $totalsRow['total_sales'] = $columnTotals['total_sales'];
                $reportRows->push($totalsRow);
                
                $data['items'] = $reportRows->toArray();
                $data['prices'] = $prices;
                $data['productNames'] = $productNames;
                $data['monthYear'] = $startDate->format('F, Y');
                
                // Debug output
                \Log::info('Sales Summary Data Prepared', [
                    'items_count' => count($reportRows),
                    'product_names' => $productNames,
                    'has_prices' => !empty($prices),
                    'first_item' => $reportRows->first(),
                ]);
                break;
                
            case 'inventory_report':
                 // Get all production logs in the date range
                 $productionLogs = ProductionLog::with('eggProduct')
                     ->whereBetween('log_date', [$startDate, $endDate])
                     ->get();
                 
                 // Group by date and product name
                 $logsByDate = [];
                 foreach ($productionLogs as $log) {
                     // Normalize date to Y-m-d format
                     $dateKey = Carbon::parse($log->log_date)->format('Y-m-d');
                     $productName = strtoupper($log->eggProduct->name ?? '');
                     
                     if (!isset($logsByDate[$dateKey])) {
                         $logsByDate[$dateKey] = [];
                     }
                     if (!isset($logsByDate[$dateKey][$productName])) {
                         $logsByDate[$dateKey][$productName] = 0;
                     }
                     $logsByDate[$dateKey][$productName] += $log->quantity;
                 }
                 
                 // Get current chicken stock as baseline
                 $currentStock = FarmStat::where('stat_key', 'current_chicken_stock')->value('stat_value') ?? 0;
                 
                 // Get all chicken stock adjustments up to the end date
                 $stockAdjustments = ChickenStockLog::whereDate('created_at', '<=', $endDate->endOfDay())
                     ->orderBy('created_at', 'asc')
                     ->get();
                 
                 // Calculate stock count for each day
                 // Start from current stock and work backwards, or start from 0 and work forwards
                 // We'll calculate by working backwards from current stock
                 $stockByDate = [];
                 
                 // Generate all dates in the range
                 $dates = [];
                 $currentDate = $startDate->copy();
                 while ($currentDate <= $endDate) {
                     $dates[] = $currentDate->copy();
                     $currentDate->addDay();
                 }
                 
                 // For each date, calculate the stock count
                 // We'll work backwards: start with current stock and subtract adjustments after that date
                 foreach ($dates as $date) {
                     $dateKey = $date->format('Y-m-d');
                     
                     // Calculate stock for this date by:
                     // 1. Start with current stock
                     // 2. Subtract all adjustments made after this date
                     $stockCount = $currentStock;
                     
                     foreach ($stockAdjustments as $adjustment) {
                         $adjustmentDate = Carbon::parse($adjustment->created_at)->format('Y-m-d');
                         
                         // If adjustment is after this date, reverse it
                         if ($adjustmentDate > $dateKey) {
                             if ($adjustment->adjustment_type === 'addition') {
                                 $stockCount -= $adjustment->quantity;
                             } else {
                                 $stockCount += $adjustment->quantity;
                             }
                         }
                     }
                     
                     // Ensure stock count is not negative
                     $stockCount = max(0, $stockCount);
                     $stockByDate[$dateKey] = $stockCount;
                 }
                 
                 // Map product names to column names
                 $eggSizeMap = [
                     'PULLETS' => 'PULLETS',
                     'SMALL' => 'SMALL',
                     'MEDIUM' => 'MEDIUM',
                     'LARGE' => 'LARGE',
                     'X-LARGE' => 'X-LARGE',
                     'XL' => 'X-LARGE',
                     'JUMBO' => 'JUMBO',
                     'DAMAGED' => 'DAMAGED',
                     'BROKEN EGGS' => 'DAMAGED',
                 ];
                 
                 // Build daily report
                 $dailyReport = [];
                 $totals = [
                     'PULLETS' => 0,
                     'SMALL' => 0,
                     'MEDIUM' => 0,
                     'LARGE' => 0,
                     'X-LARGE' => 0,
                     'JUMBO' => 0,
                     'DAMAGED' => 0,
                 ];
                 
                 foreach ($dates as $date) {
                     $dateKey = $date->format('Y-m-d');
                     $dayNumber = $date->day;
                     
                     $row = [
                         'day' => $dayNumber,
                         'date' => $dateKey,
                         'hens' => $stockByDate[$dateKey] ?? $currentStock, // Use calculated stock count
                         'PULLETS' => 0,
                         'SMALL' => 0,
                         'MEDIUM' => 0,
                         'LARGE' => 0,
                         'X-LARGE' => 0,
                         'JUMBO' => 0,
                         'DAMAGED' => 0,
                     ];
                     
                     // Get production for this date
                     if (isset($logsByDate[$dateKey])) {
                         foreach ($logsByDate[$dateKey] as $productName => $quantity) {
                             // Map product name to column
                             $columnName = $eggSizeMap[$productName] ?? null;
                             
                             if ($columnName && isset($row[$columnName])) {
                                 $row[$columnName] = $quantity;
                                 $totals[$columnName] += $quantity;
                             }
                         }
                     }
                     
                     $dailyReport[] = $row;
                 }
                 
                 // Add totals row
                 $dailyReport[] = [
                     'day' => 'TOTAL',
                     'date' => '',
                     'hens' => '', // Empty for totals row
                     'PULLETS' => $totals['PULLETS'],
                     'SMALL' => $totals['SMALL'],
                     'MEDIUM' => $totals['MEDIUM'],
                     'LARGE' => $totals['LARGE'],
                     'X-LARGE' => $totals['X-LARGE'],
                     'JUMBO' => $totals['JUMBO'],
                     'DAMAGED' => $totals['DAMAGED'],
                 ];
                 
                 $data['items'] = $dailyReport;
                 $data['monthYear'] = $startDate->format('F, Y');
                 break;
        }
        return $data;
    }
}