<?php

namespace App\Http\Controllers\Treasurer;

use App\Http\Controllers\Controller;
use App\Models\SaleItem;
use App\Models\SalesTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;

class ForecastingController extends Controller
{
    public function index()
    {
        $forecastResults = $this->loadForecastResults();
        $historical = $this->buildHistoricalSeries();
        $forecastSeries = $this->buildForecastSeries($forecastResults);
        $productBreakdown = $this->buildProductBreakdown($forecastResults);

        $chartData = $this->composeChartData($historical, $forecastSeries);

        $summary = [
            'next30Days' => $productBreakdown['totals']['next30Days'],
            'avgDailyForecast' => $productBreakdown['totals']['avgDaily'],
            'generatedAt' => $productBreakdown['totals']['generatedAt'],
            'topProduct' => $productBreakdown['topProducts'][0] ?? null,
        ];

        return inertia('Treasurer/Forecasting', [
            'chartData' => $chartData,
            'summary' => $summary,
            'topProducts' => $productBreakdown['topProducts'],
        ]);
    }

    private function buildHistoricalSeries(): array
    {
        $monthsBack = 5;
        $startDate = Carbon::now()->subMonths($monthsBack)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        $rows = SaleItem::query()
            ->join('sales_transactions', 'sale_items.sales_transaction_id', '=', 'sales_transactions.id')
            ->selectRaw("DATE_FORMAT(sales_transactions.created_at, '%Y-%m') as month_key, SUM(sale_items.quantity) as total_qty")
            ->whereBetween('sales_transactions.created_at', [$startDate, $endDate])
            ->groupBy('month_key')
            ->orderBy('month_key')
            ->get();

        return $rows->mapWithKeys(fn ($row) => [
            $row->month_key => (float) $row->total_qty,
        ])->toArray();
    }

    private function buildForecastSeries(array $results): array
    {
        $totals = [];
        foreach ($results as $record) {
            if (empty($record['forecast'])) {
                continue;
            }

            foreach ($record['forecast'] as $entry) {
                $date = Carbon::parse($entry['date']);
                if ($date->lt(Carbon::now()->startOfMonth())) {
                    continue;
                }

                $monthKey = $date->format('Y-m');
                $totals[$monthKey] = ($totals[$monthKey] ?? 0) + (float) ($entry['yhat'] ?? 0);
            }
        }

        ksort($totals);

        return collect($totals)->take(4)->toArray();
    }

    private function composeChartData(array $historical, array $forecast): ?array
    {
        $monthKeys = collect(array_keys($historical))
            ->merge(array_keys($forecast))
            ->unique()
            ->sort()
            ->values();

        if ($monthKeys->isEmpty()) {
            return null;
        }

        $labels = [];
        $actualSeries = [];
        $forecastSeries = [];

        foreach ($monthKeys as $key) {
            $labels[] = Carbon::createFromFormat('Y-m', $key)->format('M Y');
            $actualSeries[] = $historical[$key] ?? null;
            $forecastSeries[] = $forecast[$key] ?? null;
        }

        return [
            'labels' => $labels,
            'actual' => $actualSeries,
            'forecast' => $forecastSeries,
        ];
    }

    private function buildProductBreakdown(array $results): array
    {
        $next30End = Carbon::now()->addDays(29)->endOfDay();
        $totals = [
            'next30Days' => 0,
            'avgDaily' => 0,
            'generatedAt' => null,
        ];

        $products = [];

        foreach ($results as $name => $record) {
            if (empty($record['forecast'])) {
                continue;
            }

            $windowTotal = 0;
            $daysCounted = 0;

            foreach ($record['forecast'] as $entry) {
                $date = Carbon::parse($entry['date']);
                if ($date->greaterThan($next30End)) {
                    continue;
                }
                if ($date->lt(Carbon::now()->startOfDay())) {
                    continue;
                }

                $windowTotal += (float) ($entry['yhat'] ?? 0);
                $daysCounted++;
            }

            if ($windowTotal <= 0) {
                continue;
            }

            $avgDaily = $daysCounted > 0 ? $windowTotal / $daysCounted : 0;

            $products[] = [
                'name' => $name,
                'next30Days' => round($windowTotal),
                'avgDaily' => round($avgDaily, 2),
                'mae' => $record['metrics']['mae'] ?? null,
                'rmse' => $record['metrics']['rmse'] ?? null,
            ];

            $totals['next30Days'] += $windowTotal;
        }

        $totals['avgDaily'] = $products
            ? round($totals['next30Days'] / 30, 2)
            : 0;

        $totals['generatedAt'] = $results[array_key_first($results)]['generated_at'] ?? null;

        usort($products, fn ($a, $b) => $b['next30Days'] <=> $a['next30Days']);

        return [
            'totals' => $totals,
            'topProducts' => array_slice($products, 0, 5),
        ];
    }

    private function loadForecastResults(): array
    {
        $path = base_path('forecasting_scripts/forecast_results.json');

        if (!File::exists($path)) {
            return [];
        }

        $contents = json_decode(File::get($path), true);

        return is_array($contents) ? $contents : [];
    }
}



