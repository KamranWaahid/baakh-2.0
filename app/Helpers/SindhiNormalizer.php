<?php

namespace App\Helpers;

class SindhiNormalizer
{
    /**
     * Standardizes Sindhi text using phonetic-contextual rules.
     */
    public static function normalize($text)
    {
        if (empty($text))
            return $text;

        // Stage 1: Removed visual hack

        // Tokenize into words to apply contextual rules accurately
        $tokens = preg_split('/(\s+|[،؛.”“؟!?,.()\[\]{}])+/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $result = [];

        foreach ($tokens as $token) {
            if ($token === null || $token === '')
                continue;
            // Only process words, not separators
            if (preg_match('/[\x{0600}-\x{06FF}]/u', $token)) {
                $result[] = self::normalizeWord($token);
            } else {
                $result[] = $token;
            }
        }

        $text = implode('', $result);

        // Stage 3: Secondary Character Normalization
        // Normalize Yeh: U+06CC (Farsi Yeh) -> U+064A (Arabic Yeh)
        $text = str_replace('ی', 'ي', $text); // Farsi ی -> Arabic ي

        // Atomic Normalization
        $text = self::normalizeAtomic($text);

        return $text;
    }

    public static function normalizeWord($word)
    {
        if (empty($word))
            return $word;

        // Kaf Normalization (Expert Rule - Early to prevent Heh rules from breaking digraphs)
        // Fixes common Urdu-keyboard errors: کھ -> Sindhi ک, standalone ک -> Sindhi ڪ
        $word = str_replace('کھ', 'TEMP_KH_INTERNAL', $word);
        $word = str_replace('ک', 'ڪ', $word);

        $aspirationConsonants = 'ڻگنملجڙڏور';

        // Rule A: The Aspiration Trigger
        // If Heh variant follows an aspirate-ready consonant, force it to Doachashmee ھ (U+06BE)
        $word = preg_replace('/([' . $aspirationConsonants . '])[هہةھە]/u', '$1ھ', $word);

        // Rule B: Medial Normalization
        // Any other Heh variants (start or medial not following aspiration) -> Standard ه (U+0647)
        $word = preg_replace('/(?<![' . $aspirationConsonants . '])[ہةھە](?!$)/u', 'ه', $word);

        // Rule C: Word-Final Weak Heh vs. Aspiration Digraph
        $length = mb_strlen($word);
        if ($length > 0) {
            $lastChar = mb_substr($word, -1);
            if (in_array($lastChar, ['ه', 'ہ', 'ة', 'ھ', 'ە'])) {
                $prevChar = $length > 1 ? mb_substr($word, -2, 1) : '';

                if (mb_strpos($aspirationConsonants, $prevChar) !== false) {
                    // Preceded by aspiration-ready consonant -> it's an Aspiration Digraph
                    // Map to ھ (U+06BE) and append ہ (U+06C1) for font support (Legacy Trigraph)
                    $word = mb_substr($word, 0, -1) . 'ھہ';
                } else {
                    // Otherwise it's a Weak Heh (Silent Heh) -> Map to ہ (U+06C1)
                    $word = mb_substr($word, 0, -1) . 'ہ';
                }
            }
        }

        // Restore Sindhi Kh
        $word = str_replace('TEMP_KH_INTERNAL', 'ک', $word);

        return $word;
    }

    private static function normalizeAtomic($text)
    {
        // Replace Alef + Madda with Alef with Madda Above
        $text = str_replace('ا' . 'ٓ', 'آ', $text);
        return $text;
    }

    /**
     * Removes diacritics (Zabar, Zer, Pesh, etc.) from Sindhi text.
     */
    public static function stripDiacritics($text)
    {
        // Remove Arabic diacritics: U+064B-U+0653 (tashkeel) and U+0670 (superscript alef)
        return preg_replace('/[\x{064B}-\x{0653}\x{0670}]/u', '', $text);
    }
}
