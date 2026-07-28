<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Singer;
use App\Traits\HasMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SingerController extends Controller
{
    use HasMedia;

    public function __construct()
    {
        $this->middleware('can:view_singers')->only(['index', 'show', 'checkSlug']);
        $this->middleware('can:create_singers')->only(['store']);
        $this->middleware('can:edit_singers')->only(['update', 'toggleVisibility', 'toggleFeatured', 'restore']);
        $this->middleware('can:delete_singers')->only(['destroy', 'permanentDelete']);
    }

    public function index(Request $request)
    {
        $query = Singer::query()->with('allDetails')->withCount('lyrics');

        if ($request->has('only_trashed') && $request->only_trashed === 'true') {
            $query->onlyTrashed();
        }

        if ($request->filled('search')) {
            $like = '%' . addcslashes($request->search, '%_\\') . '%';
            $query->where(function ($q) use ($like) {
                $q->where('singer_slug', 'like', $like)
                    ->orWhereHas('allDetails', function ($sq) use ($like) {
                        $sq->where('singer_name', 'like', $like)
                            ->orWhere('singer_laqab', 'like', $like)
                            ->orWhere('tagline', 'like', $like);
                    });
            });
        }

        $perPage = (int) $request->get('per_page', 10);
        $singers = $query->orderByDesc('created_at')->paginate($perPage);

        /** @var \Illuminate\Pagination\LengthAwarePaginator $singers */
        $singers->through(function (Singer $singer) {
            $sd = $singer->allDetails->firstWhere('lang', 'sd')
                ?? $singer->allDetails->first();

            return [
                'id' => $singer->id,
                'singer_slug' => $singer->singer_slug,
                'singer_pic' => $singer->singer_pic,
                'singer_name' => $sd?->singer_name ?? $singer->singer_slug,
                'singer_laqab' => $sd?->singer_laqab,
                'visibility' => $singer->visibility,
                'is_featured' => $singer->is_featured,
                'lyrics_count' => $singer->lyrics_count ?? 0,
                'date_of_birth' => $singer->date_of_birth?->format('Y-m-d'),
                'date_of_death' => $singer->date_of_death?->format('Y-m-d'),
                'deleted_at' => $singer->deleted_at,
            ];
        });

        return response()->json($singers);
    }

    public function show($id)
    {
        $singer = Singer::with('allDetails')
            ->withCount('lyrics')
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('singer_slug', $id);
            })
            ->firstOrFail();

        $sd = $singer->allDetails->firstWhere('lang', 'sd');
        $en = $singer->allDetails->firstWhere('lang', 'en');

        return response()->json([
            'id' => $singer->id,
            'singer_slug' => $singer->singer_slug,
            'singer_pic' => $singer->singer_pic,
            'date_of_birth' => $singer->date_of_birth?->format('Y-m-d'),
            'date_of_death' => $singer->date_of_death?->format('Y-m-d'),
            'visibility' => $singer->visibility,
            'is_featured' => $singer->is_featured,
            'lyrics_count' => $singer->lyrics_count ?? 0,
            'singer_name' => $sd?->singer_name ?? '',
            'singer_laqab' => $sd?->singer_laqab ?? '',
            'tagline' => $sd?->tagline ?? '',
            'birth_place' => $sd?->birth_place ?? '',
            'death_place' => $sd?->death_place ?? '',
            'singer_bio' => $sd?->singer_bio ?? '',
            'singer_name_roman' => $en?->singer_name ?? '',
            'singer_laqab_roman' => $en?->singer_laqab ?? '',
            'tagline_roman' => $en?->tagline ?? '',
            'singer_bio_roman' => $en?->singer_bio ?? '',
            'details' => $singer->allDetails,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request);

        $slug = $this->resolveSlug($validated);

        DB::beginTransaction();
        try {
            $imagePath = $this->handleImageUpload($request, $slug, null);

            $singer = Singer::create([
                'singer_slug' => $slug,
                'singer_pic' => $imagePath,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'date_of_death' => $validated['date_of_death'] ?? null,
                'visibility' => filter_var($validated['visibility'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'is_featured' => filter_var($validated['is_featured'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ]);

            $this->syncDetails($singer, $validated);

            DB::commit();

            $displayName = $validated['singer_laqab'] ?: $validated['singer_name'];

            return response()->json([
                'message' => 'Singer created',
                'id' => $singer->id,
                'singer' => [
                    'id' => $singer->id,
                    'name' => $displayName,
                    'slug' => $singer->singer_slug,
                    'pic' => $singer->singer_pic,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create singer: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $singer = Singer::where(function ($q) use ($id) {
            $q->where('id', $id)->orWhere('singer_slug', $id);
        })->firstOrFail();

        $validated = $this->validatePayload($request, $singer->id);
        $slug = $this->resolveSlug($validated, $singer->id);

        DB::beginTransaction();
        try {
            $imagePath = $this->handleImageUpload($request, $slug, $singer->singer_pic);

            if ($request->boolean('remove_image') && $singer->singer_pic) {
                $this->deleteImageFiles($singer->singer_pic, true);
                $imagePath = null;
            }

            $singer->update([
                'singer_slug' => $slug,
                'singer_pic' => $imagePath,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'date_of_death' => $validated['date_of_death'] ?? null,
                'visibility' => filter_var($validated['visibility'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'is_featured' => filter_var($validated['is_featured'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ]);

            $this->syncDetails($singer, $validated);

            DB::commit();

            return response()->json([
                'message' => 'Singer updated',
                'id' => $singer->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update singer: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $singer = Singer::where(function ($q) use ($id) {
            $q->where('id', $id)->orWhere('singer_slug', $id);
        })->firstOrFail();

        $singer->delete();

        return response()->json(['message' => 'Singer moved to trash']);
    }

    public function restore($id)
    {
        $singer = Singer::onlyTrashed()
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('singer_slug', $id);
            })
            ->firstOrFail();

        $singer->restore();

        return response()->json(['message' => 'Singer restored']);
    }

    public function permanentDelete($id)
    {
        $singer = Singer::withTrashed()
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('singer_slug', $id);
            })
            ->firstOrFail();

        DB::beginTransaction();
        try {
            if ($singer->singer_pic) {
                $this->deleteImageFiles($singer->singer_pic, true);
            }
            $singer->allDetails()->delete();
            $singer->forceDelete();
            DB::commit();

            return response()->json(['message' => 'Singer permanently deleted']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to permanently delete: ' . $e->getMessage()], 500);
        }
    }

    public function toggleVisibility($id)
    {
        $singer = Singer::where(function ($q) use ($id) {
            $q->where('id', $id)->orWhere('singer_slug', $id);
        })->firstOrFail();

        $singer->visibility = !$singer->visibility;
        $singer->save();

        return response()->json([
            'message' => 'Visibility updated',
            'visibility' => $singer->visibility,
        ]);
    }

    public function toggleFeatured($id)
    {
        $singer = Singer::where(function ($q) use ($id) {
            $q->where('id', $id)->orWhere('singer_slug', $id);
        })->firstOrFail();

        $singer->is_featured = !$singer->is_featured;
        $singer->save();

        return response()->json([
            'message' => 'Featured status updated',
            'is_featured' => $singer->is_featured,
        ]);
    }

    public function checkSlug(Request $request)
    {
        $slug = $request->get('slug');
        $id = $request->get('id');

        $query = Singer::where('singer_slug', $slug);
        if ($id) {
            $query->where('id', '!=', $id)->where('singer_slug', '!=', $id);
        }

        return response()->json(['exists' => $query->exists()]);
    }

    private function validatePayload(Request $request, ?int $singerId = null): array
    {
        return $request->validate([
            'singer_name' => 'required|string|max:255',
            'singer_name_roman' => 'nullable|string|max:255',
            'singer_laqab' => 'nullable|string|max:255',
            'singer_laqab_roman' => 'nullable|string|max:255',
            'tagline' => 'nullable|string|max:255',
            'tagline_roman' => 'nullable|string|max:255',
            'birth_place' => 'nullable|string|max:255',
            'death_place' => 'nullable|string|max:255',
            'singer_bio' => 'nullable|string',
            'singer_bio_roman' => 'nullable|string',
            'singer_slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('singers', 'singer_slug')->ignore($singerId),
            ],
            'date_of_birth' => 'nullable|date',
            'date_of_death' => 'nullable|date',
            'visibility' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'remove_image' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpeg,webp,jpg,png|max:10240',
        ]);
    }

    private function resolveSlug(array $validated, ?int $ignoreId = null): string
    {
        $slugSource = $validated['singer_slug']
            ?? $validated['singer_name_roman']
            ?? $validated['singer_laqab_roman']
            ?? $validated['singer_name'];

        $slug = Str::slug($slugSource) ?: 'singer-' . Str::random(6);
        $base = $slug;
        $i = 1;

        while (
            Singer::where('singer_slug', $slug)
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
            $upload = $this->updateImage($request->file('image'), 'singers', $existing, $slug, true);
        } else {
            $upload = $this->uploadImage($request->file('image'), 'singers', $slug, true);
        }

        if (!empty($upload['error'])) {
            throw new \RuntimeException($upload['message'] ?? 'Image upload failed');
        }

        return $upload['full_path'] ?? $existing;
    }

    private function syncDetails(Singer $singer, array $validated): void
    {
        $singer->allDetails()->updateOrCreate(
            ['lang' => 'sd'],
            [
                'singer_name' => $validated['singer_name'],
                'singer_laqab' => $validated['singer_laqab'] ?? null,
                'tagline' => $validated['tagline'] ?? null,
                'birth_place' => $validated['birth_place'] ?? null,
                'death_place' => $validated['death_place'] ?? null,
                'singer_bio' => $validated['singer_bio'] ?? null,
            ]
        );

        if (
            !empty($validated['singer_name_roman'])
            || !empty($validated['singer_laqab_roman'])
            || !empty($validated['tagline_roman'])
            || !empty($validated['singer_bio_roman'])
        ) {
            $singer->allDetails()->updateOrCreate(
                ['lang' => 'en'],
                [
                    'singer_name' => $validated['singer_name_roman'] ?? $validated['singer_name'],
                    'singer_laqab' => $validated['singer_laqab_roman'] ?? null,
                    'tagline' => $validated['tagline_roman'] ?? null,
                    'birth_place' => $validated['birth_place'] ?? null,
                    'death_place' => $validated['death_place'] ?? null,
                    'singer_bio' => $validated['singer_bio_roman'] ?? null,
                ]
            );
        }
    }
}
