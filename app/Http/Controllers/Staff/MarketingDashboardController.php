<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EggProduct;
use App\Models\SalesTransaction;
use App\Models\Expense;
use Illuminate\Support\Facades\Auth;

class MarketingDashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        // Total egg inventory across all products
        $totalEggInventory = EggProduct::sum('stock_quantity');
        
        // Total sales amount created today
        $salesToday = SalesTransaction::whereDate('created_at', today())->sum('total_amount');
        
        // Expenses logged by this specific user today
        $expensesToday = Expense::where('user_id', $userId)->whereDate('expense_date', today())->sum('amount');
        
        // This user's recent activity (their last 5 sales)
        $recentActivities = SalesTransaction::where('user_id', $userId)->latest()->limit(5)->get();

        return inertia('Staff/MarketingDashboard', [
            'totalEggInventory' => (int) $totalEggInventory,
            'salesToday' => (float) $salesToday,
            'expensesToday' => (float) $expensesToday,
            'recentActivities' => $recentActivities,
        ]);
    }
}
