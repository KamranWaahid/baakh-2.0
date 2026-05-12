<?php

namespace App\Support;

class DictionaryText
{
    /**
     * Strip Arabic/Sindhi combining marks used for airab/diacritics.
     */
    public static function stripDiacritics(string $text): string
    {
        return preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u', '', $text) ?? $text;
    }

    public static function normalizeForLookup(string $text): string
    {
        $text = trim(self::stripDiacritics($text));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return function_exists('mb_strtolower') ? mb_strtolower($text) : strtolower($text);
    }
}
