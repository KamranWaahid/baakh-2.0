<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthenticated
{
   
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            return $next($request);
        }

        // User is not authenticated, check the current route
        if ($request->is('admin*')) {
            // If the route is in the 'admin' namespace, redirect to the login page
            return redirect('admin/login');
        }

        // For non-admin routes, continue to the route
        return $next($request);
        
    }
}
