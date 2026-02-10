<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EggProduct;
use App\Models\ProductionLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

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
        'collection_date' => [
            'required', 
            'date',
            function ($attribute, $value, $fail) {
                if ($value !== now()->format('Y-m-d')) {
                    $fail('You can only log production for today\'s date.');
                }
            },
        ],
        'quantities' => ['required', 'array'],
        'quantities.*' => ['nullable', 'integer', 'min:0'],
        'broken_quantity' => ['nullable', 'integer', 'min:0'], // <-- Add validation
        'notes' => ['nullable', 'string', 'max:255'],
    ]);

    $batchReference = (string) Str::uuid();

    // Use a database transaction for data integrity
    DB::transaction(function () use ($validated, $request, $batchReference) {
        // -- Process the main grid of sellable eggs --
        foreach ($validated['quantities'] as $productId => $quantity) {
            if ($quantity > 0) {
                ProductionLog::create([
                    'user_id' => $request->user()->id,
                    'egg_product_id' => $productId,
                    'quantity' => $quantity,
                    'log_date' => $validated['collection_date'],
                    'batch_reference' => $batchReference,
                ]);
                // Update the inventory stock for sellable eggs
                EggProduct::find($productId)->increment('stock_quantity', $quantity);
            }
        }

        // -- Process the damaged eggs separately --
        if (!empty($validated['broken_quantity']) && $validated['broken_quantity'] > 0) {
            // Find the "Damaged Eggs" or "Damage Eggs" product record (case-insensitive)
            $damagedEggProduct = EggProduct::where(function($query) {
                $query->whereRaw('LOWER(name) = ?', ['damaged eggs'])
                      ->orWhereRaw('LOWER(name) = ?', ['damage eggs']);
            })->first();
            if ($damagedEggProduct) {
                ProductionLog::create([
                    'user_id' => $request->user()->id,
                    'egg_product_id' => $damagedEggProduct->id,
                    'quantity' => $validated['broken_quantity'],
                    'log_date' => $validated['collection_date'],
                    'batch_reference' => $batchReference,
                ]);
                // You might choose to increment stock for damaged eggs if you track them
                // or do nothing if they are simply discarded. For now, we'll log them.
                $damagedEggProduct->increment('stock_quantity', $validated['broken_quantity']);
            }
        }
    });

    return to_route('production.logs.create')->with('success', 'Production batch, including damaged eggs, logged successfully!');
}
}
