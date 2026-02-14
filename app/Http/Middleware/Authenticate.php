<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // Always return JSON 401 for API routes
        if ($request->is('api/*') || $request->expectsJson()) {
            return null;
        }

        // If it's an admin route, don't redirect to frontend home
        // Especially critical during maintenance mode to avoid 503 loop
        if ($request->is('admin') || $request->is('admin/*')) {
            return null;
        }

        return url('/');
    }
}
