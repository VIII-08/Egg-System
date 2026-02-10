<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\AuditLog;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();
        
        // Update last login timestamp
        $user->last_login_at = now();
        $user->save();

        // Log login event
        AuditLog::createWithRequest([
            'user_id' => $user->id,
            'action' => 'user_login',
            'log_entry' => "`{$user->name}` logged into the system.",
        ], $request);

        return match ($user->role) {
            'admin' => redirect()->route('admin.dashboard'),
            'staff-production' => redirect()->route('production.dashboard'),
            'staff-marketing' => redirect()->route('marketing.dashboard'),
            'treasurer' => redirect()->route('treasurer.dashboard'),
            default => redirect()->route('dashboard'),
        };
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();
        
        // Log logout event before logging out
        if ($user) {
            AuditLog::createWithRequest([
                'user_id' => $user->id,
                'action' => 'user_logout',
                'log_entry' => "`{$user->name}` logged out of the system.",
            ], $request);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
