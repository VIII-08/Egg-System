<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // $roles will now be an array, e.g., ['staff-production', 'staff-marketing']
        if (! in_array($request->user()->role, $roles)) {
            // If the user's role is not in the allowed list, block them.
            abort(403, 'UNAUTHORIZED ACTION.');
        }
    
        return $next($request);
    }
    
}
