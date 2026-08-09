<?php

namespace App\Services;

use App\Models\Lemma;
use App\Models\LughatIdiomaticExpression;
use App\Models\LughatInflection;
use App\Models\LughatLemma;
use App\Models\LughatMorphology;
use App\Models\LughatRelation;
use App\Models\LughatSense;
use App\Models\LughatSenseExample;
use App\Models\LughatVariant;
use App\Support\DictionaryText;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Copy a general-dictionary lemma into Baakh Lughat (data only — no delete, no poetry sync).
 */
class DictionaryToLughatCopyService
{
    /**
     * @return array{lughat_lemma: LughatLemma, created: bool, already_existed: bool}
     */
    public function copy(Lemma $source): array
    {
        $source->loadMissing([
            'senses.examples',
            'morphology',
            'variants',
            'lemmaRelations',
            'inflections',
            'idiomaticExpressions',
        ]);

        $normalized = $source->normalized_lemma
            ?: DictionaryText::normalizeForIdentity((string) $source->lemma);
        $lookupBase = $source->lookup_base
            ?: DictionaryText::lookupBase((string) $source->lemma);

        $existing = LughatLemma::query()
            ->where('language', 'sd')
            ->where('homograph_number', 1)
            ->where(function ($q) use ($source, $normalized) {
                $q->whereRaw(DictionaryText::binaryEquals('lemma'), [$source->lemma])
                    ->orWhereRaw(DictionaryText::binaryEquals('normalized_lemma'), [$normalized]);
            })
            ->first();

        if ($existing) {
            return [
                'lughat_lemma' => $existing->load(['senses']),
                'created' => false,
                'already_existed' => true,
            ];
        }

        $lughat = DB::transaction(function () use ($source, $normalized, $lookupBase) {
            $meta = is_array($source->metadata_json) ? $source->metadata_json : [];
            $meta['dictionary'] = $meta['dictionary'] ?? 'Baakh Lughat';
            $meta['version'] = $meta['version'] ?? '1';
            $meta['copied_from_general_dictionary'] = true;
            $meta['source_lemma_id'] = $source->id;
            $meta['source_public_id'] = $source->public_id;

            $lughat = LughatLemma::create([
                'lemma' => $source->lemma,
                'normalized_lemma' => $normalized,
                'lookup_base' => $lookupBase,
                'homograph_number' => 1,
                'language' => 'sd',
                'transliteration' => $source->transliteration,
                'romanization_status' => filled($source->transliteration) ? 'proposed' : 'proposed',
                'ipa' => $source->ipa,
                'phonetic' => $source->phonetic,
                'pronunciation_simple' => $source->pronunciation_simple,
                'audio_url' => $source->audio_url,
                'syllabification' => $source->syllabification,
                'pos' => $source->pos,
                'etymology' => $source->etymology,
                'notes' => $source->notes,
                'source_confidence' => $source->source_confidence,
                'search_keywords_json' => $source->search_keywords_json,
                'metadata_json' => $meta,
                'frequency' => $source->frequency,
                'status' => $source->status ?: 'pending',
                'completion_status' => LughatLemma::COMPLETION_PENDING,
                'completion_notes' => $source->completion_notes,
                'variants_reviewed' => (bool) $source->variants_reviewed,
                'examples_reviewed' => (bool) $source->examples_reviewed,
                'morphology_reviewed' => (bool) $source->morphology_reviewed,
                'pronunciation_reviewed' => (bool) $source->pronunciation_reviewed,
            ]);

            if ($source->morphology) {
                LughatMorphology::create([
                    'lemma_id' => $lughat->id,
                    'root' => $source->morphology->root,
                    'pattern' => $source->morphology->pattern,
                    'gender' => $source->morphology->gender,
                    'number' => $source->morphology->number,
                    'case' => $source->morphology->case,
                    'aspect' => $source->morphology->aspect,
                    'tense' => $source->morphology->tense,
                    'review_status' => $source->morphology->review_status,
                ]);
            }

            foreach ($source->senses as $sense) {
                $lughatSense = LughatSense::create([
                    'lemma_id' => $lughat->id,
                    'lexical_id' => $sense->lexical_id,
                    'entry_id' => $sense->entry_id,
                    'sense_order' => $sense->sense_order,
                    'definition' => $sense->definition,
                    'definition_en' => $sense->definition_en,
                    'english_equivalents' => $sense->english_equivalents,
                    'definition_sd' => $sense->definition_sd,
                    'short_gloss' => $sense->short_gloss,
                    'full_definition' => $sense->full_definition,
                    'usage_notes' => $sense->usage_notes,
                    'usage_label' => $sense->usage_label,
                    'part_of_speech' => $sense->part_of_speech,
                    'word_variant' => $sense->word_variant,
                    'domain' => $sense->domain,
                    'register' => $sense->register,
                    'dialect' => $sense->dialect,
                    'confidence' => $sense->confidence,
                    'language_direction' => $sense->language_direction ?: 'sindhi',
                    'source_dictionary' => $sense->source_dictionary ?: 'Baakh Lughat',
                    'source' => $sense->source ?: 'general_dictionary_copy',
                    'source_entry_id' => $sense->source_entry_id,
                    'publisher' => $sense->publisher ?: 'baakh.com',
                    'license' => $sense->license,
                    'import_version' => $sense->import_version,
                    'normalized_definition' => $sense->normalized_definition,
                    'extra' => $sense->extra,
                    'status' => $sense->status ?: 'pending',
                    'review_status' => $sense->review_status ?: 'unreviewed',
                ]);

                foreach ($sense->examples as $example) {
                    LughatSenseExample::create([
                        'sense_id' => $lughatSense->id,
                        'sentence' => $example->sentence,
                        'romanization' => $example->romanization,
                        'translation' => $example->translation,
                        'source' => $example->source,
                        'citation' => $example->citation,
                        'quality_flag' => $example->quality_flag ?? 'unreviewed',
                        'review_status' => $example->review_status ?? 'unreviewed',
                    ]);
                }
            }

            foreach ($source->variants as $variant) {
                LughatVariant::create([
                    'lemma_id' => $lughat->id,
                    'variant' => $variant->variant,
                    'normalized_variant' => $variant->normalized_variant,
                    'type' => $variant->type,
                    'romanization' => $variant->romanization,
                    'dialect' => $variant->dialect,
                    'note' => $variant->note,
                    'source' => $variant->source,
                    'source_entry_id' => $variant->source_entry_id,
                    'review_status' => $variant->review_status,
                ]);
            }

            foreach ($source->lemmaRelations as $relation) {
                LughatRelation::create([
                    'lemma_id' => $lughat->id,
                    'relation_type' => $relation->relation_type,
                    'related_word' => $relation->related_word,
                    'romanization' => $relation->romanization,
                    'note' => $relation->note,
                    'gloss' => $relation->gloss,
                    'part_of_speech' => $relation->part_of_speech,
                    'related_lemma_id' => null, // do not point at general dictionary ids
                    'source' => $relation->source ?: 'general_dictionary_copy',
                ]);
            }

            foreach ($source->inflections as $inflection) {
                LughatInflection::create([
                    'lemma_id' => $lughat->id,
                    'form' => $inflection->form,
                    'romanization' => $inflection->romanization,
                    'description' => $inflection->description,
                    'source' => $inflection->source,
                    'review_status' => $inflection->review_status,
                ]);
            }

            foreach ($source->idiomaticExpressions as $idiom) {
                LughatIdiomaticExpression::create([
                    'lemma_id' => $lughat->id,
                    'phrase' => $idiom->phrase,
                    'romanization' => $idiom->romanization,
                    'english_gloss' => $idiom->english_gloss,
                    'example_sindhi' => $idiom->example_sindhi,
                    'example_english' => $idiom->example_english,
                    'source' => $idiom->source,
                    'review_status' => $idiom->review_status,
                ]);
            }

            return $lughat->load(['senses', 'morphology', 'variants']);
        });

        Cache::forget('dictionary.lughat_keys.v2');
        Cache::forget('dictionary.lughat_stats.v2');

        if (!$lughat) {
            throw new RuntimeException('Failed to copy lemma into Baakh Lughat.');
        }

        return [
            'lughat_lemma' => $lughat,
            'created' => true,
            'already_existed' => false,
        ];
    }
}
