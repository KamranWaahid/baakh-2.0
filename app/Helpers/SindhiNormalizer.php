<?php

namespace App\Helpers;

class SindhiNormalizer
{
    /**
     * Standardizes Sindhi text using phonetic-contextual rules.
     */
    public static function normalize($text)
    {
        if (empty($text)) {
            return $text;
        }

        $pipeline = new \App\Services\Hesudhar\HesudharPipeline();
        return $pipeline->process($text)->correctedText;
    }

    public static function normalizeWord($word)
    {
        if (empty($word)) {
            return $word;
        }

        $pipeline = new \App\Services\Hesudhar\HesudharPipeline();
        return $pipeline->process($word)->correctedText;
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
