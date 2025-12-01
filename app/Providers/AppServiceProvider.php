<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RedirectResponse::macro('intended', function ($default) {
            // ---- THIS IS THE LINE TO CHANGE ----
            // Before: $user = auth()->user();
            // After:
            $user = Request::user();
    
            if ($user && $user->role === 'admin') {
                return new RedirectResponse(route('admin.dashboard'));
            }
            if ($user && $user->role === 'staff-production') {
                return new RedirectResponse(route('production.dashboard'));
            }
            
            // This handles cases where user is not logged in or has no special role
            return new RedirectResponse($default);
        });
    }
}
