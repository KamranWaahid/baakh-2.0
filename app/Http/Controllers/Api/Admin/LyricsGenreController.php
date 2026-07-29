<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\LyricsGenre;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LyricsGenreController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view_lyrics')->only(['index', 'show']);
        $this->middleware('can:create_lyrics')->only(['store']);
        $this->middleware('can:edit_lyrics')->only(['update']);
        $this->middleware('can:delete_lyrics')->only(['destroy']);
    }

    public function index()
    {
        return response()->json(
            LyricsGenre::with('details')
                ->withCount('lyrics')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (LyricsGenre $genre) => $this->serialize($genre))
        );
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request);

        $genre = LyricsGenre::create([
            'slug' => $data['slug'],
            'sort_order' => $data['sort_order'] ?? 0,
            'visibility' => $data['visibility'] ?? true,
        ]);

        $this->syncDetails($genre, $data['details']);

        return response()->json($this->serialize($genre->fresh()->load('details')->loadCount('lyrics')), 201);
    }

    public function show($id)
    {
        $genre = LyricsGenre::with('details')->withCount('lyrics')->findOrFail($id);

        return response()->json($this->serialize($genre));
    }

    public function update(Request $request, $id)
    {
        $genre = LyricsGenre::findOrFail($id);
        $data = $this->validatePayload($request, $genre->id);

        $genre->update([
            'slug' => $data['slug'],
            'sort_order' => $data['sort_order'] ?? $genre->sort_order,
            'visibility' => $data['visibility'] ?? $genre->visibility,
        ]);

        $this->syncDetails($genre, $data['details']);

        return response()->json($this->serialize($genre->fresh()->load('details')->loadCount('lyrics')));
    }

    public function destroy($id)
    {
        $genre = LyricsGenre::findOrFail($id);
        $genre->delete();

        return response()->json(['message' => 'Genre deleted successfully']);
    }

    private function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        $slugRule = 'required|string|max:255|unique:lyrics_genres,slug';
        if ($ignoreId) {
            $slugRule .= ',' . $ignoreId;
        }

        $data = $request->validate([
            'slug' => $slugRule,
            'sort_order' => 'nullable|integer|min:0',
            'visibility' => 'nullable|boolean',
            'details' => 'required|array',
            'details.sd.name' => 'required|string|max:255',
            'details.en.name' => 'nullable|string|max:255',
        ]);

        $data['slug'] = Str::slug(strip_tags($data['slug'])) ?: strip_tags($data['slug']);

        return $data;
    }

    private function syncDetails(LyricsGenre $genre, array $details): void
    {
        foreach ($details as $lang => $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $genre->details()->updateOrCreate(
                ['lang' => $lang],
                ['name' => strip_tags($name)]
            );
        }
    }

    private function serialize(LyricsGenre $genre): array
    {
        return [
            'id' => $genre->id,
            'slug' => $genre->slug,
            'sort_order' => $genre->sort_order,
            'visibility' => (bool) $genre->visibility,
            'lyrics_count' => $genre->lyrics_count ?? 0,
            'details' => $genre->details->mapWithKeys(fn ($d) => [
                $d->lang => ['name' => $d->name],
            ]),
            'name' => $genre->details->firstWhere('lang', 'sd')?->name
                ?? $genre->details->first()?->name
                ?? $genre->slug,
            'name_en' => $genre->details->firstWhere('lang', 'en')?->name,
        ];
    }
}
