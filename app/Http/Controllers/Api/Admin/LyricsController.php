<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Band;
use App\Models\Lyrics;
use App\Models\LyricsGenre;
use App\Models\Poets;
use App\Models\Singer;
use App\Support\ListenLinks;
use App\Support\SafeUserData;
use App\Traits\HasMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LyricsController extends Controller
{
    use HasMedia;
    private const KINDS = ['sung', 'couplet', 'spoken', 'explanation', 'music', 'other'];
    private const RELATIONS = ['exact', 'adapted', 'inspired', 'original', 'unknown'];
    private const ROLES = ['intro', 'mid', 'body', 'outro', 'other'];
    private const ALLOWED_HTML = '<p><br><b><strong><i><em><ul><ol><li><blockquote>';

    public function __construct()
    {
        $this->middleware('can:view_lyrics')->only(['index', 'show', 'checkSlug', 'create', 'searchPoetry', 'poetryCouplets', 'searchLyrics', 'lyricsSourceParts']);
        $this->middleware('can:create_lyrics')->only(['store']);
        $this->middleware('can:edit_lyrics')->only(['update', 'toggleVisibility', 'toggleFeatured', 'restore']);
        $this->middleware('can:delete_lyrics')->only(['destroy', 'permanentDelete']);
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            if (!$user || (!$user->can('create_lyrics') && !$user->can('edit_lyrics'))) {
                abort(403);
            }
            return $next($request);
        })->only(['uploadCover']);
    }

    public function index(Request $request)
    {
        $query = Lyrics::query();

        if ($request->has('only_trashed') && $request->only_trashed === 'true') {
            $query->onlyTrashed();
        }

        $query->with([
            'info' => fn ($q) => $q->where('lang', 'sd'),
            'singer.allDetails',
            'genre.details',
            'parts',
            'user' => fn ($q) => $q->select('id', 'name'),
        ]);

        if ($request->filled('search')) {
            $like = '%' . addcslashes($request->search, '%_\\') . '%';
            $query->where(function ($q) use ($like) {
                $q->whereHas('translations', function ($sq) use ($like) {
                    $sq->where('title', 'like', $like)
                        ->orWhere('info', 'like', $like);
                })
                    ->orWhereHas('parts', function ($sq) use ($like) {
                        $sq->where('text_sd', 'like', $like)
                            ->orWhere('text_roman', 'like', $like);
                    })
                    ->orWhereHas('singer.allDetails', function ($sq) use ($like) {
                        $sq->where('singer_name', 'like', $like);
                    });
            });
        }

        $perPage = $request->get('per_page', 10);
        $lyrics = $query->orderByDesc('created_at')->paginate($perPage);

        /** @var \Illuminate\Pagination\LengthAwarePaginator $lyrics */
        $lyrics->through(function (Lyrics $item) {
            return $this->serializeIndexItem($item);
        });

        return response()->json($lyrics);
    }

    private function serializeIndexItem(Lyrics $lyrics): array
    {
        $user = $lyrics->relationLoaded('user') ? $lyrics->getRelation('user') : null;
        $lyrics->unsetRelation('user');

        $data = $lyrics->toArray();
        $data['user'] = SafeUserData::basic($user, '/api/admin/lyrics');

        $singerName = null;
        if ($lyrics->singer) {
            $sd = $lyrics->singer->allDetails->firstWhere('lang', 'sd')
                ?? $lyrics->singer->allDetails->first();
            $singerName = $sd?->singer_name ?? $lyrics->singer->singer_slug;
        }
        $data['singer_name'] = $singerName;
        $genreName = null;
        if ($lyrics->genre) {
            $gd = $lyrics->genre->details->firstWhere('lang', 'sd')
                ?? $lyrics->genre->details->first();
            $genreName = $gd?->name ?? $lyrics->genre->slug;
        }
        $data['genre_name'] = $genreName;
        $data['parts_count'] = $lyrics->parts?->count() ?? 0;
        $data['poets_count'] = $lyrics->parts
            ? $lyrics->parts->pluck('poet_id')->filter()->unique()->count()
            : 0;
        $data['has_music'] = !empty($lyrics->music_url);

        return $data;
    }

    public function show($id)
    {
        $lyrics = Lyrics::with([
            'translations',
            'parts.poet.details' => fn ($q) => $q->where('lang', 'sd'),
            'parts.poetry.info' => fn ($q) => $q->where('lang', 'sd'),
            'parts.sourceLyrics.info' => fn ($q) => $q->where('lang', 'sd'),
            'singer.allDetails',
            'band.allDetails',
            'collaborators',
            'poetry.info' => fn ($q) => $q->where('lang', 'sd'),
            'poetry.poet_details' => fn ($q) => $q->where('lang', 'sd'),
        ])
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('lyrics_slug', $id);
            })
            ->firstOrFail();

        $payload = $lyrics->toArray();
        $payload['collaborators'] = $lyrics->collaborators->map(fn ($c) => [
            'type' => $c->collaborator_type,
            'id' => $c->collaborator_id,
            'role' => $c->role,
            'sort_order' => $c->sort_order,
        ])->values();

        return response()->json($payload);
    }

    public function create()
    {
        $poets = Poets::where('visibility', 1)->with([
            'details' => fn ($q) => $q->where('lang', 'sd'),
        ])->select('id', 'poet_slug')->get()->map(function ($poet) {
            return [
                'id' => $poet->id,
                'name' => $poet->details?->poet_laqab ?? $poet->poet_slug,
            ];
        });

        $singers = Singer::where('visibility', 1)->with('allDetails')->get()->map(function ($singer) {
            $sd = $singer->allDetails->firstWhere('lang', 'sd')
                ?? $singer->allDetails->first();

            return [
                'id' => $singer->id,
                'name' => $sd?->singer_name ?? $singer->singer_slug,
                'slug' => $singer->singer_slug,
            ];
        });

        $genres = LyricsGenre::with('details')
            ->where('visibility', 1)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function ($genre) {
                $sd = $genre->details->firstWhere('lang', 'sd') ?? $genre->details->first();
                $en = $genre->details->firstWhere('lang', 'en');

                return [
                    'id' => $genre->id,
                    'slug' => $genre->slug,
                    'name' => $sd?->name ?? $genre->slug,
                    'name_en' => $en?->name,
                ];
            });

        $bands = Band::where('visibility', 1)->with('allDetails')->get()->map(function ($band) {
            $sd = $band->allDetails->firstWhere('lang', 'sd')
                ?? $band->allDetails->first();

            return [
                'id' => $band->id,
                'name' => $sd?->band_name ?? $band->band_slug,
                'slug' => $band->band_slug,
            ];
        });

        return response()->json([
            'poets' => $poets,
            'singers' => $singers,
            'bands' => $bands,
            'genres' => $genres,
            'kinds' => self::KINDS,
            'relations' => self::RELATIONS,
            'roles' => self::ROLES,
            'collab_roles' => ['feat', 'with', 'collab'],
            'content_styles' => ['justified', 'center', 'start', 'end'],
        ]);
    }

    public function searchPoetry(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $poetId = $request->get('poet_id');

        $query = \App\Models\Poetry::query()
            ->with([
                'info' => fn ($q) => $q->where('lang', 'sd'),
                'poet_details' => fn ($q) => $q->where('lang', 'sd'),
            ])
            ->where('visibility', 1);

        if ($poetId) {
            $query->where('poet_id', $poetId);
        }

        if ($search !== '') {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $query->where(function ($q) use ($like) {
                $q->whereHas('translations', function ($sq) use ($like) {
                    $sq->where('title', 'like', $like);
                })
                    ->orWhere('poetry_slug', 'like', $like)
                    ->orWhereHas('poet_details', function ($sq) use ($like) {
                        $sq->where('poet_laqab', 'like', $like)
                            ->orWhere('poet_name', 'like', $like);
                    })
                    ->orWhereHas('couplets', function ($sq) use ($like) {
                        $sq->where('lang', 'sd')->where('couplet_text', 'like', $like);
                    });
            });
        }

        $items = $query->orderByDesc('created_at')->limit(25)->get()->map(function ($poetry) {
            return [
                'id' => $poetry->id,
                'slug' => $poetry->poetry_slug,
                'title' => $poetry->info?->title ?? $poetry->poetry_slug,
                'poet_id' => $poetry->poet_id,
                'poet_name' => $poetry->poet_details?->poet_laqab
                    ?? $poetry->poet_details?->poet_name
                    ?? null,
            ];
        });

        return response()->json(['data' => $items]);
    }

    public function poetryCouplets($id)
    {
        $poetry = \App\Models\Poetry::with([
            'info' => fn ($q) => $q->where('lang', 'sd'),
            'poet_details' => fn ($q) => $q->where('lang', 'sd'),
            'couplets' => fn ($q) => $q->where('lang', 'sd')->orderBy('id'),
            'translations',
        ])
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('poetry_slug', $id);
            })
            ->firstOrFail();

        $romanCouplets = $poetry->couplets()
            ->where('lang', 'en')
            ->orderBy('id')
            ->get()
            ->values();

        $sdCouplets = $poetry->couplets->values();

        $couplets = $sdCouplets->map(function ($c, $i) use ($romanCouplets) {
            return [
                'id' => $c->id,
                'text_sd' => $c->couplet_text,
                'text_roman' => $romanCouplets[$i]->couplet_text ?? null,
            ];
        });

        $romanTitle = $poetry->translations->firstWhere('lang', 'en')?->title;

        return response()->json([
            'id' => $poetry->id,
            'slug' => $poetry->poetry_slug,
            'title' => $poetry->info?->title ?? $poetry->poetry_slug,
            'roman_title' => $romanTitle,
            'poet_id' => $poetry->poet_id,
            'poet_name' => $poetry->poet_details?->poet_laqab
                ?? $poetry->poet_details?->poet_name
                ?? null,
            'couplets' => $couplets,
        ]);
    }

    public function searchLyrics(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $exclude = $request->get('exclude_id');

        $query = Lyrics::query()
            ->with([
                'info' => fn ($q) => $q->where('lang', 'sd'),
                'singer.allDetails',
                'parts',
            ])
            ->where('visibility', 1);

        if ($exclude) {
            $query->where('id', '!=', $exclude)->where('lyrics_slug', '!=', $exclude);
        }

        if ($search !== '') {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $query->where(function ($q) use ($like) {
                $q->whereHas('translations', function ($sq) use ($like) {
                    $sq->where('title', 'like', $like);
                })
                    ->orWhere('lyrics_slug', 'like', $like)
                    ->orWhereHas('singer.allDetails', function ($sq) use ($like) {
                        $sq->where('singer_name', 'like', $like);
                    })
                    ->orWhereHas('parts', function ($sq) use ($like) {
                        $sq->where('text_sd', 'like', $like)
                            ->orWhere('text_roman', 'like', $like);
                    });
            });
        }

        $items = $query->orderByDesc('created_at')->limit(25)->get()->map(function ($lyrics) {
            $sd = $lyrics->singer?->allDetails?->firstWhere('lang', 'sd')
                ?? $lyrics->singer?->allDetails?->first();

            return [
                'id' => $lyrics->id,
                'slug' => $lyrics->lyrics_slug,
                'title' => $lyrics->info?->title ?? $lyrics->lyrics_slug,
                'singer_name' => $sd?->singer_name ?? null,
                'parts_count' => $lyrics->parts?->count() ?? 0,
            ];
        });

        return response()->json(['data' => $items]);
    }

    public function lyricsSourceParts($id)
    {
        $lyrics = Lyrics::with([
            'translations',
            'parts' => fn ($q) => $q->orderBy('sort_order'),
            'singer.allDetails',
        ])
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('lyrics_slug', $id);
            })
            ->firstOrFail();

        $sd = $lyrics->translations->firstWhere('lang', 'sd');
        $en = $lyrics->translations->firstWhere('lang', 'en');
        $singer = $lyrics->singer?->allDetails?->firstWhere('lang', 'sd')
            ?? $lyrics->singer?->allDetails?->first();

        $parts = $lyrics->parts->map(function ($part) {
            return [
                'id' => $part->id,
                'kind' => $part->kind,
                'role' => $part->role,
                'text_sd' => $part->text_sd,
                'text_roman' => $part->text_roman,
            ];
        });

        return response()->json([
            'id' => $lyrics->id,
            'slug' => $lyrics->lyrics_slug,
            'title' => $sd?->title ?? $lyrics->lyrics_slug,
            'roman_title' => $en?->title,
            'singer_name' => $singer?->singer_name,
            'parts' => $parts,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request);

        DB::beginTransaction();
        try {
            $lyrics = Lyrics::create([
                'singer_id' => $validated['singer_id'] ?? null,
                'band_id' => $validated['band_id'] ?? null,
                'genre_id' => $validated['genre_id'] ?? null,
                'poetry_id' => $validated['poetry_id'] ?? null,
                'user_id' => Auth::id(),
                'lyrics_slug' => $validated['lyrics_slug'],
                'lyrics_tags' => $validated['lyrics_tags'] ?? [],
                'visibility' => $validated['visibility'],
                'is_featured' => $validated['is_featured'],
                'content_style' => $validated['content_style'] ?? 'center',
                'music_url' => $validated['music_url'] ?? null,
                'music_title' => $validated['music_title'] ?? null,
                'music_type' => $validated['music_type'] ?? $this->detectMusicType($validated['music_url'] ?? null),
                'listen_links' => ListenLinks::fromRequest($request, $validated),
            ]);

            $this->syncTranslations($lyrics, $validated);
            $this->syncParts($lyrics, $validated['parts']);
            $this->syncCollaborators($lyrics, $validated['collaborators'] ?? []);

            DB::commit();
            return response()->json(['message' => 'Lyrics created successfully', 'id' => $lyrics->id], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create lyrics: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $lyrics = Lyrics::where(function ($q) use ($id) {
            $q->where('id', $id)->orWhere('lyrics_slug', $id);
        })->firstOrFail();
        $validated = $this->validatePayload($request, $lyrics->id);

        DB::beginTransaction();
        try {
            $lyrics->update([
                'singer_id' => $validated['singer_id'] ?? null,
                'band_id' => $validated['band_id'] ?? null,
                'genre_id' => $validated['genre_id'] ?? null,
                'poetry_id' => $validated['poetry_id'] ?? null,
                'lyrics_slug' => $validated['lyrics_slug'],
                'lyrics_tags' => $validated['lyrics_tags'] ?? [],
                'visibility' => $validated['visibility'],
                'is_featured' => $validated['is_featured'],
                'content_style' => $validated['content_style'] ?? 'center',
                'music_url' => $validated['music_url'] ?? null,
                'music_title' => $validated['music_title'] ?? null,
                'music_type' => $validated['music_type'] ?? $this->detectMusicType($validated['music_url'] ?? null),
                'listen_links' => ListenLinks::fromRequest($request, $validated),
            ]);

            $this->syncTranslations($lyrics, $validated);
            $lyrics->parts()->forceDelete();
            $this->syncParts($lyrics, $validated['parts']);
            $this->syncCollaborators($lyrics, $validated['collaborators'] ?? []);

            DB::commit();
            return response()->json(['message' => 'Lyrics updated successfully', 'id' => $lyrics->id]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update lyrics: ' . $e->getMessage()], 500);
        }
    }

    public function uploadCover(Request $request, $id)
    {
        $lyrics = Lyrics::where(function ($q) use ($id) {
            $q->where('id', $id)->orWhere('lyrics_slug', $id);
        })->firstOrFail();

        $request->validate([
            'cover_image' => 'nullable|image|mimes:jpeg,webp,jpg,png|max:10240',
            'remove_cover' => 'nullable|boolean',
        ]);

        if ($request->boolean('remove_cover')) {
            if ($lyrics->cover_image) {
                $this->deleteImageFiles($lyrics->cover_image, false);
            }
            $lyrics->cover_image = null;
            $lyrics->save();

            return response()->json([
                'message' => 'Cover removed',
                'cover_image' => null,
            ]);
        }

        if (!$request->hasFile('cover_image')) {
            return response()->json(['message' => 'No cover image provided'], 422);
        }

        try {
            $path = $this->resolveCoverUpload($request, $lyrics, $lyrics->lyrics_slug);
            $lyrics->cover_image = $path;
            $lyrics->save();

            return response()->json([
                'message' => 'Cover updated',
                'cover_image' => $lyrics->cover_image,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy($id)
    {
        $lyrics = Lyrics::where(function ($q) use ($id) {
            $q->where('id', $id)->orWhere('lyrics_slug', $id);
        })->firstOrFail();
        DB::beginTransaction();
        try {
            $lyrics->parts()->delete();
            $lyrics->delete();
            DB::commit();
            return response()->json(['message' => 'Lyrics moved to trash']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete lyrics: ' . $e->getMessage()], 500);
        }
    }

    public function restore($id)
    {
        $lyrics = Lyrics::onlyTrashed()
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('lyrics_slug', $id);
            })
            ->firstOrFail();
        $lyrics->restore();
        $lyrics->parts()->onlyTrashed()->restore();

        return response()->json(['message' => 'Lyrics restored']);
    }

    public function permanentDelete($id)
    {
        $lyrics = Lyrics::withTrashed()
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('lyrics_slug', $id);
            })
            ->firstOrFail();
        DB::beginTransaction();
        try {
            if ($lyrics->cover_image) {
                $this->deleteImageFiles($lyrics->cover_image, false);
            }
            $lyrics->parts()->withTrashed()->forceDelete();
            $lyrics->translations()->delete();
            $lyrics->forceDelete();
            DB::commit();
            return response()->json(['message' => 'Lyrics permanently deleted']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to permanently delete: ' . $e->getMessage()], 500);
        }
    }

    public function toggleVisibility($id)
    {
        $lyrics = Lyrics::where(function ($q) use ($id) {
            $q->where('id', $id)->orWhere('lyrics_slug', $id);
        })->firstOrFail();
        $lyrics->visibility = !$lyrics->visibility;
        $lyrics->save();

        return response()->json([
            'message' => 'Visibility updated',
            'visibility' => $lyrics->visibility,
        ]);
    }

    public function toggleFeatured($id)
    {
        $lyrics = Lyrics::where(function ($q) use ($id) {
            $q->where('id', $id)->orWhere('lyrics_slug', $id);
        })->firstOrFail();
        $lyrics->is_featured = !$lyrics->is_featured;
        $lyrics->save();

        return response()->json([
            'message' => 'Featured status updated',
            'is_featured' => $lyrics->is_featured,
        ]);
    }

    public function checkSlug(Request $request)
    {
        $slug = $request->get('slug');
        $id = $request->get('id');

        $query = Lyrics::where('lyrics_slug', $slug);
        if ($id) {
            $query->where('id', '!=', $id)->where('lyrics_slug', '!=', $id);
        }

        return response()->json(['exists' => $query->exists()]);
    }

    private function validatePayload(Request $request, ?int $lyricsId = null): array
    {
        return $request->validate([
            'lyrics_title' => 'required|string|max:255',
            'lyrics_slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('lyrics', 'lyrics_slug')->ignore($lyricsId),
            ],
            'singer_id' => 'nullable|exists:singers,id',
            'band_id' => 'nullable|exists:bands,id',
            'genre_id' => 'nullable|exists:lyrics_genres,id',
            'poetry_id' => 'nullable|exists:poetry_main,id',
            'content_style' => 'nullable|string',
            'visibility' => 'required|boolean',
            'is_featured' => 'required|boolean',
            'lyrics_info' => 'nullable|string',
            'source' => 'nullable|string',
            'lyrics_tags' => 'nullable|array',
            'roman_title' => 'nullable|string|max:255',
            'music_url' => 'nullable|string|max:1000',
            'music_title' => 'nullable|string|max:255',
            'music_type' => ['nullable', Rule::in(['youtube', 'audio', 'other'])],
            ...ListenLinks::rules(),
            'collaborators' => 'nullable|array',
            'collaborators.*.type' => ['required_with:collaborators', Rule::in(['singer', 'band'])],
            'collaborators.*.id' => 'required_with:collaborators|integer',
            'collaborators.*.role' => ['nullable', Rule::in(['feat', 'with', 'collab'])],
            'collaborators.*.sort_order' => 'nullable|integer|min:0',
            'parts' => 'required|array|min:1',
            'parts.*.kind' => ['required', Rule::in(self::KINDS)],
            'parts.*.section' => 'nullable|string|max:40',
            'parts.*.role' => ['nullable', Rule::in(self::ROLES)],
            'parts.*.relation' => ['nullable', Rule::in(self::RELATIONS)],
            'parts.*.poet_id' => 'nullable|exists:poets,id',
            'parts.*.poetry_id' => 'nullable|exists:poetry_main,id',
            'parts.*.couplet_id' => 'nullable|exists:poetry_couplets,id',
            'parts.*.source_lyrics_id' => 'nullable|exists:lyrics,id',
            'parts.*.source_part_id' => 'nullable|exists:lyrics_parts,id',
            'parts.*.text_sd' => 'nullable|string',
            'parts.*.text_roman' => 'nullable|string',
            'parts.*.sort_order' => 'nullable|integer|min:0',
        ]);
    }

    /**
     * @return string|null Absolute or relative path to keep on the model
     */
    private function resolveCoverUpload(Request $request, ?Lyrics $existing, string $slug): ?string
    {
        if ($request->boolean('remove_cover')) {
            if ($existing?->cover_image) {
                $this->deleteImageFiles($existing->cover_image, false);
            }
            return null;
        }

        if ($request->hasFile('cover_image')) {
            if ($existing?->cover_image) {
                $upload = $this->updateImage(
                    $request->file('cover_image'),
                    'lyrics',
                    $existing->cover_image,
                    $slug
                );
            } else {
                $upload = $this->uploadImage($request->file('cover_image'), 'lyrics', $slug);
            }

            if (!empty($upload['error'])) {
                throw new \RuntimeException($upload['message'] ?? 'Cover upload failed');
            }

            return $upload['full_path'] ?? null;
        }

        return $existing?->cover_image;
    }

    private function detectMusicType(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        $url = strtolower($url);

        if (str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be')) {
            return 'youtube';
        }

        if (preg_match('/\.(mp3|m4a|ogg|wav|aac)(\?|$)/i', $url)) {
            return 'audio';
        }

        return 'other';
    }

    private function syncTranslations(Lyrics $lyrics, array $validated): void
    {
        $lyrics->translations()->updateOrCreate(
            ['lang' => 'sd'],
            [
                'title' => strip_tags($validated['lyrics_title']),
                'info' => strip_tags($validated['lyrics_info'] ?? null, self::ALLOWED_HTML),
                'source' => strip_tags($validated['source'] ?? null),
            ]
        );

        if (!empty($validated['roman_title'])) {
            $lyrics->translations()->updateOrCreate(
                ['lang' => 'en'],
                [
                    'title' => $validated['roman_title'],
                    'info' => $validated['lyrics_info'] ?? null,
                    'source' => $validated['source'] ?? null,
                ]
            );
        }
    }

    private function syncParts(Lyrics $lyrics, array $parts): void
    {
        foreach ($parts as $index => $part) {
            $kind = $part['kind'] ?? 'sung';
            $textSd = trim((string) ($part['text_sd'] ?? ''));
            $textRoman = trim((string) ($part['text_roman'] ?? ''));

            if ($kind === 'music' && $textSd === '' && $textRoman === '') {
                $textSd = '♪ موسيقي شروع';
                $textRoman = '♪ Music starts';
            }

            if ($textSd === '' && $textRoman === '') {
                continue;
            }

            $lyrics->parts()->create([
                'sort_order' => $part['sort_order'] ?? $index,
                'kind' => $kind,
                'section' => isset($part['section']) && $part['section'] !== ''
                    ? substr((string) $part['section'], 0, 40)
                    : null,
                'role' => $part['role'] ?? ($kind === 'music' ? 'mid' : null),
                'relation' => $part['relation'] ?? (
                    !empty($part['poet_id']) || !empty($part['poetry_id']) || !empty($part['couplet_id']) || !empty($part['source_lyrics_id'])
                        ? 'exact'
                        : 'original'
                ),
                'poet_id' => $part['poet_id'] ?? null,
                'poetry_id' => $part['poetry_id'] ?? null,
                'couplet_id' => $part['couplet_id'] ?? null,
                'source_lyrics_id' => $part['source_lyrics_id'] ?? null,
                'source_part_id' => $part['source_part_id'] ?? null,
                'text_sd' => $textSd !== '' ? strip_tags($textSd, self::ALLOWED_HTML) : null,
                'text_roman' => $textRoman !== '' ? $textRoman : null,
            ]);
        }
    }

    private function syncCollaborators(Lyrics $lyrics, array $collaborators): void
    {
        $lyrics->collaborators()->delete();

        foreach (array_values($collaborators) as $index => $row) {
            $type = $row['type'] ?? null;
            $id = (int) ($row['id'] ?? 0);
            if (!$type || !$id) {
                continue;
            }

            if ($type === 'singer' && !Singer::where('id', $id)->exists()) {
                continue;
            }
            if ($type === 'band' && !Band::where('id', $id)->exists()) {
                continue;
            }

            $lyrics->collaborators()->create([
                'collaborator_type' => $type,
                'collaborator_id' => $id,
                'role' => $row['role'] ?? 'feat',
                'sort_order' => $row['sort_order'] ?? $index,
            ]);
        }
    }
}
