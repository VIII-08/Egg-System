<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductionLog; // Use our new model

use Illuminate\Support\Facades\Auth; // To get the logged-in user

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
    
        // --- Metric: Broken Eggs Today ---
        $brokenEggsToday = ProductionLog::query()
                                                ->where('user_id', $user->id)
                                                ->whereDate('log_date', today())
                                                ->whereHas('eggProduct', function ($query) {
                                                    $query->where('name', 'Broken Eggs');
                                                })
                                                ->sum('quantity');

        // Pass all the metrics to the Vue component
        return inertia('Staff/ProductionDashboard', [
            'totalEggInventory' => $totalEggInventory,
            'eggsCollectedToday' => $eggsCollectedToday,
            'brokenEggsToday' => (int) $brokenEggsToday,
            'expensesToday' => (float) $expensesToday,
            'recentActivities' => $recentActivities,
        ]);
    }
}
