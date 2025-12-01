<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\EggProduct;
use App\Models\SaleItem;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class EggProductController extends Controller
{
    public function index()
    {
        $products = EggProduct::orderBy('name')->get();

        // Get usage count for each product
        $usageCounts = SaleItem::select('egg_product_id', DB::raw('COUNT(*) as count'))
            ->groupBy('egg_product_id')
            ->pluck('count', 'egg_product_id')
            ->toArray();

        $productsWithUsage = $products->map(function ($product) use ($usageCounts) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'price' => (float) $product->price,
                'stock_quantity' => $product->stock_quantity,
                'description' => $product->description,
                'usage_count' => $usageCounts[$product->id] ?? 0,
            ];
        });

        return Inertia::render('Admin/ManageEggProducts', [
            'products' => $productsWithUsage->values()->toArray(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:egg_products,name',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'stock_quantity' => 'nullable|integer|min:0',
        ]);

        $product = EggProduct::create([
            'name' => $validated['name'],
            'price' => $validated['price'],
            'description' => $validated['description'] ?? null,
            'stock_quantity' => $validated['stock_quantity'] ?? 0,
        ]);

        // Log the creation
        $userName = Auth::user() ? Auth::user()->name : 'System';
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'egg_product_created',
            'log_entry' => "`{$userName}` added a new egg size: `{$product->name}` with price ₱{$product->price}.",
        ]);

        return back()->with('success', 'Egg product created successfully.');
    }

    public function update(Request $request, EggProduct $eggProduct)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        // Check if name is being changed and if new name already exists
        if ($validated['name'] !== $eggProduct->name) {
            $exists = EggProduct::where('name', $validated['name'])
                ->where('id', '!=', $eggProduct->id)
                ->exists();
            
            if ($exists) {
                return back()->withErrors(['name' => 'An egg product with this name already exists.']);
            }
        }

        $eggProduct->update($validated);

        return back()->with('success', 'Egg product updated successfully.');
    }

    public function destroy(EggProduct $eggProduct)
    {
        // Check if product is in use
        $inUse = SaleItem::where('egg_product_id', $eggProduct->id)->exists();
        
        if ($inUse) {
            return back()->withErrors(['product' => 'Cannot delete egg product that has been used in sales.']);
        }

        $productName = $eggProduct->name;
        $eggProduct->delete();

        // Log the deletion
        $userName = Auth::user() ? Auth::user()->name : 'System';
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'egg_product_deleted',
            'log_entry' => "`{$userName}` deleted the egg size: `{$productName}`.",
        ]);

        return back()->with('success', 'Egg product deleted successfully.');
    }
}

