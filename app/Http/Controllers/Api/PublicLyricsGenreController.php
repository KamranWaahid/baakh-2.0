<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LyricsGenre;
use Illuminate\Http\Request;

class PublicLyricsGenreController extends Controller
{
    public function index(Request $request)
    {
        $lang = resolve_request_locale($request->get('lang', $request->header('Accept-Language')), 'sd');

        $genres = LyricsGenre::query()
            ->with('details')
            ->withCount(['lyrics' => fn ($q) => $q->where('visibility', 1)])
            ->where('visibility', 1)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (LyricsGenre $genre) => $this->serialize($genre, $lang));

        return response()->json(['data' => $genres]);
    }

    public function show(Request $request, string $slug)
    {
        $lang = resolve_request_locale($request->get('lang', $request->header('Accept-Language')), 'sd');

        $genre = LyricsGenre::with('details')
            ->withCount(['lyrics' => fn ($q) => $q->where('visibility', 1)])
            ->where('visibility', 1)
            ->where(function ($q) use ($slug) {
                $q->where('slug', $slug)->orWhere('id', $slug);
            })
            ->firstOrFail();

        return response()->json($this->serialize($genre, $lang));
    }

    private function serialize(LyricsGenre $genre, string $lang): array
    {
        $sd = $genre->details->firstWhere('lang', 'sd');
        $en = $genre->details->firstWhere('lang', 'en');
        $name = $lang === 'en'
            ? ($en?->name ?: $sd?->name)
            : ($sd?->name ?: $en?->name);

        return [
            'id' => $genre->id,
            'slug' => $genre->slug,
            'name' => $name ?: $genre->slug,
            'name_sd' => $sd?->name,
            'name_en' => $en?->name,
            'lyrics_count' => $genre->lyrics_count ?? 0,
        ];
    }
}
