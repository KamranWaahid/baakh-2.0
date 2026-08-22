<?php

namespace App\Support;

class JsonColumn
{
    /**
     * True when a JSON/text column cannot be decoded.
     * Eloquent array casts already return arrays for valid JSON — those are fine.
     */
    public static function isMalformed(mixed $raw): bool
    {
        if ($raw === null || $raw === '') {
            return false;
        }

        if (is_array($raw) || $raw instanceof \JsonSerializable) {
            return false;
        }

        if (!is_string($raw)) {
            return true;
        }

        json_decode($raw, true);

        return json_last_error() !== JSON_ERROR_NONE;
    }
}
