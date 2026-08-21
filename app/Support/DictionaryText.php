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
     * Zero-width / format controls that make ڪھي look identical to a different stored string.
     */
    public static function stripInvisible(string $text): string
    {
        return preg_replace('/[\x{200B}-\x{200F}\x{FEFF}\x{00AD}]/u', '', $text) ?? $text;
    }

    /**
     * Combining marks stripped for lookup_base / SQL REPLACE (zer, zabar, pesh, tanwin, …).
     *
     * @return list<string>
     */
    public static function diacriticMarks(): array
    {
        return ['ً', 'ٌ', 'ٍ', 'َ', 'ُ', 'ِ', 'ّ', 'ْ', 'ٰ', 'ٓ', 'ٔ', 'ٕ', 'ٖ', 'ٗ', '٘', 'ٙ', 'ٚ', 'ٛ', 'ٜ', 'ٝ', 'ٞ', 'ٟ'];
    }

    /**
     * SQL expression: lowercase lemma with airab removed. Used when lookup_base is empty.
     */
    public static function sqlLookupBase(string $column): string
    {
        $expression = "LOWER(COALESCE({$column}, ''))";
        foreach (array_merge(self::diacriticMarks(), ["\u{0640}", "\u{200B}", "\u{200C}", "\u{200D}", "\u{FEFF}"]) as $mark) {
            $escaped = str_replace("'", "''", $mark);
            $expression = "REPLACE({$expression}, '{$escaped}', '')";
        }

        return $expression;
    }

    /**
     * Canonical lemma identity key — keeps zer/zabar/pesh.
     * نَھن and نُھن stay distinct.
     */
    public static function normalizeForIdentity(string $text): string
    {
        $text = self::stripInvisible($text);
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
        $text = self::stripInvisible($text);
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
        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            return "{$column} = {$binding}";
        }

        return "BINARY {$column} = {$binding}";
    }
}
