<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ChickenStockLog;
use App\Models\FarmStat;
use Illuminate\Support\Facades\DB;

class ChickenStockController extends Controller
{
    public function index()
    {
        // Get the single stat record for current chicken stock, or create it if it doesn't exist
        $currentStock = FarmStat::firstOrCreate(
            ['stat_key' => 'current_chicken_stock'],
            ['stat_value' => 0]
        );
        
        // Get the last 10 adjustments for the history log
        $recentAdjustments = ChickenStockLog::latest()->limit(10)->get();

        return inertia('Staff/AdjustChickenStock', [
            'currentStock' => $currentStock->stat_value,
            'recentAdjustments' => $recentAdjustments,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'adjustment_type' => ['required', 'in:addition,removal'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($validated, $request) {
            // Step 1: Create a log of the adjustment
            ChickenStockLog::create([
                'user_id' => $request->user()->id,
                'adjustment_type' => $validated['adjustment_type'],
                'quantity' => $validated['quantity'],
                'reason' => $validated['reason'],
                'notes' => $validated['notes'],
            ]);

            // Step 2: Update the single stat value
            $stockStat = FarmStat::firstWhere('stat_key', 'current_chicken_stock');

            if ($validated['adjustment_type'] === 'addition') {
                $stockStat->increment('stat_value', $validated['quantity']);
            } else {
                $stockStat->decrement('stat_value', $validated['quantity']);
            }
        });
        
        return to_route('chicken.stock.index')->with('success', 'Chicken stock updated successfully!');
    }
}
