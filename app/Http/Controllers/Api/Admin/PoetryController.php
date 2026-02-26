<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Poetry;
use App\Services\StaticCacheService;
use Illuminate\Http\Request;

class PoetryController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view_poetry')->only(['index', 'show', 'checkSlug']);
        $this->middleware('can:create_poetry')->only(['create', 'store']);
        $this->middleware('can:edit_poetry')->only(['update', 'toggleVisibility', 'toggleFeatured']);
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
            $query->whereHas('info', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            })->orWhereHas('poet_details', function ($q) use ($search) {
                $q->where('poet_laqab', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 10);
        $poetry = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($poetry);
    }

    public function show($id)
    {
        $poetry = Poetry::with(['translations', 'couplets', 'category', 'poet', 'topicCategory.details'])
            ->where('id', $id)
            ->orWhere('poetry_slug', $id)
            ->firstOrFail();
        return response()->json($poetry);
    }

    public function destroy($id)
    {
        $poetry = Poetry::where('id', $id)->orWhere('poetry_slug', $id)->firstOrFail();
        \DB::beginTransaction();
        try {
            // Also soft delete linked couplets
            $poetry->all_couplets()->delete();
            $poetry->delete();

            \DB::commit();
            return response()->json(['message' => 'Poetry moved to trash']);
        } catch (\Exception $e) {
            \DB::rollBack();
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
        ]);

        \DB::beginTransaction();
        try {
            $poetry = Poetry::create([
                'poet_id' => $validated['poet_id'],
                'category_id' => $validated['category_id'],
                'topic_category_id' => $validated['topic_category_id'],
                'user_id' => \Auth::id(),
                'poetry_slug' => $validated['poetry_slug'],
                'poetry_tags' => json_encode($validated['poetry_tags'] ?? []),
                'visibility' => $validated['visibility'],
                'is_featured' => $validated['is_featured'],
                'content_style' => $validated['content_style'],
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

            \DB::commit();
            return response()->json(['message' => 'Poetry created successfully', 'id' => $poetry->id], 201);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['message' => 'Failed to create poetry: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $poetry = Poetry::where('id', $id)->orWhere('poetry_slug', $id)->firstOrFail();
        $actualId = $poetry->id;

        $validated = $request->validate([
            'poet_id' => 'required|exists:poets,id',
            'category_id' => 'required|exists:categories,id',
            'topic_category_id' => 'nullable|exists:topic_categories,id',
            'poetry_slug' => 'required|unique:poetry_main,poetry_slug,' . $actualId,
            'poetry_title' => 'required|string|max:255',
            'content_style' => 'required|string',
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
        ]);

        \DB::beginTransaction();
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

            \DB::commit();
            return response()->json(['message' => 'Poetry updated successfully']);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['message' => 'Failed to update poetry: ' . $e->getMessage()], 500);
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
        \DB::beginTransaction();
        try {
            $poetry->restore();
            // Restore linked couplets if they were soft deleted with the poetry
            $poetry->all_couplets()->onlyTrashed()->restore();
            \DB::commit();
            return response()->json(['message' => 'Poetry restored']);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['message' => 'Failed to restore poetry: ' . $e->getMessage()], 500);
        }
    }

    public function permanentDelete($id)
    {
        $poetry = Poetry::onlyTrashed()->where('id', $id)->orWhere('poetry_slug', $id)->firstOrFail();
        \DB::beginTransaction();
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
            \DB::commit();
            return response()->json(['message' => 'Poetry permanently deleted']);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['message' => 'Failed to permanently delete poetry: ' . $e->getMessage()], 500);
        }
    }
}
