<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MeController extends Controller
{
    /**
     * Read a user attribute safely even if encrypted casts fail.
     */
    private function safeUserAttribute($user, string $attribute): ?string
    {
        try {
            $value = $user->{$attribute};
            return is_string($value) ? $value : null;
        } catch (\Throwable $e) {
            Log::warning('Failed reading encrypted user attribute in /api/auth/me', [
                'user_id' => $user->id ?? null,
                'attribute' => $attribute,
                'error' => $e->getMessage(),
            ]);
        }

        $raw = $user->getRawOriginal($attribute);
        if (!is_string($raw) || trim($raw) === '') {
            return null;
        }

        // Legacy rows may still contain plaintext values before encryption rollout.
        if ($attribute === 'email' && filter_var($raw, FILTER_VALIDATE_EMAIL)) {
            return $raw;
        }

        // Avoid leaking encrypted blobs in API responses.
        if (Str::startsWith($raw, 'eyJpdiI6')) {
            return null;
        }

        return $raw;
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        try {
            $user->loadMissing(['roles', 'permissions', 'teams']);
        } catch (\Throwable $e) {
            Log::warning('Failed loading user relations in /api/auth/me', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $allPermissions = $user->getAllPermissions()->pluck('name');
        } catch (\Throwable $e) {
            Log::warning('Failed loading user permissions in /api/auth/me', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            $allPermissions = collect();
        }

        try {
            $teams = $user->teams;
        } catch (\Throwable $e) {
            Log::warning('Failed loading user teams in /api/auth/me', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            $teams = [];
        }

        try {
            $ownedTeams = $user->ownedTeams;
        } catch (\Throwable $e) {
            Log::warning('Failed loading user owned teams in /api/auth/me', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            $ownedTeams = [];
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $this->safeUserAttribute($user, 'name'),
                'email' => $this->safeUserAttribute($user, 'email'),
                'username' => $user->username,
                'avatar' => $user->avatar,
                'status' => $user->status,
                'roles' => $user->getRoleNames(),
                'permissions' => $allPermissions,
                'teams' => $teams,
                'owned_teams' => $ownedTeams,
            ]
        ]);
    }
}
