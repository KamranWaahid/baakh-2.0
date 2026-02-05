<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Poets;
use Illuminate\Http\Request;

class PoetController extends Controller
{
    public function index(Request $request)
    {
        $query = Poets::query()->with('all_details');

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
        $poets = $query->paginate($perPage);

        $poets->through(function ($poet) {
            $details = $poet->all_details;
            $detail = $details->where('lang', 'sd')->first()
                ?? $details->where('lang', 'en')->first()
                ?? $details->first()
                ?? (object) [];

            return [
                'id' => $poet->id,
                'poet_slug' => $poet->poet_slug,
                'poet_pic' => $poet->poet_pic,
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

    use \App\Traits\HasMedia;

    public function show($id)
    {
        $poet = Poets::with('all_details')->findOrFail($id);
        return response()->json($poet);
    }

    public function create()
    {
        $cities = \App\Models\Cities::with([
            'details' => function ($q) {
                $q->where('lang', 'sd');
            }
        ])->get()->map(function ($city) {
            return [
                'id' => $city->id,
                'name' => $city->details->first()?->city_name ?? "City #{$city->id}"
            ];
        });

        return response()->json([
            'cities' => $cities,
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

        \DB::beginTransaction();
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

            \DB::commit();
            return response()->json(['message' => 'Poet created successfully', 'data' => $poet], 201);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['message' => 'Failed to create poet: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $poet = Poets::findOrFail($id);

        $request->validate([
            'poet_slug' => ['required', new \App\Rules\SlugRulePoet($id)],
            'date_of_birth' => 'nullable|date',
            'date_of_death' => 'nullable|date',
            'visibility' => 'required|boolean',
            'is_featured' => 'required|boolean',
            'image' => 'nullable|image|mimes:jpeg,webp,jpg,png|max:10240',
            'details' => 'required|array',
            'details.*.poet_name' => 'required|string|min:3',
            'details.*.poet_laqab' => 'required|string|min:3',
            'details.*.lang' => 'required|string',
            'details.*.birth_place' => 'nullable|exists:location_cities,id',
            'details.*.death_place' => 'nullable|exists:location_cities,id',
        ]);

        \DB::beginTransaction();
        try {
            $imagePath = $poet->poet_pic;
            if ($request->hasFile('image')) {
                $uploadImage = $this->updateImage($request->image, 'poets', public_path($poet->poet_pic), $request->poet_slug, true);
                if (isset($uploadImage['error']) && $uploadImage['error'] === true) {
                    return response()->json(['message' => $uploadImage['message']], 422);
                }
                $imagePath = $uploadImage['full_path'];
            }

            $poet->update([
                'poet_slug' => $request->poet_slug,
                'poet_pic' => $imagePath,
                'date_of_birth' => $request->date_of_birth,
                'date_of_death' => $request->date_of_death,
                'visibility' => $request->visibility,
                'is_featured' => $request->is_featured,
            ]);

            // Remove old details and re-create
            $poet->all_details()->forceDelete();

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

            \DB::commit();
            return response()->json(['message' => 'Poet updated successfully']);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['message' => 'Failed to update poet: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $poet = Poets::findOrFail($id);

        \DB::beginTransaction();
        try {
            // Delete image if exists
            if ($poet->poet_pic) {
                $this->deleteImageFiles(public_path($poet->poet_pic), true);
            }

            // Delete details
            $poet->all_details()->delete(); // Use soft delete
            $poet->delete();

            \DB::commit();
            return response()->json(['message' => 'Poet deleted successfully']);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['message' => 'Failed to delete poet: ' . $e->getMessage()], 500);
        }
    }

}
