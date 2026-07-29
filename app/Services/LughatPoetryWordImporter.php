<?php

namespace App\Services;

use App\Models\LughatInflection;
use App\Models\LughatLemma;
use App\Models\LughatOccurrence;
use App\Models\LughatWordForm;
use App\Models\Poetry;
use App\Support\DictionaryText;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Poetry → Lughat ingest (v2):
 *   token → occurrence → word_form → (optional) lemma stub
 *
 * Preserves every token occurrence. Creates lemmas only for new lookup forms
 * that have no existing word_form/lemma/inflection match.
 */
class LughatPoetryWordImporter
{
    public const CURSOR_CACHE_KEY = 'lughat.poetry_import.cursor';

    /**
     * @return array{
     *   done: bool,
     *   poetry: ?array,
     *   created: int,
     *   lemmas_created: int,
     *   word_forms_created: int,
     *   occurrences_created: int,
     *   expressions_created: int,
     *   skipped_duplicate: int,
     *   skipped_empty: int,
     *   total_tokens: int,
     *   words: list<string>,
     *   next_poetry_id: ?int,
     *   cursor: int,
     *   message?: string
     * }
     */
    public function importNext(?int $poetryId = null, bool $reset = false): array
    {
        if ($reset) {
            $this->setCursor(0);
        }

        $poetry = $this->resolvePoetry($poetryId);
        if (!$poetry) {
            return [
                'done' => true,
                'poetry' => null,
                'created' => 0,
                'lemmas_created' => 0,
                'word_forms_created' => 0,
                'occurrences_created' => 0,
                'expressions_created' => 0,
                'skipped_duplicate' => 0,
                'skipped_empty' => 0,
                'total_tokens' => 0,
                'words' => [],
                'next_poetry_id' => null,
                'cursor' => $this->cursor(),
                'message' => 'No more poetry to import from.',
            ];
        }

        $result = $this->importPoetry($poetry);
        $this->setCursor((int) $poetry->id);

        $next = Poetry::query()
            ->where('id', '>', $poetry->id)
            ->orderBy('id')
            ->value('id');

        return [
            ...$result,
            'done' => false,
            'next_poetry_id' => $next ? (int) $next : null,
            'cursor' => (int) $poetry->id,
        ];
    }

    public function peekNext(?int $poetryId = null): array
    {
        $poetry = $this->resolvePoetry($poetryId);
        if (!$poetry) {
            return [
                'done' => true,
                'poetry' => null,
                'word_count' => 0,
                'new_word_count' => 0,
                'token_count' => 0,
                'cursor' => $this->cursor(),
                'next_poetry_id' => null,
            ];
        }

        $tokens = $this->extractTokens($poetry);
        $uniqueNormalized = [];
        foreach ($tokens as $t) {
            $uniqueNormalized[$t['normalized_form']] = true;
        }
        $normalized = array_keys($uniqueNormalized);

        $existingForms = $normalized === []
            ? []
            : (Schema::hasTable('lughat_word_forms')
                ? LughatWordForm::query()->whereIn('normalized_form', $normalized)->pluck('normalized_form')->all()
                : []);
        $existingLemmas = $normalized === []
            ? []
            : (Schema::hasTable('lughat_lemmas')
                ? LughatLemma::query()->whereIn('normalized_lemma', $normalized)->pluck('normalized_lemma')->all()
                : []);
        $known = array_fill_keys(array_merge($existingForms, $existingLemmas), true);

        $newCount = count(array_filter(
            $normalized,
            static fn (string $n) => !isset($known[$n])
        ));

        $next = Poetry::query()
            ->where('id', '>', $poetry->id)
            ->orderBy('id')
            ->value('id');

        return [
            'done' => false,
            'poetry' => $this->poetryPayload($poetry),
            'word_count' => count($normalized),
            'new_word_count' => $newCount,
            'token_count' => count($tokens),
            'cursor' => $this->cursor(),
            'next_poetry_id' => $next ? (int) $next : null,
        ];
    }

    public function cursor(): int
    {
        return (int) Cache::get(self::CURSOR_CACHE_KEY, 0);
    }

    public function setCursor(int $poetryId): void
    {
        Cache::forever(self::CURSOR_CACHE_KEY, $poetryId);
    }

    public function resolvePoetry(?int $poetryId = null): ?Poetry
    {
        if ($poetryId) {
            return Poetry::query()->with(['couplets', 'translations'])->find($poetryId);
        }

        return Poetry::query()
            ->with(['couplets', 'translations'])
            ->where('id', '>', $this->cursor())
            ->orderBy('id')
            ->first();
    }

