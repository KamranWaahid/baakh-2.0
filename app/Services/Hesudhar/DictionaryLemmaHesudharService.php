<?php

namespace App\Services\Hesudhar;

use App\Models\Lemma;
use App\Support\DictionaryText;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Batch-correct general-dictionary HEADWORDS only (lemma column)
 * with Hesudhar wordnet + heh rules — never senses / meanings.
 */
class DictionaryLemmaHesudharService
{
    /**
     * Process one cursor page of lemmas.
     *
     * @return array{
     *   scanned: int,
     *   updated: int,
     *   unchanged: int,
     *   skipped_conflict: int,
     *   next_after_id: int,
     *   done: bool,
     *   samples: list<array{id:int, from:string, to:string}>
     * }
     */
    public function processBatch(int $afterId = 0, int $limit = 200): array
    {
        $limit = max(25, min(500, $limit));
        HesudharDictionary::warm();
        $pipeline = HesudharDictionary::pipeline();
        $hasLookupBase = Schema::hasColumn('lemmas', 'lookup_base');

        $lemmas = Lemma::query()
            ->where('id', '>', $afterId)
            ->orderBy('id')
            ->limit($limit)
            ->get(['id', 'lemma', 'normalized_lemma', 'lookup_base']);

        $updated = 0;
        $unchanged = 0;
        $skippedConflict = 0;
        $samples = [];
        $lastId = $afterId;

        foreach ($lemmas as $lemma) {
            $lastId = (int) $lemma->id;
            $original = (string) $lemma->lemma;
            if (trim($original) === '') {
                $unchanged++;
                continue;
            }

            // Headword only — do NOT strip airab; keep zer/zabar/pesh.
            $result = $pipeline->process($original);
            $corrected = trim((string) $result->correctedText);

            if ($corrected === '' || strcmp($corrected, $original) === 0) {
                $unchanged++;
                continue;
            }

            // Avoid colliding with an existing distinct lemma (BINARY).
            $conflict = Lemma::query()
                ->where('id', '!=', $lemma->id)
                ->whereRaw(DictionaryText::binaryEquals('lemma'), [$corrected])
                ->exists();

            if ($conflict) {
                $skippedConflict++;
                continue;
            }

            $attrs = [
                'lemma' => $corrected,
                'normalized_lemma' => DictionaryText::normalizeForIdentity($corrected),
            ];
            if ($hasLookupBase) {
                $attrs['lookup_base'] = DictionaryText::lookupBase($corrected);
            }

            $lemma->fill($attrs);
            $lemma->save();
            $updated++;

            if (count($samples) < 12) {
                $samples[] = [
                    'id' => (int) $lemma->id,
                    'from' => $original,
                    'to' => $corrected,
                ];
            }
        }

        $done = $lemmas->count() < $limit;

        if ($done || $updated > 0) {
            Cache::forget('dictionary.lughat_keys.v2');
            Cache::forget('dictionary.lughat_stats.v2');
            Cache::forget('dictionary.stats.payload.v2');
        }

        return [
            'scanned' => $lemmas->count(),
            'updated' => $updated,
            'unchanged' => $unchanged,
            'skipped_conflict' => $skippedConflict,
            'next_after_id' => $lastId,
            'done' => $done,
            'samples' => $samples,
        ];
    }
}
