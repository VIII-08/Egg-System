<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\EggProduct;
use App\Models\SaleItem;
use App\Models\SalesTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ForecastingController extends Controller
{
    public function index(Request $request)
    {
        // --- 1. Validate User's Choices (with default values) ---
        $validated = $request->validate([
            'product_id' => 'nullable|integer|exists:egg_products,id',
            'horizon' => 'nullable|integer|in:30',
        ]);

        $defaultProductId = EggProduct::whereRaw('LOWER(name) = ?', ['medium'])
            ->whereRaw('LOWER(name) != ?', ['damaged eggs'])
            ->where('name', '!=', 'DAMAGED')
            ->value('id')
            ?? EggProduct::where('name', '!=', 'DAMAGED')
                ->whereRaw('LOWER(name) != ?', ['damaged eggs'])
                ->orderBy('id')
                ->value('id');

        $productId = $validated['product_id'] ?? $defaultProductId;
        $horizon = $validated['horizon'] ?? 30; // Default to 30 days

        $selectedProduct = EggProduct::findOrFail($productId);
        
        // Prevent forecasting for Damaged Eggs
        if (strtolower($selectedProduct->name) === 'damaged eggs' || $selectedProduct->name === 'DAMAGED') {
            $productId = $defaultProductId;
            $selectedProduct = EggProduct::findOrFail($productId);
        }
        $forecastData = null;
        $prophetResults = $this->loadProphetResults();
        $prophetRecord = $this->extractProphetRecord($selectedProduct->name, $prophetResults);

        // --- 2. Find the most recent date WITH A SALE for the selected product ---
        $latestSaleDate = SaleItem::query()
            ->join('sales_transactions', 'sale_items.sales_transaction_id', '=', 'sales_transactions.id')
            ->where('sale_items.egg_product_id', $productId)
            ->max('sales_transactions.created_at');
        
        if ($latestSaleDate) {
            $endDate = Carbon::parse($latestSaleDate)->endOfDay();
            
            // --- 3. Calculate Forecast (7-Day SMA for the latest data) ---
            $smaStartDate = $endDate->copy()->subDays(6)->startOfDay();

            $smaWindowSales = SaleItem::query()
                ->join('sales_transactions', 'sale_items.sales_transaction_id', '=', 'sales_transactions.id')
                ->where('sale_items.egg_product_id', $productId)
                ->whereBetween('sales_transactions.created_at', [$smaStartDate, $endDate])
                ->select(DB::raw('DATE(sales_transactions.created_at) as sale_date'), DB::raw('SUM(sale_items.quantity) as total_sold'))
                ->groupBy('sale_date')
                ->get();

            $daysWithSales = max(1, min(7, $smaWindowSales->count()));
            $totalLastWindow = $smaWindowSales->sum('total_sold');

            // Daily average based on actual sales days inside the window
            $dailyAverage = $totalLastWindow > 0 ? $totalLastWindow / $daysWithSales : 0;
            
            // Calculate total predicted sales for the chosen horizon
            $totalPredictedSales = $dailyAverage * $horizon;

            // --- 4. Gather Chart Data (Last 90 days for better context) ---
            $chartStartDate = $endDate->copy()->subDays(89)->startOfDay();

            $historicalSales = SaleItem::query()
                ->join('sales_transactions', 'sale_items.sales_transaction_id', '=', 'sales_transactions.id')
                ->where('sale_items.egg_product_id', $productId)
                ->whereBetween('sales_transactions.created_at', [$chartStartDate, $endDate])
                ->select(DB::raw('DATE(sales_transactions.created_at) as sale_date, SUM(sale_items.quantity) as total_sold'))
                ->groupBy('sale_date')->orderBy('sale_date', 'asc')->get();
                
            // Generate the dates for the forecast line on the chart
            $forecastLabels = [];
            for ($i = 1; $i <= $horizon; $i++) {
                $forecastLabels[] = $endDate->copy()->addDays($i)->toDateString();
            }

            $historicalValues = $historicalSales->pluck('total_sold')->toArray();
            $historicalLabels = $historicalSales->pluck('sale_date')->toArray();
            $historicalCount = count($historicalValues);

            $usingProphet = false;
            $forecastValues = [];
            $forecastLowerValues = [];
            $forecastUpperValues = [];
            $modelName = '7-Day SMA';
            $modelGeneratedAt = null;
            $trainingCutoff = $endDate->toDateString();
            $plotImage = null;
            $modelMetrics = null;

            if ($prophetRecord && !empty($prophetRecord['forecast'])) {
                $prophetSeries = collect($prophetRecord['forecast']);
                
                // Filter to only future dates (from today onwards) and take the requested horizon
                $today = Carbon::today()->toDateString();
                $prophetSubset = $prophetSeries
                    ->filter(function ($item) use ($today) {
                        return $item['date'] >= $today;
                    })
                    ->take($horizon);

                if ($prophetSubset->isNotEmpty()) {
                    $usingProphet = true;
                    $modelName = 'Facebook Prophet';
                    $modelGeneratedAt = $prophetRecord['generated_at'] ?? null;
                    $trainingCutoff = $prophetRecord['history_last_date'] ?? $trainingCutoff;
                    $plotImage = isset($prophetRecord['plot'])
                        ? asset('images/forecasts/'.$prophetRecord['plot'])
                        : null;
                    $modelMetrics = $prophetRecord['metrics'] ?? null;

                    $forecastLabels = $prophetSubset->pluck('date')->toArray();
                    $forecastValues = $prophetSubset->pluck('yhat')->map(fn ($value) => round(max(0, $value), 2))->toArray();
                    $forecastLowerValues = $prophetSubset->pluck('yhat_lower')->map(fn ($value) => round(max(0, $value), 2))->toArray();
                    $forecastUpperValues = $prophetSubset->pluck('yhat_upper')->map(fn ($value) => round(max(0, $value), 2))->toArray();

                    $totalPredictedSales = $prophetSubset->sum('yhat');
                    $dailyAverage = $prophetSubset->avg('yhat');
                }
            }

            if (!$usingProphet) {
                $forecastValues = [];
                $forecastLowerValues = [];
                $forecastUpperValues = [];
                $forecastLabels = [];

                for ($i = 1; $i <= $horizon; $i++) {
                    $forecastLabels[] = $endDate->copy()->addDays($i)->toDateString();
                    $forecastValues[] = round($dailyAverage, 2);
                    $forecastLowerValues[] = round($dailyAverage, 2);
                    $forecastUpperValues[] = round($dailyAverage, 2);
                }
            }

            $forecastData = [
                'totalPredictedSales' => (int) round($totalPredictedSales),
                'dailyAverage' => round($dailyAverage, 2),
                'chartLabels' => array_merge($historicalLabels, $forecastLabels),
                'historicalData' => array_merge($historicalValues, array_fill(0, $horizon, null)),
                'forecastData' => array_merge(array_fill(0, $historicalCount, null), $forecastValues),
                'confidenceLower' => array_merge(array_fill(0, $historicalCount, null), $forecastLowerValues),
                'confidenceUpper' => array_merge(array_fill(0, $historicalCount, null), $forecastUpperValues),
                'lastSaleDate' => $endDate->toDateString(),
                'salesDaysCount' => $usingProphet ? count($forecastValues) : $smaWindowSales->count(),
                'model' => $modelName,
                'modelGeneratedAt' => $modelGeneratedAt,
                'modelTrainingCutoff' => $trainingCutoff,
                'plotImage' => $plotImage,
                'metrics' => $modelMetrics ?? null,
            ];
        }

        return Inertia::render('Admin/Forecasting', [
            'eggProducts' => EggProduct::where('name', '!=', 'DAMAGED')
                ->whereRaw('LOWER(name) != ?', ['damaged eggs'])
                ->orderBy('name')
                ->get(['id', 'name']),
            'forecastData' => $forecastData,
            'filters' => ['product_id' => (int)$productId, 'horizon' => $horizon],
        ]);
    }

    /**
     * Load cached Prophet results from disk.
     */
    private function loadProphetResults(): array
    {
        $path = base_path('forecasting_scripts/forecast_results.json');

        if (!File::exists($path)) {
            return [];
        }

        $contents = json_decode(File::get($path), true);

        return is_array($contents) ? $contents : [];
    }

    /**
     * Map a product name to the matching Prophet key and return its record.
     */
    private function extractProphetRecord(string $productName, array $results): ?array
    {
        $name = strtoupper(trim($productName));
        
        // Comprehensive mapping of database names to Prophet forecast keys
        $aliases = [
            'XL' => 'X-LARGE',
            'X-LARGE' => 'X-LARGE',
            'X LARGE' => 'X-LARGE',
            'PULLETS' => 'PULLETS',
            'PULLET' => 'PULLETS',
            'SMALL' => 'SMALL',
            'MEDIUM' => 'MEDIUM',
            'LARGE' => 'LARGE',
            'JUMBO' => 'JUMBO',
            'PEWEE' => 'PEWEE',
            'DAMAGED EGGS' => 'DAMAGED EGGS',
        ];

        // Try exact match first
        $key = $aliases[$name] ?? $name;
        
        // If not found, try direct lookup
        if (!isset($results[$key])) {
            // Try case-insensitive search through all keys
            foreach ($results as $resultKey => $resultData) {
                if (strtoupper($resultKey) === $name || strtoupper($resultKey) === $key) {
                    return $resultData;
                }
            }
        }

        return $results[$key] ?? null;
    }
}