    public function importPoetry(Poetry $poetry): array
    {
        $tokens = $this->extractTokens($poetry);
        $lemmasCreated = 0;
        $formsCreated = 0;
        $occurrencesCreated = 0;
        $linkedExisting = 0;
        $words = [];
        $touchedLemmaIds = [];

        DB::transaction(function () use (
            $tokens,
            $poetry,
            &$lemmasCreated,
            &$formsCreated,
            &$occurrencesCreated,
            &$linkedExisting,
            &$words,
            &$touchedLemmaIds
        ) {
            foreach ($tokens as $token) {
                // Skip if this exact couplet token slot already imported
                $existsOcc = LughatOccurrence::query()
                    ->where('couplet_id', $token['couplet_id'])
                    ->where('token_index', $token['token_index'])
                    ->where('language', $token['language'])
                    ->where('tokenization_version', LughatOccurrence::TOKENIZATION_VERSION)
                    ->exists();
                if ($existsOcc) {
                    $linkedExisting++;
                    continue;
                }

                [$wordForm, $formWasCreated] = $this->findOrCreateWordForm($token);
                if ($formWasCreated) {
                    $formsCreated++;
                }

                $lemma = $this->resolveLemmaForForm($wordForm, $token, $poetry);
                $lemmaCreatedNow = false;
                if ($lemma === null) {
                    $lemma = $this->createLemmaStub($token, $poetry);
                    $lemmaCreatedNow = true;
                    $lemmasCreated++;
                    $words[] = $lemma->lemma;
                } else {
                    $linkedExisting++;
                }

                if ($lemma && !$wordForm->lemma_id) {
                    $wordForm->update([
                        'lemma_id' => $lemma->id,
                        'status' => LughatWordForm::STATUS_LINKED,
                        'form_type' => $wordForm->form_type === LughatWordForm::TYPE_UNANALYZED
                            ? LughatWordForm::TYPE_LEMMA
                            : $wordForm->form_type,
                    ]);
                }

                // Ensure canonical lemma form also exists as inflection/word-form link
                if ($lemma && $lemma->normalized_lemma === $wordForm->normalized_form) {
                    $this->ensureLemmaInflection($lemma, $wordForm);
                }

                LughatOccurrence::create([
                    'lemma_id' => $lemma?->id,
                    'word_form_id' => $wordForm->id,
                    'poetry_id' => $poetry->id,
                    'couplet_id' => $token['couplet_id'],
                    'poet_id' => $poetry->poet_id,
                    'surface_form' => $token['surface_form'],
                    'normalized_form' => $token['normalized_form'],
                    'token_index' => $token['token_index'],
                    'character_start' => $token['character_start'],
                    'character_end' => $token['character_end'],
                    'context_before' => $token['context_before'],
                    'context_after' => $token['context_after'],
                    'full_couplet_snapshot' => $token['full_couplet_snapshot'],
                    'language' => $token['language'],
                    'has_diacritics' => $token['has_diacritics'],
                    'tokenization_version' => LughatOccurrence::TOKENIZATION_VERSION,
                    'normalization_version' => LughatOccurrence::NORMALIZATION_VERSION,
                    'analysis_status' => $lemma
                        ? LughatOccurrence::ANALYSIS_LINKED
                        : LughatOccurrence::ANALYSIS_UNANALYZED,
                    'analysis_confidence' => $lemmaCreatedNow ? 60 : ($lemma ? 80 : null),
                ]);
                $occurrencesCreated++;

                if ($lemma) {
                    $touchedLemmaIds[$lemma->id] = true;
                    // Cache first occurrence on lemma if empty
                    if (!$lemma->poetry_id) {
                        $lemma->update([
                            'poetry_id' => $poetry->id,
                            'couplet_id' => $token['couplet_id'],
                        ]);
                    }
                }
            }

            foreach (array_keys($touchedLemmaIds) as $lemmaId) {
                $this->refreshLemmaFrequencies((int) $lemmaId);
            }
        });

        // Token layer is done; detect multiword spans (izafat etc.) separately.
        $expressionsCreated = $this->detectExpressionSpans($poetry, $tokens);

        return [
            'poetry' => $this->poetryPayload($poetry),
            'created' => $lemmasCreated,
            'lemmas_created' => $lemmasCreated,
            'word_forms_created' => $formsCreated,
            'occurrences_created' => $occurrencesCreated,
            'expressions_created' => $expressionsCreated,
            'skipped_duplicate' => $linkedExisting,
            'skipped_empty' => 0,
            'total_tokens' => count($tokens),
            'words' => $words,
        ];
    }

