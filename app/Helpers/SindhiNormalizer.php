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

        // Stage 1: The "Visual Hack" Cleanup - REMOVED because it was incorrectly collapsing tails
        // $text = preg_replace('/ھ[ہهە]($|\s|[،؛.”“؟!?,.])/u', 'ھ$1', $text);

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

        $aspirationConsonants = 'ڻگنملجڙڏور';
        $nonConnectors = 'اودذرڈڌڏڙء';

        // Rule A: The Aspiration Trigger
        // If Heh variant follows: ڻ گ ن م ل ج ڙ ڏ و ر -> force U+06BE (ھ)
        $word = preg_replace('/([' . $aspirationConsonants . '])[هہةھە]/u', '$1ھ', $word);

        // Rule B: Medial Normalization
        // Any other Heh variants (start or medial not following aspiration) -> U+0647 (ه)
        // Uses negative lookbehind for aspiration and negative lookahead for word-end
        $word = preg_replace('/(?<![' . $aspirationConsonants . '])[ہةھە](?!$)/u', 'ه', $word);

        // Rule C: Word-Final Weak Heh with Tail
        // If Heh variant is at the end, ensure it has the correct tail
        $length = mb_strlen($word);
        if ($length > 0) {
            $lastChar = mb_substr($word, -1);
            if (in_array($lastChar, ['ه', 'ہ', 'ة', 'ھ', 'ە'])) {
                $prevChar = $length > 1 ? mb_substr($word, -2, 1) : '';

                if ($lastChar === 'ھ') {
                    // Only add tail if it follows a non-connector (isolated form)
                    if ($prevChar !== '' && mb_strpos($nonConnectors, $prevChar) !== false) {
                        $word .= 'ہ';
                    }
                } elseif ($lastChar === 'ہ' && ($prevChar === 'ه' || $prevChar === 'ھ')) {
                    // Already has the tail, do nothing
                } else {
                    // Map to U+0647 (ه) and append U+06C1 (ہ) tail
                    $word = mb_substr($word, 0, -1) . 'هہ';
                }
            }
        }

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
