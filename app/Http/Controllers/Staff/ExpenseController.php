<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FarmStat;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    public function create(Request $request)
    {
        // Get the authenticated user's role
        $userRole = $request->user()->role;

        // Get categories from the database for this role
        $expenseCategories = ExpenseCategory::where('role', $userRole)
            ->orderBy('name')
            ->pluck('name')
            ->toArray();
    
        // Fetch recent expenses for the sidebar history
        $recentExpenses = Expense::where('user_id', Auth::id())
                              ->latest()
                              ->limit(5)
                              ->with('user')
                              ->get();
    
        return Inertia::render('Staff/RecordExpense', [
            'recentExpenses' => $recentExpenses,
            'expenseCategories' => $expenseCategories,
        ]);
    }

    public function store(Request $request)
    {
        // Security: Ensure user is authenticated and has proper role
        $user = $request->user();
        if (!$user || !in_array($user->role, ['staff-production', 'staff-marketing', 'admin'])) {
            abort(403, 'Only staff members can record expenses.');
        }

        $validated = $request->validate([
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
            'category' => ['required', 'string', 'max:255'],
            'feed_quantity_kg' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'amount' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'description' => ['nullable', 'string', 'max:255'],
            'receipt_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,gif', 'max:2048'], // Validate the image
        ]);

        $imagePath = null;
        if ($request->hasFile('receipt_image')) {
            // Store the image in `storage/app/public/receipts` and get its path
            $imagePath = $request->file('receipt_image')->store('receipts', 'public');
        }

        DB::transaction(function () use ($validated, $user, $imagePath) {
            // Security: Explicitly use authenticated user ID (defense in depth)
            Expense::create([
                'user_id' => $user->id,
                'expense_date' => $validated['expense_date'],
                'category' => $validated['category'],
                'feed_quantity_kg' => $validated['feed_quantity_kg'] ?? null,
                'amount' => $validated['amount'],
                'description' => $validated['description'],
                'receipt_image_path' => $imagePath, // Save the path to the database
            ]);

            // If this is a Feeds expense with quantity, add to feed inventory
            if ($validated['category'] === 'Feeds' && isset($validated['feed_quantity_kg']) && $validated['feed_quantity_kg'] > 0) {
                $feedStat = FarmStat::firstOrCreate(
                    ['stat_key' => 'current_feed_stock_kg'],
                    ['stat_value' => 0]
                );
                $feedStat->increment('stat_value', $validated['feed_quantity_kg']);
            }
        });

        $feedAddedKg = ($validated['category'] === 'Feeds' && isset($validated['feed_quantity_kg']) && $validated['feed_quantity_kg'] > 0)
            ? (float) $validated['feed_quantity_kg']
            : null;

        if ($feedAddedKg !== null) {
            return to_route('expenses.create')
                ->with('success', 'Expense recorded successfully! ' . number_format($feedAddedKg, 2) . ' kg of feed has been added to inventory.')
                ->with('feed_added_kg', $feedAddedKg);
        }

        return to_route('expenses.create')->with('success', 'Expense recorded successfully!');
    }
}
