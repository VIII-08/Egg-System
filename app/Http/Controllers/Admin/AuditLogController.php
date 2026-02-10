<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AuditLog;
use App\Models\User;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user')->latest();

        // Apply filters
        if ($request->filled('user_id') && $request->user_id !== null && $request->user_id !== 'all' && $request->user_id !== '' && is_numeric($request->user_id)) {
            $query->where('user_id', (int) $request->user_id);
        }
        if ($request->filled('action_type')) {
            $query->where('action', $request->action_type);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('log_entry', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $logs = $query->paginate(50)->withQueryString();
        $users = User::select('id', 'name', 'email', 'role')->orderBy('name')->get();
        
        // Get actions based on selected user's role
        $selectedUserId = $request->filled('user_id') ? (int) $request->user_id : null;
        $actions = $this->getActionsForUser($selectedUserId);

        return inertia('Admin/AuditLogs', [
            'logs' => $logs,
            'users' => $users,
            'actions' => $actions,
            'filters' => $request->only(['user_id', 'action_type', 'date', 'date_from', 'date_to', 'search']),
        ]);
    }

    /**
     * Get available actions based on user role
     */
    private function getActionsForUser(?int $userId): array
    {
        // If no user selected, return all actions
        if (!$userId) {
            return AuditLog::select('action')->distinct()->orderBy('action')->pluck('action')->toArray();
        }

        $user = User::find($userId);
        if (!$user) {
            return AuditLog::select('action')->distinct()->orderBy('action')->pluck('action')->toArray();
        }

        // Map actions to roles
        $roleActions = [
            'admin' => [
                'user_login', 'user_logout',
                'user_created', 'user_updated', 'user_deleted',
                'egg_product_created', 'egg_product_updated', 'egg_product_deleted',
                'expense_category_created', 'expense_category_updated', 'expense_category_deleted',
                'correction_request_created', 'correction_request_approved', 'correction_request_rejected',
                'financial_report_submitted', 'financial_report_approved', 'financial_report_rejected',
                'report_downloaded', 'financial_report_downloaded',
                // Admin can see all actions, so include everything
                'sale_created', 'sale_updated', 'sale_deleted',
                'expense_created', 'expense_updated', 'expense_deleted',
                'production_logged', 'production_log_updated', 'production_log_deleted',
                'chicken_stock_adjusted', 'chicken_stock_log_updated', 'chicken_stock_log_deleted',
            ],
            'staff-production' => [
                'user_login', 'user_logout',
                'production_logged', 'production_log_updated', 'production_log_deleted',
                'chicken_stock_adjusted', 'chicken_stock_log_updated', 'chicken_stock_log_deleted',
                'expense_created', 'expense_updated', 'expense_deleted',
                'correction_request_created',
            ],
            'staff-marketing' => [
                'user_login', 'user_logout',
                'sale_created', 'sale_updated', 'sale_deleted',
                'expense_created', 'expense_updated', 'expense_deleted',
                'correction_request_created',
            ],
            'treasurer' => [
                'user_login', 'user_logout',
                'expense_created', 'expense_updated', 'expense_deleted',
                'egg_product_created', 'egg_product_updated',
                'financial_report_submitted',
                'correction_request_created',
            ],
        ];

        $allowedActions = $roleActions[$user->role] ?? [];
        
        // Get only actions that exist in the database and match the role
        $existingActions = AuditLog::select('action')
            ->distinct()
            ->whereIn('action', $allowedActions)
            ->orderBy('action')
            ->pluck('action')
            ->toArray();

        return $existingActions;
    }
}