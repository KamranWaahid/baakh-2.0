<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TopicCategory;
use App\Models\Tags;
use App\Models\Poetry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use App\Services\StaticCacheService;
use Illuminate\Support\Collection;

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
        $lang = $this->resolveLang($request->header('Accept-Language', 'sd'));

        $cached = $this->cache->get("explore_topics_{$lang}");
        if ($cached) {
            return response()->json($cached);
        }

        App::setLocale($lang);
        $usedTagIds = $this->getUsedVisiblePoetryTagIds();

        // Fetch Categories with Tags
        $categories = TopicCategory::with([
            'details' => function ($q) use ($lang) {
                $q->where('lang', $lang);
            },
            'tags' => function ($q) use ($usedTagIds) {
                // Return only tags that are linked to visible poetry
                if ($usedTagIds->isNotEmpty()) {
                    $q->whereIn('id', $usedTagIds->all());
                } else {
                    // No visible-poetry tags available, force empty set
                    $q->whereRaw('1 = 0');
                }
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

        // Recommended Tags - random selection from tags linked to visible poetry
        $recommended = Tags::with([
            'details' => function ($q) use ($lang) {
                $q->where('lang', $lang);
            }
        ])
            ->whereHas('details')
            ->when($usedTagIds->isNotEmpty(), function ($q) use ($usedTagIds) {
                $q->whereIn('id', $usedTagIds->all());
            }, function ($q) {
                $q->whereRaw('1 = 0');
            })
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

    /**
     * Build a unique set of tag IDs that are used by visible poetry.
     */
    private function getUsedVisiblePoetryTagIds(): Collection
    {
        return Poetry::query()
            ->where('visibility', 1)
            ->whereNotNull('poetry_tags')
            ->pluck('poetry_tags')
            ->flatMap(function ($rawTags) {
                if (is_array($rawTags)) {
                    return $rawTags;
                }

                $decoded = json_decode((string) $rawTags, true);
                return is_array($decoded) ? $decoded : [];
            })
            ->filter(function ($id) {
                return is_numeric($id);
            })
            ->map(function ($id) {
                return (int) $id;
            })
            ->unique()
            ->values();
    }

    private function resolveLang(?string $rawLang): string
    {
        $lang = strtolower((string) $rawLang);
        return str_starts_with($lang, 'en') ? 'en' : 'sd';
    }
}
