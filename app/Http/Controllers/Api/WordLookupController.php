<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lemma;
use App\Models\LughatInflection;
use App\Models\LughatLemma;
use App\Models\LughatPoetrySenseAnnotation;
use App\Models\LughatVariant;
use App\Models\LughatWordForm;
use App\Services\BundledOpenLexiconLookup;
use App\Services\LughatExpressionService;
use App\Services\StructuredDictionaryEntryService;
use App\Support\DictionaryText;
use Illuminate\Http\Request;

class WordLookupController extends Controller
{
    /**
     * Look up a single word.
     * Query: dictionary=general|lughat. Optional poetry_id for preferred Baakh Lughat sense.
     * Optional couplet_index + token_index: if that token is inside a pinned izafat /
     * expression span, return the expression meaning instead of the single-word sense.
     */
    public function lookup(Request $request, string $word)
    {
        $word = DictionaryText::stripPunctuation(urldecode(trim($word)));
        if ($word === '') {
            return response()->json(['found' => false], 200);
        }

        $dictionary = strtolower((string) $request->query('dictionary', 'general'));
        $poetryId = $request->filled('poetry_id') ? (int) $request->query('poetry_id') : null;
        $coupletIndex = $request->filled('couplet_index') ? (int) $request->query('couplet_index') : null;
        $tokenIndex = $request->filled('token_index') ? (int) $request->query('token_index') : null;

        if (
            $poetryId
            && $coupletIndex !== null
            && $tokenIndex !== null
            && in_array($dictionary, ['lughat', 'baakh_lughat', 'baakh', 'general'], true)
        ) {
            $expressions = app(LughatExpressionService::class);
            $annotation = $expressions->findPoetryAnnotationForToken($poetryId, $coupletIndex, $tokenIndex);
            if ($annotation) {
                return response()->json($expressions->publicLookupPayload($annotation));
            }
        }

        if (in_array($dictionary, ['lughat', 'baakh_lughat', 'baakh'], true)) {
            $lughat = $this->lookupLughat($word, $poetryId);
            if ($lughat) {
                return response()->json($lughat);
            }
            // Fall back to general when Baakh Lughat has no entry.
        }

        return $this->lookupGeneral($word);
    }

    private function lookupGeneral(string $word)
    {
        $with = ['morphology', 'variants', 'senses.examples', 'lemmaRelations', 'inflections', 'idiomaticExpressions'];
        $identity = DictionaryText::normalizeForIdentity($word);
        $base = DictionaryText::lookupBase($word);

        // Airab is identity: never collapse نَھن / نُھن via diacritic strip.
        $lemma = $this->findLemmaExact($word, $with)
            ?? $this->findLemmaByIdentityKey($identity, $with);

        if (!$lemma && !DictionaryText::hasDiacritics($word)) {
            $lemma = $this->findLemmaUniqueBase($base, $with);
        }

        if (!$lemma) {
            $fallback = app(BundledOpenLexiconLookup::class)->lookup($word);
            if ($fallback) {
                return response()->json($fallback);
            }

            return response()->json(['found' => false], 200);
        }

        $payload = $this->buildLemmaPayload($lemma);

        if (!$this->hasArabicScriptDefinition($payload)) {
            $fallback = app(BundledOpenLexiconLookup::class)->lookup($word);
            if ($fallback && $this->hasArabicScriptDefinition($fallback)) {
                $payload['senses'] = collect($fallback['senses'] ?? [])
                    ->concat($payload['senses'] ?? [])
                    ->values()
                    ->all();
                $payload['meanings'] = collect($fallback['meanings'] ?? [])
                    ->merge($payload['meanings'] ?? [])
                    ->unique()
                    ->values()
                    ->all();
                $payload['meanings_sd'] = collect($fallback['meanings_sd'] ?? [])
                    ->merge($payload['meanings_sd'] ?? [])
                    ->unique()
                    ->values()
                    ->all();
                $payload['meanings_en'] = collect($fallback['meanings_en'] ?? [])
                    ->merge($payload['meanings_en'] ?? [])
                    ->unique()
                    ->values()
                    ->all();
                if (empty($payload['pos']) && !empty($fallback['pos'])) {
                    $payload['pos'] = $fallback['pos'];
                }
                $payload['source'] = 'db+bundled_open_lexicon';
            }
        }

        return response()->json($payload);
    }

