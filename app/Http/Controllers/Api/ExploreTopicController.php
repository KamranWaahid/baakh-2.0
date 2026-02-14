<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TopicCategory;
use App\Models\Tags;
use App\Models\Poetry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use App\Services\StaticCacheService;

class ExploreTopicController extends Controller
{
    protected $cache;

    public function __construct(StaticCacheService $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Get all topic categories and their associated tags, localized.
     * Only shows tags and categories that are attached to at least one visible poetry.
     */
    public function index(Request $request)
    {
        $lang = $request->header('Accept-Language', 'sd');

        $cached = $this->cache->get("explore_topics_{$lang}");
        if ($cached) {
            return response()->json($cached);
        }

        App::setLocale($lang);

        // 1. Get all unique tag IDs used in poetry_main table
        // The poetry_tags column stores IDs as strings in a JSON array (e.g. ["294", "292"])
        $usedTagIds = Poetry::where('visibility', 1)
            ->whereNotNull('poetry_tags')
            ->pluck('poetry_tags')
            ->flatMap(function ($tagsJson) {
                $tags = json_decode($tagsJson, true);
                return is_array($tags) ? $tags : [];
            })
            ->unique()
            ->values()
            ->all();

        // 2. Fetch Categories with Tags, but filter Tags based on usage
        $categories = TopicCategory::with([
            'details' => function ($q) use ($lang) {
                $q->where('lang', $lang);
            },
            'tags' => function ($q) use ($usedTagIds) {
                // Filter tags relation to only include used tags by ID
                $q->whereIn('id', $usedTagIds);
            },
            'tags.details' => function ($q) use ($lang) {
                $q->where('lang', $lang);
            }
        ])
            ->get()
            ->map(function ($category) use ($lang) {
                // Map to response structure
                $catDetail = $category->details->first() ?? $category->details()->where('lang', 'sd')->first() ?? $category->details()->first();

                return [
                    'id' => $category->id,
                    'slug' => $category->slug,
                    'name' => $catDetail->name ?? $category->slug,
                    'tags' => $category->tags->map(function ($tag) use ($lang) {
                        $tagDetail = $tag->details->first() ?? $tag->details()->where('lang', 'sd')->first() ?? $tag->details()->first();
                        return [
                            'id' => $tag->id,
                            'slug' => $tag->slug,
                            'name' => $tagDetail->name ?? $tag->slug,
                            'type' => $tag->type,
                        ];
                    })
                ];
            })
            // 3. Filter out categories that have no tags after filtering
            ->filter(function ($category) {
                return $category['tags']->isNotEmpty();
            })
            ->values(); // Reset array keys

        // 4. Recommended Tags - also filtered by usage (IDs)
        $recommended = Tags::whereIn('id', $usedTagIds)
            ->with([
                'details' => function ($q) use ($lang) {
                    $q->where('lang', $lang);
                }
            ])
            ->whereHas('details')
            ->inRandomOrder()
            ->take(10)
            ->get()
            ->map(function ($tag) use ($lang) {
                $tagDetail = $tag->details->first() ?? $tag->details()->where('lang', 'sd')->first() ?? $tag->details()->first();
                return [
                    'id' => $tag->id,
                    'slug' => $tag->slug,
                    'name' => $tagDetail->name ?? $tag->slug,
                    'type' => $tag->type,
                ];
            });

        return response()->json([
            'categories' => $categories,
            'recommended' => $recommended
        ]);
    }
}
