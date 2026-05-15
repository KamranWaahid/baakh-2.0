<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lemma;
use App\Services\StructuredDictionaryEntryService;
use App\Support\DictionaryText;

class WordLookupController extends Controller
{
    /**
     * Look up a single word in the dictionary.
     * Tries exact match first, then strips Arabic diacritics to find a match.
     */
    public function lookup(string $word)
    {
        $word = trim($word);
        $with = ['morphology', 'variants', 'senses.examples', 'lemmaRelations', 'inflections', 'idiomaticExpressions'];

        $lemma = $this->findLemma($word, $with);

        if (!$lemma) {
            $normalized = DictionaryText::normalizeForLookup($word);
            $lemma = Lemma::query()
                ->with($with)
                ->where(function ($query) use ($normalized) {
                    $query->whereRaw($this->normalizedSql('lemma') . ' = ?', [$normalized])
                        ->orWhereRaw($this->normalizedSql('normalized_lemma') . ' = ?', [$normalized])
                        ->orWhereHas('variants', function ($query) use ($normalized) {
                            $query->whereRaw($this->normalizedSql('variant') . ' = ?', [$normalized])
                                ->orWhereRaw($this->normalizedSql('normalized_variant') . ' = ?', [$normalized]);
                        })
                        ->orWhereHas('inflections', function ($query) use ($normalized) {
                            $query->whereRaw($this->normalizedSql('form') . ' = ?', [$normalized]);
                        })
                        ->orWhereHas('senses', function ($query) use ($normalized) {
                            $query->whereRaw($this->normalizedSql('word_variant') . ' LIKE ?', ['%' . $normalized . '%']);
                        });
                })
                ->first();
        }

        if (!$lemma) {
            return response()->json(['found' => false], 200);
        }

        // Build response
        $synonyms = $lemma->lemmaRelations
            ->where('relation_type', 'synonym')
            ->pluck('related_word')
            ->values();

        $antonyms = $lemma->lemmaRelations
            ->where('relation_type', 'antonym')
            ->pluck('related_word')
            ->values();

        $hypernyms = $lemma->lemmaRelations
            ->where('relation_type', 'hypernym')
            ->pluck('related_word')
            ->values();

        $senses = $lemma->senses->map(function ($sense) {
            return [
                'id' => $sense->id,
                'public_id' => $sense->public_id,
                'lexical_id' => $sense->lexical_id,
                'sense_order' => $sense->sense_order,
                'part_of_speech' => $sense->part_of_speech,
                'short_gloss' => $sense->short_gloss,
                'definition' => $sense->definition,
                'definition_en' => $sense->definition_en,
                'definition_sd' => $sense->definition_sd,
                'full_definition' => $sense->full_definition,
                'usage_notes' => $sense->usage_notes,
                'register' => $sense->register,
                'dialect' => $sense->dialect,
                'domain' => $sense->domain,
                'language_direction' => $sense->language_direction,
                'source' => $sense->source ?? $sense->source_dictionary,
                'source_dictionary' => $sense->source_dictionary,
                'source_entry_id' => $sense->source_entry_id ?? $sense->entry_id,
                'publisher' => $sense->publisher,
                'license' => $sense->license,
                'examples' => $sense->examples->map(fn ($example) => [
                    'id' => $example->id,
                    'public_id' => $example->public_id,
                    'sentence' => $example->sentence,
                    'translation' => $example->translation,
                    'source' => $example->source,
                    'citation' => $example->citation,
                    'quality_flag' => $example->quality_flag,
                ])->values(),
            ];
        })->values();

        $meanings = $lemma->senses->pluck('definition')->filter()->values();
        $meanings_en = $lemma->senses->pluck('definition_en')->filter()->values();
        $meanings_sd = $lemma->senses->pluck('definition_sd')->filter()->values();

        return response()->json([
            'found' => true,
            'id' => $lemma->id,
            'public_id' => $lemma->public_id,
            'word' => $lemma->lemma,
            'normalized' => $lemma->normalized_lemma,
            'romanized' => $lemma->transliteration ?? \App\Models\Romanizer::where('word_sd', $lemma->lemma)->value('word_roman'),
            'pronunciation' => [
                'ipa' => $lemma->ipa,
                'phonetic' => $lemma->phonetic,
                'simple' => $lemma->pronunciation_simple ?? $lemma->phonetic,
                'audio_url' => $lemma->audio_url,
                'syllabification' => $lemma->syllabification,
            ],
            'pos' => $lemma->pos,
            'completion_status' => $lemma->completion_status,
            'gender' => $lemma->morphology?->gender,
            'number' => $lemma->morphology?->number,
            'tense' => $lemma->morphology?->tense,
            'morphology' => $lemma->morphology,
            'variants' => $lemma->variants->map(fn ($variant) => [
                'id' => $variant->id,
                'public_id' => $variant->public_id,
                'variant' => $variant->variant,
                'form' => $variant->variant,
                'type' => $variant->type,
                'romanization' => $variant->romanization,
                'note' => $variant->note,
                'dialect' => $variant->dialect,
            ])->values(),
            'senses' => $senses,
            'meanings' => $meanings,
            'meanings_en' => $meanings_en,
            'meanings_sd' => $meanings_sd,
            'synonyms' => $synonyms,
            'antonyms' => $antonyms,
            'hypernyms' => $hypernyms,
            'structured_entry' => app(StructuredDictionaryEntryService::class)->build($lemma),
        ]);
    }

