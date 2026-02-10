<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FeedUsageLog;
use App\Models\FarmStat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class FeedController extends Controller
{
    /**
     * Record feed usage (deduct from inventory)
     */
    public function recordUsage(Request $request)
    {
        $validated = $request->validate([
            'quantity_kg' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        // Get current feed stock
        $feedStat = FarmStat::firstOrCreate(
            ['stat_key' => 'current_feed_stock_kg'],
            ['stat_value' => 0]
        );

        // Validate that we have enough feed
        if ($validated['quantity_kg'] > $feedStat->stat_value) {
            return back()->withErrors([
                'quantity_kg' => "Cannot take {$validated['quantity_kg']} kg. Current stock is only {$feedStat->stat_value} kg."
            ])->withInput();
        }

        DB::transaction(function () use ($validated, $request) {
            // Step 1: Create a log of the usage
            FeedUsageLog::create([
                'user_id' => $request->user()->id,
                'quantity_kg' => $validated['quantity_kg'],
                'notes' => $validated['notes'],
            ]);

            // Step 2: Deduct from the feed stock
            $feedStat = FarmStat::firstWhere('stat_key', 'current_feed_stock_kg');
            $feedStat->decrement('stat_value', $validated['quantity_kg']);
        });

        return back()->with('success', 'Feed usage recorded successfully!');
    }
}
