<?php

namespace App\Services\Hesudhar;

use App\Models\Couplets;
use App\Models\Poetry;
use App\Models\PoetryTranslations;
use App\Support\DictionaryText;

/**
 * Applies dictionary-first Hesudhar correction to poetry/couplet DB content.
 */
class HesudharContentRefiner
{
    /**
     * @return array{original: string, corrected: string, changed: bool, changes: list<array{original: string, corrected: string, source: string}>}
     */
    public function refineText(string $text): array
    {
        $original = $text;
        $pipeline = HesudharDictionary::pipeline();
        $allChanges = [];

        if (!preg_match('/<[^>]+>/u', $text)) {
            $stripped = DictionaryText::stripDiacritics($text);
            if ($stripped !== $text) {
                $allChanges[] = [
                    'original' => $text,
                    'corrected' => $stripped,
                    'source' => 'STRIP_DIACRITICS',
                ];
                $text = $stripped;
            }

            $result = $pipeline->process($text);
            $corrected = $result->correctedText;
            foreach ($result->changesLog as $change) {
                $allChanges[] = $change;
            }
        } else {
            $corrected = preg_replace_callback(
                '/([^<>]+)|(<[^>]+>)/u',
                function (array $matches) use ($pipeline, &$allChanges) {
                    if (($matches[2] ?? '') !== '') {
                        return $matches[2];
                    }

                    $chunk = $matches[1] ?? '';
                    if (trim($chunk) === '') {
                        return $chunk;
                    }

                    $stripped = DictionaryText::stripDiacritics($chunk);
                    if ($stripped !== $chunk) {
                        $allChanges[] = [
                            'original' => $chunk,
                            'corrected' => $stripped,
                            'source' => 'STRIP_DIACRITICS',
                        ];
                        $chunk = $stripped;
                    }

                    $result = $pipeline->process($chunk);
                    foreach ($result->changesLog as $change) {
                        $allChanges[] = $change;
                    }

                    return $result->correctedText;
                },
                $text
            ) ?? $text;
        }

        return [
            'original' => $original,
            'corrected' => $corrected,
            'changed' => $corrected !== $original,
            'changes' => $allChanges,
        ];
    }

    public function isSindhiLang(?string $lang): bool
    {
        $lang = strtolower(trim((string) $lang));

        return $lang === '' || $lang === 'sd' || $lang === 'snd' || $lang === 'sindhi';
    }

    /**
     * @return array{id: int, changed: bool, changes_count: int, skipped: bool, reason?: string}
     */
    public function refineCoupletRecord(Couplets $couplet): array
    {
        if (!$this->isSindhiLang($couplet->lang)) {
            return [
                'id' => $couplet->id,
                'changed' => false,
                'changes_count' => 0,
                'skipped' => true,
                'reason' => 'non_sindhi',
            ];
        }

        $text = (string) $couplet->couplet_text;
        if (trim($text) === '') {
            return [
                'id' => $couplet->id,
                'changed' => false,
                'changes_count' => 0,
                'skipped' => true,
                'reason' => 'empty',
            ];
        }

        $result = $this->refineText($text);
        if ($result['changed']) {
            $couplet->couplet_text = $result['corrected'];
            $couplet->save();
        }

        return [
            'id' => $couplet->id,
            'changed' => $result['changed'],
            'changes_count' => count($result['changes']),
            'skipped' => false,
        ];
    }

    /**
     * Refine all Sindhi couplets belonging to a poetry work.
     *
     * @return array{poetry_id: int, scanned: int, updated: int, changes: int, couplets: list<array>}
     */
    public function refinePoetry(Poetry $poetry): array
    {
        HesudharDictionary::warm();

        $scanned = 0;
        $updated = 0;
        $changes = 0;
        $details = [];

        $poetry->all_couplets()
            ->orderBy('id')
            ->chunkById(100, function ($rows) use (&$scanned, &$updated, &$changes, &$details) {
                foreach ($rows as $couplet) {
                    /** @var Couplets $couplet */
                    $outcome = $this->refineCoupletRecord($couplet);
                    if ($outcome['skipped'] ?? false) {
                        continue;
                    }
                    $scanned++;
                    if ($outcome['changed']) {
                        $updated++;
                        $changes += $outcome['changes_count'];
                    }
                    $details[] = $outcome;
                }
            });

        // Also refine Sindhi poetry title/info
        $translationUpdated = 0;
        PoetryTranslations::query()
            ->where('poetry_id', $poetry->id)
            ->where(function ($q) {
                $q->whereNull('lang')->orWhere('lang', 'sd')->orWhere('lang', 'snd');
            })
            ->get()
            ->each(function (PoetryTranslations $tr) use (&$translationUpdated) {
                $dirty = false;
                foreach (['title', 'info'] as $field) {
                    $value = (string) ($tr->{$field} ?? '');
                    if (trim($value) === '') {
                        continue;
                    }
                    $result = $this->refineText($value);
                    if ($result['changed']) {
                        $tr->{$field} = $result['corrected'];
                        $dirty = true;
                    }
                }
                if ($dirty) {
                    $tr->save();
                    $translationUpdated++;
                }
            });

        return [
            'poetry_id' => $poetry->id,
            'scanned' => $scanned,
            'updated' => $updated,
            'changes' => $changes,
            'translations_updated' => $translationUpdated,
            'couplets' => $details,
        ];
    }

    /**
     * Refine every Sindhi couplet in the database (chunked).
     *
     * @return array{scanned: int, updated: int, changes: int}
     */
    public function refineAllCouplets(?int $limit = null): array
    {
        HesudharDictionary::warm();

        $scanned = 0;
        $updated = 0;
        $changes = 0;

        $query = Couplets::query()->orderBy('id');
        if ($limit !== null && $limit > 0) {
            $query->limit($limit);
        }

        $query->chunkById(200, function ($rows) use (&$scanned, &$updated, &$changes, $limit) {
            foreach ($rows as $couplet) {
                if ($limit !== null && $scanned >= $limit) {
                    return false;
                }
                $outcome = $this->refineCoupletRecord($couplet);
                if ($outcome['skipped'] ?? false) {
                    continue;
                }
                $scanned++;
                if ($outcome['changed']) {
                    $updated++;
                    $changes += $outcome['changes_count'];
                }
            }
        });

        return [
            'scanned' => $scanned,
            'updated' => $updated,
            'changes' => $changes,
        ];
    }

    /**
     * Refine Sindhi couplets for every poetry work.
     *
     * @return array{poetry_scanned: int, couplets_scanned: int, couplets_updated: int, changes: int, translations_updated: int}
     */
    public function refineAllPoetry(): array
    {
        HesudharDictionary::warm();

        $poetryScanned = 0;
        $coupletsScanned = 0;
        $coupletsUpdated = 0;
        $changes = 0;
        $translationsUpdated = 0;

        Poetry::query()->orderBy('id')->chunkById(50, function ($poems) use (
            &$poetryScanned,
            &$coupletsScanned,
            &$coupletsUpdated,
            &$changes,
            &$translationsUpdated
        ) {
            foreach ($poems as $poetry) {
                $result = $this->refinePoetry($poetry);
                $poetryScanned++;
                $coupletsScanned += $result['scanned'];
                $coupletsUpdated += $result['updated'];
                $changes += $result['changes'];
                $translationsUpdated += $result['translations_updated'];
            }
        });

        return [
            'poetry_scanned' => $poetryScanned,
            'couplets_scanned' => $coupletsScanned,
            'couplets_updated' => $coupletsUpdated,
            'changes' => $changes,
            'translations_updated' => $translationsUpdated,
        ];
    }
}
