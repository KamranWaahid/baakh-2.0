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


        return url('/');
    }
}