    /**
     * Span layer: consecutive tokens like جامِ + محبت → expression + occurrence.
     *
     * @param  list<array<string, mixed>>  $tokens
     */
    private function detectExpressionSpans(Poetry $poetry, array $tokens): int
    {
        if ($tokens === []) {
            return 0;
        }

        $byCouplet = [];
        foreach ($tokens as $token) {
            $byCouplet[(int) $token['couplet_id']][] = $token;
        }

        $expressionService = app(LughatExpressionService::class);
        $created = 0;

        foreach ($byCouplet as $coupletId => $coupletTokens) {
            $occurrences = LughatOccurrence::query()
                ->where('couplet_id', $coupletId)
                ->where('tokenization_version', LughatOccurrence::TOKENIZATION_VERSION)
                ->get()
                ->keyBy('token_index');

            $enriched = [];
            foreach ($coupletTokens as $token) {
                $occ = $occurrences->get((int) $token['token_index']);
                $enriched[] = [
                    ...$token,
                    'lemma_id' => $occ?->lemma_id,
                    'word_form_id' => $occ?->word_form_id,
                ];
            }

            $created += count($expressionService->detectIzafatSpans(
                (int) $poetry->id,
                (int) $coupletId,
                $enriched
            ));
        }

        return $created;
    }

    /**
     * All Sindhi tokens with surface + normalized forms (not unique).
     *
     * @return list<array<string, mixed>>
     */
    public function extractTokens(Poetry $poetry): array
    {
        $out = [];

        $couplets = $poetry->couplets
            ->filter(function ($couplet) {
                $lang = strtolower((string) ($couplet->lang ?? 'sd'));

                return $lang === '' || $lang === 'sd' || $lang === 'snd';
            })
            ->sortBy('id');

        foreach ($couplets as $couplet) {
            $plain = html_entity_decode(strip_tags((string) $couplet->couplet_text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $plain = preg_replace("/\r\n?/", "\n", $plain) ?? $plain;
            $tokens = preg_split('/\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $tokenIndex = 0;
            $offset = 0;

            foreach ($tokens as $raw) {
                $pos = mb_strpos($plain, $raw, $offset);
                if ($pos === false) {
                    $pos = $offset;
                }
                $end = $pos + mb_strlen($raw);
                $offset = $end;

                $surface = DictionaryText::stripPunctuation($raw);
                $surface = trim($surface);
                if ($surface === '' || !preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}]/u', $surface)) {
                    continue;
                }

                $hasDiacritics = (bool) preg_match('/[\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u', $surface);
                $lookup = DictionaryText::normalizeForLookup($surface);
                if ($lookup === '') {
                    continue;
                }

                $before = trim(mb_substr($plain, max(0, $pos - 40), min(40, $pos)));
                $after = trim(mb_substr($plain, $end, 40));

                $out[] = [
                    'surface_form' => $surface,
                    'normalized_form' => $lookup,
                    'lemma' => DictionaryText::stripDiacritics($surface),
                    'couplet_id' => $couplet->id,
                    'token_index' => $tokenIndex,
                    'character_start' => $pos,
                    'character_end' => $end,
                    'context_before' => $before !== '' ? $before : null,
                    'context_after' => $after !== '' ? $after : null,
                    'full_couplet_snapshot' => $plain,
                    'language' => 'sd',
                    'has_diacritics' => $hasDiacritics,
                ];
                $tokenIndex++;
            }
        }

        return $out;
    }

    /** @deprecated use extractTokens; kept for peek uniqueness helpers */
    public function extractWords(Poetry $poetry): array
    {
        $seen = [];
        $out = [];
        foreach ($this->extractTokens($poetry) as $t) {
            if (isset($seen[$t['normalized_form']])) {
                continue;
            }
            $seen[$t['normalized_form']] = true;
            $out[] = [
                'lemma' => $t['lemma'],
                'normalized_lemma' => $t['normalized_form'],
                'couplet_id' => $t['couplet_id'],
            ];
        }

        return $out;
    }

    public function cleanToken(string $token): ?string
    {
        $token = DictionaryText::stripPunctuation($token);
        $token = DictionaryText::stripDiacritics($token);
        $token = trim($token);

        if ($token === '' || !preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}]/u', $token)) {
            return null;
        }

