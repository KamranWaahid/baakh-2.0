<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Poetry;
use App\Services\LughatExpressionService;
use App\Services\LughatPoetryRomanService;
use App\Services\LughatPoetrySenseAnnotationService;
use App\Services\StaticCacheService;
use App\Support\SafeUserData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PoetryController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view_poetry')->only([
            'index',
            'show',
            'checkSlug',
            'lookupLughatSenses',
            'lookupLughatExpressions',
            'checkLughatRoman',
            'transliterateLughatRoman',
            'senseAnnotations',
        ]);
        $this->middleware('can:create_poetry')->only(['create', 'store']);
        $this->middleware('can:edit_poetry')->only([
            'update',
            'toggleVisibility',
            'toggleFeatured',
            'refineHesudhar',
            'refineAllHesudhar',
            'syncSenseAnnotations',
        ]);
        $this->middleware('can:delete_poetry')->only(['destroy']);
    }
    public function index(Request $request)
    {
        $query = Poetry::query();

        if ($request->has('only_trashed') && $request->only_trashed === 'true') {
            $query->onlyTrashed();
        }

        $query->with([
            'info' => function ($q) {
                $q->where('lang', 'sd');
            },
            'poet_details' => function ($q) {
                $q->where('lang', 'sd');
            },
            'category.detail' => function ($q) {
                $q->where('lang', 'sd');
            },
            'topicCategory.details' => function ($q) {
                $q->where('lang', 'sd');
            },
            'user' => function ($q) {
                $q->select('id', 'name');
            }
        ]);

        if ($request->has('type') && $request->type === 'couplet') {
            $query->with([
                'couplets' => function ($q) {
                    $q->orderBy('id', 'asc');
                }
            ]);
            // Filter where category_id is NULL for independent couplets
            // OR where it has a category but we want to show it as a couplet? 
            // The user said "if couplet has category it show linked, otherwise indepented".
            // So couplets CAN have categories. 
            // BUT couplets created via the new form won't have categories.
        }

        if ($request->has('search')) {
            $search = $request->search;
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $query->where(function ($q) use ($like) {
                $q->whereHas('info', function ($sq) use ($like) {
                    $sq->where('title', 'like', $like)
                        ->orWhere('info', 'like', $like);
                })
                    ->orWhereHas('couplets', function ($sq) use ($like) {
                        $sq->where('couplet_text', 'like', $like);
                    })
                    ->orWhereHas('poet_details', function ($sq) use ($like) {
                        $sq->where('poet_laqab', 'like', $like);
                    });
            });
        }

        // Lughat workflow: processed = saved via Baakh Lughat roman pipeline.
        $lughatFilter = strtolower(trim((string) $request->get('lughat', 'all')));
        if ($lughatFilter === 'processed') {
            $query->where(function ($q) {
                $q->where('romanization_source', Poetry::ROMANIZATION_BAAKH_LUGHAT)
                    ->orWhere('dictionary_source', Poetry::DICTIONARY_LUGHAT);
            });
        } elseif (in_array($lughatFilter, ['pending', 'unprocessed', 'not_processed'], true)) {
            $query->where(function ($q) {
                $q->where(function ($inner) {
                    $inner->whereNull('romanization_source')
                        ->orWhere('romanization_source', '<>', Poetry::ROMANIZATION_BAAKH_LUGHAT);
                })->where(function ($inner) {
                    $inner->whereNull('dictionary_source')
                        ->orWhere('dictionary_source', '<>', Poetry::DICTIONARY_LUGHAT);
                });
            });
        }

        $perPage = $request->get('per_page', 10);
        $poetry = $query->orderBy('created_at', 'desc')->paginate($perPage);

        /** @var \Illuminate\Pagination\LengthAwarePaginator $poetry */
        $poetry->through(function (Poetry $item) {
            return $this->serializeIndexItem($item);
        });

        $payload = $poetry->toArray();
        $payload['lughat_counts'] = $this->lughatPoetryCounts($request->boolean('only_trashed'));

        return response()->json($payload);
    }

    /**
     * @return array{all:int,processed:int,pending:int}
     */
    private function lughatPoetryCounts(bool $onlyTrashed = false): array
    {
        $base = Poetry::query();
        if ($onlyTrashed) {
            $base->onlyTrashed();
        }

        $processed = (clone $base)->where(function ($q) {
            $q->where('romanization_source', Poetry::ROMANIZATION_BAAKH_LUGHAT)
                ->orWhere('dictionary_source', Poetry::DICTIONARY_LUGHAT);
        })->count();

        $all = (clone $base)->count();

        return [
            'all' => $all,
            'processed' => $processed,
            'pending' => max(0, $all - $processed),
        ];
    }

    private function serializeIndexItem(Poetry $poetry): array
    {
        $user = $poetry->relationLoaded('user') ? $poetry->getRelation('user') : null;
        $poetry->unsetRelation('user');

        $data = $poetry->toArray();
        $data['user'] = SafeUserData::basic($user, '/api/admin/poetry');
        $data['lughat_processed'] = $poetry->usesLughatRomanization() || $poetry->usesBaakhLughat();

        return $data;
    }

    public function show($id)
    {
        $poetry = Poetry::with(['translations', 'couplets', 'category', 'poet', 'topicCategory.details'])
            ->where('id', $id)
            ->orWhere('poetry_slug', $id)
            ->firstOrFail();

        $payload = $poetry->toArray();
        $payload['sense_annotations'] = app(LughatPoetrySenseAnnotationService::class)
            ->listForPoetry((int) $poetry->id);
        $payload['expression_annotations'] = app(LughatExpressionService::class)
            ->listPoetryAnnotations((int) $poetry->id);

        return response()->json($payload);
    }

    public function lookupLughatExpressions(Request $request)
    {
        $validated = $request->validate([
            'q' => 'required|string|min:1|max:500',
        ]);

        $hits = app(LughatExpressionService::class)->search($validated['q'], 12);

        return response()->json([
            'query' => $validated['q'],
            'matches' => $hits,
        ]);
    }

    public function lookupLughatSenses(Request $request)
    {
        $validated = $request->validate([
            'q' => 'required|string|min:1|max:255',
            'poetry_id' => 'nullable|integer|min:1',
        ]);

        return response()->json(
            app(LughatPoetrySenseAnnotationService::class)->lookupSenses(
                $validated['q'],
                isset($validated['poetry_id']) ? (int) $validated['poetry_id'] : null
            )
        );
    }

    /**
     * Check poetry title + body against Baakh Lughat for roman readiness.
     */
    public function checkLughatRoman(Request $request, LughatPoetryRomanService $roman)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:500',
            'text' => 'nullable|string',
        ]);

        $result = $roman->check(
            (string) ($validated['title'] ?? ''),
            (string) ($validated['text'] ?? '')
        );

        // Publish-ready when every extracted Sindhi word has a Lughat roman.
        // Empty text is not ready.
        $result['ready'] = empty($result['empty']) && ($result['unresolved_count'] ?? 0) === 0;

        return response()->json($result);
    }

    /**
     * Build roman title/body from Baakh Lughat transliterations.
     */
    public function transliterateLughatRoman(Request $request, LughatPoetryRomanService $roman)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:500',
            'text' => 'nullable|string',
        ]);

        return response()->json(
            $roman->transliteratePair(
                (string) ($validated['title'] ?? ''),
                (string) ($validated['text'] ?? '')
            )
        );
    }

    public function senseAnnotations($id)
    {
        $poetry = Poetry::where('id', $id)->orWhere('poetry_slug', $id)->firstOrFail();

        return response()->json([
            'poetry_id' => $poetry->id,
            'annotations' => app(LughatPoetrySenseAnnotationService::class)->listForPoetry((int) $poetry->id),
        ]);
    }

    public function syncSenseAnnotations(Request $request, $id)
    {
        $poetry = Poetry::where('id', $id)->orWhere('poetry_slug', $id)->firstOrFail();

        $validated = $request->validate([
            'annotations' => 'required|array',
            'annotations.*.couplet_index' => 'required|integer|min:0',
            'annotations.*.token_index' => 'required|integer|min:0',
            'annotations.*.sense_id' => 'required|integer|exists:lughat_senses,id',
            'annotations.*.surface_form' => 'nullable|string|max:255',
            'annotations.*.note' => 'nullable|string',
            'annotations.*.promote' => 'nullable|boolean',
            'replace' => 'nullable|boolean',
        ]);

        $service = app(LughatPoetrySenseAnnotationService::class);
        $saved = ($validated['replace'] ?? true)
            ? $service->replaceForPoetry($poetry, $validated['annotations'], true)
            : $service->syncForPoetry($poetry, $validated['annotations'], true);

        return response()->json([
            'message' => 'Sense annotations saved.',
            'annotations' => $service->listForPoetry((int) $poetry->id),
            'saved_count' => count($saved),
        ]);
    }

    public function destroy($id)
    {
        $poetry = Poetry::where('id', $id)->orWhere('poetry_slug', $id)->firstOrFail();
        DB::beginTransaction();
        try {
            // Also soft delete linked couplets
            $poetry->all_couplets()->delete();
            $poetry->delete();

            DB::commit();
            return response()->json(['message' => 'Poetry moved to trash']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete poetry: ' . $e->getMessage()], 500);
        }
    }

    public function toggleVisibility($id)
    {
        $poetry = Poetry::where('id', $id)->orWhere('poetry_slug', $id)->firstOrFail();
        $poetry->visibility = $poetry->visibility == 1 ? 0 : 1;
        $poetry->save();

        return response()->json([
            'message' => 'Visibility updated',
            'visibility' => $poetry->visibility
        ]);
    }

    public function toggleFeatured($id)
    {
        $poetry = Poetry::where('id', $id)->orWhere('poetry_slug', $id)->firstOrFail();
        $poetry->is_featured = $poetry->is_featured == 1 ? 0 : 1;
        $poetry->save();

        return response()->json([
            'message' => 'Featured status updated',
            'is_featured' => $poetry->is_featured
        ]);
    }
    public function create()
    {
        $cache = app(StaticCacheService::class);
        $cachedData = $cache->get('admin_poetry_create_data');
        if ($cachedData) {
            return response()->json($cachedData);
        }

        $poets = \App\Models\Poets::where('visibility', 1)->with([
            'details' => function ($q) {
                $q->where('lang', 'sd');
            }
        ])->select('id', 'poet_slug')->get()->map(function ($poet) {
            return [
                'id' => $poet->id,
                'name' => $poet->details?->poet_laqab ?? $poet->poet_slug
            ];
        });

        $categories = \App\Models\Categories::with([
            'detail' => function ($q) {
                $q->where('lang', 'sd');
            }
        ])->select('id', 'slug')->get()->map(function ($cat) {
            return [
                'id' => $cat->id,
                'name' => $cat->detail?->cat_name ?? $cat->slug
            ];
        });

        $tags = \App\Models\Tags::with([
            'details' => function ($q) {
                $q->where('lang', 'sd');
            }
        ])->get()->map(function ($tag) {
            return [
                'id' => $tag->id,
                'tag' => $tag->details->first()?->name ?? $tag->slug,
                'type' => $tag->type
            ];
        })->groupBy('type');

        $topicCategories = \App\Models\TopicCategory::with([
            'details' => function ($q) {
                $q->where('lang', 'sd');
            }
        ])->get()->map(function ($cat) {
            return [
                'id' => $cat->id,
                'name' => $cat->details->first()?->name ?? $cat->slug
            ];
        });

        // Fetch all books with poet info for the form
        $books = \App\Models\PoetBook::with('progress')->get()->map(function ($book) {
            return [
                'id' => $book->id,
                'poet_id' => $book->poet_id,
                'title' => $book->title,
                'total_pages' => $book->total_pages,
                'last_page' => $book->progress->last_page ?? 0
            ];
        });

        $data = [
            'poets' => $poets,
            'categories' => $categories,
            'topic_categories' => $topicCategories,
            'tags' => $tags,
            'books' => $books,
            'content_styles' => ['justified', 'center', 'start', 'end']
        ];

        // Cache it for future use
        $cache->set('admin_poetry_create_data', $data);

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'poet_id' => 'required|exists:poets,id',
            'category_id' => 'required|exists:categories,id',
            'topic_category_id' => 'nullable|exists:topic_categories,id',
            'poetry_slug' => 'required|unique:poetry_main,poetry_slug',
            'poetry_title' => 'required|string|max:255',
            'content_style' => 'required|string',
            'dictionary_source' => 'nullable|in:general,lughat',
            'romanization_source' => 'nullable|in:legacy,baakh_lughat',
            'visibility' => 'required|boolean',
            'is_featured' => 'required|boolean',
            'couplets' => 'required|array|min:1',
            'couplets.*.couplet_text' => 'required|string',
            'poetry_tags' => 'nullable|array',
            'poetry_info' => 'nullable|string',
            'source' => 'nullable|string',
            'roman_title' => 'nullable|string|max:255',
            'roman_content' => 'nullable|array',
            'book_id' => 'nullable|exists:poet_books,id',
            'page_start' => 'nullable|integer|min:1',
            'page_end' => 'nullable|integer|min:1',
            'sense_annotations' => 'nullable|array',
            'sense_annotations.*.couplet_index' => 'required_with:sense_annotations|integer|min:0',
            'sense_annotations.*.token_index' => 'required_with:sense_annotations|integer|min:0',
            'sense_annotations.*.sense_id' => 'required_with:sense_annotations|integer|exists:lughat_senses,id',
            'sense_annotations.*.surface_form' => 'nullable|string|max:255',
            'sense_annotations.*.note' => 'nullable|string',
            'sense_annotations.*.promote' => 'nullable|boolean',
            'expression_annotations' => 'nullable|array',
            'expression_annotations.*.couplet_index' => 'required_with:expression_annotations|integer|min:0',
            'expression_annotations.*.start_token_index' => 'required_with:expression_annotations|integer|min:0',
            'expression_annotations.*.end_token_index' => 'required_with:expression_annotations|integer|min:0',
            'expression_annotations.*.surface_text' => 'required_with:expression_annotations|string|max:500',
            'expression_annotations.*.expression_type' => 'nullable|string|max:40',
            'expression_annotations.*.literal_gloss' => 'nullable|string|max:500',
            'expression_annotations.*.poetic_gloss' => 'nullable|string',
            'expression_annotations.*.note' => 'nullable|string',
        ]);

        $romanizationSource = $validated['romanization_source'] ?? Poetry::ROMANIZATION_BAAKH_LUGHAT;
        if ($block = $this->lughatRomanPublishBlock($validated, $romanizationSource)) {
            return $block;
        }

        DB::beginTransaction();
        try {
            $poetry = Poetry::create([
                'poet_id' => $validated['poet_id'],
                'category_id' => $validated['category_id'],
                'topic_category_id' => $validated['topic_category_id'],
                'user_id' => Auth::id(),
                'poetry_slug' => $validated['poetry_slug'],
                'poetry_tags' => json_encode($validated['poetry_tags'] ?? []),
                'visibility' => $validated['visibility'],
                'is_featured' => $validated['is_featured'],
                'content_style' => $validated['content_style'],
                'dictionary_source' => $validated['dictionary_source'] ?? Poetry::DICTIONARY_LUGHAT,
                'romanization_source' => $romanizationSource,
                'book_id' => $validated['book_id'] ?? null,
                'page_start' => $validated['page_start'] ?? null,
                'page_end' => $validated['page_end'] ?? null,
            ]);

            if ($poetry->book_id) {
                $this->updateBookProgress($poetry);
            }

            $poetry->translations()->create([
                'title' => strip_tags($validated['poetry_title']),
                'info' => strip_tags($validated['poetry_info'] ?? null, '<p><br><b><strong><i><em><ul><ol><li><blockquote>'),
                'source' => strip_tags($validated['source'] ?? null),
                'lang' => 'sd', // Default lang for creation
            ]);

            foreach ($validated['couplets'] as $index => $couplet) {
                $poetry->couplets()->create([
                    'couplet_text' => strip_tags($couplet['couplet_text'], '<p><br><b><strong><i><em><ul><ol><li><blockquote>'),
                    'poet_id' => $validated['poet_id'],
                    'couplet_slug' => $validated['poetry_slug'] . '-' . ($index + 1),
                    'lang' => 'sd'
                ]);
            }

            if (!empty($validated['roman_content'])) {
                foreach ($validated['roman_content'] as $index => $couplet) {
                    $poetry->couplets()->create([
                        'couplet_text' => $couplet['couplet_text'],
                        'poet_id' => $validated['poet_id'],
                        'couplet_slug' => $validated['poetry_slug'] . '-roman-' . ($index + 1),
                        'lang' => 'en'
                    ]);
                }
            }

            if (!empty($validated['roman_title'])) {
                $poetry->translations()->create([
                    'title' => $validated['roman_title'],
                    'info' => $validated['poetry_info'] ?? null,
                    'source' => $validated['source'] ?? null,
                    'lang' => 'en',
                ]);
            }

            if (!empty($validated['sense_annotations'])) {
                app(LughatPoetrySenseAnnotationService::class)
                    ->replaceForPoetry($poetry->fresh(), $validated['sense_annotations'], true);
            }

            if (!empty($validated['expression_annotations'])) {
                app(LughatExpressionService::class)
                    ->replacePoetryAnnotations($poetry->fresh(), $validated['expression_annotations']);
            }

            DB::commit();
            $this->forgetPoetryPublicCache($validated['poetry_slug']);
            return response()->json(['message' => 'Poetry created successfully', 'id' => $poetry->id], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create poetry: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $poetry = Poetry::where('id', $id)->orWhere('poetry_slug', $id)->firstOrFail();
        $actualId = $poetry->id;
        $previousSlug = $poetry->poetry_slug;

        $validated = $request->validate([
            'poet_id' => 'required|exists:poets,id',
            'category_id' => 'required|exists:categories,id',
            'topic_category_id' => 'nullable|exists:topic_categories,id',
            'poetry_slug' => 'required|unique:poetry_main,poetry_slug,' . $actualId,
            'poetry_title' => 'required|string|max:255',
            'content_style' => 'required|string',
            'dictionary_source' => 'nullable|in:general,lughat',
            'romanization_source' => 'nullable|in:legacy,baakh_lughat',
            'visibility' => 'required|boolean',
            'is_featured' => 'required|boolean',
            'couplets' => 'required|array|min:1',
            'couplets.*.couplet_text' => 'required|string',
            'poetry_tags' => 'nullable|array',
            'poetry_info' => 'nullable|string',
            'source' => 'nullable|string',
            'roman_title' => 'nullable|string|max:255',
            'roman_content' => 'nullable|array',
            'book_id' => 'nullable|exists:poet_books,id',
            'page_start' => 'nullable|integer|min:1',
            'page_end' => 'nullable|integer|min:1',
            'sense_annotations' => 'nullable|array',
            'sense_annotations.*.couplet_index' => 'required_with:sense_annotations|integer|min:0',
            'sense_annotations.*.token_index' => 'required_with:sense_annotations|integer|min:0',
            'sense_annotations.*.sense_id' => 'required_with:sense_annotations|integer|exists:lughat_senses,id',
            'sense_annotations.*.surface_form' => 'nullable|string|max:255',
            'sense_annotations.*.note' => 'nullable|string',
            'sense_annotations.*.promote' => 'nullable|boolean',
            'expression_annotations' => 'nullable|array',
            'expression_annotations.*.couplet_index' => 'required_with:expression_annotations|integer|min:0',
            'expression_annotations.*.start_token_index' => 'required_with:expression_annotations|integer|min:0',
            'expression_annotations.*.end_token_index' => 'required_with:expression_annotations|integer|min:0',
            'expression_annotations.*.surface_text' => 'required_with:expression_annotations|string|max:500',
            'expression_annotations.*.expression_type' => 'nullable|string|max:40',
            'expression_annotations.*.literal_gloss' => 'nullable|string|max:500',
            'expression_annotations.*.poetic_gloss' => 'nullable|string',
            'expression_annotations.*.note' => 'nullable|string',
        ]);

        // Editing always migrates to Baakh Lughat roman when saving through the new UI.
        $romanizationSource = $validated['romanization_source'] ?? Poetry::ROMANIZATION_BAAKH_LUGHAT;
        if ($block = $this->lughatRomanPublishBlock($validated, $romanizationSource)) {
            return $block;
        }

        DB::beginTransaction();
        try {
            $poetry->update([
                'poet_id' => $validated['poet_id'],
                'category_id' => $validated['category_id'],
                'topic_category_id' => $validated['topic_category_id'],
                'poetry_slug' => $validated['poetry_slug'],
                'poetry_tags' => json_encode($validated['poetry_tags'] ?? []),
                'visibility' => $validated['visibility'],
                'is_featured' => $validated['is_featured'],
                'content_style' => $validated['content_style'],
                'dictionary_source' => $validated['dictionary_source'] ?? $poetry->dictionary_source ?? Poetry::DICTIONARY_LUGHAT,
                'romanization_source' => $romanizationSource,
                'book_id' => $validated['book_id'] ?? null,
                'page_start' => $validated['page_start'] ?? null,
                'page_end' => $validated['page_end'] ?? null,
            ]);

            if ($poetry->book_id) {
                $this->updateBookProgress($poetry);
            }

            // Update or create translation for 'sd'
            $poetry->translations()->updateOrCreate(
                ['lang' => 'sd'],
                [
                    'title' => strip_tags($validated['poetry_title']),
                    'info' => strip_tags($validated['poetry_info'] ?? null, '<p><br><b><strong><i><em><ul><ol><li><blockquote>'),
                    'source' => strip_tags($validated['source'] ?? null),
                ]
            );

            $poetry->couplets()->delete();
            foreach ($validated['couplets'] as $index => $couplet) {
                $poetry->couplets()->create([
                    'couplet_text' => strip_tags($couplet['couplet_text'], '<p><br><b><strong><i><em><ul><ol><li><blockquote>'),
                    'poet_id' => $validated['poet_id'],
                    'couplet_slug' => $validated['poetry_slug'] . '-' . ($index + 1),
                    'lang' => 'sd'
                ]);
            }

            if (!empty($validated['roman_content'])) {
                foreach ($validated['roman_content'] as $index => $couplet) {
                    $poetry->couplets()->create([
                        'couplet_text' => $couplet['couplet_text'],
                        'poet_id' => $validated['poet_id'],
                        'couplet_slug' => $validated['poetry_slug'] . '-roman-' . ($index + 1),
                        'lang' => 'en'
                    ]);
                }
            }

            if (!empty($validated['roman_title'])) {
                $poetry->translations()->updateOrCreate(
                    ['lang' => 'en'],
                    [
                        'title' => $validated['roman_title'],
                        'info' => $validated['poetry_info'] ?? null,
                        'source' => $validated['source'] ?? null,
                    ]
                );
            }

            if (array_key_exists('sense_annotations', $validated)) {
                app(LughatPoetrySenseAnnotationService::class)
                    ->replaceForPoetry($poetry->fresh(), $validated['sense_annotations'] ?? [], true);
            }

            if (array_key_exists('expression_annotations', $validated)) {
                app(LughatExpressionService::class)
                    ->replacePoetryAnnotations($poetry->fresh(), $validated['expression_annotations'] ?? []);
            }

            DB::commit();
            $this->forgetPoetryPublicCache($previousSlug);
            if ($previousSlug !== $validated['poetry_slug']) {
                $this->forgetPoetryPublicCache($validated['poetry_slug']);
            }
            return response()->json(['message' => 'Poetry updated successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update poetry: ' . $e->getMessage()], 500);
        }
    }

    /**
     * When publishing via Baakh Lughat romanization, every Sindhi token must have a roman value.
     */
    private function lughatRomanPublishBlock(array $validated, string $romanizationSource)
    {
        if ($romanizationSource !== Poetry::ROMANIZATION_BAAKH_LUGHAT) {
            return null;
        }

        $sdText = collect($validated['couplets'] ?? [])
            ->pluck('couplet_text')
            ->implode("\n\n");
        $check = app(LughatPoetryRomanService::class)->check(
            (string) ($validated['poetry_title'] ?? ''),
            $sdText
        );

        $ready = empty($check['empty']) && (int) ($check['unresolved_count'] ?? 0) === 0;
        if ($ready) {
            return null;
        }

        $unresolved = collect($check['words'] ?? [])
            ->whereIn('status', [
                LughatPoetryRomanService::STATUS_MISSING_WORD,
                LughatPoetryRomanService::STATUS_MISSING_ROMAN,
            ])
            ->values()
            ->all();

        return response()->json([
            'message' => 'Poetry cannot be published until every Sindhi word exists in Baakh Lughat with a Roman spelling.',
            'lughat_roman_check' => [
                'ready' => false,
                'unresolved_count' => count($unresolved),
                'words' => $unresolved,
            ],
        ], 422);
    }

    /** Bust public poem JSON cache so content_style / couplets update immediately. */
    private function forgetPoetryPublicCache(?string $slug): void
    {
        if (!$slug) {
            return;
        }
        $cache = app(StaticCacheService::class);
        foreach (['sd', 'en', 'snd'] as $locale) {
            $cache->forget("poetry_detail_{$slug}_{$locale}");
        }
    }

    public function checkSlug(Request $request)
    {
        $slug = $request->get('slug');
        $id = $request->get('id');

        $query = Poetry::where('poetry_slug', $slug);

        if ($id) {
            $query->where('id', '!=', $id);
        }

        $exists = $query->exists();

        return response()->json(['exists' => $exists]);
    }

    private function updateBookProgress(Poetry $poetry)
    {
        $book = \App\Models\PoetBook::find($poetry->book_id);
        if (!$book)
            return;

        $pageReached = $poetry->page_end ?: $poetry->page_start;
        if (!$pageReached)
            return;

        $progress = $book->progress;
        if (!$progress) {
            $progress = $book->progress()->create(['last_page' => 0]);
        }

        // Calculate actual pages completed by counting unique pages from all linked poetry
        $pagesCompleted = $this->calculatePagesCompleted($book);

        $updateData = [
            'last_page' => $pagesCompleted,
            'last_poetry_id' => $poetry->id
        ];

        $progress->update($updateData);
    }

    /**
     * Calculate the actual unique pages completed for a book by
     * summing (page_end - page_start + 1) for each poetry entry.
     */
    private function calculatePagesCompleted(\App\Models\PoetBook $book): int
    {
        // Get all page ranges from poetry linked to this book
        $poetryPages = Poetry::where('book_id', $book->id)
            ->whereNotNull('page_start')
            ->select('page_start', 'page_end')
            ->get();

        // Build a set of unique pages to avoid double-counting overlapping ranges
        $uniquePages = [];
        foreach ($poetryPages as $entry) {
            $start = (int) $entry->page_start;
            $end = (int) ($entry->page_end ?: $entry->page_start);
            for ($p = $start; $p <= $end; $p++) {
                $uniquePages[$p] = true;
            }
        }

        // Also count pages from independent couplets linked to this book
        $coupletPages = \App\Models\Couplets::where('book_id', $book->id)
            ->whereNotNull('page_start')
            ->select('page_start', 'page_end')
            ->get();

        foreach ($coupletPages as $entry) {
            $start = (int) $entry->page_start;
            $end = (int) ($entry->page_end ?: $entry->page_start);
            for ($p = $start; $p <= $end; $p++) {
                $uniquePages[$p] = true;
            }
        }

        return count($uniquePages);
    }
    public function restore($id)
    {
        $poetry = Poetry::onlyTrashed()->where('id', $id)->orWhere('poetry_slug', $id)->firstOrFail();
        DB::beginTransaction();
        try {
            $poetry->restore();
            // Restore linked couplets if they were soft deleted with the poetry
            $poetry->all_couplets()->onlyTrashed()->restore();
            DB::commit();
            return response()->json(['message' => 'Poetry restored']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to restore poetry: ' . $e->getMessage()], 500);
        }
    }

    public function permanentDelete($id)
    {
        $poetry = Poetry::onlyTrashed()->where('id', $id)->orWhere('poetry_slug', $id)->firstOrFail();
        DB::beginTransaction();
        try {
            // Delete media
            $poetry->media()->each(function ($m) {
                // Media might have files too
                if (method_exists($this, 'deleteMediaFiles')) {
                    // $this->deleteMediaFiles($m); // Assuming there is a helper
                }
                $m->delete();
            });

            // Delete translations (no soft delete here so force delete is just delete)
            $poetry->translations()->delete();

            // Force delete linked couplets
            $poetry->all_couplets()->withTrashed()->forceDelete();

            $poetry->forceDelete();
            DB::commit();
            return response()->json(['message' => 'Poetry permanently deleted']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to permanently delete poetry: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Refine this poetry's Sindhi couplets (+ sd title/info) with Hesudhar dictionary-first correction.
     */
    public function refineHesudhar($id, \App\Services\Hesudhar\HesudharContentRefiner $refiner)
    {
        $poetry = Poetry::where('id', $id)->orWhere('poetry_slug', $id)->firstOrFail();
        $result = $refiner->refinePoetry($poetry);

        return response()->json([
            'message' => $result['updated'] > 0 || ($result['translations_updated'] ?? 0) > 0
                ? "Hesudhar refined {$result['updated']} couplet(s) ({$result['changes']} word fixes)."
                : 'No Hesudhar changes needed for this poetry.',
            'data' => $result,
        ]);
    }

    /**
     * Refine all poetry works in the database with Hesudhar.
     */
    public function refineAllHesudhar(\App\Services\Hesudhar\HesudharContentRefiner $refiner)
    {
        set_time_limit(0);

        $result = $refiner->refineAllPoetry();

        return response()->json([
            'message' => "Hesudhar scanned {$result['poetry_scanned']} poetry works; updated {$result['couplets_updated']} couplets ({$result['changes']} word fixes).",
            'data' => $result,
        ]);
    }
}
