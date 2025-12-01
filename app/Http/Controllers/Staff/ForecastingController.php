<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;

class ForecastingController extends Controller
{
    public function production()
    {
        return inertia('Staff/ProductionForecasting', $this->buildResponse());
    }

    public function marketing()
    {
        return inertia('Staff/MarketingForecasting', $this->buildResponse());
    }

    private function buildResponse(): array
    {
        $forecastResults = $this->loadForecastResults();
        $windowDays = 14;

        $dailyForecast = $this->buildDailyForecastSeries($forecastResults, $windowDays);
        $chartData = $this->formatChartData($dailyForecast);
        $topProducts = $this->buildTopProducts($forecastResults, $windowDays);

        $summary = [
            'windowDays' => $windowDays,
            'projectedTotal' => array_sum($dailyForecast),
            'avgDailyForecast' => $windowDays > 0 ? round(array_sum($dailyForecast) / $windowDays, 2) : 0,
            'recentDailyAverage' => $this->calculateRecentAverage(),
            'generatedAt' => $this->extractGeneratedAt($forecastResults),
        ];

        return [
            'chartData' => $chartData,
            'summary' => $summary,
            'topProducts' => $topProducts,
        ];
    }

    private function buildDailyForecastSeries(array $results, int $windowDays): array
    {
        $start = Carbon::today();
        $end = Carbon::today()->addDays($windowDays - 1);

        $totals = [];

        foreach ($results as $record) {
            if (empty($record['forecast'])) {
                continue;
            }

            foreach ($record['forecast'] as $entry) {
                $date = Carbon::parse($entry['date']);
                if ($date->lt($start) || $date->gt($end)) {
                    continue;
                }

                $key = $date->toDateString();
                $totals[$key] = ($totals[$key] ?? 0) + (float) ($entry['yhat'] ?? 0);
            }
        }

        $series = [];
        for ($i = 0; $i < $windowDays; $i++) {
            $date = $start->copy()->addDays($i)->toDateString();
            $series[$date] = round($totals[$date] ?? 0, 2);
        }

        return $series;
    }

    private function formatChartData(array $dailyForecast): ?array
    {
        if (empty($dailyForecast)) {
            return null;
        }

        $labels = [];
        $values = [];

        foreach ($dailyForecast as $date => $value) {
            $labels[] = Carbon::parse($date)->format('M d');
            $values[] = $value;
        }

        return [
            'labels' => $labels,
            'forecast' => $values,
        ];
    }

    private function buildTopProducts(array $results, int $windowDays): array
    {
        $start = Carbon::today();
        $end = Carbon::today()->addDays($windowDays - 1)->endOfDay();

        $products = [];

        foreach ($results as $name => $record) {
            if (empty($record['forecast'])) {
                continue;
            }

            $total = 0;
            $daysCounted = 0;

            foreach ($record['forecast'] as $entry) {
                $date = Carbon::parse($entry['date']);
                if ($date->lt($start) || $date->gt($end)) {
                    continue;
                }

                $total += (float) ($entry['yhat'] ?? 0);
                $daysCounted++;
            }

            if ($total <= 0) {
                continue;
            }

            $products[] = [
                'name' => $name,
                'total' => round($total),
                'avgDaily' => $daysCounted > 0 ? round($total / $daysCounted, 2) : 0,
                'mae' => $record['metrics']['mae'] ?? null,
                'rmse' => $record['metrics']['rmse'] ?? null,
            ];
        }

        usort($products, fn ($a, $b) => $b['total'] <=> $a['total']);

        return array_slice($products, 0, 5);
    }

    private function calculateRecentAverage(): float
    {
        $start = Carbon::today()->subDays(6)->startOfDay();
        $end = Carbon::today()->endOfDay();

        $totals = SaleItem::query()
            ->join('sales_transactions', 'sale_items.sales_transaction_id', '=', 'sales_transactions.id')
            ->whereBetween('sales_transactions.created_at', [$start, $end])
            ->sum('sale_items.quantity');

        return round($totals / 7, 2);
    }

    private function extractGeneratedAt(array $results): ?string
    {
        foreach ($results as $record) {
            if (!empty($record['generated_at'])) {
                return $record['generated_at'];
            }
        }

        return null;
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



