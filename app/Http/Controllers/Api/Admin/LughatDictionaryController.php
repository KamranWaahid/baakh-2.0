<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\LughatLemma;
use App\Models\LughatRelation;
use App\Models\LughatSense;
use App\Models\LughatSenseExample;
use App\Models\LughatMorphology;
use App\Models\LughatVariant;
use App\Models\LughatIdiomaticExpression;
use App\Models\LughatInflection;
use App\Models\LughatExpression;
use App\Services\LughatCompletionService;
use App\Services\LughatExpressionService;
use App\Services\LughatLemmaEditorJsonService;
use App\Services\LughatLemmaJsonImportService;
use App\Services\LughatMissingWordsService;
use App\Services\LughatPoetryRomanService;
use App\Services\LughatPoetrySenseAnnotationService;
use App\Services\LughatPoetryWordImporter;
use App\Services\LughatStructuredEntryService;
use App\Services\PoetryRomanSyncService;
use App\Services\RomanizerService;
use App\Support\DictionaryText;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class LughatDictionaryController extends Controller
{
    public function index(Request $request)
    {
        $query = LughatLemma::withCount(['senses', 'lemmaRelations', 'variants'])
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

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $limit = min(100, max(1, (int) $request->get('limit', 20)));

        return response()->json($query->orderBy('lemma')->paginate($limit));
    }

    /**
     * Peek the next poetry that would be imported (oldest by id after cursor).
     */
    public function poetryImportStatus(Request $request, LughatPoetryWordImporter $importer)
    {
        $validated = $request->validate([
            'poetry_id' => 'nullable|integer|min:1',
        ]);

        return response()->json($importer->peekNext(
            isset($validated['poetry_id']) ? (int) $validated['poetry_id'] : null
        ));
    }

    /**
     * Pull all words from the next poetry (oldest first) into Baakh Lughat.
     * Creates word-only lemmas; skips duplicates; strips zabar/pesh/zer.
     * Advances the cursor so the next click processes the following poetry.
     */
    public function importFromPoetry(Request $request, LughatPoetryWordImporter $importer)
    {
        $validated = $request->validate([
            'poetry_id' => 'nullable|integer|min:1',
            'reset' => 'nullable|boolean',
        ]);

        $result = $importer->importNext(
            isset($validated['poetry_id']) ? (int) $validated['poetry_id'] : null,
            (bool) ($validated['reset'] ?? false)
        );

        return response()->json($result);
    }

    /**
     * Baakh Lughat senses for a poetry token (preferred sense first when poetry_id given).
     */
    public function lookupSenses(Request $request, LughatPoetrySenseAnnotationService $annotations)
    {
        $validated = $request->validate([
            'q' => 'required|string|min:1|max:255',
            'poetry_id' => 'nullable|integer|min:1',
        ]);

        return response()->json($annotations->lookupSenses(
            $validated['q'],
            isset($validated['poetry_id']) ? (int) $validated['poetry_id'] : null
        ));
    }

    /**
     * Resolve lemma / form / variant for a query (form → lemma).
     */
    public function search(Request $request, LughatExpressionService $expressions)
    {
        $validated = $request->validate([
            'q' => 'required|string|min:1|max:255',
        ]);

        $q = trim($validated['q']);
        $normalized = DictionaryText::normalizeForLookup($q);
        $matches = [];

        // Phrase keys: جامِ محبت / جام محبت / جاممحبت
        foreach ($expressions->search($q, 10) as $hit) {
            $matches[] = $hit;
        }

        $lemmaHits = LughatLemma::query()
            ->where('normalized_lemma', $normalized)
            ->orWhere('lemma', $q)
            ->limit(10)
            ->get(['id', 'public_id', 'lemma', 'normalized_lemma', 'transliteration', 'pos', 'token_frequency', 'poem_frequency']);

        foreach ($lemmaHits as $lemma) {
            $matches[] = [
                'match_type' => 'lemma',
                'matched_text' => $lemma->lemma,
                'lemma' => [
                    'id' => $lemma->id,
                    'public_id' => $lemma->public_id,
                    'lemma' => $lemma->lemma,
                    'transliteration' => $lemma->transliteration,
                    'pos' => $lemma->pos,
                ],
                'frequencies' => [
                    'token' => (int) $lemma->token_frequency,
                    'poem' => (int) $lemma->poem_frequency,
                ],
                'poetic_expressions' => $expressions->expressionsForLemma((int) $lemma->id, 12),
            ];
        }

        $formHits = \App\Models\LughatWordForm::query()
            ->with('lemma:id,public_id,lemma,transliteration,pos')
            ->where('normalized_form', $normalized)
            ->limit(10)
            ->get();

        foreach ($formHits as $form) {
            if (!$form->lemma) {
                $matches[] = [
                    'match_type' => 'unlinked_form',
                    'matched_text' => $form->form,
                    'form_features' => $form->morph_features_json,
                    'lemma' => null,
                ];
                continue;
            }
            $matches[] = [
                'match_type' => $form->form_type === 'inflected' ? 'inflected_form' : 'word_form',
                'matched_text' => $form->form,
                'form_features' => $form->morph_features_json,
                'lemma' => [
                    'id' => $form->lemma->id,
                    'public_id' => $form->lemma->public_id,
                    'lemma' => $form->lemma->lemma,
                    'transliteration' => $form->lemma->transliteration,
                    'pos' => $form->lemma->pos,
                ],
            ];
        }

        $inflectionHits = \App\Models\LughatInflection::query()
            ->with('lemma:id,public_id,lemma,transliteration,pos')
            ->where('normalized_form', $normalized)
            ->limit(10)
            ->get();

        foreach ($inflectionHits as $inf) {
            if (!$inf->lemma) {
                continue;
            }
            $matches[] = [
                'match_type' => 'inflected_form',
                'matched_text' => $inf->form,
                'form_features' => array_filter([
                    'gender' => $inf->gender,
                    'number' => $inf->number,
                    'case' => $inf->case_name,
                    'person' => $inf->person,
                    'stem' => $inf->stem,
                    'suffix' => $inf->suffix,
                ]),
                'lemma' => [
                    'id' => $inf->lemma->id,
                    'public_id' => $inf->lemma->public_id,
                    'lemma' => $inf->lemma->lemma,
                    'transliteration' => $inf->lemma->transliteration,
                    'pos' => $inf->lemma->pos,
                ],
            ];
        }

        // Deduplicate by match_type + lemma/expression id + matched_text
        $seen = [];
        $unique = [];
        foreach ($matches as $m) {
            $key = ($m['match_type'] ?? '')
                . '|' . ($m['lemma']['id'] ?? $m['expression']['id'] ?? 'x')
                . '|' . ($m['matched_text'] ?? '');
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique[] = $m;
        }

        return response()->json([
            'query' => $q,
            'normalized_query' => $normalized,
            'normalized_expression_query' => DictionaryText::normalizeExpression($q),
            'matches' => $unique,
        ]);
    }

    /**
     * Create word-only Baakh Lughat stubs (+ optional multiword expression).
     * Skips lemmas that already exist.
     */
    public function addStubs(Request $request, LughatMissingWordsService $missing, LughatExpressionService $expressions)
    {
        $validated = $request->validate([
            'words' => 'required|array|min:1',
            'words.*' => 'required|string|max:255',
            'expression' => 'nullable|array',
            'expression.expression' => 'required_with:expression|string|max:500',
            'expression.expression_type' => ['nullable', Rule::in(LughatExpression::TYPES)],
            'expression.literal_gloss' => 'nullable|string|max:500',
            'expression.poetic_gloss' => 'nullable|string',
            'expression.note' => 'nullable|string',
        ]);

        $result = $missing->createStubs($validated['words']);

        $expressionRow = null;
        if (!empty($validated['expression']['expression'])) {
            $surface = trim((string) $validated['expression']['expression']);
            $parts = preg_split('/\s+/u', $surface, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            // Ensure component lemmas exist (create stubs for any missing parts)
            if (count($parts) >= 2) {
                $componentResult = $missing->createStubs($parts);
                $result['created'] = array_values(array_merge($result['created'], $componentResult['created']));
                $result['skipped_existing'] = array_values(array_unique(array_merge(
                    $result['skipped_existing'],
                    $componentResult['skipped_existing']
                )));
            }

            $expressionRow = $expressions->upsert([
                'expression' => $surface,
                'expression_type' => $validated['expression']['expression_type'] ?? (
                    count($parts) === 2 && str_ends_with($parts[0] ?? '', DictionaryText::KASRA) ? 'izafat' : 'collocation'
                ),
                'literal_gloss' => $validated['expression']['literal_gloss'] ?? null,
                'poetic_gloss' => $validated['expression']['poetic_gloss'] ?? $validated['expression']['note'] ?? null,
                'register' => 'poetic',
                'status' => 'pending',
                'review_status' => 'unreviewed',
                'confidence' => 80,
                'metadata_json' => ['source' => 'hesudhar_bulk_check'],
            ]);
        }

        return response()->json([
            'message' => 'Baakh Lughat stubs updated.',
            ...$result,
            'expression' => $expressionRow ? [
                'id' => $expressionRow->id,
                'expression' => $expressionRow->expression,
                'expression_type' => $expressionRow->expression_type,
                'literal_gloss' => $expressionRow->literal_gloss,
                'poetic_gloss' => $expressionRow->poetic_gloss,
            ] : null,
        ], 201);
    }

    /**
     * Resolve an existing Baakh Lughat lemma for a surface (with/without airab)
     * or create a word-only stub. Never duplicates a normalize-match.
     */
    public function stubFromSurface(Request $request, LughatPoetryRomanService $roman)
    {
        $validated = $request->validate([
            'surface' => 'required|string|max:255',
        ]);

        try {
            $result = $roman->resolveOrCreateStub($validated['surface'], [
                'source' => 'poetry_create_missing_word',
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['created']
                ? 'Baakh Lughat stub created.'
                : 'Existing Baakh Lughat lemma found (duplicate avoided).',
            ...$result,
        ], $result['created'] ? 201 : 200);
    }

    public function storeExpression(Request $request, LughatExpressionService $expressions)
    {
        $validated = $request->validate([
            'expression' => 'required|string|max:500',
            'expression_type' => ['nullable', Rule::in(LughatExpression::TYPES)],
            'romanization' => 'nullable|string|max:255',
            'definition_sd' => 'nullable|string',
            'definition_en' => 'nullable|string',
            'literal_gloss' => 'nullable|string|max:500',
            'poetic_gloss' => 'nullable|string',
            'register' => 'nullable|string|max:40',
            'status' => 'nullable|in:pending,approved,rejected',
            'confidence' => 'nullable|numeric|min:0|max:100',
            'review_status' => 'nullable|in:unreviewed,reviewed,needs_work',
            'components' => 'nullable|array',
            'components.*.surface_form' => 'required_with:components|string',
            'components.*.position' => 'nullable|integer|min:1',
            'components.*.lemma_id' => 'nullable|integer|exists:lughat_lemmas,id',
            'components.*.word_form_id' => 'nullable|integer|exists:lughat_word_forms,id',
            'components.*.connector' => 'nullable|string|max:40',
            'components.*.role' => 'nullable|string|max:40',
        ]);

        $row = $expressions->upsert($validated);

        return response()->json($row, 201);
    }

    public function destroyExpression($id)
    {
        $expression = LughatExpression::findOrFail($id);
        $expression->delete();

        return response()->json(null, 204);
    }

    public function stats()
    {
        $totalLemmas = LughatLemma::count();
        $completeLemmas = LughatLemma::complete()->count();
        $pendingCompletion = LughatLemma::pendingCompletion()->count();

        $sources = LughatSense::query()
            ->select('source_dictionary', DB::raw('COUNT(*) as total'))
            ->whereNotNull('source_dictionary')
            ->groupBy('source_dictionary')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return response()->json([
            'name' => 'Baakh Lughat',
            'total_lemmas' => $totalLemmas,
            'pending_lemmas' => LughatLemma::where('status', 'pending')->count(),
            'approved_lemmas' => LughatLemma::where('status', 'approved')->count(),
            'complete_lemmas' => $completeLemmas,
            'pending_completion_lemmas' => $pendingCompletion,
            'completion_percentage' => $totalLemmas > 0 ? round(($completeLemmas / $totalLemmas) * 100, 1) : 0,
            'total_senses' => LughatSense::count(),
            'open_lexicon_entries' => LughatSense::whereNotNull('lexical_id')->count(),
            'variant_entries' => LughatSense::whereNotNull('word_variant')->where('word_variant', '<>', '')->count(),
            'sources' => $sources,
            'pending_by_pos' => LughatLemma::pendingCompletion()
                ->select('pos', DB::raw('COUNT(*) as total'))
                ->groupBy('pos')
                ->orderByDesc('total')
                ->limit(10)
                ->get(),
            'pending_by_domain' => LughatSense::query()
                ->join('lughat_lemmas', 'lughat_lemmas.id', '=', 'lughat_senses.lemma_id')
                ->where('lughat_lemmas.completion_status', LughatLemma::COMPLETION_PENDING)
                ->select('lughat_senses.domain', DB::raw('COUNT(DISTINCT lughat_lemmas.id) as total'))
                ->groupBy('lughat_senses.domain')
                ->orderByDesc('total')
                ->limit(10)
                ->get(),
            'pending_by_source' => LughatSense::query()
                ->join('lughat_lemmas', 'lughat_lemmas.id', '=', 'lughat_senses.lemma_id')
                ->where('lughat_lemmas.completion_status', LughatLemma::COMPLETION_PENDING)
                ->select('lughat_senses.source_dictionary', DB::raw('COUNT(DISTINCT lughat_lemmas.id) as total'))
                ->groupBy('lughat_senses.source_dictionary')
                ->orderByDesc('total')
                ->limit(10)
                ->get(),
            'recently_completed' => LughatLemma::complete()
                ->orderByDesc('completed_at')
                ->limit(10)
                ->get(['id', 'public_id', 'lemma', 'normalized_lemma', 'pos', 'completed_at', 'completed_by', 'completion_score']),
        ]);
    }

    public function senses(Request $request)
    {
        $query = LughatSense::query()
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
        $query = LughatLemma::query()
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
        $query = LughatSense::query()
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

        /** @var \Illuminate\Pagination\LengthAwarePaginator $page */
        $page->through(function (LughatSense $sense) {
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

    public function lemmaSearch(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        if ($search === '') {
            return response()->json([]);
        }

        $normalizedSearch = DictionaryText::normalizeForLookup($search);
        $limit = min(20, max(1, (int) $request->query('limit', 10)));
        $excludeLemmaId = $request->integer('exclude_lemma_id') ?: null;

        $lemmas = LughatLemma::query()
            ->select([
                'id',
                'public_id',
                'lemma',
                'normalized_lemma',
                'transliteration',
                'pos',
                'status',
                'completion_status',
            ])
            ->with([
                'variants' => function ($query) use ($search, $normalizedSearch) {
                    $query->select(['id', 'lemma_id', 'variant', 'normalized_variant', 'romanization', 'type'])
                        ->where(function ($query) use ($search, $normalizedSearch) {
                            $query->where('variant', 'like', '%' . $search . '%')
                                ->orWhere('normalized_variant', 'like', '%' . $search . '%')
                                ->orWhere('romanization', 'like', '%' . $search . '%')
                                ->orWhereRaw($this->normalizedSql('variant') . ' LIKE ?', ['%' . $normalizedSearch . '%'])
                                ->orWhereRaw($this->normalizedSql('normalized_variant') . ' LIKE ?', ['%' . $normalizedSearch . '%']);
                        })
                        ->orderBy('variant');
                },
            ])
            ->when($excludeLemmaId, fn ($query) => $query->whereKeyNot($excludeLemmaId))
            ->where(function ($query) use ($search, $normalizedSearch) {
                $query->where('lemma', 'like', '%' . $search . '%')
                    ->orWhere('normalized_lemma', 'like', '%' . $search . '%')
                    ->orWhere('transliteration', 'like', '%' . $search . '%')
                    ->orWhereRaw($this->normalizedSql('lemma') . ' LIKE ?', ['%' . $normalizedSearch . '%'])
                    ->orWhereRaw($this->normalizedSql('normalized_lemma') . ' LIKE ?', ['%' . $normalizedSearch . '%'])
                    ->orWhereHas('variants', function ($query) use ($search, $normalizedSearch) {
                        $query->where('variant', 'like', '%' . $search . '%')
                            ->orWhere('normalized_variant', 'like', '%' . $search . '%')
                            ->orWhere('romanization', 'like', '%' . $search . '%')
                            ->orWhereRaw($this->normalizedSql('variant') . ' LIKE ?', ['%' . $normalizedSearch . '%'])
                            ->orWhereRaw($this->normalizedSql('normalized_variant') . ' LIKE ?', ['%' . $normalizedSearch . '%']);
                    });
            })
            ->orderByRaw(
                'CASE WHEN lemma = ? THEN 0 WHEN normalized_lemma = ? THEN 1 WHEN transliteration = ? THEN 2 ELSE 3 END',
                [$search, $normalizedSearch, $search]
            )
            ->orderBy('lemma')
            ->limit($limit)
            ->get();

        return response()->json($lemmas->map(fn (LughatLemma $lemma) => $this->lemmaSearchResult($lemma, $search, $normalizedSearch))->values());
    }

    public function qa(LughatCompletionService $completion)
    {
        $missingSenses = LughatLemma::query()
            ->withCount('senses')
            ->doesntHave('senses')
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'lemma', 'status', 'completion_status', 'created_at']);

        $pending = LughatLemma::query()
            ->withCount('senses')
            ->pendingCompletion()
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get(['id', 'lemma', 'status', 'completion_status', 'completion_score', 'updated_at']);

        $missingNormalized = LughatLemma::query()
            ->whereNull('normalized_lemma')
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'lemma', 'status', 'completion_status']);

        $missingDefinitions = LughatSense::query()
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

        $emptyPos = LughatLemma::query()
            ->where(function ($query) {
                $query->whereNull('pos')->orWhere('pos', '');
            })
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'lemma', 'status', 'completion_status']);

        $duplicateLemmas = LughatLemma::query()
            ->select('lemma', DB::raw('COUNT(*) as total'))
            ->groupBy('lemma')
            ->having('total', '>', 1)
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $duplicateLexicalIds = LughatSense::query()
            ->select('lexical_id', DB::raw('COUNT(*) as total'))
            ->whereNotNull('lexical_id')
            ->groupBy('lexical_id')
            ->having('total', '>', 1)
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $malformedDirections = LughatSense::query()
            ->whereNotNull('language_direction')
            ->where('language_direction', '<>', '')
            ->get(['id', 'lemma_id', 'language_direction', 'lexical_id'])
            ->filter(fn (LughatSense $sense) => !$completion->isValidLanguageDirection($sense->language_direction));

        $malformedExtra = LughatSense::query()
            ->whereNotNull('extra')
            ->where('extra', '<>', '')
            ->get(['id', 'lemma_id', 'lexical_id', 'extra'])
            ->filter(function (LughatSense $sense) {
                json_decode((string) $sense->extra, true);

                return json_last_error() !== JSON_ERROR_NONE;
            });

        return response()->json([
            'summary' => [
                'pending_lemmas' => LughatLemma::pendingCompletion()->count(),
                'complete_lemmas' => LughatLemma::complete()->count(),
                'lemmas_without_senses' => LughatLemma::doesntHave('senses')->count(),
                'lemmas_without_normalized_form' => LughatLemma::whereNull('normalized_lemma')->count(),
                'senses_without_definitions' => LughatSense::query()
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
                'empty_pos_lemmas' => LughatLemma::query()
                    ->where(function ($query) {
                        $query->whereNull('pos')->orWhere('pos', '');
                    })
                    ->count(),
                'duplicate_lemma_groups' => DB::query()
                    ->fromSub(
                        LughatLemma::query()->select('lemma')->groupBy('lemma')->havingRaw('COUNT(*) > 1'),
                        'duplicate_lemmas'
                    )
                    ->count(),
                'duplicate_lexical_id_groups' => DB::query()
                    ->fromSub(
                        LughatSense::query()->select('lexical_id')->whereNotNull('lexical_id')->groupBy('lexical_id')->havingRaw('COUNT(*) > 1'),
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
            'poetry_id' => 'nullable|integer',
            'couplet_id' => 'nullable|integer',
        ]);

        $validated['normalized_lemma'] = $validated['normalized_lemma'] ?? $this->defaultNormalizedLemma($validated['lemma']);
        $validated['lookup_base'] = $validated['lookup_base'] ?? DictionaryText::lookupBase($validated['lemma']);
        $validated['completion_status'] = LughatLemma::COMPLETION_PENDING;
        $validated['metadata_json'] = $validated['metadata_json'] ?? [
            'dictionary' => 'Baakh Lughat',
            'version' => '1',
        ];

        // Identity includes airab — نَھن and نُھن are different lemmas (BINARY).
        $existing = LughatLemma::where('language', $validated['language'] ?? 'sd')
            ->where('homograph_number', $validated['homograph_number'] ?? 1)
            ->where(function ($q) use ($validated) {
                $q->whereRaw(DictionaryText::binaryEquals('normalized_lemma'), [$validated['normalized_lemma']])
                    ->orWhereRaw(DictionaryText::binaryEquals('lemma'), [$validated['lemma']]);
            })
            ->first();
        if ($existing) {
            return response()->json([
                'message' => 'This word already exists in Baakh Lughat.',
                'errors' => ['lemma' => ['This word already exists in Baakh Lughat (id ' . $existing->id . ').']],
                'existing_id' => $existing->id,
            ], 422);
        }

        $validated['homograph_number'] = $validated['homograph_number'] ?? 1;
        $validated['language'] = $validated['language'] ?? 'sd';
        $validated['romanization_status'] = $validated['romanization_status'] ?? 'proposed';

        $lemma = LughatLemma::create($validated);

        if (!empty($validated['transliteration'])) {
            $this->syncLemmaTransliteration($lemma, $validated['transliteration']);
        }

        return response()->json($lemma, 201);
    }

    public function show($id, LughatExpressionService $expressions)
    {
        $lemma = LughatLemma::with([
            'senses.examples',
            'morphology',
            'variants',
            'lemmaRelations.relatedLemma',
            'inflections',
            'idiomaticExpressions',
        ])->findOrFail($id);

        // Poetry-sourced stubs stay roman-empty until AI Import JSON fills transliteration.
        $meta = is_array($lemma->metadata_json) ? $lemma->metadata_json : [];
        $fromPoetry = ($meta['source'] ?? null) === 'poetry_import' || filled($lemma->poetry_id);

        if (empty($lemma->transliteration) && !$fromPoetry) {
            $roman = \App\Models\Romanizer::where('word_sd', $lemma->lemma)->first();
            if ($roman) {
                $lemma->transliteration = $roman->word_roman;
            }
        }

        $this->hydrateRelationLemmaLinks($lemma);

        return response()->json($this->lemmaDetailPayload($lemma, $expressions));
    }

    private function lemmaDetailPayload(LughatLemma $lemma, ?LughatExpressionService $expressions = null): array
    {
        $payload = $lemma->toArray();

        $payload['senses'] = $lemma->senses
            ->map(function (LughatSense $sense) use ($lemma) {
                $sensePayload = $sense->toArray();
                $metadata = $this->senseSourceMetadata($sense, $lemma);

                $sensePayload['extra'] = $metadata['extra'];
                $sensePayload['source_metadata'] = $metadata;

                return $sensePayload;
            })
            ->values()
            ->all();

        $manualVariants = $lemma->variants
            ->map(fn (LughatVariant $variant) => array_merge($variant->toArray(), [
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
        $payload['completion'] = app(LughatCompletionService::class)->evaluate($lemma);
        $payload['structured_entry'] = app(LughatStructuredEntryService::class)->build($lemma);
        $payload['poetic_expressions'] = ($expressions ?? app(LughatExpressionService::class))
            ->expressionsForLemma((int) $lemma->id, 40);

        return $payload;
    }

    private function senseSourceMetadata(LughatSense $sense, LughatLemma $lemma): array
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
            'source_dictionary' => $sense->source_dictionary ?: 'Baakh Lughat',
            'source' => $sense->source ?: 'Baakh Lughat',
            'source_entry_id' => $sense->source_entry_id,
            'publisher' => $sense->publisher ?: $this->metadataString($extra['publisher'] ?? null) ?: 'baakh.com',
            'publisher_url' => $this->metadataString($extra['publisher_url'] ?? null) ?: 'https://baakh.com/',
            'prepared_by' => $this->metadataString($extra['prepared_by'] ?? null) ?: 'Kamran Wahid',
            'license' => $sense->license,
            'import_version' => $sense->import_version,
            'source_extra' => $extra['extra'] ?? null,
            'extra' => $extra,
        ];
    }

    private function lemmaSourceSummary(LughatLemma $lemma, array $senses): array
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
            // Always show Baakh Lughat source panel (publisher / prepared by / URL).
            'is_open_lexicon' => true,
            'is_source_term' => $isSourceTerm,
            'word_label' => $isSourceTerm && $primaryLanguage
                ? "{$primaryLanguage} Source Term"
                : 'Word (سنڌي)',
            'primary_language' => $primaryLanguage,
            'language_directions' => $languageDirections,
            'language_labels' => $languageLabels,
            'source_dictionaries' => $sourceDictionaries !== [] ? $sourceDictionaries : ['Baakh Lughat'],
            'domains' => $domains,
            'source_words' => $sourceWords !== [] ? $sourceWords : [$lemma->lemma],
            'normalized_words' => $normalizedWords !== [] ? $normalizedWords : array_values(array_filter([$lemma->normalized_lemma])),
            'lexical_ids' => $lexicalIds,
            'entry_ids' => $entryIds,
            'publisher' => $metadata->pluck('publisher')->first(fn ($value) => filled($value)) ?: 'baakh.com',
            'publisher_url' => $metadata->pluck('publisher_url')->first(fn ($value) => filled($value)) ?: 'https://baakh.com/',
            'prepared_by' => $metadata->pluck('prepared_by')->first(fn ($value) => filled($value)) ?: 'Kamran Wahid',
            'available_morphology_fields' => $this->hasRealMorphology($lemma)
                ? $this->filledMorphologyFields($lemma)
                : [],
        ];
    }

    private function importedVariantsForLemma(LughatLemma $lemma): array
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

    private function hasRealMorphology(LughatLemma $lemma): bool
    {
        return $this->filledMorphologyFields($lemma) !== [];
    }

    private function filledMorphologyFields(LughatLemma $lemma): array
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
        $lemma = LughatLemma::findOrFail($id);

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
            $validated['lookup_base'] = DictionaryText::lookupBase($validated['lemma']);
        }

        $lemma->update($validated);

        if (!empty($validated['transliteration'])) {
            $this->syncLemmaTransliteration($lemma->fresh(), $validated['transliteration']);
        }

        // Nested updates for senses, morphology, variants could be added here if needed
        // For a full CRUD, usually we have separate endpoints or a complex sync logic.
        // Given the UI shows separate sections, we'll keep it simple for now and expand as needed.

        return response()->json($lemma);
    }

    public function destroy($id)
    {
        $lemma = LughatLemma::findOrFail($id);
        $lemma->delete();
        return response()->json(null, 204);
    }

    // LughatSense Methods
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
            'lemma_id' => 'required|integer|exists:lughat_lemmas,id',
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
        $validated['source_dictionary'] = $validated['source_dictionary'] ?: 'Baakh Lughat';
        $validated['source'] = $validated['source'] ?: 'Baakh Lughat';
        $validated['publisher'] = $validated['publisher'] ?: 'baakh.com';
        $extra = is_array($validated['extra'] ?? null) ? $validated['extra'] : [];
        $extra['prepared_by'] = $extra['prepared_by'] ?? 'Kamran Wahid';
        $extra['publisher_url'] = $extra['publisher_url'] ?? 'https://baakh.com/';
        $validated['extra'] = $extra;

        $sense = LughatSense::create($validated);
        return response()->json($sense, 201);
    }

    public function updateSense(Request $request, $id)
    {
        $sense = LughatSense::findOrFail($id);
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
        $sense = LughatSense::findOrFail($id);
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

        $example = LughatSenseExample::create([
            'sense_id' => $senseId,
            ...$validated,
        ]);

        return response()->json($example, 201);
    }

    public function updateExample(Request $request, $id)
    {
        $example = LughatSenseExample::findOrFail($id);
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
        $example = LughatSenseExample::findOrFail($id);
        $example->delete();
        return response()->json(null, 204);
    }

    // LughatMorphology Methods
    public function updateMorphology(Request $request, $lemmaId)
    {
        $lemma = LughatLemma::findOrFail($lemmaId);
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

        $morphology = LughatMorphology::updateOrCreate(
            ['lemma_id' => $lemmaId],
            $validated
        );

        return response()->json($morphology);
    }

    // LughatVariant Methods
    public function storeVariant(Request $request, $lemmaId)
    {
        LughatLemma::findOrFail($lemmaId);

        foreach (['variant', 'type', 'romanization', 'dialect', 'note', 'source', 'source_entry_id'] as $field) {
            if ($request->has($field) && is_string($request->input($field))) {
                $request->merge([$field => trim($request->input($field))]);
            }
        }

        $validated = $request->validate([
            'variant' => 'required|string',
            'type' => ['required', Rule::in(LughatVariant::TYPES)],
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

        $payload = [
            'normalized_variant' => $validated['normalized_variant'],
            'type' => $validated['type'],
            'romanization' => $validated['romanization'] ?? null,
            'dialect' => $validated['dialect'] ?? null,
            'note' => $validated['note'] ?? null,
            'source' => $validated['source'] ?? null,
            'source_entry_id' => $validated['source_entry_id'] ?? null,
            'review_status' => $validated['review_status'] ?? 'unreviewed',
        ];

        // BINARY compare keeps diacritic/spelling variants distinct under unicode_ci.
        $existing = LughatVariant::query()
            ->where('lemma_id', $lemmaId)
            ->whereRaw('variant = BINARY ?', [$validated['variant']])
            ->first();

        if ($existing) {
            $existing->update($payload);

            return response()->json($existing->fresh());
        }

        $variant = LughatVariant::create([
            'lemma_id' => $lemmaId,
            'variant' => $validated['variant'],
            ...$payload,
        ]);

        return response()->json($variant->fresh(), 201);
    }

    public function destroyVariant($id)
    {
        $variant = LughatVariant::findOrFail($id);
        $variant->delete();
        return response()->json(null, 204);
    }

    public function approve($id)
    {
        $lemma = LughatLemma::findOrFail($id);
        $lemma->update(['status' => 'approved']);
        return response()->json(['message' => 'Word approved successfully', 'status' => 'approved']);
    }

    public function importJson(Request $request, $id, LughatLemmaJsonImportService $importer, LughatLemmaEditorJsonService $editorJson)
    {
        $lemma = LughatLemma::findOrFail($id);

        $payload = $request->json()->all();
        if (!is_array($payload) || $payload === []) {
            return response()->json([
                'message' => 'Provide a JSON object for this lemma.',
            ], 422);
        }

        try {
            $updated = $importer->import($lemma, $payload);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => $exception->getMessage() ?: 'Failed to import lemma JSON.',
            ], 500);
        }

        return response()->json($editorJson->build($updated));
    }

    public function editorJson($id, LughatLemmaEditorJsonService $editorJson)
    {
        $lemma = LughatLemma::findOrFail($id);

        return response()->json($editorJson->build($lemma));
    }

    public function completion($id, LughatCompletionService $completion)
    {
        $lemma = LughatLemma::with(['senses.examples', 'morphology', 'variants'])->findOrFail($id);

        return response()->json($completion->evaluate($lemma));
    }

    public function updateCompletion(Request $request, $id, LughatCompletionService $completion)
    {
        $lemma = LughatLemma::with(['senses.examples', 'morphology', 'variants'])->findOrFail($id);

        $validated = $request->validate([
            'completion_status' => ['required', Rule::in([LughatLemma::COMPLETION_PENDING, LughatLemma::COMPLETION_COMPLETE])],
            'completion_notes' => 'nullable|string',
        ]);

        $checklist = $completion->evaluate($lemma);

        if ($validated['completion_status'] === LughatLemma::COMPLETION_COMPLETE && !$checklist['is_complete']) {
            return response()->json([
                'message' => 'This lemma is still missing required dictionary review items.',
                'completion' => $checklist,
            ], 422);
        }

        $lemma->update([
            'completion_status' => $validated['completion_status'],
            'completed_at' => $validated['completion_status'] === LughatLemma::COMPLETION_COMPLETE ? now() : null,
            'completed_by' => $validated['completion_status'] === LughatLemma::COMPLETION_COMPLETE ? auth()->id() : null,
            'completion_notes' => $validated['completion_notes'] ?? $lemma->completion_notes,
            'completion_score' => $checklist['score'],
            'checklist_json' => $checklist,
        ]);

        return response()->json([
            'message' => $lemma->completion_status === LughatLemma::COMPLETION_COMPLETE
                ? 'LughatLemma marked complete.'
                : 'LughatLemma marked pending.',
            'lemma' => $lemma->fresh(),
            'completion' => $completion->evaluate($lemma->fresh(['senses.examples', 'morphology', 'variants'])),
        ]);
    }

    // Relation Methods
    public function storeRelation(Request $request, $lemmaId)
    {
        LughatLemma::findOrFail($lemmaId);

        foreach (['related_word', 'romanization', 'note', 'gloss', 'part_of_speech', 'source'] as $field) {
            if ($request->has($field) && is_string($request->input($field))) {
                $request->merge([$field => trim($request->input($field))]);
            }
        }

        $validated = $request->validate([
            'relation_type' => 'required|in:synonym,antonym,hypernym,related,singular,plural,dialect,derived,usage',
            'related_word' => 'required|string',
            'romanization' => 'nullable|string',
            'note' => 'nullable|string',
            'gloss' => 'nullable|string',
            'part_of_speech' => 'nullable|string|max:255',
            'source' => 'nullable|string',
            'related_lemma_id' => 'nullable|integer|exists:lughat_lemmas,id',
            'create_if_missing' => 'nullable|boolean',
        ]);

        foreach (['romanization', 'note', 'gloss', 'part_of_speech', 'source'] as $field) {
            if (($validated[$field] ?? null) === '') {
                $validated[$field] = null;
            }
        }

        $relatedLemma = null;
        $relatedWord = trim($validated['related_word']);

        if (!empty($validated['related_lemma_id'])) {
            $relatedLemma = LughatLemma::findOrFail($validated['related_lemma_id']);
        } else {
            // Prefer linking an existing Baakh Lughat lemma when the word already exists.
            $relatedLemma = $this->findLemmaByDictionaryWord($relatedWord, (int) $lemmaId);
            if (!$relatedLemma && $request->boolean('create_if_missing')) {
                $relatedLemma = $this->findOrCreateRelationLemma(
                    $relatedWord,
                    $validated['romanization'] ?? null,
                    $validated['part_of_speech'] ?? null,
                    (int) $lemmaId
                );
            }
        }

        if ($relatedLemma) {
            $relatedWord = $relatedLemma->lemma;
            $validated['romanization'] = $validated['romanization'] ?? $relatedLemma->transliteration;
            $validated['part_of_speech'] = $validated['part_of_speech'] ?? $relatedLemma->pos;
        }

        $relation = LughatRelation::create([
            'lemma_id' => $lemmaId,
            'relation_type' => $validated['relation_type'],
            'related_word' => $relatedWord,
            'romanization' => $validated['romanization'] ?? null,
            'note' => $validated['note'] ?? null,
            'gloss' => $validated['gloss'] ?? null,
            'part_of_speech' => $validated['part_of_speech'] ?? null,
            'related_lemma_id' => $relatedLemma?->id,
            'source' => $validated['source'] ?? null,
        ]);

        return response()->json($relation->fresh(['relatedLemma']), 201);
    }

    public function destroyRelation($id)
    {
        $relation = LughatRelation::findOrFail($id);
        $relation->delete();
        return response()->json(null, 204);
    }

    public function storeInflection(Request $request, $lemmaId)
    {
        LughatLemma::findOrFail($lemmaId);

        $validated = $request->validate([
            'form' => 'required|string',
            'romanization' => 'nullable|string',
            'description' => 'nullable|string|max:255',
            'source' => 'nullable|string',
            'review_status' => 'nullable|in:unreviewed,reviewed,needs_work',
        ]);

        $form = trim($validated['form']);
        $payload = [
            'romanization' => $this->nullableTrimmed($validated['romanization'] ?? null),
            'description' => $this->nullableTrimmed($validated['description'] ?? null),
            'source' => $this->nullableTrimmed($validated['source'] ?? null),
            'review_status' => $validated['review_status'] ?? 'unreviewed',
        ];

        // Use BINARY compare so Arabic forms that differ only by diacritics stay distinct
        // under utf8mb4_unicode_ci (which otherwise treats them as equal).
        $existing = LughatInflection::query()
            ->where('lemma_id', $lemmaId)
            ->whereRaw('form = BINARY ?', [$form])
            ->first();

        if ($existing) {
            $existing->update($payload);

            return response()->json($existing->fresh());
        }

        $inflection = LughatInflection::create([
            'lemma_id' => $lemmaId,
            'form' => $form,
            ...$payload,
        ]);

        return response()->json($inflection->fresh(), 201);
    }

    public function destroyInflection($id)
    {
        $inflection = LughatInflection::findOrFail($id);
        $inflection->delete();

        return response()->json(null, 204);
    }

    public function storeIdiomaticExpression(Request $request, $lemmaId)
    {
        LughatLemma::findOrFail($lemmaId);

        $validated = $request->validate([
            'phrase' => 'required|string',
            'romanization' => 'nullable|string',
            'english_gloss' => 'nullable|string|max:255',
            'example_sindhi' => 'nullable|string',
            'example_english' => 'nullable|string',
            'source' => 'nullable|string',
            'review_status' => 'nullable|in:unreviewed,reviewed,needs_work',
        ]);

        $expression = LughatIdiomaticExpression::query()
            ->where('lemma_id', $lemmaId)
            ->whereRaw('phrase = BINARY ?', [trim($validated['phrase'])])
            ->first();

        $payload = [
            'romanization' => $this->nullableTrimmed($validated['romanization'] ?? null),
            'english_gloss' => $this->nullableTrimmed($validated['english_gloss'] ?? null),
            'example_sindhi' => $this->nullableTrimmed($validated['example_sindhi'] ?? null),
            'example_english' => $this->nullableTrimmed($validated['example_english'] ?? null),
            'source' => $this->nullableTrimmed($validated['source'] ?? null),
            'review_status' => $validated['review_status'] ?? 'unreviewed',
        ];

        if ($expression) {
            $expression->update($payload);

            return response()->json($expression->fresh());
        }

        $expression = LughatIdiomaticExpression::create([
            'lemma_id' => $lemmaId,
            'phrase' => trim($validated['phrase']),
            ...$payload,
        ]);

        return response()->json($expression->fresh(), 201);
    }

    public function destroyIdiomaticExpression($id)
    {
        $expression = LughatIdiomaticExpression::findOrFail($id);
        $expression->delete();

        return response()->json(null, 204);
    }

    private function defaultNormalizedLemma(string $lemma): string
    {
        return DictionaryText::normalizeForIdentity($lemma);
    }

    /**
     * Persist AI/manual roman on Romanizer + refresh poetry EN when lemma is linked.
     */
    private function syncLemmaTransliteration(LughatLemma $lemma, string $transliteration): void
    {
        $romanizer = app(RomanizerService::class);
        $romanizer->upsert($lemma->lemma, $transliteration, auth()->id() ?? 1);

        try {
            $romanizer->refreshDictionaryFile();
        } catch (\Throwable) {
            // best-effort
        }

        if ($lemma->poetry_id || $lemma->couplet_id) {
            app(PoetryRomanSyncService::class)->syncFromLemma($lemma);
        }
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

    private function lemmaSearchResult(LughatLemma $lemma, string $search, string $normalizedSearch): array
    {
        $matchType = 'headword';
        $matchLabel = $lemma->lemma;

        if ($this->sameSearchValue($lemma->transliteration, $search)) {
            $matchType = 'romanization';
            $matchLabel = $lemma->transliteration;
        } elseif ($this->sameSearchValue($lemma->normalized_lemma, $normalizedSearch)) {
            $matchType = 'normalized';
            $matchLabel = $lemma->normalized_lemma;
        } else {
            $variant = $lemma->variants->first(function (LughatVariant $variant) use ($search, $normalizedSearch) {
                return $this->containsSearchValue($variant->variant, $search)
                    || $this->containsSearchValue($variant->normalized_variant, $normalizedSearch)
                    || $this->containsSearchValue($variant->romanization, $search);
            });

            if ($variant) {
                $matchType = 'variant';
                $matchLabel = $variant->variant;
            }
        }

        return [
            'id' => $lemma->id,
            'public_id' => $lemma->public_id,
            'lemma' => $lemma->lemma,
            'normalized_lemma' => $lemma->normalized_lemma,
            'transliteration' => $lemma->transliteration,
            'pos' => $lemma->pos,
            'status' => $lemma->status,
            'completion_status' => $lemma->completion_status,
            'match_type' => $matchType,
            'match_label' => $matchLabel,
            'variants' => $lemma->variants
                ->take(3)
                ->map(fn (LughatVariant $variant) => [
                    'id' => $variant->id,
                    'variant' => $variant->variant,
                    'normalized_variant' => $variant->normalized_variant,
                    'romanization' => $variant->romanization,
                    'type' => $variant->type,
                ])
                ->values()
                ->all(),
        ];
    }

    private function findOrCreateRelationLemma(string $word, ?string $romanization, ?string $partOfSpeech, ?int $excludeLemmaId = null): LughatLemma
    {
        $existing = $this->findLemmaByDictionaryWord($word, $excludeLemmaId);

        if ($existing) {
            return $existing;
        }

        $lemma = LughatLemma::create([
            'lemma' => $word,
            'normalized_lemma' => $this->defaultNormalizedLemma($word),
            'lookup_base' => DictionaryText::lookupBase($word),
            'transliteration' => $romanization,
            'pos' => $partOfSpeech,
            'status' => 'pending',
            'completion_status' => LughatLemma::COMPLETION_PENDING,
        ]);

        if (filled($romanization)) {
            \App\Models\Romanizer::updateOrCreate(
                ['word_sd' => $lemma->lemma],
                [
                    'word_roman' => $romanization,
                    'user_id' => auth()->id() ?? 1,
                ]
            );
        }

        return $lemma;
    }

    /**
     * Backfill related_lemma_id when the related word already exists in Baakh Lughat.
     */
    private function hydrateRelationLemmaLinks(LughatLemma $lemma): void
    {
        foreach ($lemma->lemmaRelations as $relation) {
            if ($relation->related_lemma_id || blank($relation->related_word)) {
                continue;
            }

            $found = $this->findLemmaByDictionaryWord((string) $relation->related_word, (int) $lemma->id);
            if (!$found) {
                continue;
            }

            $relation->related_lemma_id = $found->id;
            $relation->setRelation('relatedLemma', $found);
            $relation->saveQuietly();
        }
    }

    private function findLemmaByDictionaryWord(string $word, ?int $excludeLemmaId = null): ?LughatLemma
    {
        $identity = $this->defaultNormalizedLemma($word);
        $base = DictionaryText::lookupBase($word);

        $lemma = LughatLemma::query()
            ->when($excludeLemmaId, fn ($query) => $query->whereKeyNot($excludeLemmaId))
            ->where(function ($query) use ($word, $identity) {
                $query->whereRaw(DictionaryText::binaryEquals('lemma'), [$word])
                    ->orWhereRaw(DictionaryText::binaryEquals('normalized_lemma'), [$identity]);
            })
            ->orderByRaw('CASE WHEN BINARY lemma = ? THEN 0 ELSE 1 END', [$word])
            ->first();

        if ($lemma) {
            return $lemma;
        }

        // Bare form without airab: unique base match only (never collapse نَھن/نُھن).
        if (!DictionaryText::hasDiacritics($word) && $base !== '') {
            $hasLookupBase = Schema::hasColumn('lughat_lemmas', 'lookup_base');
            $matches = LughatLemma::query()
                ->when($excludeLemmaId, fn ($query) => $query->whereKeyNot($excludeLemmaId))
                ->where(function ($q) use ($base, $hasLookupBase) {
                    if ($hasLookupBase) {
                        $q->where('lookup_base', $base)->orWhere('normalized_lemma', $base);
                    } else {
                        $q->where('normalized_lemma', $base);
                    }
                })
                ->limit(2)
                ->get();
            if ($matches->count() === 1) {
                return $matches->first();
            }
        }

        return LughatLemma::query()
            ->when($excludeLemmaId, fn ($query) => $query->whereKeyNot($excludeLemmaId))
            ->whereHas('variants', function ($query) use ($word, $identity) {
                $query->where('variant', $word)
                    ->orWhere('normalized_variant', $identity);
            })
            ->first();
    }

    private function sameSearchValue(mixed $value, string $search): bool
    {
        return filled($value) && DictionaryText::normalizeForLookup((string) $value) === DictionaryText::normalizeForLookup($search);
    }

    private function containsSearchValue(mixed $value, string $search): bool
    {
        if (!filled($value) || $search === '') {
            return false;
        }

        return str_contains(
            DictionaryText::normalizeForLookup((string) $value),
            DictionaryText::normalizeForLookup($search)
        );
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
