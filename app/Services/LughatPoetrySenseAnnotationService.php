<?php

namespace App\Services;

use App\Models\Couplets;
use App\Models\LughatLemma;
use App\Models\LughatOccurrence;
use App\Models\LughatPoetrySenseAnnotation;
use App\Models\LughatSense;
use App\Models\Poetry;
use App\Support\DictionaryText;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LughatPoetrySenseAnnotationService
{
    /**
     * Lookup Baakh Lughat lemma + senses for a surface token.
     * Preferred sense (from poetry annotation) is listed first when poetry_id given.
     *
     * @return array{found: bool, word: ?string, lemma: ?array, senses: list<array>, preferred_sense_id: ?int, expressions: list<array>}
     */
    public function lookupSenses(string $word, ?int $poetryId = null): array
    {
        $surface = DictionaryText::stripPunctuation(trim($word));
        $normalized = DictionaryText::normalizeForLookup($surface);

        if ($normalized === '') {
            return [
                'found' => false,
                'word' => $word,
                'lemma' => null,
                'senses' => [],
                'preferred_sense_id' => null,
                'expressions' => [],
            ];
        }

        $lemma = LughatLemma::query()
            ->where(function ($q) use ($normalized, $surface) {
                $q->where('normalized_lemma', $normalized)
                    ->orWhere('lemma', $surface);
            })
            ->orderBy('homograph_number')
            ->first();

        if (!$lemma) {
            $form = \App\Models\LughatWordForm::query()
                ->with('lemma')
                ->where('normalized_form', $normalized)
                ->first();
            $lemma = $form?->lemma;
        }

        if (!$lemma) {
            $inf = \App\Models\LughatInflection::query()
                ->with('lemma')
                ->where('normalized_form', $normalized)
                ->first();
            $lemma = $inf?->lemma;
        }

        if (!$lemma) {
            return [
                'found' => false,
                'word' => $surface,
                'normalized' => $normalized,
                'lemma' => null,
                'senses' => [],
                'preferred_sense_id' => null,
                'expressions' => [],
            ];
        }

        $preferredId = null;
        if ($poetryId) {
            $preferredId = LughatPoetrySenseAnnotation::query()
                ->where('poetry_id', $poetryId)
                ->where('lemma_id', $lemma->id)
                ->where('normalized_form', $normalized)
                ->value('sense_id');
        }

        $senses = LughatSense::query()
            ->where('lemma_id', $lemma->id)
            ->orderBy('sense_order')
            ->orderBy('id')
            ->get()
            ->sortBy(function (LughatSense $s) use ($preferredId) {
                if ($preferredId && (int) $s->id === (int) $preferredId) {
                    return -1;
                }

                return (int) ($s->sense_order ?? 999);
            })
            ->values()
            ->map(fn (LughatSense $s) => [
                'id' => $s->id,
                'sense_order' => $s->sense_order,
                'short_gloss' => $s->short_gloss,
                'definition' => $s->definition,
                'definition_sd' => $s->definition_sd,
                'definition_en' => $s->definition_en,
                'english_equivalents' => $s->english_equivalents,
                'usage_label' => $s->usage_label,
                'domain' => $s->domain,
                'is_preferred' => $preferredId && (int) $s->id === (int) $preferredId,
            ])
            ->all();

        $expressions = app(LughatExpressionService::class)->expressionsForLemma((int) $lemma->id, 12);

        return [
            'found' => true,
            'word' => $surface,
            'normalized' => $normalized,
            'lemma' => [
                'id' => $lemma->id,
                'public_id' => $lemma->public_id,
                'lemma' => $lemma->lemma,
                'transliteration' => $lemma->transliteration,
                'pos' => $lemma->pos,
            ],
            'senses' => $senses,
            'preferred_sense_id' => $preferredId ? (int) $preferredId : null,
            'expressions' => $expressions,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $annotations
     * @return list<LughatPoetrySenseAnnotation>
     */
    public function syncForPoetry(Poetry $poetry, array $annotations, bool $promoteSelected = true): array
    {
        $sdCouplets = Couplets::query()
            ->where('poetry_id', $poetry->id)
            ->where(function ($q) {
                $q->whereNull('lang')->orWhereIn('lang', ['sd', 'snd', '']);
            })
            ->orderBy('id')
            ->get()
            ->values();

        $keepIds = [];
        $saved = [];

        DB::transaction(function () use ($poetry, $annotations, $promoteSelected, $sdCouplets, &$keepIds, &$saved) {
            foreach ($annotations as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $senseId = (int) ($row['sense_id'] ?? 0);
                if ($senseId < 1) {
                    continue;
                }

                $sense = LughatSense::query()->find($senseId);
                if (!$sense) {
                    continue;
                }

                $coupletIndex = (int) ($row['couplet_index'] ?? 0);
                $tokenIndex = (int) ($row['token_index'] ?? 0);
                $surface = trim((string) ($row['surface_form'] ?? $row['surface'] ?? ''));
                $normalized = DictionaryText::normalizeForLookup(
                    $surface !== '' ? $surface : (string) ($row['normalized_form'] ?? '')
                );
                if ($normalized === '') {
                    continue;
                }

                $couplet = $sdCouplets->get($coupletIndex);
                $coupletId = $couplet?->id
                    ?? (is_numeric($row['couplet_id'] ?? null) ? (int) $row['couplet_id'] : null);

                $shouldPromote = array_key_exists('promote', $row)
                    ? (bool) $row['promote']
                    : $promoteSelected;

                $annotation = LughatPoetrySenseAnnotation::updateOrCreate(
                    [
                        'poetry_id' => $poetry->id,
                        'couplet_index' => $coupletIndex,
                        'token_index' => $tokenIndex,
                    ],
                    [
                        'couplet_id' => $coupletId,
                        'surface_form' => $surface !== '' ? $surface : $normalized,
                        'normalized_form' => $normalized,
                        'lemma_id' => $sense->lemma_id,
                        'sense_id' => $sense->id,
                        'note' => filled($row['note'] ?? null) ? trim((string) $row['note']) : null,
                        'promoted' => $shouldPromote,
                    ]
                );

                if ($shouldPromote) {
                    $this->promoteSenseToTop($sense);
                    $annotation->update(['promoted' => true]);
                }

                // Mirror onto occurrence if poetry was imported into Lughat
                if ($coupletId !== null) {
                    LughatOccurrence::query()
                        ->where('couplet_id', $coupletId)
                        ->where('token_index', $tokenIndex)
                        ->where('tokenization_version', LughatOccurrence::TOKENIZATION_VERSION)
                        ->update([
                            'sense_id' => $sense->id,
                            'lemma_id' => $sense->lemma_id,
                            'analysis_status' => LughatOccurrence::ANALYSIS_LINKED,
                        ]);
                }

                $keepIds[] = $annotation->id;
                $saved[] = $annotation->fresh(['sense', 'lemma']);
            }
        });

        return $saved;
    }

    /**
     * Replace all annotations for a poetry with the given list.
     *
     * @param  list<array<string, mixed>>  $annotations
     */
    public function replaceForPoetry(Poetry $poetry, array $annotations, bool $promoteSelected = true): array
    {
        return DB::transaction(function () use ($poetry, $annotations, $promoteSelected) {
            LughatPoetrySenseAnnotation::query()->where('poetry_id', $poetry->id)->delete();

            return $this->syncForPoetry($poetry, $annotations, $promoteSelected);
        });
    }

    public function listForPoetry(int $poetryId): array
    {
        if (!Schema::hasTable('lughat_poetry_sense_annotations')) {
            return [];
        }

        return LughatPoetrySenseAnnotation::query()
            ->with(['sense', 'lemma'])
            ->where('poetry_id', $poetryId)
            ->orderBy('couplet_index')
            ->orderBy('token_index')
            ->get()
            ->map(fn (LughatPoetrySenseAnnotation $a) => [
                'id' => $a->id,
                'couplet_id' => $a->couplet_id,
                'couplet_index' => $a->couplet_index,
                'token_index' => $a->token_index,
                'surface_form' => $a->surface_form,
                'normalized_form' => $a->normalized_form,
                'lemma_id' => $a->lemma_id,
                'lemma' => $a->lemma?->lemma,
                'sense_id' => $a->sense_id,
                'sense' => $a->sense ? [
                    'id' => $a->sense->id,
                    'short_gloss' => $a->sense->short_gloss,
                    'definition' => $a->sense->definition,
                    'definition_sd' => $a->sense->definition_sd,
                    'definition_en' => $a->sense->definition_en,
                ] : null,
                'note' => $a->note,
                'promoted' => (bool) $a->promoted,
            ])
            ->all();
    }

    public function promoteSenseToTop(LughatSense $sense): void
    {
        $others = LughatSense::query()
            ->where('lemma_id', $sense->lemma_id)
            ->where('id', '!=', $sense->id)
            ->orderBy('sense_order')
            ->orderBy('id')
            ->get();

        $sense->update(['sense_order' => 1]);
        $order = 2;
        foreach ($others as $other) {
            $other->update(['sense_order' => $order]);
            $order++;
        }
    }
}
