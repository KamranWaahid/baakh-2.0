<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $user = $request->user();

        // Load roles and permissions
        $user->load(['roles', 'permissions', 'teams']);

        // Get all permissions via roles as well
        $allPermissions = $user->getAllPermissions()->pluck('name');

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'avatar' => $user->avatar,
                'status' => $user->status,
                'roles' => $user->getRoleNames(),
                'permissions' => $allPermissions,
                'teams' => $user->teams,
                'owned_teams' => $user->ownedTeams,
            ]
        ]);
    }
}
