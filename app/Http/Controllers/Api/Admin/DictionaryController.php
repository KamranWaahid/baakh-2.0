<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lemma;
use App\Models\Sense;
use App\Models\SenseExample;
use App\Models\Morphology;
use App\Models\Variant;
use App\Models\LemmaIdiomaticExpression;
use App\Models\LemmaInflection;
use App\Services\DictionaryCompletionService;
use App\Services\StructuredDictionaryEntryService;
use App\Support\DictionaryText;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
                        'public_id',
                        'lemma_id',
                        'lexical_id',
                        'definition',
                        'definition_en',
                        'english_equivalents',
                        'definition_sd',
                        'short_gloss',
                        'full_definition',
                        'usage_label',
                        'part_of_speech',
                        'word_variant',
                        'domain',
                        'language_direction',
                        'source_dictionary',
                        'source',
                        'review_status',
                        'status',
                    ])->orderBy('sense_order')->orderBy('id');
                },
            ]);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $normalizedSearch = DictionaryText::normalizeForLookup($search);

            $query->where(function ($query) use ($search, $normalizedSearch) {
                $query->where('lemma', 'like', '%' . $search . '%')
                    ->orWhere('normalized_lemma', 'like', '%' . $search . '%')
                    ->orWhere('transliteration', 'like', '%' . $search . '%')
                    ->orWhere('search_keywords_json', 'like', '%' . $search . '%')
                    ->orWhereRaw($this->normalizedSql('lemma') . ' LIKE ?', ['%' . $normalizedSearch . '%'])
                    ->orWhereRaw($this->normalizedSql('normalized_lemma') . ' LIKE ?', ['%' . $normalizedSearch . '%'])
                        ->orWhereHas('variants', function ($query) use ($search) {
                            $query->where('variant', 'like', '%' . $search . '%')
                                ->orWhere('romanization', 'like', '%' . $search . '%')
                                ->orWhere('note', 'like', '%' . $search . '%')
                                ->orWhere('source', 'like', '%' . $search . '%')
                                ->orWhere('source_entry_id', 'like', '%' . $search . '%');
                        })
                    ->orWhereHas('variants', function ($query) use ($normalizedSearch) {
                        $query->whereRaw($this->normalizedSql('variant') . ' LIKE ?', ['%' . $normalizedSearch . '%'])
                            ->orWhereRaw($this->normalizedSql('normalized_variant') . ' LIKE ?', ['%' . $normalizedSearch . '%']);
                    })
                    ->orWhereHas('inflections', function ($query) use ($search, $normalizedSearch) {
                        $query->where('form', 'like', '%' . $search . '%')
                            ->orWhere('romanization', 'like', '%' . $search . '%')
                            ->orWhereRaw($this->normalizedSql('form') . ' LIKE ?', ['%' . $normalizedSearch . '%']);
                    })
                    ->orWhereHas('senses', function ($query) use ($search, $normalizedSearch) {
                        $query->where('definition', 'like', '%' . $search . '%')
                                ->orWhere('definition_en', 'like', '%' . $search . '%')
                                ->orWhere('english_equivalents', 'like', '%' . $search . '%')
                                ->orWhere('definition_sd', 'like', '%' . $search . '%')
                                ->orWhere('short_gloss', 'like', '%' . $search . '%')
                                ->orWhere('full_definition', 'like', '%' . $search . '%')
                            ->orWhere('normalized_definition', 'like', '%' . $search . '%')
                            ->orWhere('source_dictionary', 'like', '%' . $search . '%')
                                ->orWhere('source', 'like', '%' . $search . '%')
                                ->orWhere('source_entry_id', 'like', '%' . $search . '%')
                            ->orWhere('domain', 'like', '%' . $search . '%')
                                ->orWhere('word_variant', 'like', '%' . $search . '%')
                                ->orWhereRaw($this->normalizedSql('word_variant') . ' LIKE ?', ['%' . $normalizedSearch . '%'])
                            ->orWhere('lexical_id', $search);
                    });
            });
        }

        if ($request->filled('pos')) {
            $query->where('pos', $request->pos);
        }

        if ($request->filled('source')) {
            $query->whereHas('senses', function ($query) use ($request) {
                $query->where('source_dictionary', $request->source)
                    ->orWhere('source', $request->source);
            });
        }

        if ($request->filled('completion_status') && $request->completion_status !== 'all') {
            $query->completionStatus($request->completion_status);
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
        $totalLemmas = Lemma::count();
        $completeLemmas = Lemma::complete()->count();
        $pendingCompletion = Lemma::pendingCompletion()->count();

        $sources = Sense::query()
            ->select('source_dictionary', DB::raw('COUNT(*) as total'))
            ->whereNotNull('source_dictionary')
            ->groupBy('source_dictionary')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return response()->json([
            'total_lemmas' => $totalLemmas,
            'pending_lemmas' => Lemma::where('status', 'pending')->count(),
            'approved_lemmas' => Lemma::where('status', 'approved')->count(),
            'complete_lemmas' => $completeLemmas,
            'pending_completion_lemmas' => $pendingCompletion,
            'completion_percentage' => $totalLemmas > 0 ? round(($completeLemmas / $totalLemmas) * 100, 1) : 0,
            'total_senses' => Sense::count(),
            'open_lexicon_entries' => Sense::whereNotNull('lexical_id')->count(),
            'variant_entries' => Sense::whereNotNull('word_variant')->where('word_variant', '<>', '')->count(),
            'sources' => $sources,
            'pending_by_pos' => Lemma::pendingCompletion()
                ->select('pos', DB::raw('COUNT(*) as total'))
                ->groupBy('pos')
                ->orderByDesc('total')
                ->limit(10)
                ->get(),
            'pending_by_domain' => Sense::query()
                ->join('lemmas', 'lemmas.id', '=', 'senses.lemma_id')
                ->where('lemmas.completion_status', Lemma::COMPLETION_PENDING)
                ->select('senses.domain', DB::raw('COUNT(DISTINCT lemmas.id) as total'))
                ->groupBy('senses.domain')
                ->orderByDesc('total')
                ->limit(10)
                ->get(),
            'pending_by_source' => Sense::query()
                ->join('lemmas', 'lemmas.id', '=', 'senses.lemma_id')
                ->where('lemmas.completion_status', Lemma::COMPLETION_PENDING)
                ->select('senses.source_dictionary', DB::raw('COUNT(DISTINCT lemmas.id) as total'))
                ->groupBy('senses.source_dictionary')
                ->orderByDesc('total')
                ->limit(10)
                ->get(),
            'recently_completed' => Lemma::complete()
                ->orderByDesc('completed_at')
                ->limit(10)
                ->get(['id', 'public_id', 'lemma', 'normalized_lemma', 'pos', 'completed_at', 'completed_by', 'completion_score']),
        ]);
    }

    public function senses(Request $request)
    {
        $query = Sense::query()
            ->with(['lemma:id,lemma,normalized_lemma,pos,status'])
            ->select([
                'id',
                'public_id',
                'lemma_id',
                'lexical_id',
                'entry_id',
                'sense_order',
                'definition',
                'definition_en',
                'english_equivalents',
                'definition_sd',
                'short_gloss',
                'full_definition',
                'usage_label',
                'part_of_speech',
                'word_variant',
                'domain',
                'register',
                'dialect',
                'language_direction',
                'source_dictionary',
                'source',
                'source_entry_id',
                'review_status',
                'status',
                'created_at',
            ]);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $normalizedSearch = DictionaryText::normalizeForLookup($search);
            $query->where(function ($query) use ($search, $normalizedSearch) {
                $query->where('definition', 'like', '%' . $search . '%')
                    ->orWhere('definition_en', 'like', '%' . $search . '%')
                    ->orWhere('definition_sd', 'like', '%' . $search . '%')
                    ->orWhere('short_gloss', 'like', '%' . $search . '%')
                    ->orWhere('full_definition', 'like', '%' . $search . '%')
                    ->orWhere('normalized_definition', 'like', '%' . $search . '%')
                    ->orWhere('lexical_id', $search)
                    ->orWhere('source_dictionary', 'like', '%' . $search . '%')
                    ->orWhere('source', 'like', '%' . $search . '%')
                    ->orWhere('source_entry_id', 'like', '%' . $search . '%')
                    ->orWhere('word_variant', 'like', '%' . $search . '%')
                    ->orWhereRaw($this->normalizedSql('word_variant') . ' LIKE ?', ['%' . $normalizedSearch . '%'])
                    ->orWhereHas('lemma', function ($query) use ($search, $normalizedSearch) {
                        $query->where('lemma', 'like', '%' . $search . '%')
                            ->orWhere('normalized_lemma', 'like', '%' . $search . '%')
                            ->orWhereRaw($this->normalizedSql('lemma') . ' LIKE ?', ['%' . $normalizedSearch . '%'])
                            ->orWhereRaw($this->normalizedSql('normalized_lemma') . ' LIKE ?', ['%' . $normalizedSearch . '%']);
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
                'source',
                'source_entry_id',
            ])
            ->whereNotNull('word_variant')
            ->where('word_variant', '<>', '');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $normalizedSearch = DictionaryText::normalizeForLookup($search);
            $query->where(function ($query) use ($search, $normalizedSearch) {
                $query->where('word_variant', 'like', '%' . $search . '%')
                    ->orWhereRaw($this->normalizedSql('word_variant') . ' LIKE ?', ['%' . $normalizedSearch . '%'])
                    ->orWhere('definition', 'like', '%' . $search . '%')
                    ->orWhereHas('lemma', function ($query) use ($search, $normalizedSearch) {
                        $query->where('lemma', 'like', '%' . $search . '%')
                            ->orWhere('normalized_lemma', 'like', '%' . $search . '%')
                            ->orWhereRaw($this->normalizedSql('lemma') . ' LIKE ?', ['%' . $normalizedSearch . '%'])
                            ->orWhereRaw($this->normalizedSql('normalized_lemma') . ' LIKE ?', ['%' . $normalizedSearch . '%']);
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
                'source_dictionary' => $sense->source_dictionary ?? $sense->source,
                'source_entry_id' => $sense->source_entry_id,
                'part_of_speech' => $sense->part_of_speech,
                'definition' => $sense->definition,
                'lexical_id' => $sense->lexical_id,
            ];
        });

        return response()->json($page);
    }

    public function qa(DictionaryCompletionService $completion)
    {
        $missingSenses = Lemma::query()
            ->withCount('senses')
            ->doesntHave('senses')
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'lemma', 'status', 'completion_status', 'created_at']);

        $pending = Lemma::query()
            ->withCount('senses')
            ->pendingCompletion()
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get(['id', 'lemma', 'status', 'completion_status', 'completion_score', 'updated_at']);

        $missingNormalized = Lemma::query()
            ->whereNull('normalized_lemma')
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'lemma', 'status', 'completion_status']);

        $missingDefinitions = Sense::query()
            ->with(['lemma:id,lemma,completion_status'])
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->whereNull('definition')->orWhere('definition', '');
                })
                    ->where(function ($query) {
                        $query->whereNull('short_gloss')->orWhere('short_gloss', '');
                    })
                    ->where(function ($query) {
                        $query->whereNull('full_definition')->orWhere('full_definition', '');
                    });
            })
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'lemma_id', 'lexical_id', 'definition', 'short_gloss', 'full_definition']);

        $emptyPos = Lemma::query()
            ->where(function ($query) {
                $query->whereNull('pos')->orWhere('pos', '');
            })
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'lemma', 'status', 'completion_status']);

        $duplicateLemmas = Lemma::query()
            ->select('lemma', DB::raw('COUNT(*) as total'))
            ->groupBy('lemma')
            ->having('total', '>', 1)
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $duplicateLexicalIds = Sense::query()
            ->select('lexical_id', DB::raw('COUNT(*) as total'))
            ->whereNotNull('lexical_id')
            ->groupBy('lexical_id')
            ->having('total', '>', 1)
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $malformedDirections = Sense::query()
            ->whereNotNull('language_direction')
            ->where('language_direction', '<>', '')
            ->get(['id', 'lemma_id', 'language_direction', 'lexical_id'])
            ->filter(fn (Sense $sense) => !$completion->isValidLanguageDirection($sense->language_direction));

        $malformedExtra = Sense::query()
            ->whereNotNull('extra')
            ->where('extra', '<>', '')
            ->get(['id', 'lemma_id', 'lexical_id', 'extra'])
            ->filter(function (Sense $sense) {
                json_decode((string) $sense->extra, true);

                return json_last_error() !== JSON_ERROR_NONE;
            });

        return response()->json([
            'summary' => [
                'pending_lemmas' => Lemma::pendingCompletion()->count(),
                'complete_lemmas' => Lemma::complete()->count(),
                'lemmas_without_senses' => Lemma::doesntHave('senses')->count(),
                'lemmas_without_normalized_form' => Lemma::whereNull('normalized_lemma')->count(),
                'senses_without_definitions' => Sense::query()
                    ->where(function ($query) {
                        $query->where(function ($query) {
                            $query->whereNull('definition')->orWhere('definition', '');
                        })
                            ->where(function ($query) {
                                $query->whereNull('short_gloss')->orWhere('short_gloss', '');
                            })
                            ->where(function ($query) {
                                $query->whereNull('full_definition')->orWhere('full_definition', '');
                            });
                    })
                    ->count(),
                'empty_pos_lemmas' => Lemma::query()
                    ->where(function ($query) {
                        $query->whereNull('pos')->orWhere('pos', '');
                    })
                    ->count(),
                'duplicate_lemma_groups' => DB::query()
                    ->fromSub(
                        Lemma::query()->select('lemma')->groupBy('lemma')->havingRaw('COUNT(*) > 1'),
                        'duplicate_lemmas'
                    )
                    ->count(),
                'duplicate_lexical_id_groups' => DB::query()
                    ->fromSub(
                        Sense::query()->select('lexical_id')->whereNotNull('lexical_id')->groupBy('lexical_id')->havingRaw('COUNT(*) > 1'),
                        'duplicate_lexical_ids'
                    )
                    ->count(),
                'malformed_language_directions' => $malformedDirections->count(),
                'malformed_extra_json' => $malformedExtra->count(),
            ],
            'issues' => [
                'missing_senses' => $missingSenses,
                'pending' => $pending,
                'missing_normalized' => $missingNormalized,
                'missing_definitions' => $missingDefinitions,
                'empty_pos' => $emptyPos,
                'duplicate_lemmas' => $duplicateLemmas,
                'duplicate_lexical_ids' => $duplicateLexicalIds,
                'malformed_language_directions' => $malformedDirections->take(10)->values(),
                'malformed_extra_json' => $malformedExtra->take(10)->values(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'lemma' => 'required|string',
            'normalized_lemma' => 'nullable|string',
            'pos' => 'nullable|string',
            'transliteration' => 'nullable|string',
            'ipa' => 'nullable|string',
            'phonetic' => 'nullable|string',
            'pronunciation_simple' => 'nullable|string',
            'audio_url' => 'nullable|url',
            'syllabification' => 'nullable|string',
            'etymology' => 'nullable|string',
            'notes' => 'nullable|string',
            'source_confidence' => 'nullable|numeric|min:0|max:100',
            'search_keywords_json' => 'nullable|array',
            'metadata_json' => 'nullable|array',
            'status' => 'nullable|in:pending,approved,rejected',
            'completion_notes' => 'nullable|string',
            'variants_reviewed' => 'nullable|boolean',
            'examples_reviewed' => 'nullable|boolean',
            'morphology_reviewed' => 'nullable|boolean',
            'pronunciation_reviewed' => 'nullable|boolean',
        ]);

        $validated['normalized_lemma'] = $validated['normalized_lemma'] ?? $this->defaultNormalizedLemma($validated['lemma']);
        $validated['completion_status'] = Lemma::COMPLETION_PENDING;

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
        $lemma = Lemma::with([
            'senses.examples',
            'morphology',
            'variants',
            'lemmaRelations',
            'inflections',
            'idiomaticExpressions',
        ])->findOrFail($id);

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
        $payload['completion'] = app(DictionaryCompletionService::class)->evaluate($lemma);
        $payload['structured_entry'] = app(StructuredDictionaryEntryService::class)->build($lemma);

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
            'source' => $sense->source,
            'source_entry_id' => $sense->source_entry_id,
            'publisher' => $sense->publisher ?: $this->metadataString($extra['publisher'] ?? null),
            'publisher_url' => $this->metadataString($extra['publisher_url'] ?? null),
            'prepared_by' => $this->metadataString($extra['prepared_by'] ?? null),
            'license' => $sense->license,
            'import_version' => $sense->import_version,
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
            'normalized_lemma' => 'nullable|string',
            'pos' => 'nullable|string',
            'transliteration' => 'nullable|string',
            'ipa' => 'nullable|string',
            'phonetic' => 'nullable|string',
            'pronunciation_simple' => 'nullable|string',
            'audio_url' => 'nullable|url',
            'syllabification' => 'nullable|string',
            'etymology' => 'nullable|string',
            'notes' => 'nullable|string',
            'source_confidence' => 'nullable|numeric|min:0|max:100',
            'search_keywords_json' => 'nullable|array',
            'metadata_json' => 'nullable|array',
            'status' => 'nullable|in:pending,approved,rejected',
            'completion_notes' => 'nullable|string',
            'variants_reviewed' => 'nullable|boolean',
            'examples_reviewed' => 'nullable|boolean',
            'morphology_reviewed' => 'nullable|boolean',
            'pronunciation_reviewed' => 'nullable|boolean',
        ]);

        if (array_key_exists('lemma', $validated) && !array_key_exists('normalized_lemma', $validated)) {
            $validated['normalized_lemma'] = $this->defaultNormalizedLemma($validated['lemma']);
        }

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

        foreach (['definition', 'definition_en', 'definition_sd', 'short_gloss', 'full_definition', 'usage_notes', 'usage_label', 'domain', 'language_direction', 'source_dictionary', 'source', 'source_entry_id', 'publisher', 'license', 'register', 'dialect'] as $field) {
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
            'english_equivalents' => 'nullable|array',
            'english_equivalents.*' => 'nullable|string',
            'definition_sd' => 'nullable|string',
            'short_gloss' => 'nullable|string|max:255',
            'full_definition' => 'nullable|string',
            'usage_notes' => 'nullable|string',
            'usage_label' => 'nullable|string|max:255',
            'sense_order' => 'nullable|integer|min:0',
            'domain' => 'nullable|string',
            'register' => 'nullable|string',
            'dialect' => 'nullable|string',
            'confidence' => 'nullable|integer|min:0|max:100',
            'language_direction' => 'nullable|string|max:100',
            'source_dictionary' => 'nullable|string|max:150',
            'source' => 'nullable|string',
            'source_entry_id' => 'nullable|string|max:100',
            'publisher' => 'nullable|string',
            'license' => 'nullable|string',
            'import_version' => 'nullable|string',
            'status' => 'nullable|in:pending,approved',
            'review_status' => 'nullable|in:unreviewed,reviewed,curated,needs_work',
        ]);

        foreach (['definition_en', 'definition_sd', 'short_gloss', 'full_definition', 'usage_notes', 'usage_label', 'domain', 'register', 'dialect', 'language_direction', 'source_dictionary', 'source', 'source_entry_id', 'publisher', 'license', 'import_version'] as $field) {
            if (($validated[$field] ?? null) === '') {
                $validated[$field] = null;
            }
        }
        $validated['english_equivalents'] = $this->cleanStringArray($validated['english_equivalents'] ?? []);

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
            'english_equivalents' => 'nullable|array',
            'english_equivalents.*' => 'nullable|string',
            'definition_sd' => 'nullable|string',
            'short_gloss' => 'nullable|string|max:255',
            'full_definition' => 'nullable|string',
            'usage_notes' => 'nullable|string',
            'usage_label' => 'nullable|string|max:255',
            'sense_order' => 'nullable|integer|min:0',
            'domain' => 'nullable|string',
            'register' => 'nullable|string',
            'dialect' => 'nullable|string',
            'confidence' => 'nullable|integer|min:0|max:100',
            'language_direction' => 'nullable|string|max:100',
            'source_dictionary' => 'nullable|string|max:150',
            'source' => 'nullable|string',
            'source_entry_id' => 'nullable|string|max:100',
            'publisher' => 'nullable|string',
            'license' => 'nullable|string',
            'import_version' => 'nullable|string',
            'status' => 'nullable|in:pending,approved',
            'review_status' => 'nullable|in:unreviewed,reviewed,curated,needs_work',
        ]);

        if (array_key_exists('english_equivalents', $validated)) {
            $validated['english_equivalents'] = $this->cleanStringArray($validated['english_equivalents']);
        }

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
    public function storeExample(Request $request, $senseId)
    {
        $validated = $request->validate([
            'sentence' => 'required|string',
            'romanization' => 'nullable|string',
            'translation' => 'nullable|string',
            'source' => 'nullable|string',
            'citation' => 'nullable|string',
            'quality_flag' => 'nullable|in:unreviewed,good,needs_work,rejected',
            'review_status' => 'nullable|in:unreviewed,reviewed,needs_work',
            'corpus_sentence_id' => 'nullable|integer',
        ]);

        $example = SenseExample::create([
            'sense_id' => $senseId,
            ...$validated,
        ]);

        return response()->json($example, 201);
    }

    public function updateExample(Request $request, $id)
    {
        $example = SenseExample::findOrFail($id);
        $validated = $request->validate([
            'sentence' => 'string',
            'romanization' => 'nullable|string',
            'translation' => 'nullable|string',
            'source' => 'nullable|string',
            'citation' => 'nullable|string',
            'quality_flag' => 'nullable|in:unreviewed,good,needs_work,rejected',
            'review_status' => 'nullable|in:unreviewed,reviewed,needs_work',
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
            'review_status' => 'nullable|in:unreviewed,reviewed,needs_work',
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
        Lemma::findOrFail($lemmaId);

        foreach (['variant', 'type', 'romanization', 'dialect', 'note', 'source', 'source_entry_id'] as $field) {
            if ($request->has($field) && is_string($request->input($field))) {
                $request->merge([$field => trim($request->input($field))]);
            }
        }

        $validated = $request->validate([
            'variant' => 'required|string',
            'type' => ['required', Rule::in(Variant::TYPES)],
            'romanization' => 'nullable|string',
            'dialect' => 'nullable|string',
            'note' => 'nullable|string',
            'source' => 'nullable|string',
            'source_entry_id' => 'nullable|string|max:100',
            'review_status' => 'nullable|in:unreviewed,reviewed,needs_work',
        ]);

        foreach (['romanization', 'dialect', 'note', 'source', 'source_entry_id'] as $field) {
            if (($validated[$field] ?? null) === '') {
                $validated[$field] = null;
            }
        }
        $validated['normalized_variant'] = DictionaryText::normalizeForLookup($validated['variant']);

        $variant = Variant::firstOrCreate([
            'lemma_id' => $lemmaId,
            'variant' => $validated['variant'],
        ], [
            'normalized_variant' => $validated['normalized_variant'],
            'dialect' => $validated['dialect'] ?? null,
            'type' => $validated['type'],
            'romanization' => $validated['romanization'] ?? null,
            'note' => $validated['note'] ?? null,
            'source' => $validated['source'] ?? null,
            'source_entry_id' => $validated['source_entry_id'] ?? null,
            'review_status' => $validated['review_status'] ?? 'unreviewed',
        ]);

        if (!$variant->wasRecentlyCreated) {
            $variant->update([
                'normalized_variant' => $validated['normalized_variant'],
                'type' => $validated['type'],
                'romanization' => $validated['romanization'] ?? $variant->romanization,
                'dialect' => $validated['dialect'] ?? $variant->dialect,
                'note' => $validated['note'] ?? $variant->note,
                'source' => $validated['source'] ?? $variant->source,
                'source_entry_id' => $validated['source_entry_id'] ?? $variant->source_entry_id,
                'review_status' => $validated['review_status'] ?? $variant->review_status,
            ]);
        }

        return response()->json($variant->fresh(), $variant->wasRecentlyCreated ? 201 : 200);
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

    public function completion($id, DictionaryCompletionService $completion)
    {
        $lemma = Lemma::with(['senses.examples', 'morphology', 'variants'])->findOrFail($id);

        return response()->json($completion->evaluate($lemma));
    }

    public function updateCompletion(Request $request, $id, DictionaryCompletionService $completion)
    {
        $lemma = Lemma::with(['senses.examples', 'morphology', 'variants'])->findOrFail($id);

        $validated = $request->validate([
            'completion_status' => ['required', Rule::in([Lemma::COMPLETION_PENDING, Lemma::COMPLETION_COMPLETE])],
            'completion_notes' => 'nullable|string',
        ]);

        $checklist = $completion->evaluate($lemma);

        if ($validated['completion_status'] === Lemma::COMPLETION_COMPLETE && !$checklist['is_complete']) {
            return response()->json([
                'message' => 'This lemma is still missing required dictionary review items.',
                'completion' => $checklist,
            ], 422);
        }

        $lemma->update([
            'completion_status' => $validated['completion_status'],
            'completed_at' => $validated['completion_status'] === Lemma::COMPLETION_COMPLETE ? now() : null,
            'completed_by' => $validated['completion_status'] === Lemma::COMPLETION_COMPLETE ? auth()->id() : null,
            'completion_notes' => $validated['completion_notes'] ?? $lemma->completion_notes,
            'completion_score' => $checklist['score'],
            'checklist_json' => $checklist,
        ]);

        return response()->json([
            'message' => $lemma->completion_status === Lemma::COMPLETION_COMPLETE
                ? 'Lemma marked complete.'
                : 'Lemma marked pending.',
            'lemma' => $lemma->fresh(),
            'completion' => $completion->evaluate($lemma->fresh(['senses.examples', 'morphology', 'variants'])),
        ]);
    }

    // Relation Methods
    public function storeRelation(Request $request, $lemmaId)
    {
        $validated = $request->validate([
            'relation_type' => 'required|in:synonym,antonym,hypernym,related',
            'related_word' => 'required|string',
            'romanization' => 'nullable|string',
            'note' => 'nullable|string',
            'gloss' => 'nullable|string|max:255',
            'part_of_speech' => 'nullable|string|max:255',
            'source' => 'nullable|string',
        ]);

        $relation = \App\Models\LemmaRelation::create([
            'lemma_id' => $lemmaId,
            'relation_type' => $validated['relation_type'],
            'related_word' => $validated['related_word'],
            'romanization' => $validated['romanization'] ?? null,
            'note' => $validated['note'] ?? null,
            'gloss' => $validated['gloss'] ?? null,
            'part_of_speech' => $validated['part_of_speech'] ?? null,
            'source' => $validated['source'] ?? null,
        ]);

        return response()->json($relation, 201);
    }

    public function destroyRelation($id)
    {
        $relation = \App\Models\LemmaRelation::findOrFail($id);
        $relation->delete();
        return response()->json(null, 204);
    }

    public function storeInflection(Request $request, $lemmaId)
    {
        Lemma::findOrFail($lemmaId);

        $validated = $request->validate([
            'form' => 'required|string',
            'romanization' => 'nullable|string',
            'description' => 'nullable|string|max:255',
            'source' => 'nullable|string',
            'review_status' => 'nullable|in:unreviewed,reviewed,needs_work',
        ]);

        $inflection = LemmaInflection::firstOrCreate([
            'lemma_id' => $lemmaId,
            'form' => trim($validated['form']),
        ], [
            'romanization' => $this->nullableTrimmed($validated['romanization'] ?? null),
            'description' => $this->nullableTrimmed($validated['description'] ?? null),
            'source' => $this->nullableTrimmed($validated['source'] ?? null),
            'review_status' => $validated['review_status'] ?? 'unreviewed',
        ]);

        return response()->json($inflection->fresh(), $inflection->wasRecentlyCreated ? 201 : 200);
    }

    public function destroyInflection($id)
    {
        $inflection = LemmaInflection::findOrFail($id);
        $inflection->delete();

        return response()->json(null, 204);
    }

    public function storeIdiomaticExpression(Request $request, $lemmaId)
    {
        Lemma::findOrFail($lemmaId);

        $validated = $request->validate([
            'phrase' => 'required|string',
            'romanization' => 'nullable|string',
            'english_gloss' => 'nullable|string|max:255',
            'example_sindhi' => 'nullable|string',
            'example_english' => 'nullable|string',
            'source' => 'nullable|string',
            'review_status' => 'nullable|in:unreviewed,reviewed,needs_work',
        ]);

        $expression = LemmaIdiomaticExpression::firstOrCreate([
            'lemma_id' => $lemmaId,
            'phrase' => trim($validated['phrase']),
        ], [
            'romanization' => $this->nullableTrimmed($validated['romanization'] ?? null),
            'english_gloss' => $this->nullableTrimmed($validated['english_gloss'] ?? null),
            'example_sindhi' => $this->nullableTrimmed($validated['example_sindhi'] ?? null),
            'example_english' => $this->nullableTrimmed($validated['example_english'] ?? null),
            'source' => $this->nullableTrimmed($validated['source'] ?? null),
            'review_status' => $validated['review_status'] ?? 'unreviewed',
        ]);

        return response()->json($expression->fresh(), $expression->wasRecentlyCreated ? 201 : 200);
    }

    public function destroyIdiomaticExpression($id)
    {
        $expression = LemmaIdiomaticExpression::findOrFail($id);
        $expression->delete();

        return response()->json(null, 204);
    }

    private function defaultNormalizedLemma(string $lemma): string
    {
        return DictionaryText::normalizeForLookup($lemma);
    }

    private function cleanStringArray(array $values): array
    {
        return collect($values)
            ->map(fn ($value) => $this->nullableTrimmed($value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function nullableTrimmed(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
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

    // Scraping Method



}
