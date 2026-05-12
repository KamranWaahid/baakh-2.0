<?php

namespace App\Services;

use App\Models\Lemma;
use App\Models\LemmaRelation;
use App\Models\Sense;
use Illuminate\Support\Collection;

class StructuredDictionaryEntryService
{
    public function build(Lemma $lemma): array
    {
        $lemma->loadMissing([
            'senses.examples',
            'morphology',
            'variants',
            'lemmaRelations',
            'inflections',
            'idiomaticExpressions',
        ]);

        $senses = $lemma->senses->values();
        $keywords = $this->searchKeywords($lemma, $senses);

        return [
            'id' => $lemma->public_id ?? 'lemma_' . $lemma->id,
            'sindhi_entry' => [
                'headword' => $lemma->lemma,
                'variants' => $lemma->variants
                    ->map(fn ($variant) => [
                        'form' => $variant->variant,
                        'type' => $variant->type,
                        'note' => $variant->note ?? $variant->dialect,
                    ])
                    ->values()
                    ->all(),
                'romanization' => $lemma->transliteration,
                'pronunciation' => [
                    'ipa' => $lemma->ipa,
                    'simple' => $lemma->pronunciation_simple ?? $lemma->phonetic,
                ],
                'part_of_speech' => $lemma->pos ?: $senses->first(fn (Sense $sense) => filled($sense->part_of_speech))?->part_of_speech,
                'gender' => $lemma->morphology?->gender,
                'number' => $lemma->morphology?->number,
                'root' => $lemma->morphology?->root,
                'etymology' => $lemma->etymology,
                'meanings' => $senses
                    ->map(fn (Sense $sense, int $index) => $this->meaning($sense, $index))
                    ->values()
                    ->all(),
                'synonyms_sindhi' => $this->relations($lemma->lemmaRelations, 'synonym'),
                'antonyms_sindhi' => $this->relations($lemma->lemmaRelations, 'antonym'),
                'related_words_sindhi' => $this->relatedWords($lemma->lemmaRelations),
                'inflections' => $lemma->inflections
                    ->map(fn ($inflection) => [
                        'form' => $inflection->form,
                        'romanization' => $inflection->romanization,
                        'description' => $inflection->description,
                    ])
                    ->values()
                    ->all(),
                'idiomatic_expressions' => $lemma->idiomaticExpressions
                    ->map(fn ($expression) => [
                        'phrase' => $expression->phrase,
                        'romanization' => $expression->romanization,
                        'english_gloss' => $expression->english_gloss,
                        'example_sindhi' => $expression->example_sindhi,
                        'example_english' => $expression->example_english,
                    ])
                    ->values()
                    ->all(),
                'notes' => $lemma->notes,
            ],
            'english_index' => $this->englishIndex($lemma, $senses),
            'search_keywords' => $keywords,
            'metadata' => $this->metadata($lemma),
            'source_confidence' => $lemma->source_confidence,
        ];
    }

    private function meaning(Sense $sense, int $index): array
    {
        return [
            'sense_number' => $sense->sense_order ?: $index + 1,
            'english_definition' => $sense->definition_en ?: $sense->short_gloss ?: $sense->full_definition,
            'english_equivalents' => $this->englishEquivalents($sense),
            'usage_label' => $sense->usage_label ?? $sense->register ?? $sense->domain,
            'examples' => $sense->examples
                ->map(fn ($example) => [
                    'sindhi' => $example->sentence,
                    'romanization' => $example->romanization,
                    'english_translation' => $example->translation,
                ])
                ->values()
                ->all(),
        ];
    }

    private function relations(Collection $relations, string $type): array
    {
        return $relations
            ->where('relation_type', $type)
            ->map(fn (LemmaRelation $relation) => [
                'word' => $relation->related_word,
                'romanization' => $relation->romanization,
                'note' => $relation->note ?? $relation->source,
            ])
            ->values()
            ->all();
    }

    private function relatedWords(Collection $relations): array
    {
        return $relations
            ->filter(fn (LemmaRelation $relation) => in_array($relation->relation_type, ['related', 'hypernym'], true))
            ->map(fn (LemmaRelation $relation) => [
                'word' => $relation->related_word,
                'romanization' => $relation->romanization,
                'gloss' => $relation->gloss ?? $relation->note,
                'part_of_speech' => $relation->part_of_speech,
            ])
            ->values()
            ->all();
    }

    private function englishIndex(Lemma $lemma, Collection $senses): array
    {
        return $senses
            ->flatMap(function (Sense $sense, int $index) use ($lemma) {
                $equivalents = $this->englishEquivalents($sense);
                $definition = $sense->definition_en ?: $sense->short_gloss ?: $sense->full_definition;

                if ($equivalents === [] && filled($definition)) {
                    $equivalents = [$definition];
                }

                return collect($equivalents)
                    ->filter()
                    ->map(fn (string $englishWord) => [
                        'english_word' => $englishWord,
                        'sindhi_equivalents' => array_values(array_filter([$lemma->lemma, $sense->definition])),
                        'part_of_speech' => $sense->part_of_speech ?: $lemma->pos,
                        'sense_note' => $sense->usage_label ?? $sense->short_gloss ?? ('Sense ' . (($sense->sense_order ?: $index + 1))),
                        'example_english' => $sense->examples->first(fn ($example) => filled($example->translation))?->translation,
                        'example_sindhi' => $sense->examples->first(fn ($example) => filled($example->sentence))?->sentence,
                    ]);
            })
            ->values()
            ->all();
    }

    private function searchKeywords(Lemma $lemma, Collection $senses): array
    {
        $stored = is_array($lemma->search_keywords_json) ? $lemma->search_keywords_json : [];

        return [
            'sindhi' => $this->unique([
                $lemma->lemma,
                $lemma->normalized_lemma,
                ...$lemma->variants->pluck('variant')->all(),
                ...$lemma->inflections->pluck('form')->all(),
                ...($stored['sindhi'] ?? []),
            ]),
            'english' => $this->unique([
                ...$senses->flatMap(fn (Sense $sense) => $this->englishEquivalents($sense))->all(),
                ...($stored['english'] ?? []),
                ...$senses->pluck('definition_en')->all(),
                ...$senses->pluck('short_gloss')->all(),
            ]),
            'romanized' => $this->unique([
                $lemma->transliteration,
                ...$lemma->variants->pluck('romanization')->all(),
                ...$lemma->inflections->pluck('romanization')->all(),
                ...($stored['romanized'] ?? []),
            ]),
        ];
    }

    private function metadata(Lemma $lemma): array
    {
        $stored = is_array($lemma->metadata_json) ? $lemma->metadata_json : [];

        return array_merge([
            'language' => 'Sindhi',
            'script' => 'Arabic',
            'language_code_iso' => 'sd',
            'region' => null,
            'dialect_notes' => null,
            'unicode_range' => '0600-06FF',
            'entry_created' => optional($lemma->created_at)->toDateString(),
            'version' => null,
        ], $stored);
    }

    private function englishEquivalents(Sense $sense): array
    {
        if (is_array($sense->english_equivalents) && $sense->english_equivalents !== []) {
            return $this->unique($sense->english_equivalents);
        }

        return $this->unique(preg_split('/[,;]+|\bor\b/u', (string) $sense->definition_en) ?: []);
    }

    private function unique(array $values): array
    {
        return collect($values)
            ->map(fn ($value) => is_scalar($value) ? trim((string) $value) : null)
            ->filter(fn ($value) => filled($value))
            ->unique()
            ->values()
            ->all();
    }
}
