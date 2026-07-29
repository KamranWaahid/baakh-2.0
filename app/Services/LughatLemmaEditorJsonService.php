<?php

namespace App\Services;

use App\Models\Couplets;
use App\Models\Lemma;
use App\Models\LughatLemma;
use App\Models\LughatRelation;
use App\Models\LughatSense;
use App\Models\LughatVariant;
use App\Models\Poetry;
use App\Models\PoetryTranslations;
use App\Support\DictionaryText;

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
        $poetryContext = $this->buildPoetryContext($lemma);
        $linkedExpressions = app(LughatExpressionService::class)->expressionsForLemma((int) $lemma->id, 40);
        $tokenHints = $this->buildTokenHints($poetryContext);

        return [
            '_schema' => 'baakh.lughat.editor_json.v2',
            '_name' => 'Baakh Lughat',
            '_instructions' => 'Baakh Lughat poetic dictionary (v2). '
                . 'You receive: (1) poetry.* couplets/token_hints for this word in verse, '
                . '(2) general_dictionary.* from the site general dictionary / Open Lexicon (READ-ONLY reference), '
                . '(3) current Baakh Lughat senses/forms. '
                . 'COMPLETE THE WORD: do not leave required editor fields empty — fill every blank you can '
                . '(lemma, normalized_lemma, pos, transliteration, pronunciation_simple/phonetic when possible, '
                . 'morphology when knowable, reviewed flags when you filled those sections). '
                . 'SENSES (critical): generate NEW senses (omit id) that combine general-dictionary meanings '
                . 'WITH poetry-specific meanings so editors can tag the right sense on a line and readers understand the verse. '
                . 'Include: (a) senses adapted from general_dictionary (usage_label/domain e.g. general/literal), '
                . '(b) NEW poetic/contextual senses from poetry.* (usage_label poetic/figurative/mystical as fits). '
                . 'PRIMARY DEFINITIONS IN SINDHI: sense.definition and short_gloss MUST be Sindhi (Arabic script); '
                . 'English only in definition_en (optional). Fill definition_sd when useful. '
                . 'Every sense MUST fill short_gloss, definition (Sindhi), language_direction (prefer sindhi), '
                . 'source_dictionary, publisher, publisher_url, prepared_by; set review_status=reviewed and status=approved. '
                . 'Prefer poetry citation examples from poetry.couplets for poetic senses. '
                . 'Keep existing sense ids; upsert only — do not delete by omission. '
                . 'Fill general.transliteration (roman of headword) — never empty. '
                . 'ROMAN ONLY: transliteration and all romanization fields = plain Latin a–z/spaces/hyphens only. '
                . 'No Arabic script; no zabar/zer/pesh (َُِ), tashdeed (ّ), sukun (ْ), tanween, or Latin accented letters (āīū). e.g. aadmi not ādmī. '
                . 'Add forms[] / forms.inflections with gender/number/case/person/stem/suffix when known; null only if uncertain. '
                . 'Propose multiword spans in expression_candidates[] (indexes from poetry.token_hints). '
                . 'Set completion.completion_status=complete when required fields + curated senses are filled; else pending. '
                . 'RELATIONS (critical — fill typed buckets, do not dump into related only): '
                . 'Write relations[] with relation_type one of synonym|antonym|hypernym|related|singular|plural|dialect|derived|usage. '
                . 'Use general_dictionary.synonyms/antonyms/hypernyms/related PLUS poetry context. '
                . 'Honor relations_checklist.empty — fill those 0-count buckets when knowable; reclassify misfiled related→synonym. '
                . 'Each item: related_word (required), romanization, note, gloss, part_of_speech when known. '
                . 'synonym = near-same meaning; antonym = opposite; hypernym = broader class; '
                . 'singular/plural = number pair; dialect = regional form (note=dialect name); '
                . 'derived = derived noun/adj (محبت→محبتي); usage = people-say form + gloss example sentence; '
                . 'related = only leftover associates that are not synonyms. '
                . 'Keep existing relation ids; omit id for new rows. Do not echo general_dictionary/relations_checklist. '
                . 'Sense source defaults: source_dictionary=Baakh Lughat, publisher=baakh.com, prepared_by=Kamran Wahid, publisher_url=https://baakh.com/. '
                . 'Keep numeric ids. Paste back via Import JSON.',
            'id' => $lemma->id,
            'public_id' => $lemma->public_id,

            'poetry' => [
                ...$poetryContext,
                'token_hints' => $tokenHints,
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
     * Whitespace tokens of the source couplet so AI can propose span expressions.
     *
     * @return list<array{index:int,surface:string,lemma_candidate:string}>
     */
    private function buildTokenHints(array $poetryContext): array
    {
        $text = (string) ($poetryContext['source_couplet']['text'] ?? '');
        if ($text === '' && !empty($poetryContext['couplets'][0]['text'])) {
            $text = (string) $poetryContext['couplets'][0]['text'];
        }
        if ($text === '') {
            return [];
        }

        $parts = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $out = [];
        $index = 0;
        foreach ($parts as $raw) {
            $surface = DictionaryText::stripPunctuation($raw);
            $surface = trim($surface);
            if ($surface === '' || !preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}]/u', $surface)) {
                continue;
            }
            $out[] = [
                'index' => $index,
                'surface' => $surface,
                'lemma_candidate' => DictionaryText::stripDiacritics($surface),
            ];
            $index++;
        }

        return $out;
    }

    /**
     * Poetry / couplet source data for AI context (Sindhi only — no roman lines).
     */
    private function buildPoetryContext(LughatLemma $lemma): array
    {
        $context = [
            'poetry_id' => $lemma->poetry_id,
            'couplet_id' => $lemma->couplet_id,
            'title' => null,
            'source_couplet' => null,
            'couplets' => [],
        ];

        if ($lemma->couplet_id) {
            $source = Couplets::query()->find($lemma->couplet_id);
            if ($source) {
                $context['source_couplet'] = [
                    'id' => $source->id,
                    'text' => $this->plainText((string) $source->couplet_text),
                    'lang' => $source->lang,
                ];
            }
        }

        if (!$lemma->poetry_id) {
            return $context;
        }

        $poetry = Poetry::query()->find($lemma->poetry_id);
        if (!$poetry) {
            return $context;
        }

        $title = PoetryTranslations::query()
            ->where('poetry_id', $poetry->id)
            ->where(function ($q) {
                $q->whereNull('lang')->orWhere('lang', 'sd')->orWhere('lang', 'snd');
            })
            ->value('title');

        $context['title'] = $title ?: ($poetry->poetry_title ?? null);
        $context['poetry_slug'] = $poetry->poetry_slug;
        $context['poet_id'] = $poetry->poet_id;

        $context['couplets'] = Couplets::query()
            ->where('poetry_id', $poetry->id)
            ->where(function ($q) {
                $q->whereNull('lang')->orWhere('lang', 'sd')->orWhere('lang', 'snd');
            })
            ->orderBy('id')
            ->get()
            ->map(fn (Couplets $c) => [
                'id' => $c->id,
                'text' => $this->plainText((string) $c->couplet_text),
            ])
            ->values()
            ->all();

        return $context;
    }

    /**
     * Compact general-dictionary / Open Lexicon snapshot for AI sense generation.
     * Read-only context — stripped on import.
     *
     * @return array{found:bool, source:?string, word:?string, pos:?string, meanings:list<string>, meanings_en:list<string>, meanings_sd:list<string>, senses:list<array>}
     */
    private function buildGeneralDictionaryContext(LughatLemma $lemma): array
    {
        $word = trim((string) $lemma->lemma);
        $empty = [
            'found' => false,
            'source' => null,
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
            '_note' => 'Read-only. Use with poetry.* to write Baakh Lughat senses[] and typed relations[]. Do not echo this key in output.',
        ];

        if ($word === '') {
            return $empty;
        }

        $normalized = DictionaryText::normalizeForLookup($word);
        $dbLemma = Lemma::query()
            ->with([
                'senses' => fn ($q) => $q->orderBy('sense_order')->limit(12),
                'lemmaRelations',
            ])
            ->where(function ($q) use ($word, $normalized) {
                $q->where('lemma', $word)
                    ->orWhere('normalized_lemma', $normalized);
            })
            ->first();

        if ($dbLemma) {
            $senses = $dbLemma->senses->map(fn ($sense) => [
                'short_gloss' => $sense->short_gloss,
                'definition' => $sense->definition,
                'definition_sd' => $sense->definition_sd,
                'definition_en' => $sense->definition_en,
                'domain' => $sense->domain,
                'usage_label' => $sense->usage_label ?? $sense->register,
            ])->filter(fn ($s) => filled($s['definition']) || filled($s['short_gloss']) || filled($s['definition_sd']) || filled($s['definition_en']))
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
                $rw = trim((string) ($rel->related_word ?? ''));
                if ($rw !== '') {
                    $relationsByType[$type][] = $rw;
                }
            }
            foreach ($relationsByType as $type => $words) {
                $relationsByType[$type] = array_values(array_unique($words));
            }

            return [
                'found' => $senses !== [] || filled($dbLemma->pos) || collect($relationsByType)->flatten()->isNotEmpty(),
                'source' => 'site_dictionary',
                'word' => $dbLemma->lemma,
                'pos' => $dbLemma->pos,
                'meanings' => collect($senses)->pluck('definition')->filter()->unique()->take(8)->values()->all(),
                'meanings_en' => collect($senses)->pluck('definition_en')->filter()->unique()->take(8)->values()->all(),
                'meanings_sd' => collect($senses)->pluck('definition_sd')->filter()->unique()->take(8)->values()->all(),
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
                '_note' => 'Read-only. Merge into Baakh Lughat senses[] AND relations[] with correct relation_type. Do not dump everything as related. Do not echo this key in output.',
            ];
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

        return [
            'found' => true,
            'source' => $fallback['source'] ?? 'bundled_open_lexicon',
            'word' => $fallback['word'] ?? $word,
            'pos' => $fallback['pos'] ?? null,
            'meanings' => array_values(array_slice($fallback['meanings'] ?? [], 0, 8)),
            'meanings_en' => array_values(array_slice($fallback['meanings_en'] ?? [], 0, 8)),
            'meanings_sd' => array_values(array_slice($fallback['meanings_sd'] ?? [], 0, 8)),
            'senses' => $senses,
            'synonyms' => array_values($fallback['synonyms'] ?? []),
            'antonyms' => array_values($fallback['antonyms'] ?? []),
            'hypernyms' => array_values($fallback['hypernyms'] ?? []),
            'related' => array_values($fallback['related'] ?? []),
            'singular' => [],
            'plural' => [],
            'dialect' => [],
            'derived' => [],
            'usage' => [],
            '_note' => 'Read-only. Merge into Baakh Lughat senses[] AND relations[] with correct relation_type. Do not dump everything as related. Do not echo this key in output.',
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

    private function plainText(string $htmlOrText): string
    {
        $text = html_entity_decode(strip_tags($htmlOrText), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/\r\n?/", "\n", $text) ?? $text;

        return trim($text);
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