    private function lookupLughat(string $word, ?int $poetryId = null): ?array
    {
        $identity = DictionaryText::normalizeForIdentity($word);
        $base = DictionaryText::lookupBase($word);
        if ($identity === '') {
            return null;
        }

        $with = ['morphology', 'variants', 'senses.examples', 'lemmaRelations', 'inflections', 'idiomaticExpressions'];

        $lemma = LughatLemma::query()
            ->with($with)
            ->where(function ($q) use ($word, $identity) {
                $q->whereRaw(DictionaryText::binaryEquals('lemma'), [$word])
                    ->orWhereRaw(DictionaryText::binaryEquals('normalized_lemma'), [$identity]);
            })
            ->orderBy('homograph_number')
            ->first();

        if (!$lemma) {
            $form = LughatWordForm::query()->with(['lemma' => fn ($q) => $q->with($with)])
                ->where(function ($q) use ($word, $identity) {
                    $q->whereRaw(DictionaryText::binaryEquals('form'), [$word])
                        ->orWhereRaw(DictionaryText::binaryEquals('normalized_form'), [$identity]);
                })
                ->first();
            $lemma = $form?->lemma;
        }

        if (!$lemma) {
            $inf = LughatInflection::query()->with(['lemma' => fn ($q) => $q->with($with)])
                ->where(function ($q) use ($word, $identity) {
                    $q->whereRaw(DictionaryText::binaryEquals('form'), [$word])
                        ->orWhereRaw(DictionaryText::binaryEquals('normalized_form'), [$identity]);
                })
                ->first();
            $lemma = $inf?->lemma;
        }

        if (!$lemma && \Illuminate\Support\Facades\Schema::hasTable('lughat_variants')) {
            $variant = LughatVariant::query()->with(['lemma' => fn ($q) => $q->with($with)])
                ->where(function ($q) use ($word, $identity) {
                    $q->whereRaw(DictionaryText::binaryEquals('variant'), [$word])
                        ->orWhereRaw(DictionaryText::binaryEquals('normalized_variant'), [$identity]);
                })
                ->first();
            $lemma = $variant?->lemma;
        }

        // Poetry often carries airab (پَرين) while Baakh Lughat stores unmarked پرين.
        // Unique lookup_base match in both directions; never pick one of several airab variants.
        if (!$lemma) {
            $hit = LughatLemma::findConflictingHeadword(0, $word, $identity);
            if ($hit) {
                $lemma = LughatLemma::query()->with($with)->find($hit->id);
            }
        }

        if (!$lemma) {
            return null;
        }

        $preferredSenseId = null;
        if ($poetryId) {
            $preferredSenseId = LughatPoetrySenseAnnotation::query()
                ->where('poetry_id', $poetryId)
                ->where('lemma_id', $lemma->id)
                ->where(function ($q) use ($identity, $base) {
                    $q->where('normalized_form', $identity)->orWhere('normalized_form', $base);
                })
                ->value('sense_id');
        }

        $senses = $lemma->senses
            ->sortBy(fn ($sense) => ($preferredSenseId && (int) $sense->id === (int) $preferredSenseId)
                ? -1
                : (int) ($sense->sense_order ?? 999))
            ->values();

        $meanings = collect();
        $meaningsEn = collect();
        $meaningsSd = collect();
        $sensePayload = $senses->map(function ($sense) use (&$meanings, &$meaningsEn, &$meaningsSd, $preferredSenseId) {
            foreach ([$sense->definition, $sense->full_definition, $sense->short_gloss, $sense->definition_sd] as $text) {
                if (filled($text)) {
                    $meanings->push($text);
                }
            }
            if (filled($sense->definition_en)) {
                $meaningsEn->push($sense->definition_en);
            }
            if (filled($sense->definition_sd)) {
                $meaningsSd->push($sense->definition_sd);
            }

            return [
                'id' => $sense->id,
                'short_gloss' => $sense->short_gloss,
                'definition' => $sense->definition,
                'definition_en' => $sense->definition_en,
                'definition_sd' => $sense->definition_sd,
                'source' => $sense->source_dictionary ?: $sense->source ?: 'Baakh Lughat',
                'source_dictionary' => $sense->source_dictionary ?: 'Baakh Lughat',
                'is_preferred' => $preferredSenseId && (int) $sense->id === (int) $preferredSenseId,
            ];
        })->all();

        return [
            'found' => true,
            'id' => $lemma->id,
            'public_id' => $lemma->public_id,
            'word' => $lemma->lemma,
            'romanized' => $lemma->transliteration,
            'pos' => $lemma->pos,
            'gender' => $lemma->morphology?->gender,
            'number' => $lemma->morphology?->number,
            'completion_status' => $lemma->completion_status,
            'meanings' => $meanings->unique()->values()->all(),
            'meanings_en' => $meaningsEn->unique()->values()->all(),
            'meanings_sd' => $meaningsSd->unique()->values()->all(),
            'senses' => $sensePayload,
            'synonyms' => $lemma->lemmaRelations->where('relation_type', 'synonym')->pluck('related_word')->values()->all(),
            'antonyms' => $lemma->lemmaRelations->where('relation_type', 'antonym')->pluck('related_word')->values()->all(),
            'hypernyms' => $lemma->lemmaRelations->where('relation_type', 'hypernym')->pluck('related_word')->values()->all(),
            'poetic_expressions' => app(LughatExpressionService::class)->expressionsForLemma((int) $lemma->id, 8),
            'source' => 'baakh_lughat',
            'dictionary' => 'lughat',
        ];
    }

