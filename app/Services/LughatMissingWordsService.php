<?php

namespace App\Services;

use App\Models\LughatInflection;
use App\Models\LughatLemma;
use App\Models\LughatWordForm;
use App\Support\DictionaryText;

/**
 * Find Sindhi tokens in free text that are not yet in Baakh Lughat.
 */
class LughatMissingWordsService
{
    /**
     * @return list<string> unique surface lemmas (diacritics stripped) missing from Lughat
     */
    public function missingFromText(string $text): array
    {
        $tokens = $this->extractTokens($text);
        if ($tokens === []) {
            return [];
        }

        $normalized = array_values(array_unique(array_column($tokens, 'normalized')));
        $known = $this->knownNormalizedSet($normalized);

        $missing = [];
        $seen = [];
        foreach ($tokens as $token) {
            $n = $token['normalized'];
            if (isset($known[$n]) || isset($seen[$n])) {
                continue;
            }
            $seen[$n] = true;
            $missing[] = $token['lemma'];
        }

        sort($missing, SORT_STRING);

        return $missing;
    }

    /**
     * Create word-only lemma stubs for words not already present.
     *
     * @param  list<string>  $words
     * @return array{created: list<array>, skipped_existing: list<string>, failed: list<array>}
     */
    public function createStubs(array $words, array $metadata = []): array
    {
        $created = [];
        $skipped = [];
        $failed = [];

        foreach ($words as $raw) {
            $surface = trim((string) $raw);
            if ($surface === '') {
                continue;
            }

            $lemmaText = DictionaryText::stripDiacritics(DictionaryText::stripPunctuation($surface));
            $lemmaText = trim($lemmaText);
            $normalized = DictionaryText::normalizeForLookup($surface);

            if ($normalized === '' || $lemmaText === '') {
                $failed[] = ['word' => $surface, 'reason' => 'empty_after_normalize'];
                continue;
            }

            $exists = LughatLemma::query()
                ->where('normalized_lemma', $normalized)
                ->where('homograph_number', 1)
                ->where('language', 'sd')
                ->exists()
                || LughatWordForm::query()->where('normalized_form', $normalized)->whereNotNull('lemma_id')->exists()
                || LughatInflection::query()->where('normalized_form', $normalized)->exists();

            if ($exists) {
                $skipped[] = $lemmaText;
                continue;
            }

            $lemma = LughatLemma::create([
                'lemma' => $lemmaText,
                'normalized_lemma' => $normalized,
                'homograph_number' => 1,
                'language' => 'sd',
                'transliteration' => null,
                'romanization_status' => 'proposed',
                'status' => 'pending',
                'completion_status' => LughatLemma::COMPLETION_PENDING,
                'metadata_json' => array_merge([
                    'dictionary' => 'Baakh Lughat',
                    'version' => '2',
                    'source' => 'hesudhar_bulk_check',
                ], $metadata),
            ]);

            $created[] = [
                'id' => $lemma->id,
                'lemma' => $lemma->lemma,
                'normalized_lemma' => $lemma->normalized_lemma,
            ];
        }

        return [
            'created' => $created,
            'skipped_existing' => $skipped,
            'failed' => $failed,
        ];
    }

    /**
     * @param  list<string>  $normalized
     * @return array<string, true>
     */
    private function knownNormalizedSet(array $normalized): array
    {
        if ($normalized === []) {
            return [];
        }

        $known = [];
        foreach (
            LughatLemma::query()->whereIn('normalized_lemma', $normalized)->pluck('normalized_lemma') as $n
        ) {
            $known[$n] = true;
        }
        foreach (
            LughatWordForm::query()->whereIn('normalized_form', $normalized)->pluck('normalized_form') as $n
        ) {
            $known[$n] = true;
        }
        foreach (
            LughatInflection::query()->whereIn('normalized_form', $normalized)->pluck('normalized_form') as $n
        ) {
            $known[$n] = true;
        }

        return $known;
    }

    /**
     * @return list<array{surface: string, lemma: string, normalized: string}>
     */
    private function extractTokens(string $text): array
    {
        $plain = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $parts = preg_split('/\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $out = [];

        foreach ($parts as $raw) {
            $surface = DictionaryText::stripPunctuation($raw);
            $surface = trim($surface);
            if ($surface === '' || !preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}]/u', $surface)) {
                continue;
            }
            $normalized = DictionaryText::normalizeForLookup($surface);
            if ($normalized === '') {
                continue;
            }
            $out[] = [
                'surface' => $surface,
                'lemma' => DictionaryText::stripDiacritics($surface),
                'normalized' => $normalized,
            ];
        }

        return $out;
    }
}
