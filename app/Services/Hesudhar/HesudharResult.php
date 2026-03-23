<?php

namespace App\Services\Hesudhar;

/**
 * Container for pipeline output including diagnostics.
 * Ported from Hesudhar Python Reference Implementation.
 */
class HesudharResult
{
    public string $originalText;
    public string $correctedText;
    public array $changesLog = [];
    public array $skippedLog = [];
    public array $flaggedForReview = [];

    public function __construct(string $originalText)
    {
        $this->originalText = $originalText;
        $this->correctedText = $originalText;
    }

    public function logChange(string $original, string $corrected, string $source): void
    {
        $this->changesLog[] = [
            'original' => $original,
            'corrected' => $corrected,
            'source' => $source, // 'WORDNET' or 'ALGORITHM'
        ];
    }

    public function logSkipped(string $word, string $reason): void
    {
        $this->skippedLog[] = [
            'word' => $word,
            'reason' => $reason
        ];
    }

    public function getSummary(): string
    {
        $summary = "------------------------------------------------------------\n";
        $summary .= "HESUDHAR NORMALIZATION REPORT\n";
        $summary .= "------------------------------------------------------------\n";
        $summary .= "Total changes:        " . count($this->changesLog) . "\n";

        $wordnetCount = count(array_filter($this->changesLog, fn($c) => $c['source'] === 'WORDNET'));
        $algorithmCount = count(array_filter($this->changesLog, fn($c) => $c['source'] === 'ALGORITHM'));

        $summary .= "  → Via WordNet:      " . $wordnetCount . "\n";
        $summary .= "  → Via Algorithm:    " . $algorithmCount . "\n";
        $summary .= "Arabic citations:     " . count($this->skippedLog) . " (skipped)\n";
        $summary .= "Flagged for review:   " . count($this->flaggedForReview) . "\n";
        $summary .= "------------------------------------------------------------\n";

        if (!empty($this->changesLog)) {
            $summary .= "\nCHANGES MADE:\n";
            foreach ($this->changesLog as $c) {
                $summary .= "  [" . $c['source'] . "]  " . $c['original'] . "  →  " . $c['corrected'] . "\n";
            }
        }

        if (!empty($this->flaggedForReview)) {
            $summary .= "\nFLAGGED FOR WORDNET REVIEW:\n";
            foreach ($this->flaggedForReview as $f) {
                $summary .= "  [" . $f['confidence'] . "]  " . $f['original'] . "  →  " . $f['algorithm_correction'] . "\n";
            }
        }

        return $summary;
    }
}
