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

        // Fetch Categories with Tags
        $categories = TopicCategory::with([
            'details' => function ($q) use ($lang) {
                $q->where('lang', $lang);
            },
            'tags' => function ($q) {
                // Return all tags in the category
                $q->orderBy('slug', 'asc');
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
            // Filter out categories that have no tags
            ->filter(function ($category) {
                return $category['tags']->isNotEmpty();
            })
            ->values(); // Reset array keys

        // Recommended Tags - random selection from all tags
        $recommended = Tags::with([
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

        $responseData = [
            'categories' => $categories,
            'recommended' => $recommended
        ];

        // Cache the result
        $this->cache->set("explore_topics_{$lang}", $responseData);

        return response()->json($responseData);
    }
}
