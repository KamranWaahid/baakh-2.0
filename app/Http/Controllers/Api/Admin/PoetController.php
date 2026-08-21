<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Poets;
use App\Services\PoetEditorJsonService;
use App\Support\PoetImageUrl;
use App\Support\PoetSameAs;
use App\Traits\HasMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class PoetController extends Controller
{
    use HasMedia;

    public function __construct()
    {
        $this->middleware('can:view_poets')->only(['index', 'show', 'editorJson']);
        $this->middleware('can:create_poets')->only(['create', 'store']);
        $this->middleware('can:edit_poets')->only(['update', 'importJson']);
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
                'poet_pic' => PoetImageUrl::resolve($poet->poet_pic),
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
            $poet = Poets::with([
                'all_details.birthCity.details',
                'all_details.deathCity.details',
            ])->findOrFail($id);

            $cityLabel = static function ($city): ?string {
                if (!$city) {
                    return null;
                }
                $details = $city->details ?? collect();
                $sdName = $details->firstWhere('lang', 'sd')?->city_name;
                $enName = $details->firstWhere('lang', 'en')?->city_name;
                $anyName = $details->first()?->city_name;

                $label = $sdName ?: $enName ?: $anyName;
                return $label !== null && $label !== '' ? $label : null;
            };

            $payload = [
                'id' => $poet->id,
                'poet_slug' => $poet->poet_slug,
                'poet_pic' => PoetImageUrl::resolve($poet->poet_pic),
                'poet_pic_raw' => $poet->poet_pic,
                'poet_pic_url' => PoetImageUrl::resolve($poet->poet_pic),
                'date_of_birth' => $poet->date_of_birth,
                'date_of_death' => $poet->date_of_death,
                'visibility' => $poet->visibility,
                'is_featured' => $poet->is_featured,
                'poet_tags' => $poet->poet_tags,
                'identities' => PoetSameAs::emptyForm(is_array($poet->identities) ? $poet->identities : []),
                'all_details' => $poet->all_details->map(function ($detail) use ($cityLabel) {
                    return [
                        'id' => $detail->id,
                        'poet_id' => $detail->poet_id,
                        'poet_name' => $detail->poet_name,
                        'poet_laqab' => $detail->poet_laqab,
                        'pen_name' => $detail->pen_name,
                        'tagline' => $detail->tagline,
                        'poet_bio' => $detail->poet_bio,
                        'birth_place' => $detail->birth_place !== null && $detail->birth_place !== ''
                            ? (string) $detail->birth_place
                            : null,
                        'birth_place_name' => $cityLabel($detail->birthCity),
                        'death_place' => $detail->death_place !== null && $detail->death_place !== ''
                            ? (string) $detail->death_place
                            : null,
                        'death_place_name' => $cityLabel($detail->deathCity),
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
            $anyName = $city->details->first()?->city_name;
            $legacyName = $city->getAttributes()['city_name'] ?? null;

            return [
                'id' => $city->id,
                'name' => $sdName ?? $enName ?? $anyName ?? $legacyName ?? "City #{$city->id}"
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
            'identities' => 'nullable|array',
            'identities.wikipedia_url' => 'nullable|string|max:500',
            'identities.wikidata_id' => 'nullable|string|max:64',
            'identities.google_kgmid' => 'nullable|string|max:128',
            'identities.website_url' => 'nullable|string|max:500',
            'identities.twitter' => 'nullable|string|max:128',
            'identities.facebook' => 'nullable|string|max:255',
            'identities.instagram' => 'nullable|string|max:128',
        ]);

        $identities = PoetSameAs::sanitize($request->input('identities'));

        DB::beginTransaction();
        try {
            $imagePath = null;
            if ($request->hasFile('image')) {
                $uploadImage = $this->uploadImage($request->image, 'poets', $request->poet_slug, true);
                if (isset($uploadImage['error']) && $uploadImage['error'] === true) {
                    DB::rollBack();
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
                'identities' => $identities !== [] ? $identities : null,
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
            'identities' => 'nullable|array',
            'identities.wikipedia_url' => 'nullable|string|max:500',
            'identities.wikidata_id' => 'nullable|string|max:64',
            'identities.google_kgmid' => 'nullable|string|max:128',
            'identities.website_url' => 'nullable|string|max:500',
            'identities.twitter' => 'nullable|string|max:128',
            'identities.facebook' => 'nullable|string|max:255',
            'identities.instagram' => 'nullable|string|max:128',
        ]);

        $identities = $request->has('identities')
            ? PoetSameAs::sanitize($request->input('identities'))
            : null;

        DB::beginTransaction();
        try {
            $imagePath = $poet->poet_pic;
            $removeImage = $request->boolean('remove_image');

            if ($request->hasFile('image')) {
                $slugForImage = $request->input('poet_slug', $poet->poet_slug);
                $uploadImage = $this->updateImage($request->image, 'poets', $poet->poet_pic, $slugForImage, true);
                if (isset($uploadImage['error']) && $uploadImage['error'] === true) {
                    DB::rollBack();
                    return response()->json(['message' => $uploadImage['message']], 422);
                }
                $imagePath = $uploadImage['full_path'];
                $removeImage = false;
            } elseif ($removeImage) {
                if ($poet->poet_pic) {
                    $this->deleteImageFiles($poet->poet_pic, true);
                }
                $imagePath = null;
            } elseif ($request->has('image') && !$request->hasFile('image')) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Image upload failed. The file may be too large for this server, or the request was blocked. Try a JPEG/PNG under 10 MB.',
                ], 422);
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
            if ($request->hasFile('image') || $removeImage) {
                $updates['poet_pic'] = $imagePath;
            }
            if ($request->has('identities')) {
                $updates['identities'] = $identities !== [] ? $identities : null;
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

    public function editorJson($id, PoetEditorJsonService $editorJson)
    {
        $poet = Poets::findOrFail($id);

        return response()->json(
            $editorJson->build($poet),
            200,
            [],
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
    }

    public function importJson(Request $request, $id, PoetEditorJsonService $editorJson)
    {
        $poet = Poets::findOrFail($id);

        $payload = $request->json()->all();
        if (!is_array($payload) || $payload === []) {
            return response()->json([
                'message' => 'Provide a JSON object for this poet.',
            ], 422);
        }

        try {
            $updated = $editorJson->import($poet, $payload);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => $exception->getMessage() ?: 'Failed to import poet JSON.',
            ], 500);
        }

        return response()->json(
            $editorJson->build($updated),
            200,
            [],
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
    }

}
