<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        // Revoke the token that was used to authenticate the current request (if API token based)
        if ($request->user() && !$request->user()->currentAccessToken() instanceof \Laravel\Sanctum\TransientToken) {
            $request->user()->currentAccessToken()->delete();
        }

        // Properly logout the user from the web guard (if Session/Cookie based SPA)
        if (auth('web')->check()) {
            auth('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }
}
