<?php

namespace App\Services;

use App\Models\LughatLemma;
use App\Models\LughatSense;

class LughatCompletionService
{
    /** POS that normally carry gender/number/root morphology. */
    private const OPEN_CLASS_POS = [
        'noun', 'verb', 'adjective', 'adj', 'adverb', 'adv',
        'proper_noun', 'participle',
    ];

    public function evaluate(LughatLemma $lemma): array
    {
        $lemma->loadMissing([
            'senses.examples',
            'morphology',
            'variants',
            'lemmaRelations',
            'inflections',
            'idiomaticExpressions',
        ]);

        $senses = $lemma->senses;
        $hasRealMorphology = $this->hasRealMorphology($lemma);
        $hasVariants = $lemma->variants->isNotEmpty()
            || $senses->contains(fn (LughatSense $sense) => filled($sense->word_variant));
        $hasExamples = $senses->flatMap->examples->isNotEmpty();
        $isSindhiHeadword = (bool) preg_match('/[\x{0600}-\x{06FF}]/u', (string) $lemma->lemma);
        $pos = strtolower(trim((string) ($lemma->pos ?: '')));
        $isOpenClass = $pos !== '' && collect(self::OPEN_CLASS_POS)->contains(
            fn (string $p) => $pos === $p || str_starts_with($pos, $p)
        );

        $typedRelationCount = $lemma->lemmaRelations
            ->filter(fn ($r) => in_array((string) $r->relation_type, [
                'synonym', 'antonym', 'hypernym', 'singular', 'plural', 'dialect', 'derived', 'usage',
            ], true))
            ->count();
        $relatedOnlyCount = $lemma->lemmaRelations
            ->filter(fn ($r) => (string) ($r->relation_type ?: 'related') === 'related')
            ->count();

        $keywords = is_array($lemma->search_keywords_json) ? $lemma->search_keywords_json : [];
        $hasKeywordBucket = fn (string $key): bool => collect($keywords[$key] ?? [])
            ->contains(fn ($item) => filled(is_scalar($item) ? trim((string) $item) : null));

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
            'has_transliteration' => [
                'label' => 'Roman transliteration is present',
                'passed' => filled($lemma->transliteration)
                    && (bool) preg_match('/^[a-zA-Z][a-zA-Z\s\-]{0,80}$/', trim((string) $lemma->transliteration)),
                'missing' => 'Add plain ASCII roman transliteration (e.g. lae, aadmi).',
            ],
            'has_pos' => [
                'label' => 'Part of speech is present',
                'passed' => filled($lemma->pos) || $senses->contains(fn (LughatSense $sense) => filled($sense->part_of_speech)),
                'missing' => 'Assign a part of speech on the lemma or at least one sense.',
            ],
            'has_pronunciation' => [
                'label' => 'Pronunciation fields filled',
                'passed' => filled($lemma->pronunciation_simple)
                    && (filled($lemma->phonetic) || filled($lemma->ipa)),
                'missing' => 'Fill pronunciation_simple AND phonetic or ipa.',
            ],
            'has_syllabification' => [
                'label' => 'Syllabification is present',
                'passed' => filled($lemma->syllabification),
                'missing' => 'Fill syllabification (General tab).',
            ],
            'has_notes' => [
                'label' => 'Notes are present',
                'passed' => filled($lemma->notes),
                'missing' => 'Fill Notes (General tab).',
            ],
            'has_search_keywords' => [
                'label' => 'Structured search keywords present',
                'passed' => $hasKeywordBucket('sindhi')
                    && $hasKeywordBucket('english')
                    && $hasKeywordBucket('romanized'),
                'missing' => 'Fill Sindhi, English, and Romanized search keywords.',
            ],
            'has_curated_sense' => [
                'label' => 'At least one curated sense exists',
                'passed' => $senses->contains(fn (LughatSense $sense) => $this->isCuratedSense($sense)),
                'missing' => 'Approve or review at least one sense.',
            ],
            'senses_have_primary_definition' => [
                'label' => 'Every sense has primary definition',
                'passed' => $senses->isNotEmpty()
                    && $senses->every(fn (LughatSense $sense) => $this->containsArabicScript((string) $sense->definition)),
                'missing' => 'Fill Definition (Primary) in Sindhi for every sense.',
            ],
            'senses_have_short_gloss' => [
                'label' => 'Every sense has short gloss',
                'passed' => $senses->isNotEmpty()
                    && $senses->every(fn (LughatSense $sense) => $this->containsArabicScript((string) $sense->short_gloss)),
                'missing' => 'Fill Short Gloss in Sindhi for every sense.',
            ],
            'senses_have_definition_sd' => [
                'label' => 'Every sense has Sindhi definition',
                'passed' => $senses->isNotEmpty()
                    && $senses->every(fn (LughatSense $sense) => $this->containsArabicScript((string) $sense->definition_sd)
                        || $this->containsArabicScript((string) $sense->definition)),
                'missing' => 'Fill Sindhi Definition (definition_sd) for every sense.',
            ],
            'senses_have_english' => [
                'label' => 'Every sense has English definition + equivalents',
                'passed' => $senses->isNotEmpty()
                    && $senses->every(fn (LughatSense $sense) => filled($sense->definition_en) && $this->hasEnglishEquivalents($sense)),
                'missing' => 'Fill definition_en AND english_equivalents[] on every sense.',
            ],
            'senses_have_usage_label' => [
                'label' => 'Every sense has usage_label',
                'passed' => $senses->isNotEmpty()
                    && $senses->every(fn (LughatSense $sense) => filled($sense->usage_label)),
                'missing' => 'Set usage_label on every sense (e.g. general, poetic, figurative, literary).',
            ],
            'senses_have_domain' => [
                'label' => 'Every sense has domain',
                'passed' => $senses->isNotEmpty()
                    && $senses->every(fn (LughatSense $sense) => filled($sense->domain)),
                'missing' => 'Set domain on every sense (e.g. general, poetry, grammar).',
            ],
            'senses_have_language_direction' => [
                'label' => 'Every sense has language direction',
                'passed' => $senses->isNotEmpty()
                    && $senses->every(fn (LughatSense $sense) => $this->isValidLanguageDirection($sense->language_direction)),
                'missing' => 'Set a valid language direction for every sense.',
            ],
            'senses_have_source' => [
                'label' => 'Every sense has provenance',
                'passed' => $senses->isNotEmpty() && $senses->every(fn (LughatSense $sense) => $this->hasSenseSource($sense)),
                'missing' => 'Add source_dictionary / publisher on every sense.',
            ],
            'senses_have_examples' => [
                'label' => 'Every sense has at least one example',
                'passed' => $senses->isNotEmpty()
                    && $senses->every(fn (LughatSense $sense) => $sense->examples->isNotEmpty()),
                'missing' => 'Add senses[].examples[] (sentence + translation) for every sense.',
            ],
            'has_variants' => [
                'label' => 'Variants / airab forms present',
                'passed' => !$isSindhiHeadword || $hasVariants,
                'missing' => 'Add spelling/airab variants (diacritic, fully_voweled_variant, etc.).',
            ],
            'variants_have_romanization' => [
                'label' => 'Variants have romanization',
                'passed' => !$hasVariants || $lemma->variants->every(fn ($v) => filled($v->romanization)),
                'missing' => 'Fill romanization on every variant.',
            ],
            'has_typed_relations' => [
                'label' => 'Typed linguistic relations present',
                'passed' => $typedRelationCount >= 1,
                'missing' => 'Fill synonym/antonym/hypernym/… relations — do not dump everything into related.'
                    . ($relatedOnlyCount > 0 ? " ({$relatedOnlyCount} related-only; reclassify as synonym where apt.)" : ''),
            ],
            'open_class_morphology' => [
                'label' => 'Open-class morphology filled when applicable',
                'passed' => !$isOpenClass
                    || (
                        (filled($lemma->morphology?->root) || filled($lemma->morphology?->pattern))
                        && (filled($lemma->morphology?->gender) || filled($lemma->morphology?->number)
                            || filled($lemma->morphology?->case) || filled($lemma->morphology?->tense))
                    ),
                'missing' => 'For nouns/verbs/adjectives fill morphology root/pattern plus gender/number/case/tense.',
            ],
            'closed_class_morphology_note' => [
                'label' => 'Closed-class morphology note when applicable',
                'passed' => $isOpenClass || $pos === ''
                    || filled($lemma->morphology?->pattern)
                    || filled($lemma->morphology?->root),
                'missing' => 'For particles/postpositions fill morphology.pattern (e.g. غير متصرف حرف اضافت).',
            ],
            'open_class_inflections' => [
                'label' => 'Open-class inflection forms present',
                'passed' => !$isOpenClass || $lemma->inflections->isNotEmpty(),
                'missing' => 'Add forms.inflections[] for open-class POS (Forms tab).',
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
                'label' => 'Pronunciation marked reviewed',
                'passed' => (bool) $lemma->pronunciation_reviewed
                    && filled($lemma->pronunciation_simple)
                    && (filled($lemma->phonetic) || filled($lemma->ipa)),
                'missing' => 'Fill pronunciation fields and set pronunciation_reviewed=true.',
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
            'status' => $score === 100 ? LughatLemma::COMPLETION_COMPLETE : LughatLemma::COMPLETION_PENDING,
            'score' => $score,
            'passed' => $passed,
            'total' => $total,
            'checks' => $checks,
            'missing_requirements' => $missing,
            'is_complete' => $score === 100,
        ];
    }

