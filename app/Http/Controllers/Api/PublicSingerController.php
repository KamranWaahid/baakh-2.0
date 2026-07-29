<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Singer;
use Illuminate\Http\Request;

class PublicSingerController extends Controller
{
    public function index(Request $request)
    {
        $lang = resolve_request_locale($request->get('lang', $request->header('Accept-Language')), 'sd');
        $perPage = min((int) $request->get('per_page', 24), 50);

        $query = Singer::query()
            ->with('allDetails')
            ->withCount(['lyrics' => fn ($q) => $q->where('visibility', 1)])
            ->where('visibility', 1)
            ->orderByDesc('is_featured')
            ->orderByDesc('created_at');

        if ($request->boolean('featured')) {
            $query->where('is_featured', 1);
        }

        if ($request->filled('search')) {
            $like = '%' . addcslashes($request->search, '%_\\') . '%';
            $query->where(function ($q) use ($like) {
                $q->where('singer_slug', 'like', $like)
                    ->orWhereHas('allDetails', function ($sq) use ($like) {
                        $sq->where('singer_name', 'like', $like)
                            ->orWhere('singer_laqab', 'like', $like);
                    });
            });
        }

        $items = $query->paginate($perPage);
        $items->through(fn (Singer $singer) => $this->serialize($singer, $lang, false));

        return response()->json($items);
    }

    public function show(Request $request, string $slug)
    {
        $lang = resolve_request_locale($request->get('lang', $request->header('Accept-Language')), 'sd');

        $singer = Singer::with(['allDetails', 'bands.allDetails'])
            ->withCount(['lyrics' => fn ($q) => $q->where('visibility', 1)])
            ->where('visibility', 1)
            ->where(function ($q) use ($slug) {
                $q->where('singer_slug', $slug)->orWhere('id', $slug);
            })
            ->firstOrFail();

        return response()->json($this->serialize($singer, $lang, true));
    }

    public function lyrics(Request $request, string $slug)
    {
        $singer = Singer::where('visibility', 1)
            ->where(function ($q) use ($slug) {
                $q->where('singer_slug', $slug)->orWhere('id', $slug);
            })
            ->firstOrFail();

        $request->merge(['singer' => $singer->singer_slug]);

        return app(PublicLyricsController::class)->index($request);
    }

    private function serialize(Singer $singer, string $lang, bool $detail): array
    {
        $sd = $singer->allDetails->firstWhere('lang', 'sd');
        $en = $singer->allDetails->firstWhere('lang', 'en');

        $pick = function (?string $sdVal, ?string $enVal) use ($lang) {
            return $lang === 'en' ? ($enVal ?: $sdVal) : ($sdVal ?: $enVal);
        };

        $data = [
            'id' => $singer->id,
            'slug' => $singer->singer_slug,
            'name' => $pick(
                $sd?->singer_laqab ?: $sd?->singer_name,
                $en?->singer_laqab ?: $en?->singer_name
            ) ?: $singer->singer_slug,
            'full_name' => $pick($sd?->singer_name, $en?->singer_name),
            'tagline' => $pick($sd?->tagline, $en?->tagline),
            'pic' => $this->mediaUrl($singer->singer_pic),
            'is_featured' => (bool) $singer->is_featured,
            'lyrics_count' => $singer->lyrics_count ?? 0,
            'listen_links' => \App\Support\ListenLinks::forApi($singer->listen_links),
        ];

        if ($detail) {
            $data['bio'] = $pick($sd?->singer_bio, $en?->singer_bio);
            $data['birth_place'] = $sd?->birth_place ?: $en?->birth_place;
            $data['death_place'] = $sd?->death_place ?: $en?->death_place;
            $data['date_of_birth'] = $singer->date_of_birth?->format('Y-m-d');
            $data['date_of_death'] = $singer->date_of_death?->format('Y-m-d');
            $data['bands'] = ($singer->bands ?? collect())
                ->filter(fn ($band) => (bool) $band->visibility)
                ->map(function ($band) use ($lang) {
                    $bsd = $band->allDetails->firstWhere('lang', 'sd');
                    $ben = $band->allDetails->firstWhere('lang', 'en');
                    $name = $lang === 'en'
                        ? ($ben?->band_name ?: $bsd?->band_name)
                        : ($bsd?->band_name ?: $ben?->band_name);

                    return [
                        'id' => $band->id,
                        'slug' => $band->band_slug,
                        'name' => $name ?: $band->band_slug,
                        'role' => $band->pivot->role,
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
