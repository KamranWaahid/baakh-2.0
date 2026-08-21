<?php

namespace App\Services;

use App\Models\LughatInflection;
use App\Models\LughatLemma;
use App\Models\LughatWordForm;
use App\Support\DictionaryText;

/**
 * Find Sindhi tokens in free text that are not yet in Baakh Lughat.
 * Airab is part of identity — نَھن and نُھن are tracked separately.
 */
class LughatMissingWordsService
{
    /**
     * @return list<string> unique surfaces (airab preserved) missing from Lughat
     */
    public function missingFromText(string $text): array
    {
        $tokens = $this->extractTokens($text);
        if ($tokens === []) {
            return [];
        }

        $missing = [];
        $seen = [];
        foreach ($tokens as $token) {
            $identity = $token['identity'];
            if (isset($seen[$identity])) {
                continue;
            }
            $seen[$identity] = true;

            if ($this->existsInLughat($token['surface'], $identity, $token['lookup_base'])) {
                continue;
            }
            $missing[] = $token['surface'];
        }

        sort($missing, SORT_STRING);

        return $missing;
    }

    /**
     * @param  list<string>  $words
     * @return array{created: list<array>, skipped_existing: list<string>, failed: list<array>}
     */
    public function createStubs(array $words, array $metadata = []): array
    {
        $created = [];
        $skipped = [];
        $failed = [];

        foreach ($words as $raw) {
            $surface = trim(DictionaryText::stripPunctuation((string) $raw));
            if ($surface === '') {
                continue;
            }

            $identity = DictionaryText::normalizeForIdentity($surface);
            $base = DictionaryText::lookupBase($surface);

            if ($identity === '') {
                $failed[] = ['word' => $surface, 'reason' => 'empty_after_normalize'];
                continue;
            }

            if ($this->existsInLughat($surface, $identity, $base)) {
                $skipped[] = $surface;
                continue;
            }

            $lemma = LughatLemma::create([
                'lemma' => $surface,
                'normalized_lemma' => $identity,
                'lookup_base' => $base,
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
            ];
        }

        return [
            'created' => $created,
            'skipped_existing' => $skipped,
            'failed' => $failed,
        ];
    }

    private function existsInLughat(string $surface, string $identity, string $base): bool
    {
        if (LughatLemma::query()
            ->where(function ($q) use ($surface, $identity) {
                $q->where('lemma', $surface)->orWhere('normalized_lemma', $identity);
            })
            ->exists()) {
            return true;
        }

        if (LughatWordForm::query()
            ->where(function ($q) use ($surface, $identity) {
                $q->where('form', $surface)->orWhere('normalized_form', $identity);
            })
            ->whereNotNull('lemma_id')
            ->exists()) {
            return true;
        }

        if (LughatInflection::query()
            ->where(function ($q) use ($surface, $identity) {
                $q->where('form', $surface)->orWhere('normalized_form', $identity);
            })
            ->exists()) {
            return true;
        }

        // Bare undiacritized surface: unique base match counts as present.
        if (!DictionaryText::hasDiacritics($surface) && $base !== '') {
            return LughatLemma::findByLookupBase($base, 8)->count() === 1;
        }

        return false;
    }

    /**
     * @return list<array{surface: string, identity: string, lookup_base: string}>
     */
    private function extractTokens(string $text): array
    {
        $plain = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $parts = preg_split('/\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $out = [];

        foreach ($parts as $raw) {
            $surface = trim(DictionaryText::stripPunctuation($raw));
            if ($surface === '' || !$this->isSindhiToken($surface)) {
                continue;
            }
            $identity = DictionaryText::normalizeForIdentity($surface);
            if ($identity === '') {
                continue;
            }
            $out[] = [
                'surface' => $surface,
                'identity' => $identity,
                'lookup_base' => DictionaryText::lookupBase($surface),
            ];
        }

        return $out;
    }

    private function isSindhiToken(string $word): bool
    {
        return (bool) preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}]/u', $word);
    }
}
