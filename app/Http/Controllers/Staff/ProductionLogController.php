<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EggProduct;
use App\Models\ProductionLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProductionLogController extends Controller
{
    // Method to SHOW the form page
    public function create()
{
    $eggProducts = EggProduct::orderBy('id')->get();
    
    // --- THIS IS THE CORRECTED QUERY ---
    // It now correctly filters for logs CREATED TODAY.
    $recentLogs = ProductionLog::where('user_id', Auth::id())
                              ->whereDate('created_at', today()) // <-- This is the new, critical line
                              ->whereHas('user')
                              ->whereHas('eggProduct')
                              ->with(['user', 'eggProduct'])
                              ->latest()
                              ->get(); // We get all of today's logs, no need for limit(5)

    return inertia('Staff/LogProduction', [
        'eggProducts' => $eggProducts,
        'recentLogs' => $recentLogs,
    ]);
}

    // Method to SAVE the form data
    public function store(Request $request)
{
    // Validate the incoming data, including our new field
    $validated = $request->validate([
        'collection_date' => ['required', 'date'],
        'quantities' => ['required', 'array'],
        'quantities.*' => ['nullable', 'integer', 'min:0'],
        'broken_quantity' => ['nullable', 'integer', 'min:0'], // <-- Add validation
        'notes' => ['nullable', 'string', 'max:255'],
    ]);

    // Use a database transaction for data integrity
    DB::transaction(function () use ($validated, $request) {
        // -- Process the main grid of sellable eggs --
        foreach ($validated['quantities'] as $productId => $quantity) {
            if ($quantity > 0) {
                ProductionLog::create([
                    'user_id' => $request->user()->id,
                    'egg_product_id' => $productId,
                    'quantity' => $quantity,
                    'log_date' => $validated['collection_date'],
                ]);
                // Update the inventory stock for sellable eggs
                EggProduct::find($productId)->increment('stock_quantity', $quantity);
            }
        }

        // -- Process the broken eggs separately --
        if (!empty($validated['broken_quantity']) && $validated['broken_quantity'] > 0) {
            // Find the "Broken Eggs" product record
            $brokenEggProduct = EggProduct::where('name', 'Broken Eggs')->first();
            if ($brokenEggProduct) {
                ProductionLog::create([
                    'user_id' => $request->user()->id,
                    'egg_product_id' => $brokenEggProduct->id,
                    'quantity' => $validated['broken_quantity'],
                    'log_date' => $validated['collection_date'],
                ]);
                // You might choose to increment stock for broken eggs if you track them
                // or do nothing if they are simply discarded. For now, we'll log them.
                $brokenEggProduct->increment('stock_quantity', $validated['broken_quantity']);
            }
        }
    });

    return to_route('production.logs.create')->with('success', 'Production batch, including broken eggs, logged successfully!');
}
}
