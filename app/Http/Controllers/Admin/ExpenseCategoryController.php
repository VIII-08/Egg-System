<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class ExpenseCategoryController extends Controller
{
    public function index(Request $request)
    {
        $selectedRole = $request->input('role', 'all');

        $query = ExpenseCategory::query();

        if ($selectedRole !== 'all') {
            $query->where('role', $selectedRole);
        }

        $categories = $query->orderBy('name')->get();

        // Get usage count for each category from actual expenses
        $usageCounts = Expense::query()
            ->join('users', 'expenses.user_id', '=', 'users.id')
            ->select('expenses.category', DB::raw('COUNT(*) as count'))
            ->whereNotNull('expenses.category')
            ->where('expenses.category', '!=', '');

        if ($selectedRole !== 'all') {
            $usageCounts->where('users.role', $selectedRole);
        }

        $usageCounts = $usageCounts->groupBy('expenses.category')
            ->pluck('count', 'expenses.category')
            ->toArray();

        $categoriesWithUsage = $categories->map(function ($category) use ($usageCounts) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'role' => $category->role,
                'usage_count' => $usageCounts[$category->name] ?? 0,
            ];
        })->toArray();

        return Inertia::render('Admin/ManageExpenseCategories', [
            'categories' => $categoriesWithUsage ?: [],
            'selectedRole' => $selectedRole,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string|max:255',
            'role' => 'required|string|in:staff-production,staff-marketing',
        ]);

        // Check if category already exists for this role
        $exists = ExpenseCategory::where('name', $validated['category'])
            ->where('role', $validated['role'])
            ->exists();
        
        if ($exists) {
            return back()->withErrors(['category' => 'This category already exists for this role.']);
        }

        // Create the category
        $category = ExpenseCategory::create([
            'name' => $validated['category'],
            'role' => $validated['role'],
        ]);

        // Log the creation
        $userName = Auth::user() ? Auth::user()->name : 'System';
        $roleDisplay = $validated['role'] === 'staff-production' ? 'Production Staff' : 'Marketing Staff';
        AuditLog::createWithRequest([
            'user_id' => Auth::id(),
            'action' => 'expense_category_created',
            'log_entry' => "`{$userName}` added a new expense category: `{$category->name}` for {$roleDisplay}.",
        ], $request);

        return back()->with('success', 'Category added successfully for ' . $validated['role'] . ' staff.');
    }

    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        $validated = $request->validate([
            'category' => 'required|string|max:255',
            'role' => 'required|string|in:staff-production,staff-marketing',
        ]);

        // Check if name is being changed and if new name already exists for this role
        if ($validated['category'] !== $expenseCategory->name || $validated['role'] !== $expenseCategory->role) {
            $exists = ExpenseCategory::where('name', $validated['category'])
                ->where('role', $validated['role'])
                ->where('id', '!=', $expenseCategory->id)
                ->exists();
            
            if ($exists) {
                return back()->withErrors(['category' => 'This category already exists for this role.']);
            }
        }

        // If category name changed and it's in use, update all related expenses
        if ($validated['category'] !== $expenseCategory->name && $expenseCategory->role === $validated['role']) {
            $inUse = Expense::where('category', $expenseCategory->name)
                ->join('users', 'expenses.user_id', '=', 'users.id')
                ->where('users.role', $expenseCategory->role)
                ->exists();
            
            if ($inUse) {
                // Update all expenses with the old category name to the new name
                Expense::where('category', $expenseCategory->name)
                    ->join('users', 'expenses.user_id', '=', 'users.id')
                    ->where('users.role', $expenseCategory->role)
                    ->update(['expenses.category' => $validated['category']]);
            }
        }

        $oldName = $expenseCategory->name;
        $oldRole = $expenseCategory->role;
        
        $expenseCategory->update([
            'name' => $validated['category'],
            'role' => $validated['role'],
        ]);

        // Log the update
        $userName = Auth::user() ? Auth::user()->name : 'System';
        $roleDisplay = $validated['role'] === 'staff-production' ? 'Production Staff' : 'Marketing Staff';
        AuditLog::createWithRequest([
            'user_id' => Auth::id(),
            'action' => 'expense_category_updated',
            'log_entry' => "`{$userName}` updated expense category from `{$oldName}` to `{$expenseCategory->name}` for {$roleDisplay}.",
        ], $request);

        return back()->with('success', 'Category updated successfully.');
    }

    public function destroy(Request $request, ExpenseCategory $expenseCategory)
    {
        // Check if category is in use
        $inUse = Expense::where('category', $expenseCategory->name)
            ->join('users', 'expenses.user_id', '=', 'users.id')
            ->where('users.role', $expenseCategory->role)
            ->exists();
        
        if ($inUse) {
            return back()->withErrors(['category' => 'Cannot delete category that is in use.']);
        }

        $categoryName = $expenseCategory->name;
        $categoryRole = $expenseCategory->role;
        $expenseCategory->delete();

        // Log the deletion
        $userName = Auth::user() ? Auth::user()->name : 'System';
        $roleDisplay = $categoryRole === 'staff-production' ? 'Production Staff' : 'Marketing Staff';
        AuditLog::createWithRequest([
            'user_id' => Auth::id(),
            'action' => 'expense_category_deleted',
            'log_entry' => "`{$userName}` deleted the expense category: `{$categoryName}` for {$roleDisplay}.",
        ], $request);

        return back()->with('success', 'Category deleted successfully.');
    }
}

