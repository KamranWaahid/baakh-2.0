<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Band;
use App\Models\Lyrics;
use App\Models\LyricsCollaborator;
use App\Models\LyricsGenre;
use App\Models\Singer;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PublicLyricsController extends Controller
{
    public function index(Request $request)
    {
        $lang = resolve_request_locale($request->get('lang', $request->header('Accept-Language')), 'sd');
        $perPage = min((int) $request->get('per_page', 20), 50);

        $query = Lyrics::query()
            ->with([
                'translations',
                'singer.allDetails',
                'band.allDetails',
                'genre.details',
                'parts',
                'collaborators',
            ])
            ->where('visibility', 1)
            ->orderByDesc('is_featured')
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $like = '%' . addcslashes($request->search, '%_\\') . '%';
            $query->where(function ($q) use ($like) {
                $q->where('lyrics_slug', 'like', $like)
                    ->orWhereHas('translations', fn ($sq) => $sq->where('title', 'like', $like))
                    ->orWhereHas('parts', function ($sq) use ($like) {
                        $sq->where('text_sd', 'like', $like)->orWhere('text_roman', 'like', $like);
                    })
                    ->orWhereHas('singer.allDetails', function ($sq) use ($like) {
                        $sq->where('singer_name', 'like', $like)->orWhere('singer_laqab', 'like', $like);
                    })
                    ->orWhereHas('band.allDetails', function ($sq) use ($like) {
                        $sq->where('band_name', 'like', $like);
                    });
            });
        }

        if ($request->filled('singer')) {
            $singer = $request->get('singer');
            $query->whereHas('singer', function ($q) use ($singer) {
                $q->where('singer_slug', $singer)->orWhere('id', $singer);
            });
        }

        if ($request->filled('band')) {
            $band = $request->get('band');
            $query->where(function ($q) use ($band) {
                $q->whereHas('band', function ($bq) use ($band) {
                    $bq->where('band_slug', $band)->orWhere('id', $band);
                })->orWhereHas('collaborators', function ($cq) use ($band) {
                    $cq->where('collaborator_type', 'band')
                        ->whereIn('collaborator_id', function ($sub) use ($band) {
                            $sub->select('id')
                                ->from('bands')
                                ->where('band_slug', $band)
                                ->orWhere('id', $band);
                        });
                });
            });
        }

        if ($request->filled('genre')) {
            $genre = $request->get('genre');
            $query->whereHas('genre', function ($q) use ($genre) {
                $q->where('visibility', 1)
                    ->where(function ($gq) use ($genre) {
                        $gq->where('slug', $genre)->orWhere('id', $genre);
                    });
            });
        }

        if ($request->boolean('featured')) {
            $query->where('is_featured', 1);
        }

        $items = $query->paginate($perPage);

        $items->through(fn (Lyrics $lyrics) => $this->serializeListItem($lyrics, $lang));

        return response()->json($items);
    }

    public function show(Request $request, string $slug)
    {
        $lang = resolve_request_locale($request->get('lang', $request->header('Accept-Language')), 'sd');

        $lyrics = Lyrics::with([
            'translations',
            'singer.allDetails',
            'band.allDetails',
            'genre.details',
            'collaborators',
            'parts' => fn ($q) => $q->orderBy('sort_order'),
            'parts.poet.details' => fn ($q) => $q->where('lang', 'sd'),
            'parts.poetry.info' => fn ($q) => $q->where('lang', 'sd'),
            'poetry.info' => fn ($q) => $q->where('lang', 'sd'),
            'poetry.translations',
            'poetry.poet_details' => fn ($q) => $q->where('lang', 'sd'),
            'poetry.couplets' => fn ($q) => $q->where('lang', 'sd')->orderBy('id'),
        ])
            ->where('visibility', 1)
            ->where(function ($q) use ($slug) {
                $q->where('lyrics_slug', $slug)->orWhere('id', $slug);
            })
            ->firstOrFail();

        return response()->json($this->serializeDetail($lyrics, $lang));
    }

    private function serializeListItem(Lyrics $lyrics, string $lang): array
    {
        $sd = $lyrics->translations->firstWhere('lang', 'sd');
        $en = $lyrics->translations->firstWhere('lang', 'en');
        $title = $lang === 'en'
            ? ($en?->title ?: $sd?->title)
            : ($sd?->title ?: $en?->title);

        $singer = $this->serializeSingerBrief($lyrics->singer, $lang);
        $band = $this->serializeBandBrief($lyrics->band, $lang);
        $genre = $this->serializeGenreBrief($lyrics->genre, $lang);

        return [
            'id' => $lyrics->id,
            'slug' => $lyrics->lyrics_slug,
            'title' => $title ?: $lyrics->lyrics_slug,
            'title_sd' => $sd?->title,
            'title_en' => $en?->title,
            'cover' => $this->mediaUrl($lyrics->cover_image),
            'is_featured' => (bool) $lyrics->is_featured,
            'music_url' => $lyrics->music_url,
            'music_type' => $lyrics->music_type,
            'music_title' => $lyrics->music_title,
            'listen_links' => \App\Support\ListenLinks::forApi(
                $lyrics->listen_links,
                $lyrics->music_url
            ),
            'parts_count' => $lyrics->parts?->count() ?? 0,
            'has_poetry' => !empty($lyrics->poetry_id),
            'singer' => $singer,
            'band' => $band,
            'genre' => $genre,
            'collaborators' => $this->serializeCollaborators($lyrics->collaborators ?? collect(), $lang),
        ];
    }

    private function serializeDetail(Lyrics $lyrics, string $lang): array
    {
        $base = $this->serializeListItem($lyrics, $lang);
        $sd = $lyrics->translations->firstWhere('lang', 'sd');
        $en = $lyrics->translations->firstWhere('lang', 'en');

        $base['info'] = $lang === 'en' ? ($en?->info ?: $sd?->info) : ($sd?->info ?: $en?->info);
        $base['source'] = $lang === 'en' ? ($en?->source ?: $sd?->source) : ($sd?->source ?: $en?->source);
        $base['content_style'] = $lyrics->content_style;
        $base['parts'] = $lyrics->parts->map(function ($part) {
            return [
                'id' => $part->id,
                'kind' => $part->kind,
                'section' => $part->section,
                'role' => $part->role,
                'relation' => $part->relation,
                'text_sd' => $part->text_sd,
                'text_roman' => $part->text_roman,
                'poet_name' => $part->poet?->details?->poet_laqab
                    ?? $part->poet?->details?->poet_name,
                'poetry_title' => $part->poetry?->info?->title,
            ];
        })->values();
        $base['poetry'] = $this->serializeFullPoetry($lyrics->poetry, $lang);

        return $base;
    }

    private function serializeFullPoetry($poetry, string $lang): ?array
    {
        if (!$poetry) {
            return null;
        }

        $sdTitle = $poetry->info?->title;
        $enTitle = $poetry->translations?->firstWhere('lang', 'en')?->title;
        $title = $lang === 'en'
            ? ($enTitle ?: $sdTitle)
            : ($sdTitle ?: $enTitle);

        $poetName = $poetry->poet_details?->poet_laqab
            ?? $poetry->poet_details?->poet_name;

        $romanCouplets = $poetry->couplets()
            ->where('lang', 'en')
            ->orderBy('id')
            ->get()
            ->values();

        $couplets = ($poetry->couplets ?? collect())->values()->map(function ($c, $i) use ($romanCouplets) {
            return [
                'id' => $c->id,
                'text_sd' => $c->couplet_text,
                'text_roman' => $romanCouplets[$i]->couplet_text ?? null,
            ];
        })->values();

        return [
            'id' => $poetry->id,
            'slug' => $poetry->poetry_slug,
            'title' => $title ?: $poetry->poetry_slug,
            'title_sd' => $sdTitle,
            'title_en' => $enTitle,
            'poet_name' => $poetName,
            'couplets' => $couplets,
        ];
    }

    private function serializeGenreBrief(?LyricsGenre $genre, string $lang): ?array
    {
        if (!$genre || !$genre->visibility) {
            return null;
        }

        $sd = $genre->details->firstWhere('lang', 'sd');
        $en = $genre->details->firstWhere('lang', 'en');
        $name = $lang === 'en'
            ? ($en?->name ?: $sd?->name)
            : ($sd?->name ?: $en?->name);

        return [
            'id' => $genre->id,
            'slug' => $genre->slug,
            'name' => $name ?: $genre->slug,
        ];
    }

    private function serializeSingerBrief(?Singer $singer, string $lang): ?array
    {
        if (!$singer || !$singer->visibility) {
            return null;
        }

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
        ];
    }

    private function serializeBandBrief(?Band $band, string $lang): ?array
    {
        if (!$band || !$band->visibility) {
            return null;
        }

        $sd = $band->allDetails->firstWhere('lang', 'sd');
        $en = $band->allDetails->firstWhere('lang', 'en');
        $name = $lang === 'en'
            ? ($en?->band_name ?: $sd?->band_name)
            : ($sd?->band_name ?: $en?->band_name);

        return [
            'id' => $band->id,
            'slug' => $band->band_slug,
            'name' => $name ?: $band->band_slug,
            'pic' => $this->mediaUrl($band->band_pic),
        ];
    }

    private function serializeCollaborators(Collection $rows, string $lang): array
    {
        if ($rows->isEmpty()) {
            return [];
        }

        $singerIds = $rows->where('collaborator_type', 'singer')->pluck('collaborator_id')->unique()->values();
        $bandIds = $rows->where('collaborator_type', 'band')->pluck('collaborator_id')->unique()->values();

        $singers = $singerIds->isNotEmpty()
            ? Singer::with('allDetails')->whereIn('id', $singerIds)->where('visibility', 1)->get()->keyBy('id')
            : collect();
        $bands = $bandIds->isNotEmpty()
            ? Band::with('allDetails')->whereIn('id', $bandIds)->where('visibility', 1)->get()->keyBy('id')
            : collect();

        return $rows->map(function (LyricsCollaborator $row) use ($lang, $singers, $bands) {
            if ($row->collaborator_type === 'band') {
                $brief = $this->serializeBandBrief($bands->get($row->collaborator_id), $lang);
            } else {
                $brief = $this->serializeSingerBrief($singers->get($row->collaborator_id), $lang);
            }

            if (!$brief) {
                return null;
            }

            return [
                'type' => $row->collaborator_type,
                'role' => $row->role ?: 'feat',
                'id' => $brief['id'],
                'slug' => $brief['slug'],
                'name' => $brief['name'],
                'pic' => $brief['pic'] ?? null,
            ];
        })->filter()->values()->all();
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
