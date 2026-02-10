<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tags;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index(Request $request)
    {
        $query = Tags::with('details');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('details', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })->orWhere('slug', 'like', "%{$search}%")
                ->orWhere('type', 'like', "%{$search}%");
        }

        $perPage = $request->get('per_page', 10);
        $tags = $query->orderBy('id', 'desc')->paginate($perPage);

        $tags->through(function ($tag) {
            return [
                'id' => $tag->id,
                'slug' => $tag->slug,
                'type' => $tag->type,
                'details' => $tag->details->mapWithKeys(function ($d) {
                    return [$d->lang => ['name' => $d->name]];
                }),
                'tag' => $tag->details->where('lang', 'sd')->first()?->name ?? $tag->details->first()?->name,
                'available_translations' => $tag->details->pluck('lang')->toArray()
            ];
        });

        return response()->json([
            'tags' => $tags,
            'available_types' => Tags::TYPES
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'slug' => 'required|string|max:255|unique:baakh_tags,slug',
            'type' => 'required|string|in:' . implode(',', Tags::TYPES),
            'details' => 'required|array',
            'details.sd.name' => 'required|string|max:255',
            'details.en.name' => 'nullable|string|max:255',
        ]);

        $tag = Tags::create([
            'slug' => $request->slug,
            'type' => $request->type
        ]);

        foreach ($request->details as $lang => $data) {
            if (!empty($data['name'])) {
                $tag->details()->create([
                    'lang' => $lang,
                    'name' => $data['name']
                ]);
            }
        }

        return response()->json([
            'message' => 'Tag created successfully',
            'data' => $tag->load('details')
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $tag = Tags::findOrFail($id);

        $request->validate([
            'slug' => 'required|string|max:255|unique:baakh_tags,slug,' . $id,
            'type' => 'required|string|in:' . implode(',', Tags::TYPES),
            'details' => 'required|array',
            'details.sd.name' => 'required|string|max:255',
            'details.en.name' => 'nullable|string|max:255',
        ]);

        $tag->update([
            'slug' => $request->slug,
            'type' => $request->type
        ]);

        foreach ($request->details as $lang => $data) {
            if (!empty($data['name'])) {
                $tag->details()->updateOrCreate(
                    ['lang' => $lang],
                    ['name' => $data['name']]
                );
            }
        }

        return response()->json([
            'message' => 'Tag updated successfully',
            'data' => $tag->load('details')
        ]);
    }

    public function destroy($id)
    {
        $tag = Tags::findOrFail($id);
        $tag->delete();

        return response()->json([
            'message' => 'Tag deleted successfully'
        ]);
    }
}
