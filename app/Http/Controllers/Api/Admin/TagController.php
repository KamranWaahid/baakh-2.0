<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tags;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view_tags')->only(['index', 'show']);
        $this->middleware('can:manage_tags')->only(['store', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $query = Tags::with(['details', 'topicCategory.details']);

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
                'topic_category_id' => $tag->topic_category_id,
                'topic_category_name' => $tag->topicCategory?->details->where('lang', 'sd')->first()?->name ?? $tag->topicCategory?->details->first()?->name,
                'details' => $tag->details->mapWithKeys(function ($d) {
                    return [$d->lang => ['name' => $d->name]];
                }),
                'tag' => $tag->details->where('lang', 'sd')->first()?->name ?? $tag->details->first()?->name,
                'available_translations' => $tag->details->pluck('lang')->toArray()
            ];
        });

        $topicCategories = \App\Models\TopicCategory::with('details')->get()->map(function ($cat) {
            return [
                'id' => $cat->id,
                'name' => $cat->details->where('lang', 'sd')->first()?->name ?? $cat->details->first()?->name
            ];
        });

        return response()->json([
            'tags' => $tags,
            'topic_categories' => $topicCategories,
            'available_types' => collect(Tags::TYPES)->map(function ($type) {
                return [
                    'value' => $type,
                    'label' => Tags::TYPE_LABELS[$type] ?? $type
                ];
            })
        ]);
    }

    public function show($id)
    {
        $tag = Tags::with(['details', 'topicCategory.details'])->findOrFail($id);

        return response()->json([
            'id' => $tag->id,
            'slug' => $tag->slug,
            'type' => $tag->type,
            'topic_category_id' => $tag->topic_category_id,
            'details' => $tag->details->mapWithKeys(function ($d) {
                return [$d->lang => ['name' => $d->name]];
            })
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'slug' => 'required|string|max:255|unique:baakh_tags,slug',
            'type' => 'required|string|in:' . implode(',', Tags::TYPES),
            'topic_category_id' => 'nullable|exists:topic_categories,id',
            'details' => 'required|array',
            'details.sd.name' => 'required|string|max:255',
            'details.en.name' => 'nullable|string|max:255',
        ]);

        $tag = Tags::create([
            'slug' => strip_tags($request->slug),
            'type' => $request->type,
            'topic_category_id' => $request->topic_category_id
        ]);

        foreach ($request->details as $lang => $data) {
            if (!empty($data['name'])) {
                $tag->details()->create([
                    'lang' => $lang,
                    'name' => strip_tags($data['name'])
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
            'topic_category_id' => 'nullable|exists:topic_categories,id',
            'details' => 'required|array',
            'details.sd.name' => 'required|string|max:255',
            'details.en.name' => 'nullable|string|max:255',
        ]);

        $tag->update([
            'slug' => strip_tags($request->slug),
            'type' => $request->type,
            'topic_category_id' => $request->topic_category_id
        ]);

        foreach ($request->details as $lang => $data) {
            if (!empty($data['name'])) {
                $tag->details()->updateOrCreate(
                    ['lang' => $lang],
                    ['name' => strip_tags($data['name'])]
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
