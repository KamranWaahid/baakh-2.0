<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lemma;
use App\Models\Sense;
use App\Models\SenseExample;
use App\Models\Morphology;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DictionaryController extends Controller
{
    public function index(Request $request)
    {
        $query = Lemma::withCount(['senses', 'lemmaRelations', 'variants'])
            ->with([
                'morphology',
                'senses' => function ($query) {
                    $query->select([
                        'id',
                        'lemma_id',
                        'lexical_id',
                        'definition',
                        'part_of_speech',
                        'word_variant',
                        'domain',
                        'language_direction',
                        'source_dictionary',
                        'status',
                    ])->orderBy('id');
                },
            ]);

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($query) use ($search) {
                $query->where('lemma', 'like', '%' . $search . '%')
                    ->orWhere('normalized_lemma', 'like', '%' . $search . '%')
                    ->orWhere('transliteration', 'like', '%' . $search . '%')
                    ->orWhereHas('senses', function ($query) use ($search) {
                        $query->where('definition', 'like', '%' . $search . '%')
                            ->orWhere('normalized_definition', 'like', '%' . $search . '%')
                            ->orWhere('source_dictionary', 'like', '%' . $search . '%')
                            ->orWhere('domain', 'like', '%' . $search . '%')
                            ->orWhere('lexical_id', $search);
                    });
            });
        }

        if ($request->filled('pos')) {
            $query->where('pos', $request->pos);
        }

        if ($request->filled('source')) {
            $query->whereHas('senses', function ($query) use ($request) {
                $query->where('source_dictionary', $request->source);
            });
        }

        if ($request->has('status')) {
            if ($request->status !== 'all') {
                $query->where('status', $request->status);
            }
        } else {
            // Default to only showing approved words on the main dictionary browse page
            $query->where('status', 'approved');
        }

        $limit = min(100, max(1, (int) $request->get('limit', 20)));

        return response()->json($query->orderBy('lemma')->paginate($limit));
    }

    public function stats()
    {
        $sources = Sense::query()
            ->select('source_dictionary', DB::raw('COUNT(*) as total'))
            ->whereNotNull('source_dictionary')
            ->groupBy('source_dictionary')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return response()->json([
            'total_lemmas' => Lemma::count(),
            'pending_lemmas' => Lemma::where('status', 'pending')->count(),
            'approved_lemmas' => Lemma::where('status', 'approved')->count(),
            'total_senses' => Sense::count(),
            'open_lexicon_entries' => Sense::whereNotNull('lexical_id')->count(),
            'variant_entries' => Sense::whereNotNull('word_variant')->where('word_variant', '<>', '')->count(),
            'sources' => $sources,
        ]);
    }

    public function senses(Request $request)
    {
        $query = Sense::query()
            ->with(['lemma:id,lemma,normalized_lemma,pos,status'])
            ->select([
                'id',
                'lemma_id',
                'lexical_id',
                'entry_id',
                'definition',
                'part_of_speech',
                'word_variant',
                'domain',
                'language_direction',
                'source_dictionary',
                'status',
                'created_at',
            ]);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($query) use ($search) {
                $query->where('definition', 'like', '%' . $search . '%')
                    ->orWhere('normalized_definition', 'like', '%' . $search . '%')
                    ->orWhere('lexical_id', $search)
                    ->orWhere('source_dictionary', 'like', '%' . $search . '%')
                    ->orWhereHas('lemma', function ($query) use ($search) {
                        $query->where('lemma', 'like', '%' . $search . '%')
                            ->orWhere('normalized_lemma', 'like', '%' . $search . '%');
                    });
            });
        }

        if ($request->filled('source')) {
            $query->where('source_dictionary', $request->source);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $limit = min(100, max(1, (int) $request->get('limit', 20)));

        return response()->json($query->orderByDesc('id')->paginate($limit));
    }

    public function morphology(Request $request)
    {
        $query = Lemma::query()
            ->with(['morphology'])
            ->withCount(['senses', 'variants']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($query) use ($search) {
                $query->where('lemma', 'like', '%' . $search . '%')
                    ->orWhere('normalized_lemma', 'like', '%' . $search . '%')
                    ->orWhereHas('morphology', function ($query) use ($search) {
                        $query->where('root', 'like', '%' . $search . '%')
                            ->orWhere('pattern', 'like', '%' . $search . '%');
                    });
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->boolean('missing')) {
            $query->whereDoesntHave('morphology');
        }

        $limit = min(100, max(1, (int) $request->get('limit', 20)));

        return response()->json($query->orderBy('lemma')->paginate($limit));
    }

    public function variants(Request $request)
    {
        $query = Sense::query()
            ->with(['lemma:id,lemma,normalized_lemma,pos,status'])
            ->select([
                'id',
                'lemma_id',
                'lexical_id',
                'definition',
                'part_of_speech',
                'word_variant',
                'language_direction',
                'source_dictionary',
            ])
            ->whereNotNull('word_variant')
            ->where('word_variant', '<>', '');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($query) use ($search) {
                $query->where('word_variant', 'like', '%' . $search . '%')
                    ->orWhere('definition', 'like', '%' . $search . '%')
                    ->orWhereHas('lemma', function ($query) use ($search) {
                        $query->where('lemma', 'like', '%' . $search . '%')
                            ->orWhere('normalized_lemma', 'like', '%' . $search . '%');
                    });
            });
        }

        if ($request->filled('source')) {
            $query->where('source_dictionary', $request->source);
        }

        $limit = min(100, max(1, (int) $request->get('limit', 20)));
        $page = $query->orderByDesc('id')->paginate($limit);

        $page->getCollection()->transform(function (Sense $sense) {
            return [
                'id' => $sense->id,
                'lemma_id' => $sense->lemma_id,
                'lemma' => $sense->lemma,
                'variant' => $sense->word_variant,
                'type' => 'lexicon_variant',
                'dialect' => $sense->language_direction,
                'source_dictionary' => $sense->source_dictionary,
                'part_of_speech' => $sense->part_of_speech,
                'definition' => $sense->definition,
                'lexical_id' => $sense->lexical_id,
            ];
        });

        return response()->json($page);
    }

    public function qa()
    {
        $missingSenses = Lemma::query()
            ->withCount('senses')
            ->doesntHave('senses')
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'lemma', 'status', 'created_at']);

        $pending = Lemma::query()
            ->withCount('senses')
            ->where('status', 'pending')
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get(['id', 'lemma', 'status', 'updated_at']);

        $missingNormalized = Lemma::query()
            ->whereNull('normalized_lemma')
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'lemma', 'status']);

        $duplicateLemmas = Lemma::query()
            ->select('lemma', DB::raw('COUNT(*) as total'))
            ->groupBy('lemma')
            ->having('total', '>', 1)
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return response()->json([
            'summary' => [
                'pending_lemmas' => Lemma::where('status', 'pending')->count(),
                'lemmas_without_senses' => Lemma::doesntHave('senses')->count(),
                'lemmas_without_normalized_form' => Lemma::whereNull('normalized_lemma')->count(),
                'duplicate_lemma_groups' => DB::query()
                    ->fromSub(
                        Lemma::query()->select('lemma')->groupBy('lemma')->havingRaw('COUNT(*) > 1'),
                        'duplicate_lemmas'
                    )
                    ->count(),
            ],
            'issues' => [
                'missing_senses' => $missingSenses,
                'pending' => $pending,
                'missing_normalized' => $missingNormalized,
                'duplicate_lemmas' => $duplicateLemmas,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'lemma' => 'required|string',
            'pos' => 'nullable|string',
            'transliteration' => 'nullable|string',
            'status' => 'nullable|in:pending,approved,rejected',
        ]);

        $lemma = Lemma::create($validated);

        // Sync with Romanizer
        if (!empty($validated['transliteration'])) {
            \App\Models\Romanizer::updateOrCreate(
                ['word_sd' => $lemma->lemma],
                [
                    'word_roman' => $validated['transliteration'],
                    'user_id' => auth()->id() ?? 1
                ]
            );
        }

        return response()->json($lemma, 201);
    }

    public function show($id)
    {
        $lemma = Lemma::with(['senses.examples', 'morphology', 'variants', 'lemmaRelations'])->findOrFail($id);

        // Auto-fetch transliteration from Romanizer if it's empty
        if (empty($lemma->transliteration)) {
            $roman = \App\Models\Romanizer::where('word_sd', $lemma->lemma)->first();
            if ($roman) {
                // Attach the transliteration just for the response so the frontend receives it
                $lemma->transliteration = $roman->word_roman;
            }
        }

        return response()->json($this->lemmaDetailPayload($lemma));
    }

    private function lemmaDetailPayload(Lemma $lemma): array
    {
        $payload = $lemma->toArray();

        $payload['senses'] = $lemma->senses
            ->map(function (Sense $sense) use ($lemma) {
                $sensePayload = $sense->toArray();
                $metadata = $this->senseSourceMetadata($sense, $lemma);

                $sensePayload['extra'] = $metadata['extra'];
                $sensePayload['source_metadata'] = $metadata;

                return $sensePayload;
            })
            ->values()
            ->all();

        $manualVariants = $lemma->variants
            ->map(fn (Variant $variant) => array_merge($variant->toArray(), [
                'source' => 'Manual',
                'is_imported' => false,
            ]))
            ->values()
            ->all();
        $importedVariants = $this->importedVariantsForLemma($lemma);

        $payload['source_summary'] = $this->lemmaSourceSummary($lemma, $payload['senses']);
        $payload['manual_variants_count'] = count($manualVariants);
        $payload['imported_variants_count'] = count($importedVariants);
        $payload['imported_variants'] = $importedVariants;
        $payload['variants'] = array_values(array_merge($manualVariants, $importedVariants));
        $payload['has_real_morphology'] = $this->hasRealMorphology($lemma);

        return $payload;
    }

    private function senseSourceMetadata(Sense $sense, Lemma $lemma): array
    {
        $extra = $this->decodeSenseExtra($sense->extra);

        return [
            'lexical_id' => $sense->lexical_id,
            'entry_id' => $sense->entry_id,
            'source_word' => $this->metadataString($extra['original_word'] ?? null) ?? $lemma->lemma,
            'source_variant' => $sense->word_variant,
            'normalized_word' => $this->metadataString($extra['original_normalized_word'] ?? null) ?? $lemma->normalized_lemma,
            'normalized_definition' => $sense->normalized_definition,
            'part_of_speech' => $sense->part_of_speech,
            'domain' => $sense->domain,
            'language_direction' => $sense->language_direction,
            'language_label' => $this->languageLabel($sense->language_direction),
            'source_dictionary' => $sense->source_dictionary,
            'publisher' => $this->metadataString($extra['publisher'] ?? null),
            'publisher_url' => $this->metadataString($extra['publisher_url'] ?? null),
            'prepared_by' => $this->metadataString($extra['prepared_by'] ?? null),
            'source_extra' => $extra['extra'] ?? null,
            'extra' => $extra,
        ];
    }

    private function lemmaSourceSummary(Lemma $lemma, array $senses): array
    {
        $metadata = collect($senses)
            ->pluck('source_metadata')
            ->filter(fn ($item) => is_array($item));

        $languageDirections = $this->uniqueFilled($metadata->pluck('language_direction')->all());
        $languageLabels = $this->uniqueFilled(array_map(
            fn ($direction) => $this->languageLabel($direction),
            $languageDirections
        ));
        $sourceDictionaries = $this->uniqueFilled($metadata->pluck('source_dictionary')->all());
        $domains = $this->uniqueFilled($metadata->pluck('domain')->all());
        $sourceWords = $this->uniqueFilled($metadata->pluck('source_word')->all());
        $normalizedWords = $this->uniqueFilled($metadata->pluck('normalized_word')->all());
        $lexicalIds = $this->uniqueFilled($metadata->pluck('lexical_id')->all());
        $entryIds = $this->uniqueFilled($metadata->pluck('entry_id')->all());

        $primaryLanguage = $languageLabels[0] ?? null;
        $isSourceTerm = $this->isSourceTerm($languageDirections, $sourceDictionaries);

        return [
            'is_open_lexicon' => count($lexicalIds) > 0,
            'is_source_term' => $isSourceTerm,
            'word_label' => $isSourceTerm && $primaryLanguage
                ? "{$primaryLanguage} Source Term"
                : 'Word (سنڌي)',
            'primary_language' => $primaryLanguage,
            'language_directions' => $languageDirections,
            'language_labels' => $languageLabels,
            'source_dictionaries' => $sourceDictionaries,
            'domains' => $domains,
            'source_words' => $sourceWords,
            'normalized_words' => $normalizedWords,
            'lexical_ids' => $lexicalIds,
            'entry_ids' => $entryIds,
            'publisher' => $metadata->pluck('publisher')->first(fn ($value) => filled($value)),
            'publisher_url' => $metadata->pluck('publisher_url')->first(fn ($value) => filled($value)),
            'prepared_by' => $metadata->pluck('prepared_by')->first(fn ($value) => filled($value)),
            'available_morphology_fields' => $this->hasRealMorphology($lemma)
                ? $this->filledMorphologyFields($lemma)
                : [],
        ];
    }

    private function importedVariantsForLemma(Lemma $lemma): array
    {
        $variants = [];
        $seen = [];

        foreach ($lemma->senses as $sense) {
            $extra = $this->decodeSenseExtra($sense->extra);
            $sources = [
                $sense->word_variant,
                $extra['original_word'] ?? null,
            ];

            foreach ($sources as $sourceText) {
                foreach ($this->variantCandidates($sourceText) as $candidate) {
                    if ($this->sameDictionaryValue($candidate, $lemma->lemma)) {
                        continue;
                    }

                    $key = mb_strtolower($candidate) . '|' . ($sense->lexical_id ?? $sense->id);
                    if (isset($seen[$key])) {
                        continue;
                    }

                    $seen[$key] = true;
                    $variants[] = [
                        'id' => 'imported-' . $sense->id . '-' . count($variants),
                        'lemma_id' => $lemma->id,
                        'variant' => $candidate,
                        'type' => 'imported',
                        'dialect' => $sense->language_direction,
                        'source' => 'Open Lexicon',
                        'source_dictionary' => $sense->source_dictionary,
                        'definition' => $sense->definition,
                        'domain' => $sense->domain,
                        'sense_id' => $sense->id,
                        'lexical_id' => $sense->lexical_id,
                        'is_imported' => true,
                    ];
                }
            }
        }

        return $variants;
    }

    private function variantCandidates(mixed $value): array
    {
        if (is_array($value)) {
            return [];
        }

        $value = $this->metadataString($value);
        if ($value === null) {
            return [];
        }

        $parts = preg_split('/(?:\s*[,،;؛\/|]+\s*|\s+يا\s+)/u', trim($value)) ?: [$value];

        return collect($parts)
            ->map(fn ($part) => trim((string) $part))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function decodeSenseExtra(mixed $extra): array
    {
        if (is_array($extra)) {
            return $extra;
        }

        if (!filled($extra)) {
            return [];
        }

        $decoded = json_decode((string) $extra, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function metadataString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_scalar($value) && !$value instanceof \Stringable) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function uniqueFilled(array $values): array
    {
        return collect($values)
            ->map(fn ($value) => is_scalar($value) ? trim((string) $value) : null)
            ->filter(fn ($value) => filled($value))
            ->unique()
            ->values()
            ->all();
    }

    private function languageLabel(?string $direction): ?string
    {
        if (!filled($direction)) {
            return null;
        }

        $normalized = strtolower(trim($direction));

        return match ($normalized) {
            'sd', 'sindhi' => 'Sindhi',
            'en', 'eng', 'english' => 'English',
            'hi', 'hindi' => 'Hindi',
            'ar', 'arabic' => 'Arabic',
            default => str($direction)->replace(['_', '-'], ' ')->title()->toString(),
        };
    }

    private function isSourceTerm(array $languageDirections, array $sourceDictionaries): bool
    {
        $directions = array_map(fn ($value) => strtolower((string) $value), $languageDirections);
        if (count(array_diff($directions, ['', 'sd', 'sindhi'])) > 0) {
            return true;
        }

        foreach ($sourceDictionaries as $source) {
            if (str_contains((string) $source, '→ Sindhi')) {
                return true;
            }
        }

        return false;
    }

    private function sameDictionaryValue(string $left, ?string $right): bool
    {
        return filled($right) && mb_strtolower(trim($left)) === mb_strtolower(trim((string) $right));
    }

    private function hasRealMorphology(Lemma $lemma): bool
    {
        return $this->filledMorphologyFields($lemma) !== [];
    }

    private function filledMorphologyFields(Lemma $lemma): array
    {
        if (!$lemma->morphology) {
            return [];
        }

        return collect($lemma->morphology->only(['root', 'pattern', 'gender', 'number', 'case', 'aspect', 'tense']))
            ->filter(fn ($value) => filled($value))
            ->keys()
            ->values()
            ->all();
    }

    public function update(Request $request, $id)
    {
        $lemma = Lemma::findOrFail($id);

        $validated = $request->validate([
            'lemma' => 'string',
            'pos' => 'nullable|string',
            'transliteration' => 'nullable|string',
            'status' => 'nullable|in:pending,approved,rejected',
        ]);

        $lemma->update($validated);

        // Sync with Romanizer
        if (!empty($validated['transliteration'])) {
            \App\Models\Romanizer::updateOrCreate(
                ['word_sd' => $lemma->lemma],
                [
                    'word_roman' => $validated['transliteration'],
                    'user_id' => auth()->id() ?? 1
                ]
            );
        }

        // Nested updates for senses, morphology, variants could be added here if needed
        // For a full CRUD, usually we have separate endpoints or a complex sync logic.
        // Given the UI shows separate sections, we'll keep it simple for now and expand as needed.

        return response()->json($lemma);
    }

    public function destroy($id)
    {
        $lemma = Lemma::findOrFail($id);
        $lemma->delete();
        return response()->json(null, 204);
    }

    // Sense Methods
    public function storeSense(Request $request, ?int $lemmaId = null)
    {
        $merge = [];

        if ($lemmaId && !$request->filled('lemma_id')) {
            $merge['lemma_id'] = $lemmaId;
        }

        foreach (['definition', 'definition_en', 'definition_sd', 'domain', 'language_direction'] as $field) {
            if ($request->has($field) && is_string($request->input($field))) {
                $merge[$field] = trim($request->input($field));
            }
        }

        if (!empty($merge)) {
            $request->merge($merge);
        }

        $validated = $request->validate([
            'lemma_id' => 'required|integer|exists:lemmas,id',
            'definition' => 'required|string',
            'definition_en' => 'nullable|string',
            'definition_sd' => 'nullable|string',
            'domain' => 'nullable|string',
            'language_direction' => 'nullable|string|max:100',
            'status' => 'nullable|in:pending,approved',
        ]);

        foreach (['definition_en', 'definition_sd', 'domain', 'language_direction'] as $field) {
            if (($validated[$field] ?? null) === '') {
                $validated[$field] = null;
            }
        }

        $validated['status'] = $validated['status'] ?? 'pending';

        $sense = Sense::create($validated);
        return response()->json($sense, 201);
    }

    public function updateSense(Request $request, $id)
    {
        $sense = Sense::findOrFail($id);
        $validated = $request->validate([
            'definition' => 'string',
            'definition_en' => 'nullable|string',
            'definition_sd' => 'nullable|string',
            'domain' => 'nullable|string',
            'status' => 'nullable|in:pending,approved',
        ]);

        $sense->update($validated);
        return response()->json($sense);
    }

    public function destroySense($id)
    {
        $sense = Sense::findOrFail($id);
        $sense->delete();
        return response()->json(null, 204);
    }

    // Example Methods
    public function updateExample(Request $request, $id)
    {
        $example = SenseExample::findOrFail($id);
        $validated = $request->validate([
            'sentence' => 'string',
            'source' => 'nullable|string',
            'corpus_sentence_id' => 'nullable|integer',
        ]);

        $example->update($validated);
        return response()->json($example);
    }

    public function destroyExample($id)
    {
        $example = SenseExample::findOrFail($id);
        $example->delete();
        return response()->json(null, 204);
    }

    // Morphology Methods
    public function updateMorphology(Request $request, $lemmaId)
    {
        $lemma = Lemma::findOrFail($lemmaId);
        $validated = $request->validate([
            'root' => 'nullable|string',
            'pattern' => 'nullable|string',
            'gender' => 'nullable|string',
            'number' => 'nullable|string',
            'case' => 'nullable|string',
            'aspect' => 'nullable|string',
            'tense' => 'nullable|string',
        ]);

        $morphology = Morphology::updateOrCreate(
            ['lemma_id' => $lemmaId],
            $validated
        );

        return response()->json($morphology);
    }

    // Variant Methods
    public function storeVariant(Request $request, $lemmaId)
    {
        $validated = $request->validate([
            'variant' => 'required|string',
            'type' => 'required|in:dialectal,misspelling,historical',
            'dialect' => 'nullable|string',
        ]);

        $variant = Variant::create([
            'lemma_id' => $lemmaId,
            'variant' => $validated['variant'],
            'type' => $validated['type'],
            'dialect' => $validated['dialect'],
        ]);

        return response()->json($variant, 201);
    }

    public function destroyVariant($id)
    {
        $variant = Variant::findOrFail($id);
        $variant->delete();
        return response()->json(null, 204);
    }

    public function approve($id)
    {
        $lemma = Lemma::findOrFail($id);
        $lemma->update(['status' => 'approved']);
        return response()->json(['message' => 'Lemma approved successfully']);
    }

    // Relation Methods
    public function storeRelation(Request $request, $lemmaId)
    {
        $validated = $request->validate([
            'relation_type' => 'required|in:synonym,antonym,hypernym',
            'related_word' => 'required|string',
        ]);

        $relation = \App\Models\LemmaRelation::create([
            'lemma_id' => $lemmaId,
            'relation_type' => $validated['relation_type'],
            'related_word' => $validated['related_word'],
        ]);

        return response()->json($relation, 201);
    }

    public function destroyRelation($id)
    {
        $relation = \App\Models\LemmaRelation::findOrFail($id);
        $relation->delete();
        return response()->json(null, 204);
    }

    // Scraping Method



}