    private function findLemma(string $word, array $with): ?Lemma
    {
        return Lemma::query()
            ->with($with)
            ->where(function ($query) use ($word) {
                $query->where('lemma', $word)
                    ->orWhere('normalized_lemma', $word)
                    ->orWhere('transliteration', $word)
                    ->orWhere('search_keywords_json', 'like', '%' . $word . '%')
                    ->orWhereHas('variants', function ($query) use ($word) {
                        $query->where('variant', $word)
                            ->orWhere('normalized_variant', $word)
                            ->orWhere('romanization', $word);
                    })
                    ->orWhereHas('inflections', function ($query) use ($word) {
                        $query->where('form', $word)
                            ->orWhere('romanization', $word);
                    })
                    ->orWhereHas('senses', function ($query) use ($word) {
                        $query->where('word_variant', 'like', '%' . $word . '%')
                            ->orWhere('definition', 'like', '%' . $word . '%')
                            ->orWhere('definition_en', 'like', '%' . $word . '%')
                            ->orWhere('english_equivalents', 'like', '%' . $word . '%')
                            ->orWhere('definition_sd', 'like', '%' . $word . '%')
                            ->orWhere('normalized_definition', 'like', '%' . $word . '%')
                            ->orWhere('source', 'like', '%' . $word . '%')
                            ->orWhere('source_dictionary', 'like', '%' . $word . '%')
                            ->orWhere('source_entry_id', $word)
                            ->orWhere('lexical_id', $word);
                    });
            })
            ->orderByRaw('CASE WHEN lemma = ? THEN 0 WHEN normalized_lemma = ? THEN 1 WHEN transliteration = ? THEN 2 ELSE 3 END', [$word, $word, $word])
            ->first();
    }

    private function normalizedSql(string $column): string
    {
        $expression = "LOWER(COALESCE({$column}, ''))";

        foreach ($this->diacriticMarks() as $mark) {
            $expression = "REPLACE({$expression}, '{$mark}', '')";
        }

        return $expression;
    }

    private function diacriticMarks(): array
    {
        return ['ً', 'ٌ', 'ٍ', 'َ', 'ُ', 'ِ', 'ّ', 'ْ', 'ٰ', 'ٓ', 'ٔ', 'ٕ', 'ٖ', 'ٗ', '٘', 'ٙ', 'ٚ', 'ٛ', 'ٜ', 'ٝ', 'ٞ', 'ٟ'];
    }
}
