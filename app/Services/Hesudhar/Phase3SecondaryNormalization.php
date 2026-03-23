<?php

namespace App\Services\Hesudhar;

/**
 * Additional character-level corrections beyond Heh.
 * Ported from Hesudhar Python Reference Implementation.
 */
class Phase3SecondaryNormalization
{
    /**
     * Common loanwords from Arabic where /h/ is pronounced (Malfoozi).
     * These would wrongly trigger aspiration rules without this whitelist.
     */
    public const ARABIC_LOANWORDS_WITH_PRONOUNCED_HEH = [
        'جهاز',
        'جهازن',
        'جهازون',   // ship
        'مهم',
        'مهمن',
        'مهمون',       // important / mission
        'تهران',
        'بغداد',              // city names
        'الله',
        'اللہ',               // Allah
    ];

    public function run(string $word): string
    {
        $word = $this->normalizeKaf($word);
        $word = $this->fixLoanwordHeh($word);
        return $word;
    }

    private function normalizeKaf(string $word): string
    {
        /**
         * Arabic ك (U+0643) -> Sindhi ڪ (U+06AA) for native unaspirated /k/.
         */
        return str_replace(SindhiUnicode::KAF_ARABIC, SindhiUnicode::KAF_SINDHI_SWASH, $word);
    }

    private function fixLoanwordHeh(string $word): string
    {
        $cleanWord = trim($word);
        if (in_array($cleanWord, self::ARABIC_LOANWORDS_WITH_PRONOUNCED_HEH)) {
            // Replace any aspirated Heh with Malfoozi in these specific words
            return str_replace(
                SindhiUnicode::HEH_DOACHASHMEE,
                SindhiUnicode::HEH_ARABIC,
                $word
            );
        }
        return $word;
    }
}
