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
        // Added '۔' (U+06D4 Arabic Full Stop) to separators
        $tokens = preg_split('/(\s+|[،؛.”“؟!?,.()\[\]{}۔])+/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
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

        // Exception for Allah (standard Arabic or Sindhi shape)
        if (in_array($word, ['الله', 'اللہ'])) {
            return $word;
        }

        // Phase 1: Global Script Normalization
        $word = str_replace('ك', 'ڪ', $word); // Standard Arabic Kaf -> Swash Kaf

        // Phase 2: Advanced Semantic Cleanup & Phonetic Inference

        // 1. Collapse Legacy Trigraphs (Tail Hacks) like گروھہ -> گروھ, تباھہ -> تباہ
        // This effectively collapses any two terminal "Heh" variants (like ڳالهه uses ه+ه, تباھہ uses ھ+ہ) into pure aspiration ھ.
        $word = preg_replace('/[هہةەھ]{2}$/u', 'ھ', $word);

        // Arabic Citation Check
        // If the word starts with 'ال', it's likely an Arabic loanword quotation, bypass aspiration rules.
        $isArabicCitation = mb_strpos($word, 'ال') === 0;

        // 2. Aspiration Trigger (Safe Consonants)
        // Sindhi uses U+06BE specifically as an aspiration marker after specific consonants.
        // We strictly exclude N, M, R, W, and Dd from the automatic list because they are too frequently followed
        // by a standard pronounced /h/ (e.g. انهن, مهم, رهيو, جيڪڏهن), preventing false aspiration.
        $aspirationConsonants = 'ڻگلجڙ';

        if (!$isArabicCitation) {
            // 2. Aspiration Trigger (Safe Consonants)
            // Sindhi uses U+06BE specifically as an aspiration marker after specific consonants.
            $word = preg_replace('/([' . $aspirationConsonants . '])[هہةە]/u', '$1ھ', $word);

            // 3. Word-Final Weak Heh (Silent/Waning)
            // Absolute end of word NOT preceded by aspiration (which are now ھ anyway).
            $word = preg_replace('/(?<![' . $aspirationConsonants . '])[هةەھ]$/u', 'ہ', $word);

            // 4. General Pronounced Sounds (Initial & Medial Onsets)
            // Syllable onsets default strictly to standard Arabic Heh (ه U+0647).
            // This corrects visual hacks: ھڪ -> هڪ, اھم -> اهم, آھي -> آهي
            $word = preg_replace('/(?<![' . $aspirationConsonants . '])[ہةەھ](?!$)/u', 'ه', $word);
        } else {
            // Bypass aspiration for Arabic citations, but enforce word-final weak heh and default medial/initial
            // Ex: الجزيره -> الجزيرہ
            $word = preg_replace('/[هةەھ]$/u', 'ہ', $word);
            $word = preg_replace('/[ہةەھ](?!$)/u', 'ه', $word);
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
