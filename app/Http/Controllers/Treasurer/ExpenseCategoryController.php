<?php

namespace App\Http\Controllers\Treasurer;

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

        return Inertia::render('Treasurer/ManageExpenseCategories', [
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

        $exists = ExpenseCategory::where('name', $validated['category'])
            ->where('role', $validated['role'])
            ->exists();
        
        if ($exists) {
            return back()->withErrors(['category' => 'This category already exists for this role.']);
        }

        $category = ExpenseCategory::create([
            'name' => $validated['category'],
            'role' => $validated['role'],
        ]);

        // Log the creation
        $userName = Auth::user() ? Auth::user()->name : 'System';
        $roleDisplay = $validated['role'] === 'staff-production' ? 'Production Staff' : 'Marketing Staff';
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'expense_category_created',
            'log_entry' => "`{$userName}` added a new expense category: `{$category->name}` for {$roleDisplay}.",
        ]);

        return back()->with('success', 'Category added successfully for ' . $validated['role'] . ' staff.');
    }

    public function destroy(Request $request, ExpenseCategory $expenseCategory)
    {
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
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'expense_category_deleted',
            'log_entry' => "`{$userName}` deleted the expense category: `{$categoryName}` for {$roleDisplay}.",
        ]);

        return back()->with('success', 'Category deleted successfully.');
    }
}