    public function isValidLanguageDirection(mixed $direction): bool
    {
        if (is_array($direction)) {
            $direction = implode('-', array_filter($direction, 'is_scalar'));
        }

        if (!is_string($direction) && !is_numeric($direction)) {
            return false;
        }

        $direction = (string) $direction;
        if (!filled($direction)) {
            return false;
        }

        $normalized = strtolower(trim($direction));
        $normalized = str_replace(['_', ' '], '-', $normalized);
        $valid = array_map('strtolower', config('dictionary.completion.valid_language_directions', []));

        if (
            in_array($normalized, $valid, true)
            || str_contains($normalized, '→')
            || str_contains($normalized, '->')
            || str_contains($normalized, '/')
        ) {
            return true;
        }

        $parts = preg_split('/[-–—\/]+/u', $normalized) ?: [];
        $parts = array_values(array_filter(array_map('trim', $parts)));

        return count($parts) >= 2
            && collect($parts)->every(fn (string $part) => in_array($part, $valid, true));
    }

    private function isCuratedSense(LughatSense $sense): bool
    {
        return in_array($sense->review_status, ['reviewed', 'curated'], true)
            || $sense->status === 'approved';
    }

    private function hasEnglishEquivalents(LughatSense $sense): bool
    {
        $equivalents = is_array($sense->english_equivalents) ? $sense->english_equivalents : [];
        $equivalents = array_values(array_filter(array_map(
            fn ($item) => is_scalar($item) ? trim((string) $item) : '',
            $equivalents
        )));

        return $equivalents !== [];
    }

    private function hasSenseSource(LughatSense $sense): bool
    {
        return filled($sense->source)
            || filled($sense->source_dictionary)
            || filled($sense->source_entry_id)
            || filled($sense->entry_id)
            || filled($sense->lexical_id)
            || filled($sense->publisher);
    }

    private function hasRealMorphology(LughatLemma $lemma): bool
    {
        if (!$lemma->morphology) {
            return false;
        }

        return collect($lemma->morphology->only(['root', 'pattern', 'gender', 'number', 'case', 'aspect', 'tense']))
            ->filter(fn ($value) => filled($value))
            ->isNotEmpty();
    }

    private function containsArabicScript(string $text): bool
    {
        $text = trim($text);

        return $text !== '' && (bool) preg_match('/[\x{0600}-\x{06FF}]/u', $text);
    }
}
