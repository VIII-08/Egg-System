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
        $validated = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'exists:egg_products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'amount_paid_now' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Security: Ensure user is authenticated and has proper role
        $user = $request->user();
        if (!$user || !in_array($user->role, ['staff-marketing', 'admin'])) {
            abort(403, 'Only marketing staff can record sales.');
        }

        // Start a database transaction to ensure all operations succeed or none do
        $transaction = DB::transaction(function () use ($request, $user, $validated) {
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
            $timestamp = now();
            foreach($saleItemsData as &$item) {
                $item['sales_transaction_id'] = $salesTransaction->id;
                $item['created_at'] = $timestamp;
                $item['updated_at'] = $timestamp;
            }
            SaleItem::insert($saleItemsData);

            // Finally, update the product stock
            foreach ($productUpdates as $update) {
                EggProduct::where('id', $update['id'])->decrement('stock_quantity', $update['quantity']);
            }

            // Handle collectibles (credit/utang)
            $amountPaidNow = max(0, (float) ($validated['amount_paid_now'] ?? 0));
            $balance = round($totalAmount - $amountPaidNow, 2);

            if ($balance > 0) {
                $status = $amountPaidNow > 0 ? 'partial' : 'unpaid';
                $salesTransaction->collectible()->create([
                    'customer_name' => $request->input('customer_name'),
                    'total_amount' => $totalAmount,
                    'amount_paid' => $amountPaidNow,
                    'balance' => $balance,
                    'status' => $status,
                    'last_payment_date' => $amountPaidNow > 0 ? now()->toDateString() : null,
                    'fully_paid_date' => null,
                ]);
            }

            return $salesTransaction;
        });

        return to_route('sales.create')->with('success', 'Sale completed successfully!');
    }
}
