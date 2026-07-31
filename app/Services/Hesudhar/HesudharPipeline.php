<?php

namespace App\Services\Hesudhar;

/**
 * Master pipeline for the Hesudhar Sindhi text normalization engine.
 * Ported from Hesudhar Python Reference Implementation.
 */
class HesudharPipeline
{
    private Phase1GlobalNormalization $phase1;
    private Phase2HehDisambiguator $phase2;
    private Phase3SecondaryNormalization $phase3;
    private ArabicCitationDetector $citationDetector;
    private ?\Closure $wordnetLookup = null;

    public function __construct(?callable $wordnetLookup = null)
    {
        $this->phase1 = new Phase1GlobalNormalization();
        $this->phase2 = new Phase2HehDisambiguator();
        $this->phase3 = new Phase3SecondaryNormalization();
        $this->citationDetector = new ArabicCitationDetector();
        // Always prefer Hesudhar dictionary unless a custom lookup is injected.
        $this->wordnetLookup = \Closure::fromCallable(
            $wordnetLookup ?? HesudharDictionary::callback()
        );
    }

    /**
     * Full pipeline execution.
     *
     * @param  bool  $dictionaryOnly  When true, only apply Hesudhar WordNet matches.
     *                                Misses keep the original surface (airab/endings intact).
     */
    public function process(string $text, bool $dictionaryOnly = false): HesudharResult
    {
        $result = new HesudharResult($text);

        // -- PHASE 1: Global pre-normalization --
        // Skip for dictionary-only poetry refine — Phase1 can still mutate letters.
        if (!$dictionaryOnly) {
            $text = $this->phase1->run($text);
        }

        // -- Tokenize into words --
        $tokens = $this->tokenize($text);
        $correctedTokens = [];

        foreach ($tokens as $token) {
            $originalToken = $token;

            // Skip non-Sindhi tokens (punctuation, numbers, Latin)
            if (!$this->isSindhiWord($token)) {
                $correctedTokens[] = $token;
                continue;
            }

            // Split trailing case-ending hamza (ءِ / ءَ / ءُ) so lookup/algorithm
            // never drops it; reattach after correction.
            [$stem, $hamzaEnding] = $this->splitFinalHamzaEnding($token);

            // Preserve damma (ُ) on the stem — vocalized forms are left untouched.
            // A lone ُ on final ءُ does not trigger this bypass.
            if ($this->hasDamma($stem)) {
                $correctedTokens[] = $token;
                $result->logSkipped($token, 'DAMMA_PRESERVE');
                continue;
            }

            // -- PRIORITY 1: Hesudhar dictionary (WordNet) --
            // Wrong→correct mappings always win over phonetic algorithm.
            if ($this->wordnetLookup) {
                $lookup = ($this->wordnetLookup)($token);
                if ($lookup === null && $hamzaEnding !== '') {
                    $stemLookup = ($this->wordnetLookup)($stem);
                    if ($stemLookup !== null) {
                        $lookup = $this->ensureHamzaEnding($stemLookup, $hamzaEnding);
                    }
                }
                if ($lookup !== null) {
                    $lookup = $this->ensureHamzaEnding($lookup, $hamzaEnding);
                    if ($lookup !== $originalToken) {
                        $result->logChange($originalToken, $lookup, 'WORDNET');
                    }
                    $correctedTokens[] = $lookup;
                    continue;
                }
            }

            // Poetry refine: search-only — do not rewrite unmatched tokens.
            if ($dictionaryOnly) {
                $correctedTokens[] = $originalToken;
                continue;
            }

            // -- PHASE 4: Arabic citation bypass --
            if ($this->citationDetector->isArabicCitation($token)) {
                $correctedTokens[] = $token;
                $result->logSkipped($token, 'ARABIC_CITATION');
                continue;
            }

            // -- PHASE 2: Heh disambiguation --
            $token = $this->phase2->processWord($token);

            // -- PHASE 3: Secondary normalization --
            $token = $this->phase3->run($token);

            // Guarantee final ءِ / ءَ / ءُ survived processing.
            $token = $this->ensureHamzaEnding($token, $hamzaEnding);

            // -- Flag for review if changed by algorithm --
            if ($token !== $originalToken) {
                $result->logChange($originalToken, $token, 'ALGORITHM');

                // Flagging logic can be handled by the caller examining the result
                $result->flaggedForReview[] = [
                    'original' => $originalToken,
                    'algorithm_correction' => $token,
                    'confidence' => $this->calculateConfidence($originalToken, $token)
                ];
            }

            $correctedTokens[] = $token;
        }

        $result->correctedText = implode('', $correctedTokens);
        return $result;
    }

    private function tokenize(string $text): array
    {
        /**
         * Split text into tokens while preserving separators.
         * Pattern: capture everything that's NOT a separator OR capture the separators themselves.
         */
        $pattern = '/([^\s\x{06D4}\x{060C}\x{061F}!.,;:()\[\]"\'"]+|[\s\x{06D4}\x{060C}\x{061F}!.,;:()\[\]"\'"]+)/u';
        return preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [$text];
    }

    private function isSindhiWord(string $token): bool
    {
        return (bool) preg_match('/[\x{0600}-\x{06FF}]/u', $token);
    }

    private function hasDamma(string $token): bool
    {
        return str_contains($token, SindhiUnicode::DAMMA);
    }

    /**
     * @return array{0: string, 1: string} [stem, ending] where ending is ء / ءِ / ءَ / ءُ or ''
     */
    private function splitFinalHamzaEnding(string $token): array
    {
        if (preg_match('/^(.*)(\x{0621}[\x{064E}\x{064F}\x{0650}]?)$/u', $token, $matches)) {
            return [$matches[1], $matches[2]];
        }

        return [$token, ''];
    }

    /**
     * Reattach a preserved final hamza ending if the corrected form lost it.
     */
    private function ensureHamzaEnding(string $token, string $hamzaEnding): string
    {
        if ($hamzaEnding === '') {
            return $token;
        }

        if (str_ends_with($token, $hamzaEnding)) {
            return $token;
        }

        // Correct form may already end with bare ء — upgrade to ءِ / ءَ if needed.
        if (str_ends_with($token, SindhiUnicode::HAMZA) && mb_strlen($hamzaEnding) > 1) {
            return mb_substr($token, 0, mb_strlen($token) - 1) . $hamzaEnding;
        }

        return $token . $hamzaEnding;
    }

    private function calculateConfidence(string $original, string $corrected): string
    {
        $originalLen = mb_strlen($original);
        $correctedLen = mb_strlen($corrected);
        $diffCount = 0;

        $minLen = min($originalLen, $correctedLen);
        for ($i = 0; $i < $minLen; $i++) {
            if (mb_substr($original, $i, 1) !== mb_substr($corrected, $i, 1)) {
                $diffCount++;
            }
        }
        $diffCount += abs($originalLen - $correctedLen);

        if ($diffCount === 1) {
            return 'HIGH';
        } elseif ($diffCount <= 3) {
            return 'MEDIUM';
        }
        return 'LOW';
    }
}
