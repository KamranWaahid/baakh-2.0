<?php

namespace App\Services\Hesudhar;

/**
 * Detect Arabic religious/literary quotations embedded in Sindhi text.
 * Ported from Hesudhar Python Reference Implementation.
 */
class ArabicCitationDetector
{
    public const ARABIC_CITATION_MARKERS = [
        'الله',
        'اللہ',
        'بسم',
        'قرآن',
        'سبحان',
        'الرحمن',
        'الرحيم',
        'انا',
        'انّا',
    ];

    public const ARABIC_HARAKAT = [
        "\u{064B}",
        "\u{064C}",
        "\u{064D}",
        "\u{064E}",
        "\u{064F}",
        "\u{0650}",
        "\u{0651}",
        "\u{0652}"
    ];

    public function isArabicCitation(string $word): bool
    {
        // Check for known Arabic markers
        foreach (self::ARABIC_CITATION_MARKERS as $marker) {
            if (str_contains($word, $marker)) {
                return true;
            }
        }

        // Check for Arabic diacritics (Harakat) — very rare in Sindhi writing
        $harakatCount = 0;
        $chars = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($chars as $ch) {
            if (in_array($ch, self::ARABIC_HARAKAT)) {
                $harakatCount++;
            }
        }

        if ($harakatCount >= 2) {
            return true;
        }

        // Check for Alif-Lam (definite article)
        if (str_starts_with($word, SindhiUnicode::ARABIC_DEFINITE_ARTICLE)) {
            return true;
        }

        return false;
    }
}
