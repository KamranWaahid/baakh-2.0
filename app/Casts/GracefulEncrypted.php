<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class GracefulEncrypted implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_string($value)) {
            return null;
        }

        try {
            $decrypted = Crypt::decryptString($value);

            return is_string($decrypted) ? $decrypted : null;
        } catch (DecryptException) {
            if ($this->looksLikePlaintext($key, $value)) {
                return $value;
            }

            return null;
        }
    }

    public function set($model, string $key, $value, array $attributes): array
    {
        if ($value === null || $value === '') {
            return [$key => null];
        }

        return [$key => Crypt::encryptString((string) $value)];
    }

    private function looksLikePlaintext(string $key, string $value): bool
    {
        if (Str::startsWith($value, 'eyJpdiI6')) {
            return false;
        }

        if ($key === 'email') {
            return (bool) filter_var($value, FILTER_VALIDATE_EMAIL);
        }

        return trim($value) !== '';
    }
}