        return $token;
    }

    /**
     * @return array{0: LughatWordForm, 1: bool}
     */
    private function findOrCreateWordForm(array $token): array
    {
        $existing = LughatWordForm::query()
            ->where('normalized_form', $token['normalized_form'])
            ->first();

        if ($existing) {
            return [$existing, false];
        }

        $form = LughatWordForm::create([
            'form' => $token['surface_form'],
            'normalized_form' => $token['normalized_form'],
            'form_type' => LughatWordForm::TYPE_UNANALYZED,
            'status' => LughatWordForm::STATUS_PENDING,
            'source' => 'poetry_import',
            'confidence' => 50,
        ]);

        return [$form, true];
    }

    private function resolveLemmaForForm(LughatWordForm $wordForm, array $token, Poetry $poetry): ?LughatLemma
    {
        if ($wordForm->lemma_id) {
            return LughatLemma::query()->find($wordForm->lemma_id);
        }

        // Exact lemma headword
        $lemma = LughatLemma::query()
            ->where('normalized_lemma', $token['normalized_form'])
            ->where('homograph_number', 1)
            ->first();
        if ($lemma) {
            return $lemma;
        }

        // Known inflection of an existing lemma
        $inflection = LughatInflection::query()
            ->where('normalized_form', $token['normalized_form'])
            ->orderBy('id')
            ->first();
        if ($inflection) {
            $wordForm->update([
                'form_type' => LughatWordForm::TYPE_INFLECTED,
                'lemma_id' => $inflection->lemma_id,
                'status' => LughatWordForm::STATUS_LINKED,
            ]);

            return LughatLemma::query()->find($inflection->lemma_id);
        }

        return null;
    }

    private function createLemmaStub(array $token, Poetry $poetry): LughatLemma
    {
        return LughatLemma::create([
            'lemma' => $token['lemma'],
            'normalized_lemma' => $token['normalized_form'],
            'homograph_number' => 1,
            'language' => 'sd',
            'transliteration' => null,
            'romanization_status' => 'proposed',
            'status' => 'pending',
            'completion_status' => LughatLemma::COMPLETION_PENDING,
            'poetry_id' => $poetry->id,
            'couplet_id' => $token['couplet_id'],
            'metadata_json' => [
                'dictionary' => 'Baakh Lughat',
                'version' => '2',
                'source' => 'poetry_import',
                'poetry_id' => $poetry->id,
            ],
        ]);
    }

    private function ensureLemmaInflection(LughatLemma $lemma, LughatWordForm $wordForm): void
    {
        $exists = LughatInflection::query()
            ->where('lemma_id', $lemma->id)
            ->where('normalized_form', $wordForm->normalized_form)
            ->exists();
        if ($exists) {
            return;
        }

        LughatInflection::create([
            'lemma_id' => $lemma->id,
            'word_form_id' => $wordForm->id,
            'form' => $wordForm->form,
            'normalized_form' => $wordForm->normalized_form,
            'form_type' => 'lemma',
            'review_status' => 'unreviewed',
            'source' => 'poetry_import',
        ]);
    }

    public function refreshLemmaFrequencies(int $lemmaId): void
    {
        $tokenFrequency = LughatOccurrence::query()->where('lemma_id', $lemmaId)->count();
        $poemFrequency = LughatOccurrence::query()->where('lemma_id', $lemmaId)->distinct('poetry_id')->count('poetry_id');
        $coupletFrequency = LughatOccurrence::query()->where('lemma_id', $lemmaId)->distinct('couplet_id')->count('couplet_id');

        LughatLemma::query()->where('id', $lemmaId)->update([
            'token_frequency' => $tokenFrequency,
            'poem_frequency' => $poemFrequency,
            'couplet_frequency' => $coupletFrequency,
            'frequency' => $tokenFrequency,
        ]);
    }

    private function poetryPayload(Poetry $poetry): array
    {
        $title = $poetry->translations
            ->firstWhere('lang', 'sd')
            ?->title
            ?? $poetry->translations->first()?->title
            ?? $poetry->poetry_title
            ?? null;

        return [
            'id' => $poetry->id,
            'title' => $title,
            'poet_id' => $poetry->poet_id,
            'couplet_count' => $poetry->couplets->count(),
        ];
    }
}
