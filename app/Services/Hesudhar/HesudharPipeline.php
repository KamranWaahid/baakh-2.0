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

    public function __construct(callable $wordnetLookup = null)
    {
        $this->phase1 = new Phase1GlobalNormalization();
        $this->phase2 = new Phase2HehDisambiguator();
        $this->phase3 = new Phase3SecondaryNormalization();
        $this->citationDetector = new ArabicCitationDetector();
        $this->wordnetLookup = $wordnetLookup ? \Closure::fromCallable($wordnetLookup) : null;
    }

    /**
     * Full pipeline execution.
     */
    public function process(string $text): HesudharResult
    {
        $result = new HesudharResult($text);

        // -- PHASE 1: Global pre-normalization --
        $text = $this->phase1->run($text);

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

            // -- PHASE 0: WordNet lookup (Optional) --
            if ($this->wordnetLookup) {
                $lookup = ($this->wordnetLookup)($token);
                if ($lookup !== null) {
                    if ($lookup !== $token) {
                        $result->logChange($originalToken, $lookup, 'WORDNET');
                    }
                    $correctedTokens[] = $lookup;
                    continue;
                }
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
        return preg_match('/[\x{0600}-\x{06FF}]/u', $token);
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
