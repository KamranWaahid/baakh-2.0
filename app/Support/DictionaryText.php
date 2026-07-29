<?php

namespace App\Support;

class DictionaryText
{
    public const KASRA = "\u{0650}"; // ِ — often izafat marker after a noun

    /**
     * Strip Arabic/Sindhi combining marks used for airab/diacritics.
     */
    public static function stripDiacritics(string $text): string
    {
        return preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u', '', $text) ?? $text;
    }

    /**
     * Strip punctuation attached to clicked poetry tokens (e.g. تھمت، → تھمت).
     * Includes Arabic comma/semicolon/question mark/full stop and Latin punct.
     * Does NOT strip Arabic vowel diacritics (kasra/zabar/pesh) — those stay on surface forms.
     */
    public static function stripPunctuation(string $text): string
    {
        // Arabic punctuation + tatweel + common Latin/unicode punctuation/symbols
        $stripped = preg_replace(
            '/[\x{060C}\x{061B}\x{061F}\x{06D4}\x{0640}\x{00AB}\x{00BB}\x{2018}-\x{201F}\p{P}\p{S}]+/u',
            '',
            $text
        );

        return trim($stripped ?? $text);
    }

    public static function normalizeForLookup(string $text): string
    {
        $text = self::stripPunctuation($text);
        $text = trim(self::stripDiacritics($text));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return function_exists('mb_strtolower') ? mb_strtolower($text) : strtolower($text);
    }

    /**
     * Expression search key: strip diacritics, collapse whitespace (keeps word boundaries).
     * جامِ محبت → جام محبت
     */
    public static function normalizeExpression(string $text): string
    {
        $text = preg_replace('/\s+/u', ' ', trim($text)) ?? trim($text);
        $text = self::stripDiacritics($text);
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);

        return function_exists('mb_strtolower') ? mb_strtolower($text) : strtolower($text);
    }

    /**
     * Compact key without spaces (secondary search only — not unique identity).
     * جام محبت → جاممحبت
     */
    public static function compactExpressionKey(string $text): string
    {
        return preg_replace('/\s+/u', '', self::normalizeExpression($text)) ?? '';
    }

    public static function hasTrailingKasra(string $token): bool
    {
        $token = self::stripPunctuation($token);

        return str_ends_with($token, self::KASRA);
    }
}
