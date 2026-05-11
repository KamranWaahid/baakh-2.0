<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {

        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Use blind index (email_hash) for lookup because email is encrypted (non-deterministic)
        $credentials = [
            'email_hash' => hash('sha256', strtolower($request->email)),
            'password' => $request->password,
        ];

        if (!Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $user = Auth::user();


        if (!$user->isActive()) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'Your account is suspended or inactive.',
            ]);
        }

        $user->updateLastLogin();

        // Log activity
        ActivityLog::log('login', $user, null, 'User logged in');

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $this->userPayload($user),
            'token' => $token,
        ]);
    }

    /**
     * Build a login response without forcing all encrypted user casts to decrypt.
     */
    private function userPayload($user): array
    {
        try {
            $roles = $user->getRoleNames();
        } catch (\Throwable $e) {
            Log::warning('Failed loading user roles in /api/auth/login', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);
            $roles = collect();
        }

        return [
            'id' => $user->id,
            'name' => $this->safeUserAttribute($user, 'name'),
            'email' => $this->safeUserAttribute($user, 'email'),
            'username' => $user->username,
            'avatar' => $user->avatar,
            'status' => $user->status,
            'roles' => $roles,
        ];
    }

    /**
     * Read a user attribute safely even if encrypted casts fail.
     */
    private function safeUserAttribute($user, string $attribute): ?string
    {
        try {
            $value = $user->{$attribute};
            return is_string($value) ? $value : null;
        } catch (\Throwable $e) {
            Log::warning('Failed reading encrypted user attribute in /api/auth/login', [
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
}
