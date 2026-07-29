<?php

namespace App\Services;

use App\Models\Lemma;
use App\Models\LemmaIdiomaticExpression;
use App\Models\LemmaInflection;
use App\Models\LemmaRelation;
use App\Models\Morphology;
use App\Models\Sense;
use App\Models\SenseExample;
use App\Models\Variant;
use App\Support\DictionaryText;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DictionaryLemmaJsonImportService
{
    private const LEMMA_FIELDS = [
        'lemma',
        'normalized_lemma',
        'transliteration',
        'ipa',
        'phonetic',
        'pronunciation_simple',
        'audio_url',
        'syllabification',
        'pos',
        'etymology',
        'notes',
        'source_confidence',
        'search_keywords_json',
        'metadata_json',
        'status',
        'completion_notes',
        'variants_reviewed',
        'examples_reviewed',
        'morphology_reviewed',
        'pronunciation_reviewed',
    ];

    private const SENSE_FIELDS = [
        'lexical_id',
        'entry_id',
        'sense_order',
        'definition',
        'definition_en',
        'english_equivalents',
        'definition_sd',
        'short_gloss',
        'full_definition',
        'usage_notes',
        'usage_label',
        'part_of_speech',
        'word_variant',
        'domain',
        'register',
        'dialect',
        'confidence',
        'language_direction',
        'source_dictionary',
        'source',
        'source_entry_id',
        'publisher',
        'license',
        'import_version',
        'normalized_definition',
        'extra',
        'status',
        'review_status',
    ];

    private const MORPHOLOGY_FIELDS = [
        'root',
        'pattern',
        'gender',
        'number',
        'case',
        'aspect',
        'tense',
        'review_status',
    ];

    public function import(Lemma $lemma, array $payload): Lemma
    {
        $payload = app(DictionaryLemmaEditorJsonService::class)->normalizeForImport($payload);

        if (isset($payload['id']) && (int) $payload['id'] !== (int) $lemma->id) {
            throw new InvalidArgumentException('JSON id does not match this lemma.');
        }

        if (isset($payload['public_id']) && filled($lemma->public_id) && $payload['public_id'] !== $lemma->public_id) {
            throw new InvalidArgumentException('JSON public_id does not match this lemma.');
        }

        return DB::transaction(function () use ($lemma, $payload) {
            $this->applyLemmaFields($lemma, $payload);

            if (array_key_exists('completion_status', $payload) && filled($payload['completion_status'])) {
                $status = strtolower((string) $payload['completion_status']);
                if (in_array($status, ['complete', 'completed'], true)) {
                    $status = Lemma::COMPLETION_COMPLETE;
                } elseif ($status === 'pending') {
                    $status = Lemma::COMPLETION_PENDING;
                }

                if (in_array($status, [Lemma::COMPLETION_PENDING, Lemma::COMPLETION_COMPLETE], true)) {
                    $lemma->update([
                        'completion_status' => $status,
                        'completion_score' => $status === Lemma::COMPLETION_COMPLETE
                            ? (int) ($payload['completion_score'] ?? $lemma->completion_score ?? 100)
                            : (int) ($payload['completion_score'] ?? $lemma->completion_score ?? 0),
                    ]);
                }
            }

            if (array_key_exists('morphology', $payload)) {
                $this->syncMorphology($lemma, is_array($payload['morphology']) ? $payload['morphology'] : null);
            }

            if (array_key_exists('senses', $payload) && is_array($payload['senses'])) {
                $this->syncSenses($lemma, $payload['senses']);
            }

            if (array_key_exists('lemma_relations', $payload) && is_array($payload['lemma_relations'])) {
                $this->syncRelations($lemma, $payload['lemma_relations']);
            }

            if (array_key_exists('variants', $payload) && is_array($payload['variants'])) {
                $this->syncVariants($lemma, $payload['variants']);
            }

            if (array_key_exists('inflections', $payload) && is_array($payload['inflections'])) {
                $this->syncInflections($lemma, $payload['inflections']);
            }

            if (array_key_exists('idiomatic_expressions', $payload) && is_array($payload['idiomatic_expressions'])) {
                $this->syncIdioms($lemma, $payload['idiomatic_expressions']);
            }

            return $lemma->fresh([
                'senses.examples',
                'morphology',
                'variants',
                'lemmaRelations.relatedLemma',
                'inflections',
                'idiomaticExpressions',
            ]);
        });
    }

    private function applyLemmaFields(Lemma $lemma, array $payload): void
    {
        $updates = [];

        foreach (self::LEMMA_FIELDS as $field) {
            if (!array_key_exists($field, $payload)) {
                continue;
            }

            $value = $payload[$field];

            if (in_array($field, ['search_keywords_json', 'metadata_json'], true)) {
                $updates[$field] = is_array($value) ? $value : null;
                continue;
            }

            if (in_array($field, ['variants_reviewed', 'examples_reviewed', 'morphology_reviewed', 'pronunciation_reviewed'], true)) {
                $updates[$field] = (bool) $value;
                continue;
            }

            if ($field === 'source_confidence') {
                if ($value === '' || $value === null) {
                    $updates[$field] = null;
                } else {
                    $confidence = (float) $value;
                    if ($confidence > 0 && $confidence <= 1) {
                        $confidence *= 100;
                    }
                    $updates[$field] = max(0, min(100, $confidence));
                }
                continue;
            }

            if (is_string($value)) {
                $trimmed = trim($value);
                $updates[$field] = $trimmed === '' ? null : $trimmed;
                continue;
            }

            $updates[$field] = $value;
        }

        if ($updates !== []) {
            if (isset($updates['lemma']) && !array_key_exists('normalized_lemma', $updates)) {
                $updates['normalized_lemma'] = DictionaryText::normalizeForLookup($updates['lemma']);
            }

            $lemma->update($updates);

            if (!empty($updates['transliteration'])) {
                \App\Models\Romanizer::updateOrCreate(
                    ['word_sd' => $lemma->lemma],
                    [
                        'word_roman' => $updates['transliteration'],
                        'user_id' => auth()->id() ?? 1,
                    ]
                );
            }
        }
    }

    private function syncMorphology(Lemma $lemma, ?array $morphology): void
    {
        if ($morphology === null || $morphology === []) {
            $lemma->morphology()?->delete();

            return;
        }

        $payload = [];
        foreach (self::MORPHOLOGY_FIELDS as $field) {
            if (!array_key_exists($field, $morphology)) {
                continue;
            }
            $value = $morphology[$field];
            $payload[$field] = is_string($value) ? (trim($value) === '' ? null : trim($value)) : $value;
        }

        if ($payload === []) {
            return;
        }

        Morphology::updateOrCreate(
            ['lemma_id' => $lemma->id],
            $payload
        );
    }

    private function syncSenses(Lemma $lemma, array $senses): void
    {
        $keepIds = [];

        foreach (array_values($senses) as $index => $senseData) {
            if (!is_array($senseData)) {
                continue;
            }

            $sensePayload = $this->extractFields($senseData, self::SENSE_FIELDS);
            $sensePayload['lemma_id'] = $lemma->id;
            unset($sensePayload['public_id']);

            if (!isset($sensePayload['sense_order'])) {
                $sensePayload['sense_order'] = $index + 1;
            }

            if (!isset($sensePayload['status'])) {
                $sensePayload['status'] = 'pending';
            }

            if (!isset($sensePayload['review_status'])) {
                $sensePayload['review_status'] = 'unreviewed';
            }

            if (array_key_exists('english_equivalents', $sensePayload)) {
                $sensePayload['english_equivalents'] = $this->stringList($sensePayload['english_equivalents']);
            }

            if (array_key_exists('confidence', $sensePayload) && is_numeric($sensePayload['confidence'])) {
                $confidence = (float) $sensePayload['confidence'];
                if ($confidence > 0 && $confidence <= 1) {
                    $confidence *= 100;
                }
                $sensePayload['confidence'] = (int) max(0, min(100, round($confidence)));
            }

            if (array_key_exists('extra', $sensePayload) && is_string($sensePayload['extra'])) {
                $decoded = json_decode($sensePayload['extra'], true);
                $sensePayload['extra'] = is_array($decoded) ? $decoded : null;
            }

            if (array_key_exists('extra', $sensePayload) && !is_array($sensePayload['extra']) && $sensePayload['extra'] !== null) {
                $sensePayload['extra'] = null;
            }

            $sense = null;
            $senseId = $senseData['id'] ?? null;
            if (is_numeric($senseId)) {
                $sense = Sense::query()
                    ->where('lemma_id', $lemma->id)
                    ->where('id', (int) $senseId)
                    ->first();
            }

            if ($sense) {
                $sense->update($sensePayload);
            } else {
                if (
                    empty($sensePayload['definition'])
                    && empty($sensePayload['definition_en'])
                    && empty($sensePayload['definition_sd'])
                    && empty($sensePayload['short_gloss'])
                    && empty($sensePayload['full_definition'])
                ) {
                    continue;
                }
                if (empty($sensePayload['definition'])) {
                    $sensePayload['definition'] = $sensePayload['definition_en']
                        ?: $sensePayload['definition_sd']
                        ?: $sensePayload['short_gloss']
                        ?: $sensePayload['full_definition'];
                }
                $sense = Sense::create($sensePayload);
            }

            $keepIds[] = $sense->id;

            if (array_key_exists('examples', $senseData) && is_array($senseData['examples'])) {
                $this->syncExamples($sense, $senseData['examples']);
            }
        }

        $deleteQuery = Sense::query()->where('lemma_id', $lemma->id);
        if ($keepIds !== []) {
            $deleteQuery->whereNotIn('id', $keepIds);
        }
        $deleteQuery->delete();
    }

    private function syncExamples(Sense $sense, array $examples): void
    {
        $keepIds = [];

        foreach ($examples as $exampleData) {
            if (!is_array($exampleData)) {
                continue;
            }

            $sentence = trim((string) (
                $exampleData['sentence']
                ?? $exampleData['example_sindhi']
                ?? $exampleData['sindhi']
                ?? ''
            ));
            $translation = $this->nullableString(
                $exampleData['translation']
                ?? $exampleData['example_english']
                ?? $exampleData['english']
                ?? null
            );

            if ($sentence === '' && $translation) {
                $sentence = $translation;
                $translation = null;
            }

            if ($sentence === '') {
                continue;
            }

            $payload = [
                'sentence' => $sentence,
                'romanization' => $this->nullableString($exampleData['romanization'] ?? null),
                'translation' => $translation,
                'source' => $this->nullableString($exampleData['source'] ?? null),
                'citation' => $this->nullableString($exampleData['citation'] ?? null),
                'quality_flag' => $exampleData['quality_flag'] ?? 'unreviewed',
                'review_status' => $exampleData['review_status'] ?? 'unreviewed',
            ];

            $example = null;
            if (is_numeric($exampleData['id'] ?? null)) {
                $example = SenseExample::query()
                    ->where('sense_id', $sense->id)
                    ->where('id', (int) $exampleData['id'])
                    ->first();
            }

            if ($example) {
                $example->update($payload);
            } else {
                $example = SenseExample::create([
                    'sense_id' => $sense->id,
                    ...$payload,
                ]);
            }

            $keepIds[] = $example->id;
        }

        $deleteQuery = SenseExample::query()->where('sense_id', $sense->id);
        if ($keepIds !== []) {
            $deleteQuery->whereNotIn('id', $keepIds);
        }
        $deleteQuery->delete();
    }

    private function syncRelations(Lemma $lemma, array $relations): void
    {
        $keepIds = [];

        foreach ($relations as $row) {
            if (!is_array($row)) {
                continue;
            }

            $relatedWord = trim((string) (
                $row['related_word']
                ?? $row['related_lemma']
                ?? $row['word']
                ?? ''
            ));
            $relationType = $this->normalizeRelationType($row['relation_type'] ?? 'related');
            if ($relatedWord === '') {
                continue;
            }

            $payload = [
                'relation_type' => $relationType,
                'related_word' => $relatedWord,
                'romanization' => $this->nullableString($row['romanization'] ?? null),
                'note' => $this->nullableString($row['note'] ?? $row['definition'] ?? null),
                'gloss' => $this->nullableString($row['gloss'] ?? null),
                'part_of_speech' => $this->nullableString($row['part_of_speech'] ?? $row['related_pos'] ?? null),
                'related_lemma_id' => is_numeric($row['related_lemma_id'] ?? null) ? (int) $row['related_lemma_id'] : null,
                'source' => $this->nullableString($row['source'] ?? null),
            ];

            if ($payload['related_lemma_id']) {
                $relatedLemma = Lemma::find($payload['related_lemma_id']);
                if ($relatedLemma) {
                    $payload['related_word'] = $relatedLemma->lemma;
                    $payload['romanization'] = $payload['romanization'] ?: $relatedLemma->transliteration;
                    $payload['part_of_speech'] = $payload['part_of_speech'] ?: $relatedLemma->pos;
                } else {
                    $payload['related_lemma_id'] = null;
                }
            }

            $relation = null;
            if (is_numeric($row['id'] ?? null)) {
                $relation = LemmaRelation::query()
                    ->where('lemma_id', $lemma->id)
                    ->where('id', (int) $row['id'])
                    ->first();
            }

            if ($relation) {
                $relation->update($payload);
            } else {
                $relation = LemmaRelation::create([
                    'lemma_id' => $lemma->id,
                    ...$payload,
                ]);
            }

            $keepIds[] = $relation->id;
        }

        $deleteQuery = LemmaRelation::query()->where('lemma_id', $lemma->id);
        if ($keepIds !== []) {
            $deleteQuery->whereNotIn('id', $keepIds);
        }
        $deleteQuery->delete();
    }

    private function syncVariants(Lemma $lemma, array $variants): void
    {
        $manualVariants = array_values(array_filter($variants, function ($variant) {
            if (!is_array($variant)) {
                return false;
            }
            if (($variant['is_imported'] ?? false) === true) {
                return false;
            }
            if (($variant['source'] ?? null) === 'Imported' || ($variant['source'] ?? null) === 'Open Lexicon') {
                return false;
            }

            return filled($variant['variant'] ?? null);
        }));

        $keepIds = [];

        foreach ($manualVariants as $variantData) {
            $text = trim((string) $variantData['variant']);
            $type = $this->normalizeVariantType($variantData['type'] ?? 'spelling');

            $payload = [
                'normalized_variant' => DictionaryText::normalizeForLookup($text),
                'type' => $type,
                'romanization' => $this->nullableString($variantData['romanization'] ?? null),
                'dialect' => $this->nullableString($variantData['dialect'] ?? null),
                'note' => $this->nullableString($variantData['note'] ?? $variantData['definition'] ?? null),
                'source' => $this->nullableString(($variantData['source'] ?? null) === 'Manual' ? null : ($variantData['source'] ?? null)),
                'source_entry_id' => $this->nullableString($variantData['source_entry_id'] ?? null),
                'review_status' => $variantData['review_status'] ?? 'unreviewed',
            ];

            $variant = null;
            if (is_numeric($variantData['id'] ?? null)) {
                $variant = Variant::query()
                    ->where('lemma_id', $lemma->id)
                    ->where('id', (int) $variantData['id'])
                    ->first();
            }

            if (!$variant) {
                $variant = Variant::query()
                    ->where('lemma_id', $lemma->id)
                    ->whereRaw('variant = BINARY ?', [$text])
                    ->first();
            }

            if ($variant) {
                $variant->update($payload);
            } else {
                $variant = Variant::create([
                    'lemma_id' => $lemma->id,
                    'variant' => $text,
                    ...$payload,
                ]);
            }

            $keepIds[] = $variant->id;
        }

        $deleteQuery = Variant::query()->where('lemma_id', $lemma->id);
        if ($keepIds !== []) {
            $deleteQuery->whereNotIn('id', $keepIds);
        }
        $deleteQuery->delete();
    }

    private function syncInflections(Lemma $lemma, array $inflections): void
    {
        $keepIds = [];

        foreach ($inflections as $row) {
            if (!is_array($row)) {
                continue;
            }

            $form = trim((string) ($row['form'] ?? ''));
            if ($form === '') {
                continue;
            }

            $description = $this->nullableString(
                $row['description']
                ?? $row['definition']
                ?? $row['inflection_type']
                ?? null
            );
            if ($description === null && !empty($row['part_of_speech'])) {
                $description = trim((string) $row['part_of_speech']);
            }

            $payload = [
                'romanization' => $this->nullableString($row['romanization'] ?? $row['normalized_form'] ?? null),
                'description' => $description,
                'source' => $this->nullableString($row['source'] ?? null),
                'review_status' => $row['review_status'] ?? 'unreviewed',
            ];

            $inflection = null;
            if (is_numeric($row['id'] ?? null)) {
                $inflection = LemmaInflection::query()
                    ->where('lemma_id', $lemma->id)
                    ->where('id', (int) $row['id'])
                    ->first();
            }

            if (!$inflection) {
                $inflection = LemmaInflection::query()
                    ->where('lemma_id', $lemma->id)
                    ->whereRaw('form = BINARY ?', [$form])
                    ->first();
            }

            if ($inflection) {
                $inflection->update($payload);
            } else {
                $inflection = LemmaInflection::create([
                    'lemma_id' => $lemma->id,
                    'form' => $form,
                    ...$payload,
                ]);
            }

            $keepIds[] = $inflection->id;
        }

        $deleteQuery = LemmaInflection::query()->where('lemma_id', $lemma->id);
        if ($keepIds !== []) {
            $deleteQuery->whereNotIn('id', $keepIds);
        }
        $deleteQuery->delete();
    }

    private function syncIdioms(Lemma $lemma, array $idioms): void
    {
        $keepIds = [];

        foreach ($idioms as $row) {
            if (!is_array($row)) {
                continue;
            }

            $phrase = trim((string) (
                $row['phrase']
                ?? $row['expression']
                ?? $row['normalized_expression']
                ?? ''
            ));
            if ($phrase === '') {
                continue;
            }

            $englishGloss = $this->nullableString(
                $row['english_gloss']
                ?? $row['meaning_en']
                ?? $row['meaning']
                ?? null
            );

            $payload = [
                'romanization' => $this->nullableString($row['romanization'] ?? null),
                'english_gloss' => $englishGloss,
                'example_sindhi' => $this->nullableString($row['example_sindhi'] ?? null),
                'example_english' => $this->nullableString($row['example_english'] ?? null),
                'source' => $this->nullableString($row['source'] ?? $row['usage_label'] ?? null),
                'review_status' => $row['review_status'] ?? 'unreviewed',
            ];

            if ($englishGloss === null && !empty($row['meaning_sd']) && empty($row['example_sindhi'])) {
                $payload['english_gloss'] = $this->nullableString($row['meaning_sd']);
            }

            $expression = null;
            if (is_numeric($row['id'] ?? null)) {
                $expression = LemmaIdiomaticExpression::query()
                    ->where('lemma_id', $lemma->id)
                    ->where('id', (int) $row['id'])
                    ->first();
            }

            if (!$expression) {
                $expression = LemmaIdiomaticExpression::query()
                    ->where('lemma_id', $lemma->id)
                    ->whereRaw('phrase = BINARY ?', [$phrase])
                    ->first();
            }

            if ($expression) {
                $expression->update($payload);
            } else {
                $expression = LemmaIdiomaticExpression::create([
                    'lemma_id' => $lemma->id,
                    'phrase' => $phrase,
                    ...$payload,
                ]);
            }

            $keepIds[] = $expression->id;
        }

        $deleteQuery = LemmaIdiomaticExpression::query()->where('lemma_id', $lemma->id);
        if ($keepIds !== []) {
            $deleteQuery->whereNotIn('id', $keepIds);
        }
        $deleteQuery->delete();
    }

    private function extractFields(array $source, array $fields): array
    {
        $payload = [];

        foreach ($fields as $field) {
            if (!array_key_exists($field, $source)) {
                continue;
            }

            $value = $source[$field];
            if (is_string($value)) {
                $trimmed = trim($value);
                $payload[$field] = $trimmed === '' ? null : $trimmed;
            } else {
                $payload[$field] = $value;
            }
        }

        return $payload;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function stringList(mixed $value): array
    {
        if (is_string($value)) {
            return array_values(array_filter(array_map('trim', preg_split('/[,;]+/', $value) ?: [])));
        }

        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($item) => is_scalar($item) ? trim((string) $item) : '',
            $value
        )));
    }

    private function normalizeRelationType(mixed $type): string
    {
        $normalized = strtolower(trim((string) $type));
        $map = [
            'synonym' => 'synonym',
            'antonym' => 'antonym',
            'hypernym' => 'hypernym',
            'related' => 'related',
            'related_term' => 'related',
            'related-term' => 'related',
            'phrasal_verb' => 'related',
            'singular' => 'singular',
            'singular_of' => 'singular',
            'singular-of' => 'singular',
            'plural' => 'plural',
            'plural_of' => 'plural',
            'plural-of' => 'plural',
            'dialect' => 'dialect',
            'dialectal' => 'dialect',
            'dialect_form' => 'dialect',
            'derived' => 'derived',
            'derivative' => 'derived',
            'derived_form' => 'derived',
            'nisba' => 'derived',
            'usage' => 'usage',
            'people_say' => 'usage',
            'people-say' => 'usage',
            'form' => 'usage',
            'first_form' => 'usage',
            'second_form' => 'usage',
        ];

        return $map[$normalized] ?? 'related';
    }

    private function normalizeVariantType(mixed $type): string
    {
        $normalized = strtolower(trim((string) $type));
        if (in_array($normalized, Variant::TYPES, true)) {
            return $normalized;
        }

        $map = [
            'misspelling' => 'misspelling',
            'misspellings' => 'misspelling',
            'spelling' => 'spelling',
            'standard' => 'spelling',
            'base form' => 'normalized',
            'base_form' => 'normalized',
            'normalized' => 'normalized',
            'abbreviation' => 'spelling',
            'abbreviation of singular base' => 'spelling',
            'dialectal' => 'dialectal',
            'historical' => 'historical',
            'diacritic' => 'diacritic',
        ];

        return $map[$normalized] ?? 'spelling';
    }
}
