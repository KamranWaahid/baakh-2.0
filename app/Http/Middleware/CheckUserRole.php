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
            Log::info("CheckUserRole: User {$user->id} ({$user->email}) has role column: '{$user->role}'");

            // Check if the user has the role 'user'
            if ($user->role === 'user') {
                Log::warning("CheckUserRole: Redirecting user {$user->id} to profile due to 'user' role.");

                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Unauthorized: Admin access required.'], 403);
                }

                return redirect('/user/profile');
            }
        }
        return $next($request);
    }
}
