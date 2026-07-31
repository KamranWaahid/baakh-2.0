<?php

namespace App\Services;

use App\Models\LughatLemma;
use App\Models\LughatIdiomaticExpression;
use App\Models\LughatInflection;
use App\Models\LughatRelation;
use App\Models\LughatMorphology;
use App\Models\LughatSense;
use App\Models\LughatSenseExample;
use App\Models\LughatVariant;
use App\Models\LughatWordForm;
use App\Support\DictionaryText;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LughatLemmaJsonImportService
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

    public function import(LughatLemma $lemma, array $payload): LughatLemma
    {
        $payload = app(LughatLemmaEditorJsonService::class)->normalizeForImport($payload);

        if (isset($payload['id']) && (int) $payload['id'] !== (int) $lemma->id) {
            throw new InvalidArgumentException('JSON id does not match this lemma.');
        }

        if (isset($payload['public_id']) && filled($lemma->public_id) && $payload['public_id'] !== $lemma->public_id) {
            throw new InvalidArgumentException('JSON public_id does not match this lemma.');
        }

        return DB::transaction(function () use ($lemma, $payload) {
            $beforeRoman = $lemma->transliteration;
            $replaceMissing = (bool) ($payload['_replace_missing'] ?? false);
            $publishRoman = (bool) ($payload['publish_romanization'] ?? false);

            $this->applyLemmaFields($lemma, $payload);

            // Never trust AI completion_status alone — re-evaluate after sync below.
            if (array_key_exists('completion_notes', $payload) && is_string($payload['completion_notes'])) {
                $lemma->update(['completion_notes' => $payload['completion_notes']]);
            }

            if (array_key_exists('morphology', $payload)) {
                $this->syncMorphology($lemma, is_array($payload['morphology']) ? $payload['morphology'] : null);
            }

            if (array_key_exists('senses', $payload) && is_array($payload['senses'])) {
                $this->syncSenses($lemma, $payload['senses'], $replaceMissing);
            }

            if (array_key_exists('lemma_relations', $payload) && is_array($payload['lemma_relations'])) {
                $this->syncRelations($lemma, $payload['lemma_relations'], $replaceMissing);
            }

            if (array_key_exists('variants', $payload) && is_array($payload['variants'])) {
                $this->syncVariants($lemma, $payload['variants'], $replaceMissing);
            }

            if (array_key_exists('inflections', $payload) && is_array($payload['inflections'])) {
                $this->syncInflections($lemma, $payload['inflections'], $replaceMissing);
            }

            if (array_key_exists('idiomatic_expressions', $payload) && is_array($payload['idiomatic_expressions'])) {
                $this->syncIdioms($lemma, $payload['idiomatic_expressions'], $replaceMissing);
            }

            $expressionRows = [];
            if (array_key_exists('expression_candidates', $payload) && is_array($payload['expression_candidates'])) {
                $expressionRows = array_merge($expressionRows, $payload['expression_candidates']);
            }
            if (array_key_exists('expressions', $payload) && is_array($payload['expressions'])) {
                $expressionRows = array_merge($expressionRows, $payload['expressions']);
            }
            if ($expressionRows !== []) {
                $this->syncExpressions($lemma, $expressionRows);
            }

            $fresh = $lemma->fresh([
                'senses.examples',
                'morphology',
                'variants',
                'lemmaRelations.relatedLemma',
                'inflections',
                'idiomaticExpressions',
            ]);

            // AI roman → Romanizer (always as proposed); poetry EN only when publishing.
            if ($fresh && filled($fresh->transliteration) && $fresh->transliteration !== $beforeRoman) {
                $romanizer = app(RomanizerService::class);
                $romanizer->upsert($fresh->lemma, $fresh->transliteration, auth()->id() ?? 1);
                $fresh->update(['romanization_status' => $publishRoman ? 'approved' : 'proposed']);
                try {
                    $romanizer->refreshDictionaryFile();
                } catch (\Throwable) {
                    // best-effort
                }
                if ($publishRoman || $fresh->romanization_status === 'approved') {
                    app(PoetryRomanSyncService::class)->syncFromLemma($fresh->fresh());
                    $fresh->update(['romanization_status' => 'published']);
                }
            }

            // Server checklist is source of truth — AI cannot mark complete with empty boxes.
            if ($fresh) {
                $checklist = app(LughatCompletionService::class)->evaluate($fresh);
                $notes = filled($fresh->completion_notes) ? (string) $fresh->completion_notes : '';
                if (!$checklist['is_complete'] && !empty($checklist['missing_requirements'])) {
                    $gaps = collect($checklist['missing_requirements'])
                        ->pluck('message')
                        ->filter()
                        ->take(8)
                        ->implode(' · ');
                    if ($gaps !== '') {
                        $notes = trim($notes === '' ? $gaps : ($notes."\n".$gaps));
                    }
                }
                $fresh->update([
                    'completion_status' => $checklist['status'],
                    'completion_score' => $checklist['score'],
                    'checklist_json' => $checklist,
                    'completion_notes' => $notes !== '' ? $notes : $fresh->completion_notes,
                    'completed_at' => $checklist['is_complete'] ? ($fresh->completed_at ?: now()) : null,
                    'completed_by' => $checklist['is_complete']
                        ? ($fresh->completed_by ?: (auth()->id() ?? null))
                        : null,
                ]);
                $fresh = $fresh->fresh([
                    'senses.examples',
                    'morphology',
                    'variants',
                    'lemmaRelations.relatedLemma',
                    'inflections',
                    'idiomaticExpressions',
                ]);
            }

            return $fresh;
        });
    }

    private function applyLemmaFields(LughatLemma $lemma, array $payload): void
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
                if ($field === 'lemma') {
                    $trimmed = DictionaryText::stripPunctuation($trimmed);
                } elseif (in_array($field, ['etymology', 'notes'], true)) {
                    $trimmed = trim(DictionaryText::stripGuillemets($trimmed));
                }
                $updates[$field] = $trimmed === '' ? null : $trimmed;
                continue;
            }

            $updates[$field] = $value;
        }

        if ($updates !== []) {
            if (isset($updates['lemma']) && !array_key_exists('normalized_lemma', $updates)) {
                $updates['normalized_lemma'] = DictionaryText::normalizeForIdentity($updates['lemma']);
                $updates['lookup_base'] = DictionaryText::lookupBase($updates['lemma']);
            }

            $lemma->update($updates);

            if (!empty($updates['transliteration'])) {
                app(RomanizerService::class)->upsert(
                    $lemma->lemma,
                    $updates['transliteration'],
                    auth()->id() ?? 1
                );
            }
        }
    }

    private function syncMorphology(LughatLemma $lemma, ?array $morphology): void
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

        LughatMorphology::updateOrCreate(
            ['lemma_id' => $lemma->id],
            $payload
        );
    }

    private function syncSenses(LughatLemma $lemma, array $senses, bool $replaceMissing = false): void
    {
        $keepIds = [];

        foreach (array_values($senses) as $index => $senseData) {
            if (!is_array($senseData)) {
                continue;
            }

            $sensePayload = $this->extractFields($senseData, self::SENSE_FIELDS);
            $sensePayload['lemma_id'] = $lemma->id;
            unset($sensePayload['public_id']);

            foreach (['definition', 'definition_sd', 'definition_en', 'short_gloss', 'full_definition', 'usage_notes', 'notes'] as $textField) {
                if (isset($sensePayload[$textField]) && is_string($sensePayload[$textField])) {
                    $sensePayload[$textField] = trim(DictionaryText::stripGuillemets($sensePayload[$textField]));
                    if ($sensePayload[$textField] === '') {
                        $sensePayload[$textField] = null;
                    }
                }
            }

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

            $sensePayload = $this->applyBaakhSenseSourceDefaults($sensePayload, $senseData);

            $sense = null;
            $senseId = $senseData['id'] ?? null;
            if (is_numeric($senseId)) {
                $sense = LughatSense::query()
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
                $sense = LughatSense::create($sensePayload);
            }

            $keepIds[] = $sense->id;

            if (array_key_exists('examples', $senseData) && is_array($senseData['examples'])) {
                $this->syncExamples($sense, $senseData['examples']);
            }
        }

        $deleteQuery = LughatSense::query()->where('lemma_id', $lemma->id);
        if ($keepIds !== []) {
            $deleteQuery->whereNotIn('id', $keepIds);
        }
        if ($replaceMissing) {
            $deleteQuery->delete();
        }
    }

    private function syncExamples(LughatSense $sense, array $examples): void
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
                'example_type' => $exampleData['example_type']
                    ?? (($exampleData['poetry_id'] ?? null) || ($exampleData['couplet_id'] ?? null)
                        ? 'poetry_citation'
                        : 'editorial'),
                'poetry_id' => is_numeric($exampleData['poetry_id'] ?? null) ? (int) $exampleData['poetry_id'] : null,
                'couplet_id' => is_numeric($exampleData['couplet_id'] ?? null) ? (int) $exampleData['couplet_id'] : null,
            ];

            $example = null;
            if (is_numeric($exampleData['id'] ?? null)) {
                $example = LughatSenseExample::query()
                    ->where('sense_id', $sense->id)
                    ->where('id', (int) $exampleData['id'])
                    ->first();
            }

            if ($example) {
                $example->update($payload);
            } else {
                $example = LughatSenseExample::create([
                    'sense_id' => $sense->id,
                    ...$payload,
                ]);
            }

            $keepIds[] = $example->id;
        }

        $deleteQuery = LughatSenseExample::query()->where('sense_id', $sense->id);
        if ($keepIds !== []) {
            $deleteQuery->whereNotIn('id', $keepIds);
        }
        $deleteQuery->delete();
    }

    private function syncRelations(LughatLemma $lemma, array $relations, bool $replaceMissing = false): void
    {
        $keepIds = [];

        foreach ($relations as $row) {
            if (!is_array($row)) {
                continue;
            }

            $relatedWord = DictionaryText::stripPunctuation(trim((string) (
                $row['related_word']
                ?? $row['related_lemma']
                ?? $row['word']
                ?? ''
            )));
            // Keep multi-word relations (هڪ آدمي) — stripPunctuation only removes marks.
            $relatedWord = trim(DictionaryText::stripGuillemets($relatedWord));
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
                $relatedLemma = LughatLemma::find($payload['related_lemma_id']);
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
                $relation = LughatRelation::query()
                    ->where('lemma_id', $lemma->id)
                    ->where('id', (int) $row['id'])
                    ->first();
            }

            if ($relation) {
                $relation->update($payload);
            } else {
                $relation = LughatRelation::create([
                    'lemma_id' => $lemma->id,
                    ...$payload,
                ]);
            }

            $keepIds[] = $relation->id;
        }

        $deleteQuery = LughatRelation::query()->where('lemma_id', $lemma->id);
        if ($keepIds !== []) {
            $deleteQuery->whereNotIn('id', $keepIds);
        }
        if ($replaceMissing) {
            $deleteQuery->delete();
        }
    }

    private function syncVariants(LughatLemma $lemma, array $variants, bool $replaceMissing = false): void
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

            return filled($variant['variant'] ?? $variant['form'] ?? $variant['surface'] ?? null);
        }));

        $keepIds = [];

        foreach ($manualVariants as $variantData) {
            // Keep airab; strip wrappers like «عِشْق» → عِشْق
            $text = DictionaryText::stripPunctuation(trim((string) (
                $variantData['variant'] ?? $variantData['form'] ?? $variantData['surface']
            )));
            $type = $this->normalizeVariantType($variantData['type'] ?? $variantData['variant_type'] ?? 'spelling');
            if ($text === '') {
                continue;
            }

            $normalizedOverride = $variantData['normalized_variant']
                ?? $variantData['normalized_form']
                ?? $variantData['normalized']
                ?? null;

            $payload = [
                'normalized_variant' => DictionaryText::normalizeForLookup(
                    filled($normalizedOverride) ? (string) $normalizedOverride : $text
                ),
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
                $variant = LughatVariant::query()
                    ->where('lemma_id', $lemma->id)
                    ->where('id', (int) $variantData['id'])
                    ->first();
            }

            if (!$variant) {
                $variant = LughatVariant::query()
                    ->where('lemma_id', $lemma->id)
                    ->whereRaw('variant = BINARY ?', [$text])
                    ->first();
            }

            if ($variant) {
                $variant->update($payload);
            } else {
                $variant = LughatVariant::create([
                    'lemma_id' => $lemma->id,
                    'variant' => $text,
                    ...$payload,
                ]);
            }

            $keepIds[] = $variant->id;
        }

        $deleteQuery = LughatVariant::query()->where('lemma_id', $lemma->id);
        if ($keepIds !== []) {
            $deleteQuery->whereNotIn('id', $keepIds);
        }
        if ($replaceMissing) {
            $deleteQuery->delete();
        }
    }

    private function syncInflections(LughatLemma $lemma, array $inflections, bool $replaceMissing = false): void
    {
        $keepIds = [];

        foreach ($inflections as $row) {
            if (!is_array($row)) {
                continue;
            }

            $form = trim((string) ($row['form'] ?? $row['surface'] ?? ''));
            if ($form === '') {
                continue;
            }

            $analysis = is_array($row['analysis'] ?? null) ? $row['analysis'] : [];
            $normalized = DictionaryText::normalizeForLookup(
                (string) ($row['normalized_form'] ?? $row['normalized'] ?? $form)
            );

            $confidence = null;
            if (isset($row['confidence']) && is_numeric($row['confidence'])) {
                $c = (float) $row['confidence'];
                if ($c > 0 && $c <= 1) {
                    $c *= 100;
                }
                $confidence = (int) max(0, min(100, round($c)));
            }

            $payload = [
                'normalized_form' => $normalized,
                'romanization' => $this->nullableString($row['romanization'] ?? null),
                'form_type' => $this->nullableString($row['form_type'] ?? $analysis['form_type'] ?? 'inflected') ?: 'inflected',
                'gender' => $this->nullableString($row['gender'] ?? $analysis['gender'] ?? null),
                'number' => $this->nullableString($row['number'] ?? $analysis['number'] ?? null),
                'case_name' => $this->nullableString($row['case'] ?? $row['case_name'] ?? $analysis['case'] ?? null),
                'person' => isset($row['person']) || isset($analysis['person'])
                    ? (string) ($row['person'] ?? $analysis['person'])
                    : null,
                'stem' => $this->nullableString($row['stem'] ?? $analysis['stem'] ?? null),
                'suffix' => $this->nullableString($row['suffix'] ?? $analysis['suffix'] ?? null),
                'analysis_json' => $analysis !== [] ? $analysis : null,
                'confidence' => $confidence,
                'description' => $this->nullableString($row['description'] ?? $row['definition'] ?? null),
                'source' => $this->nullableString($row['source'] ?? null),
                'review_status' => $row['review_status'] ?? 'unreviewed',
            ];

            $inflection = null;
            if (is_numeric($row['id'] ?? null)) {
                $inflection = LughatInflection::query()
                    ->where('lemma_id', $lemma->id)
                    ->where('id', (int) $row['id'])
                    ->first();
            }

            if (!$inflection) {
                $inflection = LughatInflection::query()
                    ->where('lemma_id', $lemma->id)
                    ->where('normalized_form', $normalized)
                    ->first();
            }

            if ($inflection) {
                $inflection->update($payload + ['form' => $form]);
            } else {
                $inflection = LughatInflection::create($payload + [
                    'lemma_id' => $lemma->id,
                    'form' => $form,
                ]);
            }

            $wordForm = LughatWordForm::query()->where('normalized_form', $normalized)->first();
            if (!$wordForm) {
                $wordForm = LughatWordForm::create([
                    'lemma_id' => $lemma->id,
                    'form' => $form,
                    'normalized_form' => $normalized,
                    'romanization' => $payload['romanization'],
                    'form_type' => $payload['form_type'] === 'lemma'
                        ? LughatWordForm::TYPE_LEMMA
                        : LughatWordForm::TYPE_INFLECTED,
                    'status' => LughatWordForm::STATUS_LINKED,
                    'morph_features_json' => array_filter([
                        'gender' => $payload['gender'],
                        'number' => $payload['number'],
                        'case' => $payload['case_name'],
                        'person' => $payload['person'],
                        'stem' => $payload['stem'],
                        'suffix' => $payload['suffix'],
                    ]),
                    'source' => 'ai_import',
                    'confidence' => $payload['confidence'],
                ]);
            } else {
                $wordForm->update([
                    'lemma_id' => $lemma->id,
                    'romanization' => $payload['romanization'] ?: $wordForm->romanization,
                    'form_type' => $payload['form_type'] === 'lemma'
                        ? LughatWordForm::TYPE_LEMMA
                        : LughatWordForm::TYPE_INFLECTED,
                    'status' => LughatWordForm::STATUS_LINKED,
                ]);
            }
            $inflection->update(['word_form_id' => $wordForm->id]);

            $keepIds[] = $inflection->id;
        }

        $deleteQuery = LughatInflection::query()->where('lemma_id', $lemma->id);
        if ($keepIds !== []) {
            $deleteQuery->whereNotIn('id', $keepIds);
        }
        if ($replaceMissing) {
            $deleteQuery->delete();
        }
    }

    /**
     * Upsert multiword poetic expressions from AI candidates / forms.expressions.
     * Always enters as pending review — never auto-approves.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    private function syncExpressions(LughatLemma $lemma, array $rows): void
    {
        $service = app(LughatExpressionService::class);

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $surface = trim((string) (
                $row['surface']
                ?? $row['expression']
                ?? $row['phrase']
                ?? ''
            ));
            if ($surface === '') {
                continue;
            }

            $type = (string) ($row['type'] ?? $row['expression_type'] ?? 'izafat');
            if (!in_array($type, \App\Models\LughatExpression::TYPES, true)) {
                $type = 'other';
            }

            $components = [];
            if (isset($row['components']) && is_array($row['components'])) {
                $components = $row['components'];
            } else {
                $components = $service->inferComponentsFromExpression($surface, $type);
                // Ensure current lemma is linked when it matches a component
                foreach ($components as &$comp) {
                    $norm = DictionaryText::normalizeForLookup((string) ($comp['surface_form'] ?? ''));
                    if ($norm === $lemma->normalized_lemma) {
                        $comp['lemma_id'] = $lemma->id;
                    }
                }
                unset($comp);
            }

            $expression = $service->upsert([
                'expression' => $surface,
                'expression_type' => $type,
                'romanization' => $row['romanization'] ?? $row['roman_search_key'] ?? null,
                'literal_gloss' => $row['literal_gloss'] ?? null,
                'poetic_gloss' => $row['poetic_gloss'] ?? $row['poetic_interpretation'] ?? null,
                'definition_sd' => $row['definition_sd'] ?? null,
                'definition_en' => $row['definition_en'] ?? null,
                'register' => $row['register'] ?? 'poetic',
                'status' => 'pending',
                'review_status' => 'unreviewed',
                'confidence' => $row['confidence'] ?? null,
                'metadata_json' => [
                    'source' => 'ai_import',
                    'start_token' => $row['start_token'] ?? $row['start_token_index'] ?? null,
                    'end_token' => $row['end_token'] ?? $row['end_token_index'] ?? null,
                    'lemma_id' => $lemma->id,
                ],
                'components' => $components,
            ]);

            $coupletId = is_numeric($row['couplet_id'] ?? null)
                ? (int) $row['couplet_id']
                : ($lemma->couplet_id ? (int) $lemma->couplet_id : null);
            $poetryId = is_numeric($row['poetry_id'] ?? null)
                ? (int) $row['poetry_id']
                : ($lemma->poetry_id ? (int) $lemma->poetry_id : null);
            $start = $row['start_token'] ?? $row['start_token_index'] ?? null;
            $end = $row['end_token'] ?? $row['end_token_index'] ?? null;

            if ($poetryId && $coupletId && is_numeric($start) && is_numeric($end)) {
                $service->recordOccurrence(
                    $expression,
                    $poetryId,
                    $coupletId,
                    (int) $start,
                    (int) $end,
                    $surface,
                    'ai',
                    isset($row['confidence']) && is_numeric($row['confidence'])
                        ? (int) (round(((float) $row['confidence']) <= 1 ? ((float) $row['confidence']) * 100 : (float) $row['confidence']))
                        : 70
                );
            }
        }
    }

    private function syncIdioms(LughatLemma $lemma, array $idioms, bool $replaceMissing = false): void
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
                $expression = LughatIdiomaticExpression::query()
                    ->where('lemma_id', $lemma->id)
                    ->where('id', (int) $row['id'])
                    ->first();
            }

            if (!$expression) {
                $expression = LughatIdiomaticExpression::query()
                    ->where('lemma_id', $lemma->id)
                    ->whereRaw('phrase = BINARY ?', [$phrase])
                    ->first();
            }

            if ($expression) {
                $expression->update($payload);
            } else {
                $expression = LughatIdiomaticExpression::create([
                    'lemma_id' => $lemma->id,
                    'phrase' => $phrase,
                    ...$payload,
                ]);
            }

            $keepIds[] = $expression->id;
        }

        $deleteQuery = LughatIdiomaticExpression::query()->where('lemma_id', $lemma->id);
        if ($keepIds !== []) {
            $deleteQuery->whereNotIn('id', $keepIds);
        }
        if ($replaceMissing) {
            $deleteQuery->delete();
        }
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

    /**
     * Baakh Lughat Open Lexicon Source defaults + fold publisher_url / prepared_by into extra.
     */
    private function applyBaakhSenseSourceDefaults(array $sensePayload, array $senseData): array
    {
        if (empty($sensePayload['source_dictionary'])) {
            $sensePayload['source_dictionary'] = 'Baakh Lughat';
        }
        if (empty($sensePayload['source'])) {
            $sensePayload['source'] = 'Baakh Lughat';
        }
        if (empty($sensePayload['publisher'])) {
            $sensePayload['publisher'] = 'baakh.com';
        }

        $extra = is_array($sensePayload['extra'] ?? null) ? $sensePayload['extra'] : [];

        $preparedBy = $senseData['prepared_by']
            ?? $extra['prepared_by']
            ?? null;
        $publisherUrl = $senseData['publisher_url']
            ?? $extra['publisher_url']
            ?? null;

        $extra['prepared_by'] = filled($preparedBy) ? (string) $preparedBy : 'Kamran Wahid';
        $extra['publisher_url'] = filled($publisherUrl) ? (string) $publisherUrl : 'https://baakh.com/';

        $sensePayload['extra'] = $extra;

        return $sensePayload;
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
        if (in_array($normalized, LughatVariant::TYPES, true)) {
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
            'diacritic_airab' => 'diacritic',
            'diacritic/airab' => 'diacritic',
            'airab' => 'diacritic',
            'fully_voweled' => 'fully_voweled_variant',
            'fully_vowelled' => 'fully_voweled_variant',
            'fully_vowelled_variant' => 'fully_voweled_variant',
            'short_vowel' => 'short_vowel_variant',
            'short_vowels' => 'short_vowel_variant',
            'fatha' => 'fatha_variant',
        ];

        return $map[$normalized] ?? 'spelling';
    }
}
