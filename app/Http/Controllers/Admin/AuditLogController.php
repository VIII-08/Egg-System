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
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('action_type')) {
            $query->where('action', $request->action_type);
        }
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $logs = $query->paginate(20)->withQueryString();
        $users = User::orderBy('name')->get();
        $actions = AuditLog::select('action')->distinct()->pluck('action'); // Get all unique actions

        return inertia('Admin/AuditLogs', [
            'logs' => $logs,
            'users' => $users,
            'actions' => $actions,
            'filters' => $request->only(['user_id', 'action_type', 'date']),
        ]);
    }
}