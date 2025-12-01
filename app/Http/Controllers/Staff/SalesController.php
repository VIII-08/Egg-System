<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EggProduct;
use App\Models\SalesTransaction;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesController extends Controller
{
    public function create()
    {
        $products = EggProduct::orderBy('id')->get();
        return inertia('Staff/RecordSale', ['products' => $products]);
    }

    // Method to save the entire sale
    public function store(Request $request)
    {
        $request->validate([
            'customer_name' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'exists:egg_products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        // Security: Ensure user is authenticated and has proper role
        $user = $request->user();
        if (!$user || !in_array($user->role, ['staff-marketing', 'admin'])) {
            abort(403, 'Only marketing staff can record sales.');
        }

        // Start a database transaction to ensure all operations succeed or none do
        $transaction = DB::transaction(function () use ($request, $user) {
            $totalAmount = 0;
            $saleItemsData = [];
            $productUpdates = [];

            // First, validate stock and calculate total on the server-side for security
            foreach ($request->input('items') as $item) {
                $product = EggProduct::find($item['id']);
                
                // Security: Validate product exists
                if (!$product) {
                    throw ValidationException::withMessages([
                        'items' => 'Invalid product selected.',
                    ]);
                }
                
                if ($product->stock_quantity < $item['quantity']) {
                    // If not enough stock, throw an error to cancel the transaction
                    throw ValidationException::withMessages([
                        'items' => "Not enough stock for {$product->name}. Only {$product->stock_quantity} available.",
                    ]);
                }

                $subtotal = $product->price * $item['quantity'];
                $totalAmount += $subtotal;
                
                $saleItemsData[] = [
                    'egg_product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                ];

                $productUpdates[] = ['id' => $product->id, 'quantity' => $item['quantity']];
            }

            // If validation passes, create the main transaction record
            // Security: Explicitly use authenticated user ID (defense in depth)
            $salesTransaction = SalesTransaction::create([
                'user_id' => $user->id,
                'total_amount' => $totalAmount,
                'customer_name' => $request->input('customer_name'),
            ]);

            // Create the individual sale items
            foreach($saleItemsData as &$item) {
                $item['sales_transaction_id'] = $salesTransaction->id;
            }
            SaleItem::insert($saleItemsData);

            // Finally, update the product stock
            foreach ($productUpdates as $update) {
                EggProduct::where('id', $update['id'])->decrement('stock_quantity', $update['quantity']);
            }

            return $salesTransaction;
        });

        return to_route('sales.create')->with('success', 'Sale completed successfully!');
    }
}
