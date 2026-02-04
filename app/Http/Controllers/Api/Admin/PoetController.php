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

        return response()->json($poets);
    }

    use \App\Traits\HasMedia;

    public function show($id)
    {
        $poet = Poets::with('all_details')->findOrFail($id);
        return response()->json($poet);
    }

    public function store(Request $request)
    {
        $request->validate([
            'poet_slug' => ['required', new \App\Rules\SlugRulePoet()],
            'date_of_birth' => 'nullable|date',
            'date_of_death' => 'nullable|date',
            'image' => 'required|image|mimes:jpeg,webp,jpg,png|max:10240',
            'details' => 'required|array',
            'details.*.poet_name' => 'required|string|min:3',
            'details.*.poet_laqab' => 'required|string|min:3',
            'details.*.lang' => 'required|string',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $uploadImage = $this->uploadImage($request->image, 'poets', $request->poet_slug, true);
            if(isset($uploadImage['error']) && $uploadImage['error'] === true) {
                return response()->json(['message' => $uploadImage['message']], 422);
            }
            $imagePath = $uploadImage['full_path'];
        }

        $poet = Poets::create([
            'poet_slug' => $request->poet_slug,
            'poet_pic' => $imagePath,
            'date_of_birth' => $request->date_of_birth,
            'date_of_death' => $request->date_of_death,
            'poet_tags' => null, 
        ]);

        foreach ($request->details as $detail) {
            // Decoding Json object if it came from FormData as string
             if (is_string($detail)) {
                $detail = json_decode($detail, true);
            }
            
            $poet->details()->create([
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

        return response()->json(['message' => 'Poet created successfully', 'data' => $poet], 201);
    }

    public function update(Request $request, $id)
    {
        $poet = Poets::findOrFail($id);

        $request->validate([
            'poet_slug' => ['required', new \App\Rules\SlugRulePoet($id)],
            'date_of_birth' => 'nullable|date',
            'date_of_death' => 'nullable|date',
            'image' => 'nullable|image|mimes:jpeg,webp,jpg,png|max:10240',
            'details' => 'required|array',
            'details.*.poet_name' => 'required|string|min:3',
            'details.*.poet_laqab' => 'required|string|min:3',
            'details.*.lang' => 'required|string',
        ]);

        $imagePath = $poet->poet_pic;
        if ($request->hasFile('image')) {
            $uploadImage = $this->updateImage($request->image, 'poets', $poet->poet_pic, $request->poet_slug, true);
            if(isset($uploadImage['error']) && $uploadImage['error'] === true) {
                return response()->json(['message' => $uploadImage['message']], 422);
            }
            $imagePath = $uploadImage['full_path'];
        }

        $poet->update([
            'poet_slug' => $request->poet_slug,
            'poet_pic' => $imagePath,
            'date_of_birth' => $request->date_of_birth,
            'date_of_death' => $request->date_of_death,
        ]);

        // Remove old details and re-create
        $poet->details()->forceDelete();

        foreach ($request->details as $detail) {
             if (is_string($detail)) {
                $detail = json_decode($detail, true);
            }
            
            $poet->details()->create([
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

        return response()->json(['message' => 'Poet updated successfully']);
    }

    public function destroy($id)
    {
        $poet = Poets::findOrFail($id);

        // Delete image if exists
        if ($poet->poet_pic) {
            $this->deleteImageFiles(public_path($poet->poet_pic), true);
        }

        // Delete details (handled by cascade or manually)
        $poet->details()->forceDelete();
        $poet->delete();

        return response()->json(['message' => 'Poet deleted successfully']);
    }
}
