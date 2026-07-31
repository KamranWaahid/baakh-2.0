<?php

namespace App\Services;

use App\Models\Couplets;
use App\Models\LughatExpression;
use App\Models\LughatExpressionComponent;
use App\Models\LughatExpressionOccurrence;
use App\Models\LughatLemma;
use App\Models\LughatPoetryExpressionAnnotation;
use App\Models\LughatWordForm;
use App\Models\Poetry;
use App\Support\DictionaryText;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Multiword poetic expressions (izafat, collocations, metaphors…).
 */
class LughatExpressionService
{
    /**
     * Upsert an expression with ordered components.
     *
     * @param  array{
     *   expression: string,
     *   expression_type?: string,
     *   romanization?: ?string,
     *   definition_sd?: ?string,
     *   definition_en?: ?string,
     *   literal_gloss?: ?string,
     *   poetic_gloss?: ?string,
     *   register?: ?string,
     *   status?: ?string,
     *   confidence?: ?int,
     *   review_status?: ?string,
     *   metadata_json?: ?array,
     *   components?: list<array{
     *     surface_form: string,
     *     lemma_id?: ?int,
     *     word_form_id?: ?int,
     *     connector?: ?string,
     *     role?: ?string,
     *     position?: int
     *   }>
     * }  $data
     */
    public function upsert(array $data): LughatExpression
    {
        $expression = trim((string) ($data['expression'] ?? ''));
        if ($expression === '') {
            throw new \InvalidArgumentException('expression is required');
        }

        $type = $data['expression_type'] ?? 'izafat';
        if (!in_array($type, LughatExpression::TYPES, true)) {
            $type = 'other';
        }

        $normalized = DictionaryText::normalizeExpression($expression);
        $compact = DictionaryText::compactExpressionKey($expression);

        return DB::transaction(function () use ($data, $expression, $type, $normalized, $compact) {
            $row = LughatExpression::query()
                ->where('normalized_expression', $normalized)
                ->where('expression_type', $type)
                ->first();

            if ($row) {
                // Prefer surface that still carries izafat kasra; never wipe enriched fields with null.
                $updates = [
                    'normalized_expression' => $normalized,
                    'compact_search_key' => $compact,
                    'expression_type' => $type,
                ];
                if ($this->surfaceRicherThan($expression, (string) $row->expression)) {
                    $updates['expression'] = $expression;
                }
                foreach ([
                    'romanization' => $data['romanization'] ?? null,
                    'definition_sd' => $data['definition_sd'] ?? null,
                    'definition_en' => $data['definition_en'] ?? null,
                    'literal_gloss' => $data['literal_gloss'] ?? null,
                    'poetic_gloss' => $data['poetic_gloss'] ?? $data['poetic_interpretation'] ?? null,
                    'register' => $data['register'] ?? null,
                ] as $field => $value) {
                    $value = $this->nullable($value);
                    if ($value !== null) {
                        $updates[$field] = $value;
                    }
                }
                if (isset($data['status'])) {
                    $updates['status'] = $data['status'];
                }
                if (isset($data['review_status'])) {
                    $updates['review_status'] = $data['review_status'];
                }
                if (isset($data['confidence']) && is_numeric($data['confidence'])) {
                    $updates['confidence'] = $this->normalizeConfidence($data['confidence']);
                }
                if (is_array($data['metadata_json'] ?? null)) {
                    $updates['metadata_json'] = array_merge(
                        is_array($row->metadata_json) ? $row->metadata_json : [],
                        $data['metadata_json']
                    );
                }
                $row->update($updates);
            } else {
                $row = LughatExpression::create([
                    'expression' => $expression,
                    'normalized_expression' => $normalized,
                    'compact_search_key' => $compact,
                    'romanization' => $this->nullable($data['romanization'] ?? null),
                    'expression_type' => $type,
                    'definition_sd' => $this->nullable($data['definition_sd'] ?? null),
                    'definition_en' => $this->nullable($data['definition_en'] ?? null),
                    'literal_gloss' => $this->nullable($data['literal_gloss'] ?? null),
                    'poetic_gloss' => $this->nullable($data['poetic_gloss'] ?? $data['poetic_interpretation'] ?? null),
                    'register' => $this->nullable($data['register'] ?? 'poetic') ?? 'poetic',
                    'status' => $data['status'] ?? 'pending',
                    'confidence' => isset($data['confidence']) && is_numeric($data['confidence'])
                        ? $this->normalizeConfidence($data['confidence'])
                        : null,
                    'review_status' => $data['review_status'] ?? 'unreviewed',
                    'metadata_json' => is_array($data['metadata_json'] ?? null) ? $data['metadata_json'] : null,
                ]);
            }

            if (isset($data['components']) && is_array($data['components'])) {
                $this->syncComponents($row, $data['components']);
            } elseif ($row->components()->count() === 0) {
                $this->syncComponents($row, $this->inferComponentsFromExpression($expression, $type));
            }

            return $row->fresh(['components.lemma', 'components.wordForm']);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $components
     */
    public function syncComponents(LughatExpression $expression, array $components): void
    {
        $keep = [];
        $position = 0;

        foreach (array_values($components) as $index => $comp) {
            if (!is_array($comp)) {
                continue;
            }
            $surface = trim((string) ($comp['surface_form'] ?? $comp['surface'] ?? ''));
            if ($surface === '') {
                continue;
            }
            $position = (int) ($comp['position'] ?? ($index + 1));
            $normalized = DictionaryText::normalizeForLookup($surface);

            $lemmaId = is_numeric($comp['lemma_id'] ?? null) ? (int) $comp['lemma_id'] : null;
            if (!$lemmaId) {
                $lemmaId = LughatLemma::query()
                    ->where('normalized_lemma', $normalized)
                    ->where('homograph_number', 1)
                    ->value('id');
            }

            $wordFormId = is_numeric($comp['word_form_id'] ?? null) ? (int) $comp['word_form_id'] : null;
            if (!$wordFormId) {
                $wordFormId = LughatWordForm::query()
                    ->where('normalized_form', $normalized)
                    ->value('id');
            }

            $row = LughatExpressionComponent::query()
                ->where('expression_id', $expression->id)
                ->where('position', $position)
                ->first();

            $payload = [
                'lemma_id' => $lemmaId,
                'word_form_id' => $wordFormId,
                'surface_form' => $surface,
                'normalized_form' => $normalized,
                'connector' => $this->nullable($comp['connector'] ?? null),
                'role' => $this->nullable($comp['role'] ?? null),
            ];

            if ($row) {
                $row->update($payload);
            } else {
                $row = LughatExpressionComponent::create($payload + [
                    'expression_id' => $expression->id,
                    'position' => $position,
                ]);
            }
            $keep[] = $row->id;
        }

        $q = LughatExpressionComponent::query()->where('expression_id', $expression->id);
        if ($keep !== []) {
            $q->whereNotIn('id', $keep);
        }
        $q->delete();
    }

    /**
     * Infer جامِ + محبت style components from a surface expression.
     *
     * @return list<array<string, mixed>>
     */
    public function inferComponentsFromExpression(string $expression, string $type = 'izafat'): array
    {
        $parts = preg_split('/\s+/u', trim($expression), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $out = [];
        foreach ($parts as $i => $part) {
            $hasKasra = DictionaryText::hasTrailingKasra($part);
            $out[] = [
                'position' => $i + 1,
                'surface_form' => $part,
                'connector' => ($type === 'izafat' && $hasKasra) ? 'izafat' : null,
                'role' => $i === 0 ? 'head' : ($i === count($parts) - 1 ? 'complement' : 'modifier'),
            ];
        }

        return $out;
    }

    /**
     * Record a span occurrence of an expression in a couplet.
     */
    public function recordOccurrence(
        LughatExpression $expression,
        int $poetryId,
        int $coupletId,
        int $startToken,
        int $endToken,
        string $surfaceText,
        string $detectionMethod = 'rule_based',
        ?int $confidence = 70
    ): LughatExpressionOccurrence {
        return LughatExpressionOccurrence::updateOrCreate(
            [
                'expression_id' => $expression->id,
                'couplet_id' => $coupletId,
                'start_token_index' => $startToken,
                'end_token_index' => $endToken,
            ],
            [
                'poetry_id' => $poetryId,
                'surface_text' => $surfaceText,
                'normalized_text' => DictionaryText::normalizeExpression($surfaceText),
                'confidence' => $confidence,
                'detection_method' => $detectionMethod,
                'review_status' => $detectionMethod === 'manual' ? 'reviewed' : 'unreviewed',
            ]
        );
    }

    /**
     * Detect izafat spans in already-extracted tokens for one couplet.
     *
     * @param  list<array<string, mixed>>  $tokens  tokens for a single couplet (token_index local)
     * @return list<LughatExpressionOccurrence>
     */
    public function detectIzafatSpans(int $poetryId, int $coupletId, array $tokens): array
    {
        $created = [];
        $byIndex = [];
        foreach ($tokens as $t) {
            $byIndex[(int) $t['token_index']] = $t;
        }
        ksort($byIndex);
        $indexes = array_keys($byIndex);

        for ($i = 0; $i < count($indexes) - 1; $i++) {
            $a = $byIndex[$indexes[$i]];
            $b = $byIndex[$indexes[$i + 1]];
            $surfaceA = (string) $a['surface_form'];

            if (!DictionaryText::hasTrailingKasra($surfaceA)) {
                continue;
            }

            $surface = $surfaceA . ' ' . $b['surface_form'];
            $expression = $this->upsert([
                'expression' => $surface,
                'expression_type' => 'izafat',
                'register' => 'poetic',
                'status' => 'pending',
                'confidence' => 75,
                'review_status' => 'unreviewed',
                'components' => [
                    [
                        'position' => 1,
                        'surface_form' => $surfaceA,
                        'connector' => 'izafat',
                        'role' => 'head',
                        'lemma_id' => $a['lemma_id'] ?? null,
                        'word_form_id' => $a['word_form_id'] ?? null,
                    ],
                    [
                        'position' => 2,
                        'surface_form' => $b['surface_form'],
                        'connector' => null,
                        'role' => 'complement',
                        'lemma_id' => $b['lemma_id'] ?? null,
                        'word_form_id' => $b['word_form_id'] ?? null,
                    ],
                ],
            ]);

            $created[] = $this->recordOccurrence(
                $expression,
                $poetryId,
                $coupletId,
                (int) $a['token_index'],
                (int) $b['token_index'],
                $surface,
                'rule_based',
                75
            );
        }

        return $created;
    }

    /**
     * Expressions that include a given lemma as a component.
     *
     * @return list<array<string, mixed>>
     */
    public function expressionsForLemma(int $lemmaId, int $limit = 20): array
    {
        return LughatExpression::query()
            ->whereHas('components', fn ($q) => $q->where('lemma_id', $lemmaId))
            ->with(['components' => fn ($q) => $q->orderBy('position')])
            ->orderBy('expression')
            ->limit($limit)
            ->get()
            ->map(fn (LughatExpression $e) => [
                'id' => $e->id,
                'public_id' => $e->public_id,
                'expression' => $e->expression,
                'normalized_expression' => $e->normalized_expression,
                'expression_type' => $e->expression_type,
                'literal_gloss' => $e->literal_gloss,
                'poetic_gloss' => $e->poetic_gloss,
                'romanization' => $e->romanization,
                'register' => $e->register,
                'components' => $e->components->map(fn ($c) => [
                    'position' => $c->position,
                    'surface_form' => $c->surface_form,
                    'lemma_id' => $c->lemma_id,
                    'connector' => $c->connector,
                    'role' => $c->role,
                ])->values()->all(),
            ])
            ->all();
    }

    public function search(string $query, int $limit = 20): array
    {
        $normalized = DictionaryText::normalizeExpression($query);
        $compact = DictionaryText::compactExpressionKey($query);

        return LughatExpression::query()
            ->where(function ($q) use ($query, $normalized, $compact) {
                $q->where('expression', $query)
                    ->orWhere('normalized_expression', $normalized)
                    ->orWhere('compact_search_key', $compact)
                    ->orWhere('normalized_expression', 'like', '%' . $normalized . '%')
                    ->orWhere('romanization', 'like', '%' . $query . '%');
            })
            ->with('components')
            ->orderByRaw('CASE WHEN normalized_expression = ? THEN 0 WHEN expression = ? THEN 1 ELSE 2 END', [$normalized, $query])
            ->limit($limit)
            ->get()
            ->map(fn (LughatExpression $e) => [
                'match_type' => 'expression',
                'matched_text' => $e->expression,
                'expression' => [
                    'id' => $e->id,
                    'public_id' => $e->public_id,
                    'expression' => $e->expression,
                    'expression_type' => $e->expression_type,
                    'literal_gloss' => $e->literal_gloss,
                    'poetic_gloss' => $e->poetic_gloss,
                    'romanization' => $e->romanization,
                ],
                'components' => $e->components->map(fn ($c) => [
                    'surface_form' => $c->surface_form,
                    'lemma_id' => $c->lemma_id,
                    'role' => $c->role,
                    'connector' => $c->connector,
                ])->values()->all(),
            ])
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $annotations
     * @return list<LughatPoetryExpressionAnnotation>
     */
    public function replacePoetryAnnotations(Poetry $poetry, array $annotations): array
    {
        return DB::transaction(function () use ($poetry, $annotations) {
            LughatPoetryExpressionAnnotation::query()->where('poetry_id', $poetry->id)->delete();
            // Drop prior manual span occurrences for this poetry (keep rule_based import detections).
            LughatExpressionOccurrence::query()
                ->where('poetry_id', $poetry->id)
                ->where('detection_method', 'manual')
                ->delete();

            return $this->syncPoetryAnnotations($poetry, $annotations);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $annotations
     * @return list<\App\Models\LughatPoetryExpressionAnnotation>
     */
    public function syncPoetryAnnotations(Poetry $poetry, array $annotations): array
    {
        $sdCouplets = Couplets::query()
            ->where('poetry_id', $poetry->id)
            ->where(function ($q) {
                $q->whereNull('lang')->orWhereIn('lang', ['sd', 'snd', '']);
            })
            ->orderBy('id')
            ->get()
            ->values();

        $saved = [];

        foreach ($annotations as $row) {
            if (!is_array($row)) {
                continue;
            }

            $surface = trim((string) ($row['surface_text'] ?? $row['surface'] ?? $row['expression'] ?? ''));
            if ($surface === '') {
                continue;
            }

            $start = (int) ($row['start_token_index'] ?? $row['start_token'] ?? 0);
            $end = (int) ($row['end_token_index'] ?? $row['end_token'] ?? $start);
            if ($end < $start) {
                [$start, $end] = [$end, $start];
            }

            $type = (string) ($row['expression_type'] ?? $row['type'] ?? 'izafat');
            if (!in_array($type, LughatExpression::TYPES, true)) {
                $type = 'other';
            }

            $expression = $this->upsert([
                'expression' => $surface,
                'expression_type' => $type,
                'literal_gloss' => $row['literal_gloss'] ?? null,
                'poetic_gloss' => $row['poetic_gloss'] ?? $row['poetic_interpretation'] ?? null,
                'definition_sd' => $row['definition_sd'] ?? null,
                'definition_en' => $row['definition_en'] ?? null,
                'romanization' => $row['romanization'] ?? null,
                'register' => $row['register'] ?? 'poetic',
                'status' => 'pending',
                'review_status' => 'unreviewed',
                'confidence' => 85,
                'components' => is_array($row['components'] ?? null)
                    ? $row['components']
                    : $this->inferComponentsFromExpression($surface, $type),
            ]);

            $coupletIndex = (int) ($row['couplet_index'] ?? 0);
            $couplet = $sdCouplets->get($coupletIndex);
            $coupletId = $couplet?->id
                ?? (is_numeric($row['couplet_id'] ?? null) ? (int) $row['couplet_id'] : null);

            $annotation = LughatPoetryExpressionAnnotation::updateOrCreate(
                [
                    'poetry_id' => $poetry->id,
                    'couplet_index' => $coupletIndex,
                    'start_token_index' => $start,
                    'end_token_index' => $end,
                ],
                [
                    'couplet_id' => $coupletId,
                    'surface_text' => $surface,
                    'normalized_text' => DictionaryText::normalizeExpression($surface),
                    'expression_id' => $expression->id,
                    'expression_type' => $type,
                    'note' => filled($row['note'] ?? null) ? trim((string) $row['note']) : null,
                ]
            );

            if ($coupletId !== null) {
                $this->recordOccurrence(
                    $expression,
                    (int) $poetry->id,
                    (int) $coupletId,
                    $start,
                    $end,
                    $surface,
                    'manual',
                    90
                );
            }

            $saved[] = $annotation->fresh(['expression']);
        }

        return $saved;
    }

    public function listPoetryAnnotations(int $poetryId): array
    {
        if (!Schema::hasTable('lughat_poetry_expression_annotations')) {
            return [];
        }

        return LughatPoetryExpressionAnnotation::query()
            ->with('expression')
            ->where('poetry_id', $poetryId)
            ->orderBy('couplet_index')
            ->orderBy('start_token_index')
            ->get()
            ->map(fn (LughatPoetryExpressionAnnotation $a) => $this->serializePoetryAnnotation($a))
            ->all();
    }

    /**
     * Slim payload for public poem readers (izafat / collocation spans).
     *
     * @return list<array<string, mixed>>
     */
    public function listPoetryAnnotationsForPublic(int $poetryId): array
    {
        return collect($this->listPoetryAnnotations($poetryId))
            ->map(fn (array $a) => [
                'couplet_index' => $a['couplet_index'],
                'start_token_index' => $a['start_token_index'],
                'end_token_index' => $a['end_token_index'],
                'surface_text' => $a['surface_text'],
                'expression_type' => $a['expression_type'] ?: 'izafat',
                'literal_gloss' => $a['literal_gloss'],
                'poetic_gloss' => $a['poetic_gloss'],
            ])
            ->values()
            ->all();
    }

    /**
     * Find a pinned expression covering a couplet token (for public word clicks).
     */
    public function findPoetryAnnotationForToken(int $poetryId, int $coupletIndex, int $tokenIndex): ?array
    {
        if (!Schema::hasTable('lughat_poetry_expression_annotations')) {
            return null;
        }

        $annotation = LughatPoetryExpressionAnnotation::query()
            ->with('expression')
            ->where('poetry_id', $poetryId)
            ->where('couplet_index', $coupletIndex)
            ->where('start_token_index', '<=', $tokenIndex)
            ->where('end_token_index', '>=', $tokenIndex)
            ->orderByDesc('end_token_index')
            ->orderBy('start_token_index')
            ->first();

        return $annotation ? $this->serializePoetryAnnotation($annotation) : null;
    }

    /**
     * Public dictionary-card payload for a pinned poetic expression.
     *
     * @param  array<string, mixed>  $annotation
     * @return array<string, mixed>
     */
    public function publicLookupPayload(array $annotation): array
    {
        $surface = (string) ($annotation['surface_text'] ?? '');
        $poetic = trim((string) ($annotation['poetic_gloss'] ?? ''));
        $literal = trim((string) ($annotation['literal_gloss'] ?? ''));
        $note = trim((string) ($annotation['note'] ?? ''));
        $type = (string) ($annotation['expression_type'] ?? 'izafat');

        $meanings = collect([$poetic, $literal, $note])->filter()->unique()->values()->all();

        return [
            'found' => true,
            'id' => $annotation['expression_id'] ?? $annotation['id'] ?? null,
            'word' => $surface,
            'romanized' => null,
            'pos' => $type === 'izafat' ? 'izafat' : $type,
            'meanings' => $meanings,
            'meanings_en' => $literal !== '' ? [$literal] : [],
            'meanings_sd' => $poetic !== '' ? [$poetic] : [],
            'senses' => $meanings === [] ? [] : [[
                'id' => null,
                'short_gloss' => $type === 'izafat' ? 'اضافت' : $type,
                'definition' => $poetic !== '' ? $poetic : ($literal !== '' ? $literal : $note),
                'definition_en' => $literal !== '' ? $literal : null,
                'definition_sd' => $poetic !== '' ? $poetic : null,
                'is_preferred' => true,
            ]],
            'synonyms' => [],
            'antonyms' => [],
            'hypernyms' => [],
            'is_expression' => true,
            'expression_type' => $type,
            'source' => 'baakh_lughat',
            'dictionary' => 'lughat',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePoetryAnnotation(LughatPoetryExpressionAnnotation $a): array
    {
        return [
            'id' => $a->id,
            'couplet_id' => $a->couplet_id,
            'couplet_index' => $a->couplet_index,
            'start_token_index' => $a->start_token_index,
            'end_token_index' => $a->end_token_index,
            'surface_text' => $a->surface_text,
            'normalized_text' => $a->normalized_text,
            'expression_id' => $a->expression_id,
            'expression_type' => $a->expression_type ?: $a->expression?->expression_type,
            'literal_gloss' => $a->expression?->literal_gloss,
            'poetic_gloss' => $a->expression?->poetic_gloss,
            'note' => $a->note,
            'expression' => $a->expression ? [
                'id' => $a->expression->id,
                'expression' => $a->expression->expression,
                'expression_type' => $a->expression->expression_type,
                'literal_gloss' => $a->expression->literal_gloss,
                'poetic_gloss' => $a->expression->poetic_gloss,
            ] : null,
        ];
    }

    private function nullable(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeConfidence(mixed $value): int
    {
        $c = (float) $value;
        if ($c > 0 && $c <= 1) {
            $c *= 100;
        }

        return (int) max(0, min(100, round($c)));
    }

    /** Prefer forms that still carry diacritics (e.g. جامِ محبت over جام محبت). */
    private function surfaceRicherThan(string $candidate, string $existing): bool
    {
        if ($existing === '') {
            return true;
        }
        $candMarks = preg_match_all('/[\x{064B}-\x{065F}]/u', $candidate) ?: 0;
        $existMarks = preg_match_all('/[\x{064B}-\x{065F}]/u', $existing) ?: 0;

        return $candMarks > $existMarks;
    }
}
