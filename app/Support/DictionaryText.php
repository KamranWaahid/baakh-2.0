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

    public static function hasDiacritics(string $text): bool
    {
        return (bool) preg_match('/[\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u', $text);
    }

    /**
     * Strip punctuation attached to clicked poetry tokens (e.g. تھمت، → تھمت).
     * Does NOT strip Arabic vowel diacritics (kasra/zabar/pesh).
     *
     * Avoid \p{S}: Unicode marks Sindhi letters ۾ (U+06FE) and ۽ (U+06FD) as
     * Symbol, which would delete them. Decorative Arabic signs are listed explicitly.
     */
    public static function stripPunctuation(string $text): string
    {
        $stripped = preg_replace(
            '/[\x{060C}\x{061B}\x{061F}\x{06D4}\x{0640}\x{0606}-\x{0608}\x{060B}\x{060E}\x{060F}\x{06DE}\x{06E9}\x{00AB}\x{00BB}\x{2039}\x{203A}\x{2018}-\x{201F}\p{P}]+/u',
            '',
            $text
        );

        return trim($stripped ?? $text);
    }

    /**
     * Map Arabic/Sindhi punctuation (and related quote marks) to Latin equivalents
     * for Roman output. Leaves letters and already-Latin punctuation unchanged.
     */
    public static function romanizePunctuation(string $text): string
    {
        return strtr($text, [
            '،' => ',',   // Arabic comma
            '؛' => ';',   // Arabic semicolon
            '؟' => '?',   // Arabic question mark
            '۔' => '.',   // Arabic full stop
            '٪' => '%',   // Arabic percent
            '٫' => '.',   // Arabic decimal separator
            '٬' => ',',   // Arabic thousands separator
            '«' => '"',
            '»' => '"',
            '‹' => "'",
            '›' => "'",
            '“' => '"',
            '”' => '"',
            '„' => '"',
            '‘' => "'",
            '’' => "'",
            '‚' => "'",
            'ـ' => '',    // tatweel / kashida — not used in Roman
        ]);
    }

    /**
     * Remove decorative guillemets / angle quotes used as word wrappers («آدمي» → آدمي).
     * Safe for definition prose (keeps ، ؟ . etc.).
     */
    public static function stripGuillemets(string $text): string
    {
        $stripped = preg_replace('/[\x{00AB}\x{00BB}\x{2039}\x{203A}]+/u', '', $text);

        return $stripped ?? $text;
    }

    /**
     * Canonical lemma identity key — keeps zer/zabar/pesh.
     * نَھن and نُھن stay distinct.
     */
    public static function normalizeForIdentity(string $text): string
    {
        $text = self::stripPunctuation($text);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $text = trim($text);

        return function_exists('mb_strtolower') ? mb_strtolower($text) : strtolower($text);
    }

    /**
     * Fuzzy / base key with airab removed — search hints & ambiguous fallback only.
     * Never use this as a unique lemma identity.
     */
    public static function lookupBase(string $text): string
    {
        $text = self::stripPunctuation($text);
        $text = trim(self::stripDiacritics($text));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return function_exists('mb_strtolower') ? mb_strtolower($text) : strtolower($text);
    }

    /**
     * @deprecated Prefer normalizeForIdentity() for uniqueness / resolve,
     *             or lookupBase() for fuzzy search.
     * Kept as alias of lookupBase for older call sites during migration.
     */
    public static function normalizeForLookup(string $text): string
    {
        return self::lookupBase($text);
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

    /**
     * SQL fragment for airab-safe equality (works even before bin collation migration).
     */
    public static function binaryEquals(string $column, string $binding = '?'): string
    {
        return "BINARY {$column} = {$binding}";
    }
}
