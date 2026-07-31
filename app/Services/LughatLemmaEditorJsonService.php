<?php

namespace App\Services;

use App\Models\Lemma;
use App\Models\LughatLemma;
use App\Models\LughatRelation;
use App\Models\LughatSense;
use App\Models\LughatVariant;
use App\Support\DictionaryText;
use Illuminate\Support\Collection;

/**
 * Editor JSON for Baakh Lughat — mirrors DictionaryLemmaEditorJsonService shape
 * so meanings / morphology / examples can be generated later from the same template.
 */
class LughatLemmaEditorJsonService
{
    public function build(LughatLemma $lemma): array
    {
        $lemma->loadMissing([
            'senses.examples',
            'morphology',
            'variants',
            'lemmaRelations.relatedLemma',
            'inflections',
            'idiomaticExpressions',
            'expressionComponents.expression.components',
        ]);

        $keywords = is_array($lemma->search_keywords_json) ? $lemma->search_keywords_json : [];
        $metadata = is_array($lemma->metadata_json) ? $lemma->metadata_json : [];
        $primarySense = $lemma->senses->first();
        // Keep expression list tiny in editor-json (AI paste size); full list is on the Expressions UI.
        $linkedExpressions = app(LughatExpressionService::class)->expressionsForLemma((int) $lemma->id, 5);

        return [
            '_schema' => 'baakh.lughat.editor_json.v2',
            '_name' => 'Baakh Lughat',
            // Kept short — full AI rules live in the Copy-for-AI prompt (avoid duplicating a huge blob in JSON).
            '_instructions' => 'Complete this Baakh Lughat lemma (v2). Fill empty fields. '
                . 'Sindhi primary definitions. Plain ASCII roman only. '
                . 'Fill senses, typed relations, airab variants, forms.inflections. '
                . 'Omit general_dictionary/relations_checklist in output. Keep numeric ids. Paste via Import JSON.',
            'id' => $lemma->id,
            'public_id' => $lemma->public_id,

            // Poetry omitted from editor-json (full poems blow up ChatGPT paste size).
            // Paste couplets separately in chat when poetic senses / expressions are needed.
            'poetry' => [
                'poetry_id' => $lemma->poetry_id,
                'couplet_id' => $lemma->couplet_id,
                'title' => null,
                'source_couplet' => null,
                'couplets' => [],
                'token_hints' => [],
                '_note' => 'Poetry text omitted to keep JSON small. Paste couplet(s) separately in chat when needed for poetic senses / expression_candidates. occurrence_summary still has frequency counts.',
            ],

            // Read-only reference for AI — not imported.
            'general_dictionary' => $this->buildGeneralDictionaryContext($lemma),

            'general' => [
                'lemma' => $lemma->lemma,
                'normalized_lemma' => $lemma->normalized_lemma,
                // Only DB value — never auto-fill from Romanizer / poetry EN.
                'transliteration' => $lemma->transliteration,
                'ipa' => $lemma->ipa,
                'phonetic' => $lemma->phonetic,
                'pronunciation_simple' => $lemma->pronunciation_simple,
                'audio_url' => $lemma->audio_url,
                'syllabification' => $lemma->syllabification,
                'pos' => $lemma->pos,
                'source_confidence' => $lemma->source_confidence,
                'status' => $lemma->status,
                'etymology' => $lemma->etymology,
                'notes' => $lemma->notes,
                'search_keywords_sindhi' => $keywords['sindhi'] ?? [],
                'search_keywords_english' => $keywords['english'] ?? [],
                'search_keywords_romanized' => $keywords['romanized'] ?? [],
                'metadata_region' => $metadata['region'] ?? null,
                'metadata_dialect_notes' => $metadata['dialect_notes'] ?? null,
                'metadata_version' => $metadata['version'] ?? null,
                'variants_reviewed' => (bool) $lemma->variants_reviewed,
                'examples_reviewed' => (bool) $lemma->examples_reviewed,
                'morphology_reviewed' => (bool) $lemma->morphology_reviewed,
                'pronunciation_reviewed' => (bool) $lemma->pronunciation_reviewed,
                'primary_meanings' => [
                    'definition' => $primarySense?->definition,
                    'definition_sd' => $primarySense?->definition_sd,
                    'definition_en' => $primarySense?->definition_en,
                ],
            ],

            'completion' => [
                'completion_notes' => $lemma->completion_notes,
                'completion_status' => $lemma->completion_status,
            ],

            'morphology' => [
                'root' => $lemma->morphology?->root,
                'pattern' => $lemma->morphology?->pattern,
                'gender' => $lemma->morphology?->gender,
                'number' => $lemma->morphology?->number,
                'case' => $lemma->morphology?->case,
                'tense' => $lemma->morphology?->tense,
            ],

            'senses' => $lemma->senses
                ->map(fn (LughatSense $sense) => [
                    'id' => $sense->id,
                    'definition' => $sense->definition,
                    'short_gloss' => $sense->short_gloss,
                    'definition_sd' => $sense->definition_sd,
                    'definition_en' => $sense->definition_en,
                    'english_equivalents' => is_array($sense->english_equivalents) ? $sense->english_equivalents : [],
                    'usage_label' => $sense->usage_label,
                    'domain' => $sense->domain,
                    'language_direction' => $sense->language_direction,
                    'source_dictionary' => $sense->source_dictionary ?: 'Baakh Lughat',
                    'publisher' => $sense->publisher ?: 'baakh.com',
                    'publisher_url' => $this->sensePublisherUrl($sense),
                    'prepared_by' => $this->sensePreparedBy($sense),
                    'review_status' => $sense->review_status,
                    'examples' => $sense->examples
                        ->map(fn ($example) => [
                            'id' => $example->id,
                            'sentence' => $example->sentence,
                            'romanization' => $example->romanization,
                            'translation' => $example->translation,
                            'source' => $example->source,
                            'citation' => $example->citation,
                            'poetry_id' => $example->poetry_id,
                            'couplet_id' => $example->couplet_id,
                            'quality_flag' => $example->quality_flag,
                            'review_status' => $example->review_status,
                        ])
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),

            'relations' => $lemma->lemmaRelations
                ->map(fn (LughatRelation $relation) => [
                    'id' => $relation->id,
                    'relation_type' => $relation->relation_type,
                    'related_word' => $relation->relatedLemma?->lemma ?: $relation->related_word,
                    'related_lemma_id' => $relation->related_lemma_id,
                    'romanization' => $relation->romanization ?: $relation->relatedLemma?->transliteration,
                    'note' => $relation->note,
                    'gloss' => $relation->gloss,
                    'part_of_speech' => $relation->part_of_speech ?: $relation->relatedLemma?->pos,
                ])
                ->values()
                ->all(),

            // Read-only nudge for AI — which Linguistic Relations buckets still need filling.
            'relations_checklist' => $this->buildRelationsChecklist($lemma),

            'variants' => $lemma->variants
                ->map(fn (LughatVariant $variant) => [
                    'id' => $variant->id,
                    'variant' => $variant->variant,
                    'type' => $variant->type,
                    'romanization' => $variant->romanization,
                    'dialect' => $variant->dialect,
                    'note' => $variant->note,
                    'source' => $variant->source,
                ])
                ->values()
                ->all(),

            'forms' => [
                'inflections' => $lemma->inflections
                    ->map(fn ($inflection) => [
                        'id' => $inflection->id,
                        'form' => $inflection->form,
                        'normalized_form' => $inflection->normalized_form,
                        'romanization' => $inflection->romanization,
                        'form_type' => $inflection->form_type,
                        'gender' => $inflection->gender,
                        'number' => $inflection->number,
                        'case' => $inflection->case_name,
                        'person' => $inflection->person,
                        'stem' => $inflection->stem,
                        'suffix' => $inflection->suffix,
                        'confidence' => $inflection->confidence,
                        'description' => $inflection->description,
                        'analysis' => $inflection->analysis_json,
                    ])
                    ->values()
                    ->all(),
                'idiomatic_expressions' => $lemma->idiomaticExpressions
                    ->map(fn ($expression) => [
                        'id' => $expression->id,
                        'phrase' => $expression->phrase,
                        'romanization' => $expression->romanization,
                        'english_gloss' => $expression->english_gloss,
                        'example_sindhi' => $expression->example_sindhi,
                        'example_english' => $expression->example_english,
                    ])
                    ->values()
                    ->all(),
                'expressions' => $linkedExpressions,
            ],

            // AI fills candidates; import upserts as pending review (not auto-approved).
            'expression_candidates' => [],

            'occurrence_summary' => [
                'token_frequency' => (int) ($lemma->token_frequency ?? 0),
                'poem_frequency' => (int) ($lemma->poem_frequency ?? 0),
                'couplet_frequency' => (int) ($lemma->couplet_frequency ?? 0),
                'first_poetry_id' => $lemma->poetry_id,
                'first_couplet_id' => $lemma->couplet_id,
            ],
        ];
    }

    /**
     * Full general-dictionary snapshot for AI (old Sindhi dictionary).
     * Prefer exact airab match; also attach other lookup_base variants.
     * Read-only context — stripped on import.
     */
    private function buildGeneralDictionaryContext(LughatLemma $lemma): array
    {
        $word = trim((string) $lemma->lemma);
        $empty = [
            'found' => false,
            'source' => null,
            'match_type' => null,
            'word' => $word !== '' ? $word : null,
            'pos' => null,
            'meanings' => [],
            'meanings_en' => [],
            'meanings_sd' => [],
            'senses' => [],
            'synonyms' => [],
            'antonyms' => [],
            'hypernyms' => [],
            'related' => [],
            'singular' => [],
            'plural' => [],
            'dialect' => [],
            'derived' => [],
            'usage' => [],
            'entries' => [],
            'full_entry' => null,
            '_note' => 'Read-only. Copy accurate meanings/relations from the old general dictionary into Baakh Lughat senses[] and typed relations[]. Prefer exact airab match in entries[]. Do not echo this key in output.',
        ];

        if ($word === '') {
            return $empty;
        }

        $matches = $this->findGeneralDictionaryLemmas($word);
        if ($matches->isNotEmpty()) {
            $primary = $matches->first();
            // Compact only — nested full dictionary_json made ChatGPT paste too large.
            $entries = $matches->take(6)->map(function (Lemma $dbLemma) use ($word) {
                return [
                    'match_type' => $this->generalDictionaryMatchType($word, $dbLemma),
                    'id' => $dbLemma->id,
                    'lemma' => $dbLemma->lemma,
                    'normalized_lemma' => $dbLemma->normalized_lemma,
                    'lookup_base' => $dbLemma->lookup_base,
                    'pos' => $dbLemma->pos,
                    'transliteration' => $dbLemma->transliteration,
                ];
            })->values();

            $compact = $this->compactGeneralDictionaryEntry($primary);

            return array_merge($empty, $compact, [
                'found' => true,
                'source' => 'site_dictionary',
                'match_type' => $this->generalDictionaryMatchType($word, $primary),
                'word' => $primary->lemma,
                'entries' => $entries->all(),
                'full_entry' => null,
                'match_count' => $matches->count(),
                '_note' => 'Read-only compact snapshot from the old general dictionary (senses + typed relation lists). '
                    . 'Prefer match_type=exact / identity when several airab variants appear in entries[]. '
                    . 'Merge into Baakh Lughat senses[] + typed relations[]. Do not echo this key in output.',
            ]);
        }

        $fallback = app(BundledOpenLexiconLookup::class)->lookup($word);
        if (!$fallback || empty($fallback['found'])) {
            return $empty;
        }

        $senses = collect($fallback['senses'] ?? [])
            ->take(12)
            ->map(fn ($sense) => [
                'short_gloss' => $sense['short_gloss'] ?? null,
                'definition' => $sense['definition'] ?? $sense['full_definition'] ?? null,
                'definition_sd' => $sense['definition_sd'] ?? null,
                'definition_en' => $sense['definition_en'] ?? null,
                'domain' => $sense['domain'] ?? null,
                'usage_label' => $sense['usage_label'] ?? $sense['register'] ?? null,
            ])
            ->values()
            ->all();

        return array_merge($empty, [
            'found' => true,
            'source' => $fallback['source'] ?? 'bundled_open_lexicon',
            'match_type' => 'open_lexicon',
            'word' => $fallback['word'] ?? $word,
            'pos' => $fallback['pos'] ?? null,
            'meanings' => array_values(array_slice($fallback['meanings'] ?? [], 0, 8)),
            'meanings_en' => array_values(array_slice($fallback['meanings_en'] ?? [], 0, 8)),
            'meanings_sd' => array_values(array_slice($fallback['meanings_sd'] ?? [], 0, 8)),
            'senses' => $senses,
            'synonyms' => array_values(array_slice($fallback['synonyms'] ?? [], 0, 20)),
            'antonyms' => array_values(array_slice($fallback['antonyms'] ?? [], 0, 12)),
            'hypernyms' => array_values(array_slice($fallback['hypernyms'] ?? [], 0, 12)),
            'related' => array_values(array_slice($fallback['related'] ?? [], 0, 12)),
            'full_entry' => null,
            'entries' => [[
                'match_type' => 'open_lexicon',
                'lemma' => $fallback['word'] ?? $word,
                'pos' => $fallback['pos'] ?? null,
            ]],
            'match_count' => 1,
        ]);
    }

    /**
     * Exact airab → identity → all lookup_base variants (old dictionary often has several marked forms).
     *
     * @return Collection<int, Lemma>
     */
    private function findGeneralDictionaryLemmas(string $word): Collection
    {
        $identity = DictionaryText::normalizeForIdentity($word);
        $base = DictionaryText::lookupBase($word);
        $with = [
            'senses',
            'morphology',
            'lemmaRelations.relatedLemma',
        ];

        $exact = Lemma::query()
            ->with($with)
            ->whereRaw(DictionaryText::binaryEquals('lemma'), [$word])
            ->orderBy('id')
            ->get();

        $identityMatches = $identity === ''
            ? collect()
            : Lemma::query()
                ->with($with)
                ->whereRaw(DictionaryText::binaryEquals('normalized_lemma'), [$identity])
                ->orderBy('id')
                ->get();

        $hasLookupBase = \Illuminate\Support\Facades\Schema::hasColumn('lemmas', 'lookup_base');
        $baseMatches = $base === ''
            ? collect()
            : Lemma::query()
                ->with($with)
                ->where(function ($q) use ($base, $hasLookupBase) {
                    if ($hasLookupBase) {
                        $q->whereRaw(DictionaryText::binaryEquals('lookup_base'), [$base])
                            ->orWhereRaw(DictionaryText::binaryEquals('normalized_lemma'), [$base]);
                    } else {
                        $q->whereRaw(DictionaryText::binaryEquals('normalized_lemma'), [$base]);
                    }
                })
                ->orderBy('id')
                ->limit(12)
                ->get();

        // Prefer exact airab first, then identity, then other base variants.
        return $exact
            ->concat($identityMatches)
            ->concat($baseMatches)
            ->unique('id')
            ->values();
    }

    private function generalDictionaryMatchType(string $word, Lemma $dbLemma): string
    {
        if (strcmp((string) $dbLemma->lemma, $word) === 0) {
            return 'exact';
        }
        $identity = DictionaryText::normalizeForIdentity($word);
        if ($identity !== '' && strcmp((string) $dbLemma->normalized_lemma, $identity) === 0) {
            return 'identity';
        }

        return 'lookup_base';
    }

    /**
     * Flat summary from the primary dictionary lemma (kept for older AI prompt fields).
     */
    private function compactGeneralDictionaryEntry(Lemma $dbLemma): array
    {
        $senses = $dbLemma->senses
            ->take(8)
            ->map(fn ($sense) => [
                'short_gloss' => $sense->short_gloss,
                'definition' => $sense->definition,
                'definition_sd' => $sense->definition_sd,
                'definition_en' => $sense->definition_en,
                'english_equivalents' => array_values(array_slice(
                    is_array($sense->english_equivalents) ? $sense->english_equivalents : [],
                    0,
                    8
                )),
                'domain' => $sense->domain,
                'usage_label' => $sense->usage_label ?? $sense->register,
            ])
            ->filter(fn ($s) => filled($s['definition']) || filled($s['short_gloss']) || filled($s['definition_sd']) || filled($s['definition_en']))
            ->values()
            ->all();

        $relationsByType = [
            'synonym' => [],
            'antonym' => [],
            'hypernym' => [],
            'related' => [],
            'singular' => [],
            'plural' => [],
            'dialect' => [],
            'derived' => [],
            'usage' => [],
        ];
        foreach ($dbLemma->lemmaRelations as $rel) {
            $type = (string) ($rel->relation_type ?: 'related');
            if (!isset($relationsByType[$type])) {
                $relationsByType[$type] = [];
            }
            $rw = trim((string) ($rel->relatedLemma?->lemma ?: $rel->related_word ?: ''));
            if ($rw !== '') {
                $relationsByType[$type][] = $rw;
            }
        }
        foreach ($relationsByType as $type => $words) {
            $relationsByType[$type] = array_values(array_unique($words));
        }

        return [
            'pos' => $dbLemma->pos,
            'transliteration' => $dbLemma->transliteration,
            'etymology' => $dbLemma->etymology,
            'meanings' => collect($senses)->pluck('definition')->filter()->unique()->take(12)->values()->all(),
            'meanings_en' => collect($senses)->pluck('definition_en')->filter()->unique()->take(12)->values()->all(),
            'meanings_sd' => collect($senses)->pluck('definition_sd')->filter()->unique()->take(12)->values()->all(),
            'senses' => $senses,
            'synonyms' => $relationsByType['synonym'],
            'antonyms' => $relationsByType['antonym'],
            'hypernyms' => $relationsByType['hypernym'],
            'related' => $relationsByType['related'],
            'singular' => $relationsByType['singular'],
            'plural' => $relationsByType['plural'],
            'dialect' => $relationsByType['dialect'],
            'derived' => $relationsByType['derived'],
            'usage' => $relationsByType['usage'],
            'morphology' => [
                'root' => $dbLemma->morphology?->root,
                'gender' => $dbLemma->morphology?->gender,
                'number' => $dbLemma->morphology?->number,
            ],
        ];
    }

    /**
     * Counts per Linguistic Relations tab bucket — AI should fill empty ones.
     *
     * @return array{counts: array<string,int>, empty: list<string>, _note: string}
     */
    private function buildRelationsChecklist(LughatLemma $lemma): array
    {
        $types = ['synonym', 'antonym', 'singular', 'plural', 'dialect', 'derived', 'usage', 'hypernym', 'related'];
        $counts = array_fill_keys($types, 0);

        foreach ($lemma->lemmaRelations as $relation) {
            $type = (string) ($relation->relation_type ?: 'related');
            if (!isset($counts[$type])) {
                $counts[$type] = 0;
            }
            $counts[$type]++;
        }

        $empty = array_values(array_filter($types, fn (string $type) => ($counts[$type] ?? 0) === 0));

        return [
            'counts' => $counts,
            'empty' => $empty,
            '_note' => 'Read-only. Fill relations[] for every type in empty[] when knowable. Do not dump synonyms into related. Omit this key in output.',
        ];
    }

    private function sensePreparedBy(LughatSense $sense): string
    {
        $extra = is_array($sense->extra) ? $sense->extra : [];

        return filled($extra['prepared_by'] ?? null)
            ? (string) $extra['prepared_by']
            : 'Kamran Wahid';
    }

    private function sensePublisherUrl(LughatSense $sense): string
    {
        $extra = is_array($sense->extra) ? $sense->extra : [];

        return filled($extra['publisher_url'] ?? null)
            ? (string) $extra['publisher_url']
            : 'https://baakh.com/';
    }

    public function normalizeForImport(array $payload): array
    {
        if (isset($payload['data']) && is_array($payload['data'])) {
            $payload = $payload['data'];
        }

        // Editor-tab shape from Copy JSON / ChatGPT edits of that template.
        if (isset($payload['general']) && is_array($payload['general'])) {
            return $this->flattenEditorShape($payload);
        }

        // Flat lemma dump (full API / AI expansion) — keep top-level fields.
        return $this->normalizeFlatDump($payload);
    }

    private function flattenEditorShape(array $payload): array
    {
        $flat = [];

        if (array_key_exists('id', $payload)) {
            $flat['id'] = $payload['id'];
        }
        if (array_key_exists('public_id', $payload)) {
            $flat['public_id'] = $payload['public_id'];
        }

        $general = is_array($payload['general'] ?? null) ? $payload['general'] : [];

        foreach ([
            'lemma',
            'normalized_lemma',
            'transliteration',
            'ipa',
            'phonetic',
            'pronunciation_simple',
            'audio_url',
            'syllabification',
            'pos',
            'source_confidence',
            'status',
            'etymology',
            'notes',
            'variants_reviewed',
            'examples_reviewed',
            'morphology_reviewed',
            'pronunciation_reviewed',
        ] as $field) {
            if (array_key_exists($field, $general)) {
                $flat[$field] = $general[$field];
            }
        }

        $hasKeywordKey = array_key_exists('search_keywords_sindhi', $general)
            || array_key_exists('search_keywords_english', $general)
            || array_key_exists('search_keywords_romanized', $general);
        if ($hasKeywordKey) {
            $flat['search_keywords_json'] = [
                'sindhi' => $this->stringList($general['search_keywords_sindhi'] ?? []),
                'english' => $this->stringList($general['search_keywords_english'] ?? []),
                'romanized' => $this->stringList($general['search_keywords_romanized'] ?? []),
            ];
        }

        $hasMetadataKey = array_key_exists('metadata_region', $general)
            || array_key_exists('metadata_dialect_notes', $general)
            || array_key_exists('metadata_version', $general);
        if ($hasMetadataKey) {
            $flat['metadata_json'] = [
                'region' => $general['metadata_region'] ?? null,
                'dialect_notes' => $general['metadata_dialect_notes'] ?? null,
                'version' => $general['metadata_version'] ?? null,
            ];
        }

        if (isset($payload['completion']) && is_array($payload['completion'])) {
            if (array_key_exists('completion_notes', $payload['completion'])) {
                $flat['completion_notes'] = $payload['completion']['completion_notes'];
            }
            if (array_key_exists('completion_status', $payload['completion'])) {
                $flat['completion_status'] = $payload['completion']['completion_status'];
            }
        }

        if (array_key_exists('morphology', $payload)) {
            $flat['morphology'] = is_array($payload['morphology']) ? $payload['morphology'] : null;
        }

        if (array_key_exists('senses', $payload) && is_array($payload['senses'])) {
            $flat['senses'] = $payload['senses'];
        }

        if (array_key_exists('relations', $payload) && is_array($payload['relations'])) {
            $flat['lemma_relations'] = $payload['relations'];
        }

        if (array_key_exists('variants', $payload) && is_array($payload['variants'])) {
            $flat['variants'] = $payload['variants'];
        }

        $forms = is_array($payload['forms'] ?? null) ? $payload['forms'] : null;
        if ($forms !== null) {
            if (array_key_exists('inflections', $forms) && is_array($forms['inflections'])) {
                $flat['inflections'] = $forms['inflections'];
            }
            if (array_key_exists('idiomatic_expressions', $forms) && is_array($forms['idiomatic_expressions'])) {
                $flat['idiomatic_expressions'] = $forms['idiomatic_expressions'];
            }
            if (array_key_exists('expressions', $forms) && is_array($forms['expressions'])) {
                $flat['expressions'] = $forms['expressions'];
            }
        }
        // Also accept top-level forms[] as inflections (AI v2 shape)
        if (empty($flat['inflections']) && isset($payload['forms']) && is_array($payload['forms']) && array_is_list($payload['forms'])) {
            $flat['inflections'] = $payload['forms'];
        }
        if (array_key_exists('expression_candidates', $payload) && is_array($payload['expression_candidates'])) {
            $flat['expression_candidates'] = $payload['expression_candidates'];
        }
        if (array_key_exists('expressions', $payload) && is_array($payload['expressions']) && !isset($flat['expressions'])) {
            $flat['expressions'] = $payload['expressions'];
        }
        if (array_key_exists('_replace_missing', $payload)) {
            $flat['_replace_missing'] = (bool) $payload['_replace_missing'];
        }
        if (array_key_exists('publish_romanization', $payload)) {
            $flat['publish_romanization'] = (bool) $payload['publish_romanization'];
        }

        $primaryMeanings = is_array($general['primary_meanings'] ?? null) ? $general['primary_meanings'] : null;
        if ($primaryMeanings !== null) {
            $flat['senses'] = $flat['senses'] ?? [];
            if ($flat['senses'] === []) {
                $seed = [
                    'definition' => $primaryMeanings['definition'] ?? '',
                    'definition_sd' => $primaryMeanings['definition_sd'] ?? null,
                    'definition_en' => $primaryMeanings['definition_en'] ?? null,
                    'review_status' => 'unreviewed',
                ];
                // Only seed when at least one meaning field is filled.
                if (filled($seed['definition']) || filled($seed['definition_sd']) || filled($seed['definition_en'])) {
                    $flat['senses'][] = $seed;
                }
            } else {
                foreach (['definition', 'definition_sd', 'definition_en'] as $field) {
                    if (array_key_exists($field, $primaryMeanings) && filled($primaryMeanings[$field])) {
                        $flat['senses'][0][$field] = $primaryMeanings[$field];
                    }
                }
            }
        }

        return $flat;
    }

    private function normalizeFlatDump(array $payload): array
    {
        // Ignore computed checklist blobs; keep scalar completion fields from the root.
        if (isset($payload['completion']) && is_array($payload['completion'])) {
            if (!array_key_exists('completion_status', $payload) && isset($payload['completion']['status'])) {
                $payload['completion_status'] = $payload['completion']['status'];
            }
            if (!array_key_exists('completion_score', $payload) && isset($payload['completion']['score'])) {
                $payload['completion_score'] = $payload['completion']['score'];
            }
            unset($payload['completion']);
        }

        // Drop read-only / display-only blobs so they never confuse sync.
        foreach ([
            'source_summary',
            'structured_entry',
            'imported_variants',
            'checklist_json',
            'manual_variants_count',
            'imported_variants_count',
            'has_real_morphology',
        ] as $key) {
            unset($payload[$key]);
        }

        return $payload;
    }

    private function stringList(mixed $value): array
    {
        if (is_string($value)) {
            return array_values(array_filter(array_map('trim', preg_split('/[,\n]+/', $value) ?: [])));
        }

        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($item) => is_scalar($item) ? trim((string) $item) : '',
            $value
        )));
    }
}
