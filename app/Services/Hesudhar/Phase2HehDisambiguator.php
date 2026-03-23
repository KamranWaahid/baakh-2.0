<?php

namespace App\Services\Hesudhar;

/**
 * Core phonetic disambiguation engine.
 * Ported from Hesudhar Python Reference Implementation.
 */
class Phase2HehDisambiguator
{
    /**
     * Process a single Sindhi word token.
     */
    public function processWord(string $word): string
    {
        if (empty($word)) {
            return $word;
        }

        // Skip words that are purely non-Arabic script
        if (!$this->isArabicScript($word)) {
            return $word;
        }

        $chars = $this->mbStrSplit($word);
        $n = count($chars);

        for ($i = 0; $i < $n; $i++) {
            $char = $chars[$i];

            if (!in_array($char, SindhiUnicode::HEH_VARIANTS)) {
                continue;
            }

            // Determine position and context
            $isWordFinal = $this->isWordFinal($chars, $i, $n);
            $prevChar = $this->getPrevMeaningfulChar($chars, $i);
            $hasVowelBetween = $this->hasVowelBetween($chars, $i);

            // -- RULE 1: IMPLOSIVE RULE (Dr. Jokhio) --------------------------
            // Implosives CANNOT aspirate -> Heh after implosive = Malfoozi
            if (in_array($prevChar, SindhiUnicode::IMPLOSIVES)) {
                $chars[$i] = SindhiUnicode::HEH_ARABIC; // ه U+0647
                continue;
            }

            // -- RULE 2: ASPIRATION CHECK -------------------------------------
            // If a Heh follows an aspiration-triggering consonant AND
            // no vowel diacritic separates them. Covers both medial and final.
            if (
                in_array($prevChar, SindhiUnicode::ASPIRATION_TRIGGERS)
                && !$hasVowelBetween
            ) {
                $chars[$i] = SindhiUnicode::HEH_DOACHASHMEE; // ھ U+06BE
                continue;
            }

            // -- RULE 3: WORD-FINAL WEAK HEH ----------------------------------
            // At absolute end of word, not after aspirating consonant -> Mukhtafi
            if ($isWordFinal) {
                $chars[$i] = SindhiUnicode::HEH_GOAL; // ہ U+06C1
                continue;
            }

            // -- RULE 4: DEFAULT — MALFOOZI (Syllable Onset) ------------------
            $chars[$i] = SindhiUnicode::HEH_ARABIC; // ه U+0647
        }

        return implode('', $chars);
    }

    private function isArabicScript(string $word): bool
    {
        return preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}]/u', $word);
    }

    private function mbStrSplit(string $string): array
    {
        return preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY);
    }

    private function isWordFinal(array $chars, int $i, int $n): bool
    {
        for ($j = $i + 1; $j < $n; $j++) {
            if (!in_array($chars[$j], SindhiUnicode::VOWEL_DIACRITICS)) {
                return false;
            }
        }
        return true;
    }

    private function getPrevMeaningfulChar(array $chars, int $i): ?string
    {
        for ($j = $i - 1; $j >= 0; $j--) {
            if (!in_array($chars[$j], SindhiUnicode::VOWEL_DIACRITICS)) {
                return $chars[$j];
            }
        }
        return null;
    }

    private function hasVowelBetween(array $chars, int $i): bool
    {
        return $i > 0 && in_array($chars[$i - 1], SindhiUnicode::VOWEL_DIACRITICS);
    }
}
