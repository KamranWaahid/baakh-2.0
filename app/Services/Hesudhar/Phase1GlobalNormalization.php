<?php

namespace App\Services\Hesudhar;

use Normalizer;

/**
 * Pre-process entire text before word-level analysis.
 * Ported from Hesudhar Python Reference Implementation.
 */
class Phase1GlobalNormalization
{
    public function run(string $text): string
    {
        $text = $this->nfcNormalize($text);
        $text = $this->collapseAlefMadda($text);
        $text = $this->collapseTrigraphHacks($text);
        $text = $this->normalizeYeh($text);
        $text = $this->normalizeHehGoalHamza($text);
        return $text;
    }

    private function nfcNormalize(string $text): string
    {
        // PHP's Normalizer requires the intl extension
        if (class_exists('Normalizer')) {
            return Normalizer::normalize($text, Normalizer::FORM_C);
        }
        return $text;
    }

    private function collapseAlefMadda(string $text): string
    {
        return str_replace(SindhiUnicode::ALEF_MADDA_SEQ, SindhiUnicode::ALEF_MADDA, $text);
    }

    private function collapseTrigraphHacks(string $text): string
    {
        /**
         * Pattern: U+06BE (ھ) followed by U+06C1 (ہ), U+06D5 (ە), or U+0647 (ه) at word boundary
         */
        $pattern = '/\x{06BE}[\x{06C1}\x{06D5}\x{0647}](?=[\s\x{06D4}\x{060C}\x{061F}!.,;:()\[\]"\'"]|$)/u';
        return preg_replace($pattern, "\x{06BE}", $text);
    }

    private function normalizeYeh(string $text): string
    {
        $text = str_replace(SindhiUnicode::YEH_FARSI, SindhiUnicode::YEH_ARABIC, $text);
        $text = str_replace(SindhiUnicode::YEH_ARABIC_MAX, SindhiUnicode::YEH_ARABIC, $text);
        return $text;
    }

    private function normalizeHehGoalHamza(string $text): string
    {
        return str_replace(SindhiUnicode::HEH_GOAL_HAMZA, SindhiUnicode::HEH_GOAL, $text);
    }
}
