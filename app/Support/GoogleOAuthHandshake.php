<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class GoogleOAuthHandshake
{
    public const TTL_SECONDS = 120;

    public static function put(string $token, bool $newUser = false): string
    {
        $key = bin2hex(random_bytes(32));

        Cache::put(self::cacheKey($key), [
            'token' => $token,
            'new_user' => $newUser,
        ], self::TTL_SECONDS);

        return $key;
    }

    public static function pull(string $key): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $key)) {
            return null;
        }

        $payload = Cache::pull(self::cacheKey($key));
        if (!is_array($payload) || empty($payload['token']) || !is_string($payload['token'])) {
            return null;
        }

        return [
            'token' => $payload['token'],
            'new_user' => (bool) ($payload['new_user'] ?? false),
        ];
    }

    private static function cacheKey(string $key): string
    {
        return 'google_oauth_handshake:'.$key;
    }
}