    private function buildLemmaPayload(Lemma $lemma): array
    {
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

        $meanings = collect();
        $meaningsEn = collect();
        $meaningsSd = collect();

        $senses = $lemma->senses->map(function ($sense) use (&$meanings, &$meaningsEn, &$meaningsSd) {
            $definition = $sense->definition;
            $definitionEn = $sense->definition_en;
            $definitionSd = $sense->definition_sd;

            // Partial imports often store English glosses in `definition` with dir=sindhi.
            if ($this->isMostlyLatin((string) $definition) && !filled($definitionEn)) {
                $definitionEn = $definition;
            }
            if ($this->hasArabicScript((string) $definition) && !filled($definitionSd)) {
                $definitionSd = $definition;
            }

            foreach ([$definition, $sense->full_definition, $sense->short_gloss] as $text) {
                if (filled($text)) {
                    $meanings->push($text);
                }
            }
            if (filled($definitionEn)) {
                $meaningsEn->push($definitionEn);
            }
            if (filled($definitionSd)) {
                $meaningsSd->push($definitionSd);
            }

            return [
                'id' => $sense->id,
                'public_id' => $sense->public_id,
                'lexical_id' => $sense->lexical_id,
                'sense_order' => $sense->sense_order,
                'part_of_speech' => $sense->part_of_speech,
                'short_gloss' => $sense->short_gloss,
                'definition' => $definition,
                'definition_en' => $definitionEn,
                'definition_sd' => $definitionSd,
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

        return [
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
            'meanings' => $meanings->filter()->unique()->values(),
            'meanings_en' => $meaningsEn->filter()->unique()->values(),
            'meanings_sd' => $meaningsSd->filter()->unique()->values(),
            'synonyms' => $synonyms,
            'antonyms' => $antonyms,
            'hypernyms' => $hypernyms,
            'structured_entry' => app(StructuredDictionaryEntryService::class)->build($lemma),
        ];
    }

    private function hasArabicScriptDefinition(array $payload): bool
    {
        foreach ($payload['meanings_sd'] ?? [] as $text) {
            if ($this->hasArabicScript((string) $text)) {
                return true;
            }
        }
        foreach ($payload['senses'] ?? [] as $sense) {
            foreach (['definition_sd', 'definition', 'full_definition', 'short_gloss'] as $key) {
                if ($this->hasArabicScript((string) ($sense[$key] ?? ''))) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasArabicScript(string $text): bool
    {
        return (bool) preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}]/u', $text);
    }

    private function isMostlyLatin(string $text): bool
    {
        $text = trim($text);
        if ($text === '') {
            return false;
        }

        $hasLatin = (bool) preg_match('/\p{Latin}/u', $text);

        return $hasLatin && ! $this->hasArabicScript($text);
    }

    /**
     * Exact headword / variant / inflection match only.
     */
    private function findLemmaExact(string $word, array $with): ?Lemma
    {
        return Lemma::query()
            ->with($with)
            ->where(function ($query) use ($word) {
                $query->whereRaw(DictionaryText::binaryEquals('lemma'), [$word])
                    ->orWhereRaw(DictionaryText::binaryEquals('normalized_lemma'), [$word])
                    ->orWhere('transliteration', $word)
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
                        // Exact variant form only — never definition/source text.
                        $query->where('word_variant', $word)
                            ->orWhere('lexical_id', $word)
                            ->orWhere('source_entry_id', $word);
                    });
            })
            ->orderByRaw(
                'CASE WHEN BINARY lemma = ? THEN 0 WHEN BINARY normalized_lemma = ? THEN 1 WHEN transliteration = ? THEN 2 ELSE 3 END',
                [$word, $word, $word]
            )
            ->first();
    }

    /** Match on airab-preserving identity key. */
    private function findLemmaByIdentityKey(string $identity, array $with): ?Lemma
    {
        if ($identity === '') {
            return null;
        }

        return Lemma::query()
            ->with($with)
            ->whereRaw(DictionaryText::binaryEquals('normalized_lemma'), [$identity])
            ->orderBy('id')
            ->first();
    }

    /**
     * Bare undiacritized query: only when exactly one base match exists.
     */
    private function findLemmaUniqueBase(string $base, array $with): ?Lemma
    {
        if ($base === '') {
            return null;
        }

        $hasLookupBase = \Illuminate\Support\Facades\Schema::hasColumn('lemmas', 'lookup_base');
        $matches = Lemma::query()
            ->with($with)
            ->where(function ($query) use ($base, $hasLookupBase) {
                if ($hasLookupBase) {
                    $query->where('lookup_base', $base);
                }
                $query->orWhere('normalized_lemma', $base)
                    ->orWhereRaw($this->normalizedSql('lemma') . ' = ?', [$base]);
            })
            ->limit(2)
            ->get();

        return $matches->count() === 1 ? $matches->first() : null;
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
