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
        $query = Couplets::where('lang', 'sd')->with([
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
        $couplet = Couplets::with([
            'poet_details' => function ($q) {
                $q->where('lang', 'sd');
            }
        ])->findOrFail($id);
        return response()->json($couplet);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'poet_id' => 'required|exists:poets,id',
            'couplet_text' => 'required|string',
            'couplet_slug' => 'required|unique:poetry_couplets,couplet_slug',
            'couplet_tags' => 'nullable|array',
            'lang' => 'required|string|max:10',
            'book_id' => 'nullable|exists:poet_books,id',
            'page_start' => 'nullable|integer|min:1',
            'page_end' => 'nullable|integer|min:1',
        ]);

        $couplet = Couplets::create([
            'poetry_id' => 0, // Independent couplet
            'poet_id' => $validated['poet_id'],
            'couplet_text' => strip_tags($validated['couplet_text'], '<p><br><b><strong><i><em><ul><ol><li><blockquote>'),
            'couplet_slug' => $validated['couplet_slug'],
            'couplet_tags' => json_encode($validated['couplet_tags'] ?? []),
            'lang' => $validated['lang'],
            'book_id' => $validated['book_id'] ?? null,
            'page_start' => $validated['page_start'] ?? null,
            'page_end' => $validated['page_end'] ?? null,
        ]);

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
        $couplet = Couplets::findOrFail($id);

        $validated = $request->validate([
            'poet_id' => 'required|exists:poets,id',
            'couplet_text' => 'required|string',
            'couplet_slug' => 'required|unique:poetry_couplets,couplet_slug,' . $id,
            'couplet_tags' => 'nullable|array',
            'lang' => 'required|string|max:10',
            'book_id' => 'nullable|exists:poet_books,id',
            'page_start' => 'nullable|integer|min:1',
            'page_end' => 'nullable|integer|min:1',
        ]);

        $couplet->update([
            'poet_id' => $validated['poet_id'],
            'couplet_text' => strip_tags($validated['couplet_text'], '<p><br><b><strong><i><em><ul><ol><li><blockquote>'),
            'couplet_slug' => $validated['couplet_slug'],
            'couplet_tags' => json_encode($validated['couplet_tags'] ?? []),
            'lang' => $validated['lang'],
            'book_id' => $validated['book_id'] ?? null,
            'page_start' => $validated['page_start'] ?? null,
            'page_end' => $validated['page_end'] ?? null,
        ]);

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
