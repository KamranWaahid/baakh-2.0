<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Band;
use App\Models\Lyrics;
use App\Models\Singer;
use Illuminate\Http\Request;

class PublicBandController extends Controller
{
    public function index(Request $request)
    {
        $lang = resolve_request_locale($request->get('lang', $request->header('Accept-Language')), 'sd');
        $perPage = min((int) $request->get('per_page', 24), 50);

        $query = Band::query()
            ->with('allDetails')
            ->withCount([
                'lyrics' => fn ($q) => $q->where('visibility', 1),
                'singers',
            ])
            ->where('visibility', 1)
            ->orderByDesc('is_featured')
            ->orderByDesc('created_at');

        if ($request->boolean('featured')) {
            $query->where('is_featured', 1);
        }

        if ($request->filled('search')) {
            $like = '%' . addcslashes($request->search, '%_\\') . '%';
            $query->where(function ($q) use ($like) {
                $q->where('band_slug', 'like', $like)
                    ->orWhereHas('allDetails', function ($sq) use ($like) {
                        $sq->where('band_name', 'like', $like)
                            ->orWhere('tagline', 'like', $like);
                    });
            });
        }

        $items = $query->paginate($perPage);
        $items->through(fn (Band $band) => $this->serialize($band, $lang, false));

        return response()->json($items);
    }

    public function show(Request $request, string $slug)
    {
        $lang = resolve_request_locale($request->get('lang', $request->header('Accept-Language')), 'sd');

        $band = Band::with(['allDetails', 'singers.allDetails'])
            ->withCount([
                'lyrics' => fn ($q) => $q->where('visibility', 1),
                'singers',
            ])
            ->where('visibility', 1)
            ->where(function ($q) use ($slug) {
                $q->where('band_slug', $slug)->orWhere('id', $slug);
            })
            ->firstOrFail();

        return response()->json($this->serialize($band, $lang, true));
    }

    public function lyrics(Request $request, string $slug)
    {
        $band = Band::where('visibility', 1)
            ->where(function ($q) use ($slug) {
                $q->where('band_slug', $slug)->orWhere('id', $slug);
            })
            ->firstOrFail();

        $request->merge(['band' => $band->band_slug]);

        return app(PublicLyricsController::class)->index($request);
    }

    private function serialize(Band $band, string $lang, bool $detail): array
    {
        $sd = $band->allDetails->firstWhere('lang', 'sd');
        $en = $band->allDetails->firstWhere('lang', 'en');

        $pick = function (?string $sdVal, ?string $enVal) use ($lang) {
            return $lang === 'en' ? ($enVal ?: $sdVal) : ($sdVal ?: $enVal);
        };

        $data = [
            'id' => $band->id,
            'slug' => $band->band_slug,
            'name' => $pick($sd?->band_name, $en?->band_name) ?: $band->band_slug,
            'tagline' => $pick($sd?->tagline, $en?->tagline),
            'pic' => $this->mediaUrl($band->band_pic),
            'is_featured' => (bool) $band->is_featured,
            'formed_year' => $band->formed_year,
            'lyrics_count' => $band->lyrics_count ?? 0,
            'members_count' => $band->singers_count ?? $band->singers?->count() ?? 0,
            'listen_links' => \App\Support\ListenLinks::forApi($band->listen_links),
        ];

        if ($detail) {
            $data['bio'] = $pick($sd?->band_bio, $en?->band_bio);
            $data['members'] = ($band->singers ?? collect())
                ->filter(fn (Singer $s) => (bool) $s->visibility)
                ->map(function (Singer $singer) use ($lang) {
                    $sd = $singer->allDetails->firstWhere('lang', 'sd');
                    $en = $singer->allDetails->firstWhere('lang', 'en');
                    $name = $lang === 'en'
                        ? ($en?->singer_laqab ?: $en?->singer_name ?: $sd?->singer_laqab ?: $sd?->singer_name)
                        : ($sd?->singer_laqab ?: $sd?->singer_name ?: $en?->singer_laqab ?: $en?->singer_name);

                    return [
                        'id' => $singer->id,
                        'slug' => $singer->singer_slug,
                        'name' => $name ?: $singer->singer_slug,
                        'pic' => $this->mediaUrl($singer->singer_pic),
                        'role' => $singer->pivot->role,
                    ];
                })
                ->values();
        }

        return $data;
    }

    private function mediaUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        return asset(ltrim($path, '/'));
    }
}
