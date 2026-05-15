<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\SafeUserData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MobileGoogleController extends Controller
{
    public function ui(Request $request)
    {
        return response()->json([
            'app' => [
                'name' => config('app.name'),
                'url' => config('app.url'),
            ],
            'auth' => [
                'type' => 'bearer_token',
                'provider' => 'sanctum',
                'me_endpoint' => url('/api/auth/me'),
            ],
            'google' => [
                'available' => count($this->allowedGoogleClientIds()) > 0,
                'mobile_endpoint' => url('/api/auth/google/mobile'),
                'web_login_url' => url('/login/with-google'),
                'method' => 'POST',
                'required' => ['id_token'],
                'optional' => ['access_token'],
            ],
        ]);
    }

    public function help()
    {
        return response()->json([
            'message' => 'POST a Google id_token to authenticate with the mobile API.',
            'endpoint' => url('/api/auth/google/mobile'),
            'method' => 'POST',
            'accepted_fields' => ['id_token', 'access_token'],
            'returns' => ['token', 'token_type', 'user', 'new_user'],
        ]);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'id_token' => ['required_without:access_token', 'nullable', 'string'],
            'access_token' => ['required_without:id_token', 'nullable', 'string'],
        ]);

        if (count($this->allowedGoogleClientIds()) === 0) {
            return response()->json([
                'message' => 'Google mobile auth is not configured.',
                'error' => 'missing_google_client_ids',
                'required_env' => [
                    'GOOGLE_CLIENT_ID',
                    'GOOGLE_IOS_CLIENT_ID',
                    'GOOGLE_ANDROID_CLIENT_ID',
                    'GOOGLE_EXPO_CLIENT_ID',
                    'GOOGLE_MOBILE_CLIENT_IDS',
                ],
            ], 422);
        }

        $googleUser = isset($validated['id_token'])
            ? $this->verifyIdToken($validated['id_token'])
            : $this->verifyAccessToken($validated['access_token']);

        if (!$googleUser['ok']) {
            return response()->json([
                'message' => $googleUser['message'],
                'error' => $googleUser['error'],
            ], 422);
        }

        [$user, $isNewUser] = $this->findOrCreateUser($googleUser['profile']);
        $user->updateLastLogin();

        $token = $user->createToken('mobile_auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'token_type' => 'Bearer',
            'new_user' => $isNewUser,
            'user' => $this->userPayload($user),
        ]);
    }

    private function verifyIdToken(string $idToken): array
    {
        $response = Http::acceptJson()
            ->timeout(5)
            ->get('https://oauth2.googleapis.com/tokeninfo', [
                'id_token' => $idToken,
            ]);

        if (!$response->ok()) {
            return $this->verificationError('Google ID token could not be verified.', 'invalid_google_id_token');
        }

        $payload = $response->json();
        $audience = (string) ($payload['aud'] ?? '');

        if (!in_array($audience, $this->allowedGoogleClientIds(), true)) {
            return $this->verificationError('Google ID token audience is not allowed.', 'invalid_google_audience');
        }

        return $this->profileFromGooglePayload($payload);
    }

    private function verifyAccessToken(string $accessToken): array
    {
        $response = Http::acceptJson()
            ->withToken($accessToken)
            ->timeout(5)
            ->get('https://www.googleapis.com/oauth2/v3/userinfo');

        if (!$response->ok()) {
            return $this->verificationError('Google access token could not be verified.', 'invalid_google_access_token');
        }

        return $this->profileFromGooglePayload($response->json());
    }

    private function profileFromGooglePayload(array $payload): array
    {
        $googleId = (string) ($payload['sub'] ?? '');
        $email = (string) ($payload['email'] ?? '');
        $emailVerified = filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($googleId === '' || $email === '' || !$emailVerified) {
            return $this->verificationError('Google account must include a verified email.', 'google_email_not_verified');
        }

        return [
            'ok' => true,
            'profile' => [
                'id' => $googleId,
                'email' => $email,
                'name' => (string) ($payload['name'] ?? 'Anonymous User'),
                'avatar' => (string) ($payload['picture'] ?? ''),
            ],
        ];
    }

    private function findOrCreateUser(array $googleProfile): array
    {
        $isNewUser = false;
        $user = User::withTrashed()->where('google_id', $googleProfile['id'])->first();

        if (!$user) {
            $emailHash = hash('sha256', strtolower($googleProfile['email']));
            $user = User::withTrashed()->where('email_hash', $emailHash)->first();
        }

        if ($user) {
            if ($user->trashed()) {
                $user->restore();
            }
        } else {
            $user = new User();
            $user->fill([
                'email' => $googleProfile['email'],
                'name' => $googleProfile['name'] ?? 'Anonymous User',
                'password' => bcrypt(Str::random(32)),
                'status' => 'active',
                'role' => 'user',
                'username' => 'User-' . strtoupper(Str::random(5)),
            ]);
            $isNewUser = true;
        }

        $user->google_id = $googleProfile['id'];

        if ($googleProfile['avatar'] !== '' && empty($user->avatar)) {
            $user->avatar = $googleProfile['avatar'];
        }

        $user->save();

        if ($isNewUser) {
            try {
                $user->assignRole('viewer');
            } catch (\Throwable $e) {
                Log::warning("Failed to assign 'viewer' role to mobile Google user", [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [$user, $isNewUser];
    }

    private function userPayload(User $user): array
    {
        return SafeUserData::basic($user, '/api/auth/google/mobile');
    }

    private function allowedGoogleClientIds(): array
    {
        return array_values(array_filter(config('services.google.mobile_client_ids', [])));
    }

    private function verificationError(string $message, string $error): array
    {
        return [
            'ok' => false,
            'message' => $message,
            'error' => $error,
        ];
    }
}
