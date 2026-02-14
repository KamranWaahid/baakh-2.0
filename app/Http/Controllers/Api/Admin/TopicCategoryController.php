<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\TopicCategory;
use Illuminate\Http\Request;

class TopicCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view_tags')->only(['index', 'show']);
        $this->middleware('can:manage_tags')->only(['store', 'update', 'destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(
            TopicCategory::with(['details', 'tags.details'])->get()->map(function ($tc) {
                return [
                    'id' => $tc->id,
                    'slug' => $tc->slug,
                    'details' => $tc->details->mapWithKeys(function ($d) {
                        return [$d->lang => ['name' => $d->name]];
                    }),
                    'name' => $tc->details->where('lang', 'sd')->first()?->name ?? $tc->details->first()?->name,
                    'tags' => $tc->tags->map(function ($tag) {
                        $tagName = $tag->details->where('lang', 'sd')->first()?->name ?? $tag->details->first()?->name ?? $tag->slug;
                        return [
                            'id' => $tag->id,
                            'name' => $tagName,
                            'slug' => $tag->slug
                        ];
                    })
                ];
            })
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'slug' => 'required|string|max:255|unique:topic_categories,slug',
            'details' => 'required|array',
            'details.sd.name' => 'required|string|max:255',
            'details.en.name' => 'nullable|string|max:255',
        ]);

        $topicCategory = TopicCategory::create([
            'slug' => strip_tags($request->slug)
        ]);

        foreach ($request->details as $lang => $data) {
            if (!empty($data['name'])) {
                $topicCategory->details()->create([
                    'lang' => $lang,
                    'name' => strip_tags($data['name'])
                ]);
            }
        }

        return response()->json($topicCategory->load('details'), 201);
    }

    public function show($id)
    {
        $tc = TopicCategory::with('details')->findOrFail($id);
        return response()->json([
            'id' => $tc->id,
            'slug' => $tc->slug,
            'details' => $tc->details->mapWithKeys(function ($d) {
                return [$d->lang => ['name' => $d->name]];
            }),
            'tags' => $tc->tags->map(function ($tag) {
                $tagName = $tag->details->where('lang', 'sd')->first()?->name ?? $tag->details->first()?->name ?? $tag->slug;
                return [
                    'id' => $tag->id,
                    'name' => $tagName,
                    'slug' => $tag->slug
                ];
            })
        ]);
    }

    public function update(Request $request, $id)
    {
        $topicCategory = TopicCategory::findOrFail($id);

        $request->validate([
            'slug' => 'required|string|max:255|unique:topic_categories,slug,' . $id,
            'details' => 'required|array',
            'details.sd.name' => 'required|string|max:255',
            'details.en.name' => 'nullable|string|max:255',
        ]);

        $topicCategory->update(['slug' => strip_tags($request->slug)]);

        // Sync details
        foreach ($request->details as $lang => $data) {
            if (!empty($data['name'])) {
                $topicCategory->details()->updateOrCreate(
                    ['lang' => $lang],
                    ['name' => strip_tags($data['name'])]
                );
            }
        }

        return response()->json($topicCategory->load('details'));
    }

    public function destroy($id)
    {
        $topicCategory = TopicCategory::findOrFail($id);
        $topicCategory->delete();

        return response()->json(['message' => 'Topic category deleted successfully']);
    }
}
