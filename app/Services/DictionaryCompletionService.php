<?php

namespace App\Services;

use App\Models\Lemma;
use App\Models\Sense;

class DictionaryCompletionService
{
    public function evaluate(Lemma $lemma): array
    {
        $lemma->loadMissing(['senses.examples', 'morphology', 'variants']);

        $senses = $lemma->senses;
        $hasRealMorphology = $this->hasRealMorphology($lemma);
        $hasVariants = $lemma->variants->isNotEmpty() || $senses->contains(fn (Sense $sense) => filled($sense->word_variant));
        $hasExamples = $senses->flatMap->examples->isNotEmpty();
        $requirePronunciation = (bool) config('dictionary.completion.require_pronunciation', false);

        $checks = [
            'has_headword' => [
                'label' => 'Headword is present',
                'passed' => filled($lemma->lemma),
                'missing' => 'Add the word/headword.',
            ],
            'has_normalized_form' => [
                'label' => 'Normalized form is present',
                'passed' => filled($lemma->normalized_lemma),
                'missing' => 'Add a normalized lemma form.',
            ],
            'has_pos' => [
                'label' => 'Part of speech is present',
                'passed' => filled($lemma->pos) || $senses->contains(fn (Sense $sense) => filled($sense->part_of_speech)),
                'missing' => 'Assign a part of speech on the lemma or at least one sense.',
            ],
            'has_curated_sense' => [
                'label' => 'At least one curated sense exists',
                'passed' => $senses->contains(fn (Sense $sense) => $this->isCuratedSense($sense)),
                'missing' => 'Approve or review at least one sense.',
            ],
            'senses_have_definitions' => [
                'label' => 'Every sense has a gloss or definition',
                'passed' => $senses->isNotEmpty() && $senses->every(fn (Sense $sense) => $this->hasSenseDefinition($sense)),
                'missing' => 'Add a short gloss, full definition, or primary definition to every sense.',
            ],
            'senses_have_language_direction' => [
                'label' => 'Every sense has language direction',
                'passed' => $senses->isNotEmpty() && $senses->every(fn (Sense $sense) => $this->isValidLanguageDirection($sense->language_direction)),
                'missing' => 'Set a valid language direction for every sense.',
            ],
            'senses_have_source' => [
                'label' => 'Every sense has provenance',
                'passed' => $senses->isNotEmpty() && $senses->every(fn (Sense $sense) => $this->hasSenseSource($sense)),
                'missing' => 'Add source, source dictionary, entry ID, or lexical ID to every sense.',
            ],
            'variants_reviewed' => [
                'label' => 'Variants reviewed when present',
                'passed' => !$hasVariants || (bool) $lemma->variants_reviewed,
                'missing' => 'Review variants or mark the variants section as reviewed.',
            ],
            'examples_reviewed' => [
                'label' => 'Examples reviewed when present',
                'passed' => !$hasExamples || (bool) $lemma->examples_reviewed,
                'missing' => 'Review examples or mark the examples section as reviewed.',
            ],
            'morphology_reviewed' => [
                'label' => 'Morphology reviewed when present',
                'passed' => !$hasRealMorphology || (bool) $lemma->morphology_reviewed || $lemma->morphology?->review_status === 'reviewed',
                'missing' => 'Review morphology or mark the morphology section as reviewed.',
            ],
            'pronunciation_reviewed' => [
                'label' => 'Pronunciation reviewed when required',
                'passed' => !$requirePronunciation || ((bool) $lemma->pronunciation_reviewed && $this->hasPronunciation($lemma)),
                'missing' => 'Add and review pronunciation data.',
            ],
        ];

        $passed = collect($checks)->filter(fn (array $check) => $check['passed'])->count();
        $total = count($checks);
        $score = $total > 0 ? (int) round(($passed / $total) * 100) : 0;
        $missing = collect($checks)
            ->filter(fn (array $check) => !$check['passed'])
            ->map(fn (array $check, string $key) => [
                'key' => $key,
                'label' => $check['label'],
                'message' => $check['missing'],
            ])
            ->values()
            ->all();

        return [
            'status' => $score === 100 ? Lemma::COMPLETION_COMPLETE : Lemma::COMPLETION_PENDING,
            'score' => $score,
            'passed' => $passed,
            'total' => $total,
            'checks' => $checks,
            'missing_requirements' => $missing,
            'is_complete' => $score === 100,
        ];
    }

    public function isValidLanguageDirection(?string $direction): bool
    {
        if (!filled($direction)) {
            return false;
        }

        $normalized = strtolower(trim($direction));
        $valid = array_map('strtolower', config('dictionary.completion.valid_language_directions', []));

        return in_array($normalized, $valid, true)
            || str_contains($normalized, '→')
            || str_contains($normalized, '->');
    }

    private function isCuratedSense(Sense $sense): bool
    {
        return in_array($sense->review_status, ['reviewed', 'curated'], true)
            || $sense->status === 'approved';
    }

    private function hasSenseDefinition(Sense $sense): bool
    {
        return filled($sense->short_gloss)
            || filled($sense->full_definition)
            || filled($sense->definition)
            || filled($sense->definition_en)
            || filled($sense->definition_sd);
    }

    private function hasSenseSource(Sense $sense): bool
    {
        return filled($sense->source)
            || filled($sense->source_dictionary)
            || filled($sense->source_entry_id)
            || filled($sense->entry_id)
            || filled($sense->lexical_id);
    }

    private function hasRealMorphology(Lemma $lemma): bool
    {
        if (!$lemma->morphology) {
            return false;
        }

        return collect($lemma->morphology->only(['root', 'pattern', 'gender', 'number', 'case', 'aspect', 'tense']))
            ->filter(fn ($value) => filled($value))
            ->isNotEmpty();
    }

    private function hasPronunciation(Lemma $lemma): bool
    {
        return filled($lemma->ipa)
            || filled($lemma->phonetic)
            || filled($lemma->audio_url)
            || filled($lemma->syllabification);
    }
}
