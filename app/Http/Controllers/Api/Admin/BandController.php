<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Band;
use App\Models\Singer;
use App\Support\ListenLinks;
use App\Traits\HasMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BandController extends Controller
{
    use HasMedia;

    public function __construct()
    {
        $this->middleware('can:view_bands')->only(['index', 'show', 'checkSlug']);
        $this->middleware('can:create_bands')->only(['store']);
        $this->middleware('can:edit_bands')->only(['update', 'toggleVisibility', 'toggleFeatured', 'restore']);
        $this->middleware('can:delete_bands')->only(['destroy', 'permanentDelete']);
    }

    public function index(Request $request)
    {
        $query = Band::query()
            ->with(['allDetails', 'singers.allDetails'])
            ->withCount('lyrics');

        if ($request->has('only_trashed') && $request->only_trashed === 'true') {
            $query->onlyTrashed();
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

        $perPage = (int) $request->get('per_page', 10);
        $bands = $query->orderByDesc('created_at')->paginate($perPage);

        $bands->through(function (Band $band) {
            $sd = $band->allDetails->firstWhere('lang', 'sd') ?? $band->allDetails->first();

            return [
                'id' => $band->id,
                'band_slug' => $band->band_slug,
                'band_pic' => $band->band_pic,
                'band_name' => $sd?->band_name ?? $band->band_slug,
                'tagline' => $sd?->tagline,
                'visibility' => $band->visibility,
                'is_featured' => $band->is_featured,
                'formed_year' => $band->formed_year,
                'lyrics_count' => $band->lyrics_count ?? 0,
                'members_count' => $band->singers->count(),
                'deleted_at' => $band->deleted_at,
            ];
        });

        return response()->json($bands);
    }

    public function show($id)
    {
        $band = Band::with(['allDetails', 'singers.allDetails'])
            ->withCount('lyrics')
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('band_slug', $id);
            })
            ->firstOrFail();

        $sd = $band->allDetails->firstWhere('lang', 'sd');
        $en = $band->allDetails->firstWhere('lang', 'en');

        return response()->json([
            'id' => $band->id,
            'band_slug' => $band->band_slug,
            'band_pic' => $band->band_pic,
            'formed_year' => $band->formed_year,
            'visibility' => $band->visibility,
            'is_featured' => $band->is_featured,
            'lyrics_count' => $band->lyrics_count ?? 0,
            'band_name' => $sd?->band_name ?? '',
            'tagline' => $sd?->tagline ?? '',
            'band_bio' => $sd?->band_bio ?? '',
            'band_name_roman' => $en?->band_name ?? '',
            'tagline_roman' => $en?->tagline ?? '',
            'band_bio_roman' => $en?->band_bio ?? '',
            'listen_links' => ListenLinks::normalize($band->listen_links),
            ...ListenLinks::flat($band->listen_links),
            'members' => $band->singers->map(function (Singer $singer) {
                $sd = $singer->allDetails->firstWhere('lang', 'sd');

                return [
                    'id' => $singer->id,
                    'slug' => $singer->singer_slug,
                    'name' => $sd?->singer_laqab ?: $sd?->singer_name ?: $singer->singer_slug,
                    'role' => $singer->pivot->role,
                    'sort_order' => $singer->pivot->sort_order,
                ];
            })->values(),
            'singer_ids' => $band->singers->pluck('id')->values(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request);
        $slug = $this->resolveSlug($validated);

        DB::beginTransaction();
        try {
            $imagePath = $this->handleImageUpload($request, $slug, null);

            $band = Band::create([
                'band_slug' => $slug,
                'band_pic' => $imagePath,
                'formed_year' => $validated['formed_year'] ?? null,
                'visibility' => filter_var($validated['visibility'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'is_featured' => filter_var($validated['is_featured'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'listen_links' => ListenLinks::fromRequest($request, $validated),
            ]);

            $this->syncDetails($band, $validated);
            $this->syncMembers($band, $validated, $request);

            DB::commit();

            return response()->json([
                'message' => 'Band created',
                'id' => $band->id,
                'band' => [
                    'id' => $band->id,
                    'name' => $validated['band_name'],
                    'slug' => $band->band_slug,
                    'pic' => $band->band_pic,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create band: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $band = Band::where(function ($q) use ($id) {
            $q->where('id', $id)->orWhere('band_slug', $id);
        })->firstOrFail();

        $validated = $this->validatePayload($request, $band->id);
        $slug = $this->resolveSlug($validated, $band->id);

        DB::beginTransaction();
        try {
            $imagePath = $this->handleImageUpload($request, $slug, $band->band_pic);

            if ($request->boolean('remove_image') && $band->band_pic) {
                $this->deleteImageFiles($band->band_pic, true);
                $imagePath = null;
            }

            $band->update([
                'band_slug' => $slug,
                'band_pic' => $imagePath,
                'formed_year' => $validated['formed_year'] ?? null,
                'visibility' => filter_var($validated['visibility'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'is_featured' => filter_var($validated['is_featured'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'listen_links' => ListenLinks::fromRequest($request, $validated),
            ]);

            $this->syncDetails($band, $validated);
            $this->syncMembers($band, $validated, $request);

            DB::commit();

            return response()->json([
                'message' => 'Band updated',
                'id' => $band->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update band: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $band = Band::where(function ($q) use ($id) {
            $q->where('id', $id)->orWhere('band_slug', $id);
        })->firstOrFail();

        $band->delete();

        return response()->json(['message' => 'Band moved to trash']);
    }

    public function restore($id)
    {
        $band = Band::onlyTrashed()
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('band_slug', $id);
            })
            ->firstOrFail();

        $band->restore();

        return response()->json(['message' => 'Band restored']);
    }

    public function permanentDelete($id)
    {
        $band = Band::withTrashed()
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('band_slug', $id);
            })
            ->firstOrFail();

        DB::beginTransaction();
        try {
            if ($band->band_pic) {
                $this->deleteImageFiles($band->band_pic, true);
            }
            $band->singers()->detach();
            $band->allDetails()->delete();
            $band->forceDelete();
            DB::commit();

            return response()->json(['message' => 'Band permanently deleted']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to permanently delete: ' . $e->getMessage()], 500);
        }
    }

    public function toggleVisibility($id)
    {
        $band = Band::where(function ($q) use ($id) {
            $q->where('id', $id)->orWhere('band_slug', $id);
        })->firstOrFail();

        $band->visibility = !$band->visibility;
        $band->save();

        return response()->json([
            'message' => 'Visibility updated',
            'visibility' => $band->visibility,
        ]);
    }

    public function toggleFeatured($id)
    {
        $band = Band::where(function ($q) use ($id) {
            $q->where('id', $id)->orWhere('band_slug', $id);
        })->firstOrFail();

        $band->is_featured = !$band->is_featured;
        $band->save();

        return response()->json([
            'message' => 'Featured status updated',
            'is_featured' => $band->is_featured,
        ]);
    }

    public function checkSlug(Request $request)
    {
        $slug = $request->get('slug');
        $id = $request->get('id');

        $query = Band::where('band_slug', $slug);
        if ($id) {
            $query->where('id', '!=', $id)->where('band_slug', '!=', $id);
        }

        return response()->json(['exists' => $query->exists()]);
    }

    private function validatePayload(Request $request, ?int $bandId = null): array
    {
        return $request->validate([
            'band_name' => 'required|string|max:255',
            'band_name_roman' => 'nullable|string|max:255',
            'tagline' => 'nullable|string|max:255',
            'tagline_roman' => 'nullable|string|max:255',
            'band_bio' => 'nullable|string',
            'band_bio_roman' => 'nullable|string',
            'band_slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('bands', 'band_slug')->ignore($bandId),
            ],
            'formed_year' => 'nullable|integer|min:1800|max:' . ((int) date('Y') + 1),
            'visibility' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            ...ListenLinks::rules(),
            'remove_image' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpeg,webp,jpg,png|max:10240',
            'singer_ids' => 'nullable|array',
            'singer_ids.*' => 'integer|exists:singers,id',
            'members' => 'nullable|array',
            'members.*.singer_id' => 'required_with:members|integer|exists:singers,id',
            'members.*.role' => 'nullable|string|max:40',
            'members.*.sort_order' => 'nullable|integer|min:0',
        ]);
    }

    private function resolveSlug(array $validated, ?int $ignoreId = null): string
    {
        $slugSource = $validated['band_slug']
            ?? $validated['band_name_roman']
            ?? $validated['band_name'];

        $slug = Str::slug($slugSource) ?: 'band-' . Str::random(6);
        $base = $slug;
        $i = 1;

        while (
            Band::where('band_slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    private function handleImageUpload(Request $request, string $slug, ?string $existing): ?string
    {
        if (!$request->hasFile('image')) {
            return $existing;
        }

        if ($existing) {
            $upload = $this->updateImage($request->file('image'), 'bands', $existing, $slug, true);
        } else {
            $upload = $this->uploadImage($request->file('image'), 'bands', $slug, true);
        }

        if (!empty($upload['error'])) {
            throw new \RuntimeException($upload['message'] ?? 'Image upload failed');
        }

        return $upload['full_path'] ?? $existing;
    }

    private function syncDetails(Band $band, array $validated): void
    {
        $band->allDetails()->updateOrCreate(
            ['lang' => 'sd'],
            [
                'band_name' => $validated['band_name'],
                'tagline' => $validated['tagline'] ?? null,
                'band_bio' => $validated['band_bio'] ?? null,
            ]
        );

        if (
            !empty($validated['band_name_roman'])
            || !empty($validated['tagline_roman'])
            || !empty($validated['band_bio_roman'])
        ) {
            $band->allDetails()->updateOrCreate(
                ['lang' => 'en'],
                [
                    'band_name' => $validated['band_name_roman'] ?? $validated['band_name'],
                    'tagline' => $validated['tagline_roman'] ?? null,
                    'band_bio' => $validated['band_bio_roman'] ?? null,
                ]
            );
        }
    }

    private function syncMembers(Band $band, array $validated, Request $request): void
    {
        if (
            !$request->has('singer_ids')
            && !$request->has('members')
            && !$request->boolean('sync_members')
        ) {
            return;
        }

        $sync = [];

        if (!empty($validated['members']) && is_array($validated['members'])) {
            foreach ($validated['members'] as $i => $member) {
                $singerId = (int) ($member['singer_id'] ?? 0);
                if (!$singerId) {
                    continue;
                }
                $sync[$singerId] = [
                    'role' => $member['role'] ?? 'member',
                    'sort_order' => $member['sort_order'] ?? $i,
                ];
            }
        } else {
            $ids = $validated['singer_ids'] ?? $request->input('singer_ids', []);
            if (!is_array($ids)) {
                $ids = [];
            }
            foreach (array_values($ids) as $i => $singerId) {
                if (!$singerId) {
                    continue;
                }
                $sync[(int) $singerId] = [
                    'role' => 'member',
                    'sort_order' => $i,
                ];
            }
        }

        $band->singers()->sync($sync);
    }
}
