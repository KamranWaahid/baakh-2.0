<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PoetBook;
use App\Services\StaticCacheService;
use App\Traits\HasMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PoetBookController extends Controller
{
    use HasMedia;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = PoetBook::with(['poet', 'progress']);

        if ($request->has('poet_id')) {
            $query->where('poet_id', $request->poet_id);
        }

        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $perPage = $request->get('per_page', 10);
        $books = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json($books);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'poet_id' => 'required|exists:poets,id',
            'title' => 'required|string|max:255',
            'total_pages' => 'required|integer|min:1',
            'edition' => 'nullable|string',
            'publisher' => 'nullable|string',
            'published_year' => 'nullable|string',
            'isbn' => 'nullable|string',
            'cover_image' => 'nullable|image|mimes:jpeg,webp,jpg,png|max:10240',
            'notes' => 'nullable|string',
            'visibility' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['title']) . '-' . rand(1000, 9999);

        // Handle cover image upload
        if ($request->hasFile('cover_image')) {
            $upload = $this->uploadImage($request->file('cover_image'), 'books', $validated['slug']);
            if (isset($upload['error']) && $upload['error'] === true) {
                return response()->json(['message' => $upload['message']], 422);
            }
            $validated['cover_image'] = $upload['full_path'];
        } else {
            unset($validated['cover_image']);
        }

        $book = PoetBook::create($validated);

        // Initialize progress
        $book->progress()->create([
            'last_page' => 0
        ]);

        $this->invalidatePoetryCache();

        return response()->json($book->load('progress'), 201);
    }

    /**
     * Invalidate the poetry create cache so the books list stays fresh.
     */
    private function invalidatePoetryCache()
    {
        app(StaticCacheService::class)->forget('admin_poetry_create_data');
    }

    /**
     * Display the specified resource.
     */
    public function show(PoetBook $poetBook)
    {
        return response()->json($poetBook->load(['poet', 'progress']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PoetBook $poetBook)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'total_pages' => 'sometimes|required|integer|min:1',
            'edition' => 'nullable|string',
            'publisher' => 'nullable|string',
            'published_year' => 'nullable|string',
            'isbn' => 'nullable|string',
            'cover_image' => 'nullable|image|mimes:jpeg,webp,jpg,png|max:10240',
            'notes' => 'nullable|string',
            'visibility' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        // Handle cover image upload
        if ($request->hasFile('cover_image')) {
            if ($poetBook->cover_image) {
                $upload = $this->updateImage($request->file('cover_image'), 'books', $poetBook->cover_image, $poetBook->slug);
            } else {
                $upload = $this->uploadImage($request->file('cover_image'), 'books', $poetBook->slug);
            }
            if (isset($upload['error']) && $upload['error'] === true) {
                return response()->json(['message' => $upload['message']], 422);
            }
            $validated['cover_image'] = $upload['full_path'];
        } else {
            unset($validated['cover_image']);
        }

        $poetBook->update($validated);

        $this->invalidatePoetryCache();

        return response()->json($poetBook->load('progress'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PoetBook $poetBook)
    {
        $poetBook->delete();
        $this->invalidatePoetryCache();
        return response()->json(['message' => 'Book deleted successfully']);
    }

    /**
     * Get books for a specific poet (utility for dropdowns)
     */
    public function getPoetBooks($poetId)
    {
        $books = PoetBook::where('poet_id', $poetId)
            ->with('progress')
            ->orderBy('title', 'asc')
            ->get();

        return response()->json($books);
    }
}
