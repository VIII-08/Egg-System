<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\SalesTransaction;
use App\Models\ProductionLog;
use App\Models\EggProduct;
use App\Models\DataCorrectionRequest;
use App\Models\Expense;
use App\Models\FinancialReport;
use App\Models\SaleItem;
use App\Models\AuditLog;
use Illuminate\Support\Facades\File;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // --- KPI Card Data ---
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        
        $totalSalesThisMonth = SalesTransaction::whereBetween('created_at', [$startOfMonth, $endOfMonth])->sum('total_amount');
        $eggProductionToday = ProductionLog::whereDate('log_date', today())->sum('quantity');
        $totalEggsInStock = EggProduct::sum('stock_quantity');
        $correctionRequests = DataCorrectionRequest::where('status', 'pending')->count();
        $financialReports = FinancialReport::where('status', 'submitted')->count();
        $pendingApprovalsCount = $correctionRequests + $financialReports;
        
        // --- Pie Chart Data: Inventory by Egg Size ---
        $pieChartData = EggProduct::where('name', '!=', 'Broken Eggs')
            ->get(['name', 'stock_quantity'])
            ->map(fn($p) => ['label' => $p->name, 'value' => $p->stock_quantity]);
        
        // --- Recent System Activity (A mix of the last 5 logs of different types) ---
        $latestSales = SalesTransaction::with('user')->latest()->limit(2)->get();
        $latestExpenses = Expense::with('user')->latest()->limit(2)->get();
        $latestProduction = ProductionLog::with(['user', 'eggProduct'])->latest()->limit(2)->get();
        $latestAuditLogs = AuditLog::with('user')
            ->whereIn('action', ['egg_product_created', 'egg_product_deleted', 'expense_category_created', 'expense_category_deleted'])
            ->latest()
            ->limit(3)
            ->get();

        // Get the number of eggs sold today, grouped by product
        $soldToday = SaleItem::whereHas('transaction', function ($query) {
            $query->whereDate('created_at', today());
        })
        ->groupBy('egg_product_id')
        ->selectRaw('egg_product_id, SUM(quantity) as total_sold')
        ->pluck('total_sold', 'egg_product_id');

        // Get all egg products and format the data
        $inventoryStatusData = EggProduct::orderBy('id')->get()->map(function ($product) use ($soldToday) {
            $remaining = $product->stock_quantity;
            $status = '';
            
            // NOTE: You can adjust this 'low stock' threshold
            $lowStockThreshold = 50; 

            if ($remaining <= 0) {
                $status = 'Out of Stock';
            } elseif ($remaining < $lowStockThreshold) {
                $status = 'Low on Stocks';
            } else {
                $status = 'Good';
            }
            
            return [
                'egg_size' => $product->name,
                'remaining' => $remaining,
                'sold_today' => $soldToday->get($product->id, 0), // Default to 0 if none sold
                'status' => $status,
            ];
        });
        
        // We'll tag each item with a type before merging and sorting
        $activity = collect([])
            ->merge($latestSales->map(fn($item) => ['type' => 'Sale', 'data' => $item, 'date' => $item->created_at]))
            ->merge($latestExpenses->map(fn($item) => ['type' => 'Expense', 'data' => $item, 'date' => $item->created_at]))
            ->merge($latestProduction->map(fn($item) => ['type' => 'Production', 'data' => $item, 'date' => $item->created_at]))
            ->merge($latestAuditLogs->map(fn($item) => ['type' => 'Audit', 'data' => $item, 'date' => $item->created_at]))
            ->sortByDesc('date')
            ->take(5);

        $salesVsForecastChart = $this->buildSalesVsForecastChart();

        return inertia('Admin/Dashboard', [
            'totalSalesThisMonth' => (float) $totalSalesThisMonth,
            'eggProductionToday' => (int) $eggProductionToday,
            'totalEggsInStock' => (int) $totalEggsInStock,
            'pendingApprovalsCount' => $pendingApprovalsCount,
            'inventoryPieChartData' => $pieChartData,
            'recentActivity' => $activity->values(), 
            'inventoryStatusData' => $inventoryStatusData,
            'salesVsForecastChart' => $salesVsForecastChart,
        ]);
    }

    private function buildSalesVsForecastChart(): ?array
    {
        $monthsBack = 5; // show last 6 months including current
        $startDate = Carbon::now()->subMonths($monthsBack)->startOfMonth();

        $actualRows = SalesTransaction::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month_key, SUM(total_amount) as total")
            ->where('created_at', '>=', $startDate)
            ->groupBy('month_key')
            ->orderBy('month_key')
            ->get();

        $actualMap = $actualRows->mapWithKeys(fn ($row) => [
            $row->month_key => round((float) $row->total, 2),
        ]);

        $forecastFuture = $this->loadFutureForecastTotals();
        $allMonths = collect($actualMap->keys())
            ->merge(array_keys($forecastFuture))
            ->unique()
            ->sort()
            ->values();

        if ($allMonths->isEmpty()) {
            return null;
        }

        $labels = [];
        $actualData = [];
        $forecastData = [];

        foreach ($allMonths as $monthKey) {
            $labels[] = Carbon::createFromFormat('Y-m', $monthKey)->format('M Y');
            $actualData[] = $actualMap->get($monthKey, null);
            $forecastData[] = isset($forecastFuture[$monthKey]) ? round($forecastFuture[$monthKey], 2) : null;
        }


        return [
            'labels' => $labels,
            'actual' => $actualData,
            'forecast' => $forecastData,
        ];
    }

    private function loadFutureForecastTotals(): array
    {
        $path = base_path('forecasting_scripts/forecast_results.json');

        if (!File::exists($path)) {
            return [];
        }

        $contents = json_decode(File::get($path), true);
        if (!is_array($contents)) {
            return [];
        }

        $totals = [];
        foreach ($contents as $record) {
            if (empty($record['forecast'])) {
                continue;
            }

            foreach ($record['forecast'] as $point) {
                $date = Carbon::parse($point['date']);
                if ($date->lt(Carbon::now()->startOfMonth())) {
                    continue;
                }

                $monthKey = $date->format('Y-m');
                $totals[$monthKey] = ($totals[$monthKey] ?? 0) + (float) ($point['yhat'] ?? 0);
            }
        }

        ksort($totals);

        return collect($totals)
            ->take(3)
            ->all();
    }
}