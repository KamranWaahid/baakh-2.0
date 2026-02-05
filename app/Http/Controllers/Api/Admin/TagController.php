<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tags;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index(Request $request)
    {
        $query = Tags::query()->where('lang', 'sd');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('tag', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 10);
        $tags = $query->orderBy('id', 'desc')->paginate($perPage);

        // Add available languages info
        $tags->getCollection()->transform(function ($tag) {
            $hasEn = Tags::where('slug', $tag->slug)->where('lang', 'en')->exists();
            $tag->available_translations = $hasEn ? ['sd', 'en'] : ['sd'];
            return $tag;
        });

        return response()->json($tags);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tag' => 'required|string|max:255',
            'type' => 'nullable|string',
            'lang' => 'nullable|string|max:5',
        ]);

        // Observer might handle slug, but let's be safe
        // If slug is needed logic: $validated['slug'] = Str::slug($request->tag);

        $tag = Tags::create($validated);

        return response()->json([
            'message' => 'Tag created successfully',
            'data' => $tag
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $tag = Tags::findOrFail($id);

        $validated = $request->validate([
            'tag' => 'required|string|max:255',
            'type' => 'nullable|string',
            'lang' => 'nullable|string|max:5',
        ]);

        $tag->update($validated);

        return response()->json([
            'message' => 'Tag updated successfully',
            'data' => $tag
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
