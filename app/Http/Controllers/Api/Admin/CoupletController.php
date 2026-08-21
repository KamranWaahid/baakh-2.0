<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Couplets;
use App\Support\SafeUserData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            ->independent()
            ->addSelect([
                'has_roman' => function ($q) {
                    $romanSlugExpression = DB::connection()->getDriverName() === 'sqlite'
                        ? "poetry_couplets.couplet_slug || '-roman'"
                        : "CONCAT(poetry_couplets.couplet_slug, '-roman')";

                    $q->selectRaw('count(*)')
                        ->from('poetry_couplets as pc')
                        ->whereColumn('pc.couplet_slug', DB::raw($romanSlugExpression))
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

        /** @var \Illuminate\Pagination\LengthAwarePaginator $couplets */
        $couplets->through(function (Couplets $item) {
            return $this->serializeIndexItem($item);
        });

        return response()->json($couplets);
    }

    private function serializeIndexItem(Couplets $couplet): array
    {
        $poetryUser = null;

        if ($couplet->relationLoaded('poetry') && $couplet->poetry) {
            $poetry = $couplet->poetry;
            $poetryUser = $poetry->relationLoaded('user') ? $poetry->getRelation('user') : null;
            $poetry->unsetRelation('user');
        }

        $data = $couplet->toArray();

        if (isset($data['poetry'])) {
            $data['poetry']['user'] = SafeUserData::basic($poetryUser, '/api/admin/couplets');
        }

        return $data;
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
            'visibility' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
        ]);

        $validated['couplet_text'] = $this->requireTwoLineCouplet($validated['couplet_text']);
        if (!empty($validated['roman_content'])) {
            $validated['roman_content'] = $this->clampTwoLineCouplet($validated['roman_content']);
        }

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
            'visibility' => ($validated['visibility'] ?? true) ? 1 : 0,
            'is_featured' => ($validated['is_featured'] ?? false) ? 1 : 0,
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
            'visibility' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
        ]);

        $validated['couplet_text'] = $this->requireTwoLineCouplet($validated['couplet_text']);
        if (!empty($validated['roman_content'])) {
            $validated['roman_content'] = $this->clampTwoLineCouplet($validated['roman_content']);
        }

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
            'visibility' => array_key_exists('visibility', $validated)
                ? (($validated['visibility'] ?? true) ? 1 : 0)
                : $couplet->visibility,
            'is_featured' => array_key_exists('is_featured', $validated)
                ? (($validated['is_featured'] ?? false) ? 1 : 0)
                : $couplet->is_featured,
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

    /**
     * Refine one couplet's Sindhi text with Hesudhar dictionary-first correction.
     */
    public function refineHesudhar($id, \App\Services\Hesudhar\HesudharContentRefiner $refiner)
    {
        $couplet = Couplets::where('id', $id)
            ->orWhere('couplet_slug', $id)
            ->firstOrFail();

        $outcome = $refiner->refineCoupletRecord($couplet);

        if ($outcome['skipped'] ?? false) {
            return response()->json([
                'message' => $outcome['reason'] === 'non_sindhi'
                    ? 'Skipped: not a Sindhi couplet.'
                    : 'Skipped: empty couplet text.',
                'data' => $outcome,
            ]);
        }

        return response()->json([
            'message' => $outcome['changed']
                ? "Hesudhar updated this couplet ({$outcome['changes_count']} word fixes)."
                : 'No Hesudhar changes needed for this couplet.',
            'data' => $outcome,
            'couplet_text' => $couplet->fresh()->couplet_text,
        ]);
    }

    /**
     * Refine all Sindhi couplets in the database with Hesudhar.
     */
    public function refineAllHesudhar(\App\Services\Hesudhar\HesudharContentRefiner $refiner)
    {
        set_time_limit(0);

        $result = $refiner->refineAllCouplets();

        return response()->json([
            'message' => "Hesudhar scanned {$result['scanned']} couplets; updated {$result['updated']} ({$result['changes']} word fixes).",
            'data' => $result,
        ]);
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

    private function clampTwoLineCouplet(string $text): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $text);
        $lines = array_slice(explode("\n", $normalized), 0, 2);

        return implode("\n", $lines);
    }

    private function requireTwoLineCouplet(string $text): string
    {
        $clamped = $this->clampTwoLineCouplet($text);
        $nonEmpty = array_values(array_filter(
            explode("\n", $clamped),
            fn ($line) => trim($line) !== ''
        ));

        if (count($nonEmpty) !== 2) {
            abort(response()->json([
                'message' => 'Couplet must contain exactly 2 lines.',
            ], 422));
        }

        return implode("\n", array_map('trim', $nonEmpty));
    }
}
