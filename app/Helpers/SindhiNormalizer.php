<?php

namespace App\Helpers;

use App\Services\Hesudhar\HesudharDictionary;

class SindhiNormalizer
{
    /**
     * Standardizes Sindhi text using Hesudhar dictionary first, then phonetic rules.
     */
    public static function normalize($text)
    {
        if (empty($text)) {
            return $text;
        }

        return HesudharDictionary::pipeline()->process($text)->correctedText;
    }

    public static function normalizeWord($word)
    {
        if (empty($word)) {
            return $word;
        }

        return HesudharDictionary::pipeline()->process($word)->correctedText;
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
