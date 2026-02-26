<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Couplets;
use Illuminate\Http\Request;

class CoupletController extends Controller
{
    public function index(Request $request)
    {
        // Fetch couplets directly, filtering for Sindhi by default as per request
        $query = Couplets::select('poetry_couplets.*');

        if ($request->has('only_trashed') && $request->only_trashed === 'true') {
            $query->onlyTrashed();
        }

        $query->where('lang', 'sd')
            ->addSelect([
                'has_roman' => function ($q) {
                    $q->selectRaw('count(*)')
                        ->from('poetry_couplets as pc')
                        ->whereColumn('pc.couplet_slug', \DB::raw("CONCAT(poetry_couplets.couplet_slug, '-roman')"))
                        ->where('pc.lang', 'en')
                        ->limit(1);
                }
            ])
            ->with([
                'poetry' => function ($q) {
                    $q->select('id', 'poetry_slug', 'category_id', 'visibility', 'is_featured', 'user_id', 'created_at');
                },
                'poetry.translations' => function ($q) {
                    $q->select('id', 'poetry_id', 'lang'); // Optimizing select
                },
                'poetry.category.detail' => function ($q) {
                    $q->where('lang', 'sd');
                },
                'poetry.user' => function ($q) {
                    $q->select('id', 'name');
                },
                'poet_details' => function ($q) {
                    $q->where('lang', 'sd');
                },
                'topicCategory.details' => function ($q) {
                    $q->where('lang', 'sd');
                }
            ]);

        if (!empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('couplet_text', 'like', "%{$search}%")
                    ->orWhereHas('poet_details', function ($fq) use ($search) {
                        $fq->where('poet_laqab', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = $request->get('per_page', 10);
        $couplets = $query->orderBy('id', 'desc')->paginate($perPage);
        return response()->json($couplets);
    }

    public function show($id)
    {
        $couplet = Couplets::where('id', $id)
            ->orWhere('couplet_slug', $id)
            ->with([
                'poet_details' => function ($q) {
                    $q->where('lang', 'sd');
                },
                'topicCategory.details' => function ($q) {
                    $q->where('lang', 'sd');
                }
            ])->firstOrFail();

        // Find Roman version if it exists
        $roman = Couplets::where('couplet_slug', $couplet->couplet_slug . '-roman')
            ->where('lang', 'en')
            ->first();

        $data = $couplet->toArray();
        if ($roman) {
            $data['roman_text'] = $roman->couplet_text;
        }

        return response()->json($data);
    }

    public function checkSlug(Request $request)
    {
        $slug = $request->get('slug');
        $id = $request->get('id');

        $query = Couplets::where('couplet_slug', $slug);

        if ($id) {
            $query->where('id', '!=', $id);
        }

        $available = !$query->exists();

        return response()->json(['available' => $available]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'poet_id' => 'required|exists:poets,id',
            'couplet_text' => 'required|string',
            'couplet_slug' => 'required|unique:poetry_couplets,couplet_slug',
            'couplet_tags' => 'nullable|array',
            'topic_category_id' => 'nullable|exists:topic_categories,id',
            'lang' => 'required|string|max:10',
            'book_id' => 'nullable|exists:poet_books,id',
            'page_start' => 'nullable|integer|min:1',
            'page_end' => 'nullable|integer|min:1',
            'roman_content' => 'nullable|string',
        ]);

        $couplet = Couplets::create([
            'poetry_id' => 0, // Independent couplet
            'poet_id' => $validated['poet_id'],
            'topic_category_id' => $validated['topic_category_id'] ?? null,
            'couplet_text' => strip_tags($validated['couplet_text'], '<p><br><b><strong><i><em><ul><ol><li><blockquote>'),
            'couplet_slug' => $validated['couplet_slug'],
            'couplet_tags' => json_encode($validated['couplet_tags'] ?? []),
            'lang' => $validated['lang'],
            'book_id' => $validated['book_id'] ?? null,
            'page_start' => $validated['page_start'] ?? null,
            'page_end' => $validated['page_end'] ?? null,
        ]);

        if (!empty($validated['roman_content'])) {
            Couplets::create([
                'poetry_id' => 0,
                'poet_id' => $validated['poet_id'],
                'topic_category_id' => $validated['topic_category_id'] ?? null,
                'couplet_text' => $validated['roman_content'],
                'couplet_slug' => $validated['couplet_slug'] . '-roman',
                'couplet_tags' => json_encode($validated['couplet_tags'] ?? []),
                'lang' => 'en',
                'book_id' => $validated['book_id'] ?? null,
                'page_start' => $validated['page_start'] ?? null,
                'page_end' => $validated['page_end'] ?? null,
            ]);
        }

        if ($couplet->book_id) {
            $this->updateBookProgress($couplet);
        }

        return response()->json([
            'message' => 'Couplet created successfully',
            'id' => $couplet->id
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $couplet = Couplets::where('id', $id)
            ->orWhere('couplet_slug', $id)
            ->firstOrFail();

        $actualId = $couplet->id;

        $validated = $request->validate([
            'poet_id' => 'required|exists:poets,id',
            'couplet_text' => 'required|string',
            'couplet_slug' => 'required|unique:poetry_couplets,couplet_slug,' . $actualId,
            'couplet_tags' => 'nullable|array',
            'topic_category_id' => 'nullable|exists:topic_categories,id',
            'lang' => 'required|string|max:10',
            'book_id' => 'nullable|exists:poet_books,id',
            'page_start' => 'nullable|integer|min:1',
            'page_end' => 'nullable|integer|min:1',
            'roman_content' => 'nullable|string',
        ]);

        $oldSlug = $couplet->couplet_slug;

        $couplet->update([
            'poet_id' => $validated['poet_id'],
            'topic_category_id' => $validated['topic_category_id'] ?? null,
            'couplet_text' => strip_tags($validated['couplet_text'], '<p><br><b><strong><i><em><ul><ol><li><blockquote>'),
            'couplet_slug' => $validated['couplet_slug'],
            'couplet_tags' => json_encode($validated['couplet_tags'] ?? []),
            'lang' => $validated['lang'],
            'book_id' => $validated['book_id'] ?? null,
            'page_start' => $validated['page_start'] ?? null,
            'page_end' => $validated['page_end'] ?? null,
        ]);

        // Update or Create Roman version
        if (!empty($validated['roman_content'])) {
            Couplets::updateOrCreate(
                ['couplet_slug' => $oldSlug . '-roman', 'lang' => 'en'],
                [
                    'poetry_id' => 0,
                    'poet_id' => $validated['poet_id'],
                    'topic_category_id' => $validated['topic_category_id'] ?? null,
                    'couplet_text' => $validated['roman_content'],
                    'couplet_slug' => $validated['couplet_slug'] . '-roman',
                    'couplet_tags' => json_encode($validated['couplet_tags'] ?? []),
                    'book_id' => $validated['book_id'] ?? null,
                    'page_start' => $validated['page_start'] ?? null,
                    'page_end' => $validated['page_end'] ?? null,
                ]
            );
        }

        if ($couplet->book_id) {
            $this->updateBookProgress($couplet);
        }

        return response()->json(['message' => 'Couplet updated successfully']);
    }

    public function destroy($id)
    {
        $couplet = Couplets::findOrFail($id);
        $couplet->delete();
        return response()->json(['message' => 'Couplet moved to trash']);
    }

    public function toggleVisibility($id)
    {
        $couplet = Couplets::findOrFail($id);
        $couplet->update(['visibility' => !$couplet->visibility]);
        return response()->json(['message' => 'Visibility updated', 'visibility' => $couplet->visibility]);
    }

    public function toggleFeatured($id)
    {
        $couplet = Couplets::findOrFail($id);
        $couplet->update(['is_featured' => !$couplet->is_featured]);
        return response()->json(['message' => 'Feature status updated', 'is_featured' => $couplet->is_featured]);
    }

    public function restore($id)
    {
        $couplet = Couplets::onlyTrashed()->findOrFail($id);
        $couplet->restore();
        return response()->json(['message' => 'Couplet restored']);
    }

    public function permanentDelete($id)
    {
        $couplet = Couplets::onlyTrashed()->findOrFail($id);
        $couplet->forceDelete();
        return response()->json(['message' => 'Couplet permanently deleted']);
    }

    private function updateBookProgress(Couplets $couplet)
    {
        $book = \App\Models\PoetBook::find($couplet->book_id);
        if (!$book)
            return;

        $pageReached = $couplet->page_end ?: $couplet->page_start;
        if (!$pageReached)
            return;

        $progress = $book->progress;
        if (!$progress) {
            $progress = $book->progress()->create(['last_page' => 0]);
        }

        if ($pageReached > $progress->last_page) {
            $progress->update([
                'last_page' => $pageReached,
                'last_couplet_id' => $couplet->id
            ]);
        }
    }
}
