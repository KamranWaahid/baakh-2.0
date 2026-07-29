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

            if (!in_array($char, SindhiUnicode::HEH_VARIANTS, true)) {
                continue;
            }

            // Determine position and context
            $isWordFinal = $this->isWordFinal($chars, $i, $n);
            $prevChar = $this->getPrevMeaningfulChar($chars, $i);
            $hasVowelBetween = $this->hasVowelBetween($chars, $i);

            // -- RULE 1: IMPLOSIVE RULE (Dr. Jokhio) --------------------------
            // Implosives CANNOT aspirate -> Heh after implosive = Malfoozi
            if (in_array($prevChar, SindhiUnicode::IMPLOSIVES, true)) {
                $chars[$i] = SindhiUnicode::HEH_ARABIC; // ه U+0647
                continue;
            }

            // -- RULE 2: ASPIRATION CHECK -------------------------------------
            // If a Heh follows an aspiration-triggering consonant AND
            // no vowel diacritic separates them. Never aspirate when the heh
            // is morphologically final (end of word, or before final ءِ / ءَ).
            if (
                !$isWordFinal
                && in_array($prevChar, SindhiUnicode::ASPIRATION_TRIGGERS, true)
                && !$hasVowelBetween
            ) {
                $chars[$i] = SindhiUnicode::HEH_DOACHASHMEE; // ھ U+06BE
                continue;
            }

            // -- RULE 3: WORD-FINAL WEAK HEH (incl. before ءِ / ءَ) -----------
            // Absolute end, or heh of the stem before a case-ending hamza.
            // Keep genuine aspiration (ھ) if already present; otherwise Mukhtafi.
            if ($isWordFinal) {
                if ($char !== SindhiUnicode::HEH_DOACHASHMEE) {
                    $chars[$i] = SindhiUnicode::HEH_GOAL; // ہ U+06C1
                }
                continue;
            }

            // -- RULE 4: DEFAULT — MALFOOZI (Syllable Onset) ------------------
            $chars[$i] = SindhiUnicode::HEH_ARABIC; // ه U+0647
        }

        return implode('', $chars);
    }

    private function isArabicScript(string $word): bool
    {
        return (bool) preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}]/u', $word);
    }

    private function mbStrSplit(string $string): array
    {
        return preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * Heh is "word-final" when only diacritics remain after it, OR when what
     * follows is a case-ending hamza ء with optional ِ / َ / ُ (ءِ، ءَ، ءُ).
     * Those endings must never be stripped and must not trigger aspiration.
     */
    private function isWordFinal(array $chars, int $i, int $n): bool
    {
        $j = $i + 1;

        while ($j < $n && in_array($chars[$j], SindhiUnicode::VOWEL_DIACRITICS, true)) {
            $j++;
        }

        if ($j < $n && $chars[$j] === SindhiUnicode::HAMZA) {
            $j++;
            while ($j < $n && in_array($chars[$j], SindhiUnicode::FINAL_HAMZA_DIACRITICS, true)) {
                $j++;
            }
        }

        return $j >= $n;
    }

    private function getPrevMeaningfulChar(array $chars, int $i): ?string
    {
        for ($j = $i - 1; $j >= 0; $j--) {
            if (!in_array($chars[$j], SindhiUnicode::VOWEL_DIACRITICS, true)) {
                return $chars[$j];
            }
        }
        return null;
    }

    private function hasVowelBetween(array $chars, int $i): bool
    {
        return $i > 0 && in_array($chars[$i - 1], SindhiUnicode::VOWEL_DIACRITICS, true);
    }
}
