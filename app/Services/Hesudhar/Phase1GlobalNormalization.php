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
        // Repair literal PHP/PCRE escapes accidentally saved as text (e.g. \x{06BE} → ھ)
        $text = $this->repairLiteralUnicodeEscapes($text);
        $text = $this->nfcNormalize($text);
        $text = $this->collapseAlefMadda($text);
        // Collapse legacy ھہ/ھه tails, but never across a final hamza case ending (ءِ / ءَ).
        $text = $this->collapseTrigraphHacks($text);
        $text = $this->normalizeYeh($text);
        $text = $this->normalizeHehGoalHamza($text);
        return $text;
    }

    /**
     * Fix corrupted text where Unicode escapes were written as literal characters.
     * Example: "سا\x{06BE} سان" → "ساھ سان"
     */
    private function repairLiteralUnicodeEscapes(string $text): string
    {
        return preg_replace_callback(
            '/\\\\x\{([0-9A-Fa-f]{1,6})\}/u',
            static function (array $matches): string {
                $codepoint = hexdec($matches[1]);
                if ($codepoint <= 0 || $codepoint > 0x10FFFF) {
                    return $matches[0];
                }

                return mb_chr($codepoint, 'UTF-8') ?: $matches[0];
            },
            $text
        ) ?? $text;
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
         * Pattern: U+06BE (ھ) followed by U+06C1 (ہ), U+06D5 (ە), or U+0647 (ه) at word boundary.
         * Do not collapse when a case-ending hamza (ء / ءِ / ءَ) follows — that ending must stay.
         * Replacement must be a real UTF-8 character — never "\x{06BE}" (that is PCRE syntax, not a PHP string).
         */
        $pattern = '/\x{06BE}[\x{06C1}\x{06D5}\x{0647}](?=[\s\x{06D4}\x{060C}\x{061F}!.,;:()\[\]"\'"]|$)/u';
        return preg_replace($pattern, SindhiUnicode::HEH_DOACHASHMEE, $text) ?? $text;
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
