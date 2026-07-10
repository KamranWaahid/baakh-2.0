<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SafeUserData
{
    /**
     * Read a user attribute without letting legacy encrypted/plaintext rows
     * break API serialization.
     */
    public static function attribute(?User $user, string $attribute, ?string $context = null): ?string
    {
        if (!$user) {
            return null;
        }

        try {
            $value = $user->{$attribute};
            return is_string($value) ? $value : null;
        } catch (\Throwable $e) {
            Log::warning('Failed reading user attribute for API response', [
                'user_id' => $user->id ?? null,
                'attribute' => $attribute,
                'context' => $context,
                'error' => $e->getMessage(),
            ]);
        }

        $raw = $user->getRawOriginal($attribute);
        if (!is_string($raw) || trim($raw) === '') {
            return null;
        }

        if ($attribute === 'email' && filter_var($raw, FILTER_VALIDATE_EMAIL)) {
            return $raw;
        }

        // Never return an encrypted blob when the app key cannot decrypt it.
        if (Str::startsWith($raw, 'eyJpdiI6')) {
            return null;
        }

        return $raw;
    }

    public static function basic(?User $user, ?string $context = null): ?array
    {
        if (!$user) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => self::attribute($user, 'name', $context),
            'name_sd' => self::attribute($user, 'name_sd', $context),
            'email' => self::attribute($user, 'email', $context),
            'username' => $user->username,
            'avatar' => $user->avatar,
            'status' => $user->status,
            'role' => $user->role,
            'google_id' => $user->google_id,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }

    public static function withRoles(?User $user, ?string $context = null): ?array
    {
        $payload = self::basic($user, $context);
        if (!$payload || !$user) {
            return $payload;
        }

        try {
            $roles = $user->getRoleNames();
            if ($roles->isEmpty() && $user->role) {
                $roles = collect([$user->role]);
            }
            $payload['roles'] = $roles;
        } catch (\Throwable $e) {
            Log::warning('Failed loading user roles for API response', [
                'user_id' => $user->id ?? null,
                'context' => $context,
                'error' => $e->getMessage(),
            ]);
            $payload['roles'] = collect([$user->role]);
        }

        return $payload;
    }
    public static function fullAuth(?User $user, ?string $context = null): ?array
    {
        $payload = self::withRoles($user, $context);
        if (!$payload || !$user) {
            return $payload;
        }

        try {
            $permissions = $user->getAllPermissions()->pluck('name');
            if ($permissions->isEmpty() && in_array(strtolower((string)$user->role), ['admin', 'admins', 'super_admin'])) {
                $permissions = collect(['view_dashboard', 'manage_users']);
            }
            $payload['permissions'] = $permissions;
        } catch (\Throwable $e) {
            Log::warning('Failed loading user permissions for API response', [
                'user_id' => $user->id ?? null,
                'context' => $context,
                'error' => $e->getMessage(),
            ]);
            $payload['permissions'] = collect();
            if (in_array(strtolower((string)$user->role), ['admin', 'admins', 'super_admin'])) {
                $payload['permissions'] = collect(['view_dashboard', 'manage_users']);
            }
        }

        try {
            $payload['teams'] = $user->teams;
        } catch (\Throwable $e) {
            Log::warning('Failed loading user teams for API response', [
                'user_id' => $user->id ?? null,
                'context' => $context,
                'error' => $e->getMessage(),
            ]);
            $payload['teams'] = [];
        }

        try {
            $payload['owned_teams'] = $user->ownedTeams;
        } catch (\Throwable $e) {
            Log::warning('Failed loading owned teams for API response', [
                'user_id' => $user->id ?? null,
                'context' => $context,
                'error' => $e->getMessage(),
            ]);
            $payload['owned_teams'] = [];
        }

        return $payload;
    }
}
