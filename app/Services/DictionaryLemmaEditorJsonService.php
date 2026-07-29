<?php

namespace App\Services;

use App\Models\Lemma;
use App\Models\LemmaRelation;
use App\Models\Sense;
use App\Models\Variant;

/**
 * Builds / normalizes the editor-form JSON used by the dictionary eye modal.
 * Structure mirrors SenseEditor tabs: General, Completion, Morphology, Senses,
 * Relations, Variants, Forms (inflections + idioms).
 */
class DictionaryLemmaEditorJsonService
{
    public function build(Lemma $lemma): array
    {
        $lemma->loadMissing([
            'senses.examples',
            'morphology',
            'variants',
            'lemmaRelations.relatedLemma',
            'inflections',
            'idiomaticExpressions',
        ]);

        $keywords = is_array($lemma->search_keywords_json) ? $lemma->search_keywords_json : [];
        $metadata = is_array($lemma->metadata_json) ? $lemma->metadata_json : [];
        $primarySense = $lemma->senses->first();

        return [
            '_schema' => 'baakh.dictionary.editor_json.v1',
            '_instructions' => 'Edit only these editor fields. Keep numeric ids when present so existing rows update. Paste back via Input JSON → Submit & Rewrite. '
                . 'ROMAN ONLY: transliteration and all romanization fields = plain Latin a–z/spaces/hyphens. '
                . 'No Arabic script; no zabar/zer/pesh (َُِ), tashdeed (ّ), sukun (ْ), or accented Latin (āīū). e.g. aadmi not ādmī. '
                . 'PRIMARY DEFINITIONS IN SINDHI: sense.definition and short_gloss MUST be Sindhi; English only in definition_en.',
            'id' => $lemma->id,
            'public_id' => $lemma->public_id,

            'general' => [
                'lemma' => $lemma->lemma,
                'normalized_lemma' => $lemma->normalized_lemma,
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
                ->map(fn (Sense $sense) => [
                    'id' => $sense->id,
                    'definition' => $sense->definition,
                    'short_gloss' => $sense->short_gloss,
                    'definition_sd' => $sense->definition_sd,
                    'definition_en' => $sense->definition_en,
                    'english_equivalents' => is_array($sense->english_equivalents) ? $sense->english_equivalents : [],
                    'usage_label' => $sense->usage_label,
                    'domain' => $sense->domain,
                    'language_direction' => $sense->language_direction,
                    'source_dictionary' => $sense->source_dictionary ?: $sense->source,
                    'review_status' => $sense->review_status,
                    'examples' => $sense->examples
                        ->map(fn ($example) => [
                            'id' => $example->id,
                            'sentence' => $example->sentence,
                            'romanization' => $example->romanization,
                            'translation' => $example->translation,
                            'source' => $example->source,
                            'citation' => $example->citation,
                            'quality_flag' => $example->quality_flag,
                            'review_status' => $example->review_status,
                        ])
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),

            'relations' => $lemma->lemmaRelations
                ->map(fn (LemmaRelation $relation) => [
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

            'variants' => $lemma->variants
                ->map(fn (Variant $variant) => [
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
                        'romanization' => $inflection->romanization,
                        'description' => $inflection->description,
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
            ],
        ];
    }

    /**
     * Convert editor-tab JSON (or legacy flat lemma dump) into the flat import shape.
     */
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
        }

        $primaryMeanings = is_array($general['primary_meanings'] ?? null) ? $general['primary_meanings'] : null;
        if ($primaryMeanings !== null) {
            $flat['senses'] = $flat['senses'] ?? [];
            if ($flat['senses'] === []) {
                $flat['senses'][] = [
                    'definition' => $primaryMeanings['definition'] ?? '',
                    'definition_sd' => $primaryMeanings['definition_sd'] ?? null,
                    'definition_en' => $primaryMeanings['definition_en'] ?? null,
                    'review_status' => 'unreviewed',
                ];
            } else {
                foreach (['definition', 'definition_sd', 'definition_en'] as $field) {
                    if (array_key_exists($field, $primaryMeanings)) {
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
