<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductionLog;
use App\Models\FarmStat;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ProductionDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Metric 1: Total egg inventory from all products
        $totalEggInventory = (int) \App\Models\EggProduct::sum('stock_quantity');
    
        // Metric 2: Eggs collected by this specific user today
        $eggsCollectedToday = (int) \App\Models\ProductionLog::where('user_id', $user->id)
                                                    ->whereDate('log_date', today())
                                                    ->sum('quantity');
    
        // Metric 3: Expenses logged by this user today
        $expensesToday = \App\Models\Expense::where('user_id', $user->id)
                                          ->whereDate('expense_date', today())
                                          ->sum('amount');
    
        // Metric 4: Recent production logs for this user's activity feed
        $recentActivities = \App\Models\ProductionLog::where('user_id', $user->id)
                                                  ->latest()
                                                  ->limit(5)
                                                  ->with('eggProduct') // Eager load the product name
                                                  ->get();
    
        // --- Metric: Damaged Eggs Today ---
        $brokenEggsToday = ProductionLog::query()
                                                ->where('user_id', $user->id)
                                                ->whereDate('log_date', today())
                                                ->whereHas('eggProduct', function ($query) {
                                                    $query->where('name', 'Damaged Eggs');
                                                })
                                                ->sum('quantity');

        // Get today's production batches for this user
        $todayProductionBatches = $this->buildTodayProductionBatches($user->id);

        // Current feed stock for production staff
        $currentFeedStock = (float) (FarmStat::where('stat_key', 'current_feed_stock_kg')->value('stat_value') ?? 0);

        // Pass all the metrics to the Vue component
        return inertia('Staff/ProductionDashboard', [
            'totalEggInventory' => $totalEggInventory,
            'eggsCollectedToday' => $eggsCollectedToday,
            'brokenEggsToday' => (int) $brokenEggsToday,
            'expensesToday' => (float) $expensesToday,
            'recentActivities' => $recentActivities,
            'todayProductionBatches' => $todayProductionBatches,
            'currentFeedStock' => $currentFeedStock,
        ]);
    }

    /**
     * Build today's production batches for a specific user
     */
    private function buildTodayProductionBatches(int $userId): array
    {
        // Get all logs for today (check both log_date and created_at to catch all cases)
        $logs = ProductionLog::with(['eggProduct', 'user'])
            ->where('user_id', $userId)
            ->where(function($query) {
                $query->whereDate('log_date', today())
                      ->orWhereDate('created_at', today());
            })
            ->whereNotNull('batch_reference')
            ->orderByDesc('created_at')
            ->get();

        // If no logs with batch_reference, return empty array
        if ($logs->isEmpty()) {
            return [];
        }

        // Group by batch_reference
        $grouped = $logs->groupBy('batch_reference');

        return $grouped->map(function (Collection $batchLogs, string $batchReference) {
            $first = $batchLogs->first();

            return [
                'batch_reference' => $batchReference,
                'created_at' => $first?->created_at,
                'logged_by' => $first?->user?->name ?? 'Unknown',
                'total_quantity' => $batchLogs->sum('quantity'),
                'items' => $batchLogs->map(function ($log) {
                    return [
                        'egg_size' => $log->eggProduct->name ?? 'Unknown',
                        'quantity' => (int) $log->quantity,
                    ];
                })->values(),
            ];
        })->values()->all();
    }
}
