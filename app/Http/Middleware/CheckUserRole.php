<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Check if user has permission to view the dashboard (min requirement for admin panel)
            // This replaces the fragile '$user->role === 'user'' check.
            if ($user->can('view_dashboard')) {
                return $next($request);
            }
        }

        // Return JSON 403 if unauthorized or not logged in (double check)
        return response()->json(['message' => 'Unauthorized: Admin access required.'], 403);
    }
}
