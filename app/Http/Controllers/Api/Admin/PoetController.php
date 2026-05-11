<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Poets;
use App\Traits\HasMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PoetController extends Controller
{
    use HasMedia;

    public function __construct()
    {
        $this->middleware('can:view_poets')->only(['index', 'show']);
        $this->middleware('can:create_poets')->only(['create', 'store']);
        $this->middleware('can:edit_poets')->only(['update']);
        $this->middleware('can:delete_poets')->only(['destroy']);
    }
    public function index(Request $request)
    {
        $query = Poets::query();

        if ($request->has('only_trashed') && $request->only_trashed === 'true') {
            $query->onlyTrashed();
        }

        $query->with('all_details');

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('all_details', function ($q) use ($search) {
                $q->where('poet_name', 'like', "%{$search}%")
                    ->orWhere('poet_laqab', 'like', "%{$search}%");
            });
        }

        if ($request->has('sort')) {
            // Implement sorting logic if needed
            $query->orderBy('created_at', 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $perPage = $request->get('per_page', 10);
        /** @var \Illuminate\Pagination\LengthAwarePaginator $poets */
        $poets = $query->paginate($perPage);

        $poets = $poets->through(function ($poet) {
            $details = $poet->all_details;
            $detail = $details->where('lang', 'sd')->first()
                ?? $details->where('lang', 'en')->first()
                ?? $details->first()
                ?? (object) [];

            return [
                'id' => $poet->id,
                'poet_slug' => $poet->poet_slug,
                'poet_pic' => $this->resolvePoetPicUrl($poet->poet_pic),
                'poet_pic_raw' => $poet->poet_pic,
                'poet_name' => $detail->poet_name ?? 'N/A',
                'poet_laqab' => $detail->poet_laqab ?? 'N/A',
                'visibility' => $poet->visibility,
                'is_featured' => $poet->is_featured,
                'date_of_birth' => $poet->date_of_birth,
                'date_of_death' => $poet->date_of_death,
                'deleted_at' => $poet->deleted_at
            ];
        });

        return response()->json($poets);
    }

    public function show($id)
    {
        try {
            $poet = Poets::with('all_details')->findOrFail($id);

            $payload = [
                'id' => $poet->id,
                'poet_slug' => $poet->poet_slug,
                'poet_pic' => $this->resolvePoetPicUrl($poet->poet_pic),
                'poet_pic_raw' => $poet->poet_pic,
                'poet_pic_url' => $this->resolvePoetPicUrl($poet->poet_pic),
                'date_of_birth' => $poet->date_of_birth,
                'date_of_death' => $poet->date_of_death,
                'visibility' => $poet->visibility,
                'is_featured' => $poet->is_featured,
                'poet_tags' => $poet->poet_tags,
                'all_details' => $poet->all_details->map(function ($detail) {
                    return [
                        'id' => $detail->id,
                        'poet_id' => $detail->poet_id,
                        'poet_name' => $detail->poet_name,
                        'poet_laqab' => $detail->poet_laqab,
                        'pen_name' => $detail->pen_name,
                        'tagline' => $detail->tagline,
                        'poet_bio' => $detail->poet_bio,
                        'birth_place' => $detail->birth_place,
                        'death_place' => $detail->death_place,
                        'lang' => $detail->lang,
                    ];
                })->values(),
            ];

            return response()->json(
                $payload,
                200,
                [],
                JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
            );
        } catch (\Throwable $e) {
            Log::error('Failed to fetch poet for admin edit', [
                'poet_id' => $id,
                'message' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Failed to fetch poet: ' . $e->getMessage()], 500);
        }
    }

    public function create()
    {
        $cities = \App\Models\Cities::with('details')->get()->map(function ($city) {
            $sdName = $city->details->where('lang', 'sd')->first()?->city_name;
            $enName = $city->details->where('lang', 'en')->first()?->city_name;

            return [
                'id' => $city->id,
                'name' => $sdName ?? $enName ?? "City #{$city->id}"
            ];
        });

        $poets = Poets::where('visibility', 1)->with([
            'details' => function ($q) {
                $q->where('lang', 'sd');
            }
        ])->get()->map(function ($poet) {
            return [
                'id' => $poet->id,
                'name' => $poet->details?->poet_laqab ?? $poet->poet_slug
            ];
        });

        return response()->json([
            'cities' => $cities,
            'poets' => $poets,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'poet_slug' => ['required', new \App\Rules\SlugRulePoet()],
            'date_of_birth' => 'nullable|date',
            'date_of_death' => 'nullable|date',
            'visibility' => 'required|boolean',
            'is_featured' => 'required|boolean',
            'image' => 'required|image|mimes:jpeg,webp,jpg,png|max:10240',
            'details' => 'required|array',
            'details.*.poet_name' => 'required|string|min:3',
            'details.*.poet_laqab' => 'required|string|min:3',
            'details.*.lang' => 'required|string',
            'details.*.birth_place' => 'nullable|exists:location_cities,id',
            'details.*.death_place' => 'nullable|exists:location_cities,id',
        ]);

        DB::beginTransaction();
        try {
            $imagePath = null;
            if ($request->hasFile('image')) {
                $uploadImage = $this->uploadImage($request->image, 'poets', $request->poet_slug, true);
                if (isset($uploadImage['error']) && $uploadImage['error'] === true) {
                    return response()->json(['message' => $uploadImage['message']], 422);
                }
                $imagePath = $uploadImage['full_path'];
            }

            $poet = Poets::create([
                'poet_slug' => $request->poet_slug,
                'poet_pic' => $imagePath,
                'date_of_birth' => $request->date_of_birth,
                'date_of_death' => $request->date_of_death,
                'visibility' => $request->visibility,
                'is_featured' => $request->is_featured,
                'poet_tags' => null,
            ]);

            foreach ($request->details as $detail) {
                if (is_string($detail)) {
                    $detail = json_decode($detail, true);
                }

                $poet->all_details()->create([
                    'poet_name' => $detail['poet_name'] ?? null,
                    'poet_laqab' => $detail['poet_laqab'] ?? null,
                    'pen_name' => $detail['pen_name'] ?? null,
                    'tagline' => $detail['tagline'] ?? null,
                    'poet_bio' => $detail['poet_bio'] ?? null,
                    'birth_place' => $detail['birth_place'] ?? null,
                    'death_place' => $detail['death_place'] ?? null,
                    'lang' => $detail['lang'],
                ]);
            }

            DB::commit();
            return response()->json(['message' => 'Poet created successfully', 'data' => $poet], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create poet: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $poet = Poets::findOrFail($id);

        $request->validate([
            'poet_slug' => ['sometimes', 'required', new \App\Rules\SlugRulePoet($id)],
            'date_of_birth' => 'sometimes|nullable|date',
            'date_of_death' => 'sometimes|nullable|date',
            'visibility' => 'sometimes|required|boolean',
            'is_featured' => 'sometimes|required|boolean',
            'image' => 'nullable|image|mimes:jpeg,webp,jpg,png|max:10240',
            'details' => 'sometimes|required|array',
            'details.*.poet_name' => 'sometimes|required|string|min:3',
            'details.*.poet_laqab' => 'sometimes|required|string|min:3',
            'details.*.lang' => 'sometimes|required|string',
            'details.*.birth_place' => 'nullable|exists:location_cities,id',
            'details.*.death_place' => 'nullable|exists:location_cities,id',
        ]);

        DB::beginTransaction();
        try {
            $imagePath = $poet->poet_pic;
            if ($request->hasFile('image')) {
                $slugForImage = $request->input('poet_slug', $poet->poet_slug);
                $uploadImage = $this->updateImage($request->image, 'poets', $poet->poet_pic, $slugForImage, true);
                if (isset($uploadImage['error']) && $uploadImage['error'] === true) {
                    return response()->json(['message' => $uploadImage['message']], 422);
                }
                $imagePath = $uploadImage['full_path'];
            }

            $updates = [];
            if ($request->has('poet_slug')) {
                $updates['poet_slug'] = $request->poet_slug;
            }
            if ($request->has('date_of_birth')) {
                $updates['date_of_birth'] = $request->date_of_birth;
            }
            if ($request->has('date_of_death')) {
                $updates['date_of_death'] = $request->date_of_death;
            }
            if ($request->has('visibility')) {
                $updates['visibility'] = $request->visibility;
            }
            if ($request->has('is_featured')) {
                $updates['is_featured'] = $request->is_featured;
            }
            if ($request->hasFile('image')) {
                $updates['poet_pic'] = $imagePath;
            }

            if (!empty($updates)) {
                $poet->update($updates);
            }

            // Only replace language details when details payload is explicitly sent.
            if ($request->has('details')) {
                $poet->all_details()->forceDelete();

                foreach ($request->details as $detail) {
                    if (is_string($detail)) {
                        $detail = json_decode($detail, true);
                    }
                    if (!is_array($detail) || empty($detail['lang'])) {
                        continue;
                    }

                    $poet->all_details()->create([
                        'poet_name' => $detail['poet_name'] ?? null,
                        'poet_laqab' => $detail['poet_laqab'] ?? null,
                        'pen_name' => $detail['pen_name'] ?? null,
                        'tagline' => $detail['tagline'] ?? null,
                        'poet_bio' => $detail['poet_bio'] ?? null,
                        'birth_place' => $detail['birth_place'] ?? null,
                        'death_place' => $detail['death_place'] ?? null,
                        'lang' => $detail['lang'],
                    ]);
                }
            }

            DB::commit();
            return response()->json(['message' => 'Poet updated successfully']);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed to update poet', [
                'poet_id' => $id,
                'message' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Failed to update poet: ' . $e->getMessage()], 500);
        }
    }

    private function resolvePoetPicUrl(?string $value): ?string
    {
        try {
            if (!$value) {
                return null;
            }
            if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
                return $value;
            }

            $relative = ltrim($value, '/');
            if ($relative === '') {
                return null;
            }

            $cloudBaseUrl = rtrim((string) config('filesystems.disks.s3.url', ''), '/');
            if ($cloudBaseUrl !== '') {
                return $cloudBaseUrl . '/' . $relative;
            }

            return '/' . $relative;
        } catch (\Throwable $e) {
            Log::warning('Failed to resolve poet image URL in admin', [
                'value' => $value,
                'message' => $e->getMessage(),
            ]);
            return $value;
        }
    }

    public function destroy($id)
    {
        $poet = Poets::findOrFail($id);

        DB::beginTransaction();
        try {
            // Note: We don't delete image files here anymore to support Trash/Restore
            // Image deletion is moved to permanentDelete()

            // Delete details
            $poet->all_details()->delete(); // Use soft delete
            $poet->delete();

            DB::commit();
            return response()->json(['message' => 'Poet moved to trash']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete poet: ' . $e->getMessage()], 500);
        }
    }

    public function restore($id)
    {
        $poet = Poets::onlyTrashed()->findOrFail($id);
        DB::beginTransaction();
        try {
            $poet->restore();
            $poet->all_details()->restore();
            DB::commit();
            return response()->json(['message' => 'Poet restored']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to restore poet: ' . $e->getMessage()], 500);
        }
    }

    public function permanentDelete($id)
    {
        $poet = Poets::onlyTrashed()->findOrFail($id);
        DB::beginTransaction();
        try {
            // Delete image if exists
            if ($poet->poet_pic) {
                $this->deleteImageFiles($poet->poet_pic, true);
            }

            $poet->all_details()->forceDelete();
            $poet->forceDelete();
            DB::commit();
            return response()->json(['message' => 'Poet permanently deleted']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to permanently delete poet: ' . $e->getMessage()], 500);
        }
    }

}
