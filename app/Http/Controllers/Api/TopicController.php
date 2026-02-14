<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tags;
use App\Models\TopicCategory;
use App\Models\Poetry;
use App\Models\Poets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use App\Services\StaticCacheService;

class TopicController extends Controller
{
    protected $cache;

    public function __construct(StaticCacheService $cache)
    {
        $this->cache = $cache;
    }

    // Formerly 'show' - now specifically for Tags
    public function showTag(Request $request, $slug)
    {
        $lang = $request->get('lang', $request->header('Accept-Language', 'sd'));

        $cached = $this->cache->get("tag_detail_{$slug}_{$lang}");
        if ($cached) {
            return response()->json($cached);
        }

        App::setLocale($lang);

        // Find the tag by slug
        $tag = Tags::where('slug', $slug)->firstOrFail();

        // Get Topic Category
        $topicCategory = $tag->topicCategory;

        $catName = 'Unknown';
        $catSlug = '';

        if ($topicCategory) {
            $catDetail = $topicCategory->details->where('lang', $lang)->first() ?? $topicCategory->details->first();
            $catName = $catDetail->name ?? $topicCategory->slug;
            $catSlug = $topicCategory->slug;
        }

        $tagDetail = $tag->details->where('lang', $lang)->first() ?? $tag->details->first();
        $tagName = $tagDetail->name ?? $tag->slug;

        // Fetch Poetry associated with this tag
        // poetry_tags is a JSON array of IDs (e.g. ["294", "292"])
        $poetry = Poetry::where('visibility', 1)
            ->where('poetry_tags', 'like', '%"' . $tag->id . '"%')
            ->with([
                'translations' => function ($q) use ($lang) {
                    $q->where('lang', $lang);
                },
                'category',
                'category.details' => function ($q) use ($lang) {
                    $q->where('lang', $lang);
                },
                'poet',
                'poet.all_details'
            ])
            ->withCount('likes')
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($p) use ($lang) {
                return $this->formatPoetry($p, $lang);
            });

        // Fetch Poets associated with this tag
        $poets = Poets::where('visibility', 1)
            ->where('poet_tags', 'like', '%"' . $tag->id . '"%')
            ->with('all_details')
            ->withCount('poetry')
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($poet) use ($lang) {
                return $this->formatPoet($poet, $lang);
            });

        return response()->json([
            'type' => 'tag',
            'data' => [
                'id' => $tag->id,
                'slug' => $tag->slug,
                'name' => $tagName,
                'type' => $tag->type,
            ],
            'parent' => [ // The category this tag belongs to
                'slug' => $catSlug,
                'name' => $catName,
                'type' => 'category'
            ],
            'counts' => [
                'poetry' => Poetry::where('visibility', 1)->where('poetry_tags', 'like', '%"' . $tag->id . '"%')->count(),
                'poets' => Poets::where('visibility', 1)->where('poet_tags', 'like', '%"' . $tag->id . '"%')->count(),
            ],
            'poetry' => $poetry,
            'poets' => $poets
        ]);
    }

    public function showCategory(Request $request, $slug)
    {
        $lang = $request->get('lang', $request->header('Accept-Language', 'sd'));

        $cached = $this->cache->get("category_detail_{$slug}_{$lang}");
        if ($cached) {
            return response()->json($cached);
        }

        App::setLocale($lang);

        $category = TopicCategory::where('slug', $slug)->firstOrFail();

        $catDetail = $category->details->where('lang', $lang)->first() ?? $category->details->first();
        $catName = $catDetail->name ?? $category->slug;

        // Fetch Poetry for this Category
        // Fallback: If topic_category_id is missing, find poetry that has ANY tag belonging to this category
        $categoryTagIds = $category->tags()->pluck('id')->toArray();

        $poetry = Poetry::where('visibility', 1)
            ->where(function ($query) use ($category, $categoryTagIds) {
                // Direct link
                $query->where('topic_category_id', $category->id);

                // Or via tags
                if (!empty($categoryTagIds)) {
                    foreach ($categoryTagIds as $tagId) {
                        $query->orWhere('poetry_tags', 'like', '%"' . $tagId . '"%');
                    }
                }
            })
            ->with([
                'translations' => function ($q) use ($lang) {
                    $q->where('lang', $lang);
                },
                'category',
                'category.details' => function ($q) use ($lang) {
                    $q->where('lang', $lang);
                },
                'poet',
                'poet.all_details'
            ])
            ->withCount('likes')
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($p) use ($lang) {
                return $this->formatPoetry($p, $lang);
            });

        // Fetch Poets for this Category?
        // Poets don't directly have topic_category_id usually. 
        // We can find poets who have WRITTEN poetry in this category.
        $poets = Poets::where('visibility', 1)
            ->whereHas('poetry', function ($q) use ($category) {
                $q->where('topic_category_id', $category->id)
                    ->where('visibility', 1);
            })
            ->with('all_details')
            ->withCount('poetry')
            ->take(10)
            ->get()
            ->map(function ($poet) use ($lang) {
                return $this->formatPoet($poet, $lang);
            });

        return response()->json([
            'type' => 'category',
            'data' => [
                'id' => $category->id,
                'slug' => $category->slug,
                'name' => $catName,
            ],
            'parent' => null, // Root level
            'counts' => [
                'poetry' => Poetry::where('visibility', 1)
                    ->where(function ($query) use ($category, $categoryTagIds) {
                        $query->where('topic_category_id', $category->id);
                        if (!empty($categoryTagIds)) {
                            foreach ($categoryTagIds as $tagId) {
                                $query->orWhere('poetry_tags', 'like', '%"' . $tagId . '"%');
                            }
                        }
                    })->count(),
                // Approximate poet count (unique poets in this category)
                'poets' => Poets::where('visibility', 1)->whereHas('poetry', function ($q) use ($category, $categoryTagIds) {
                    $q->where('topic_category_id', $category->id)->where('visibility', 1);
                    if (!empty($categoryTagIds)) {
                        foreach ($categoryTagIds as $tagId) {
                            $q->orWhere('poetry_tags', 'like', '%"' . $tagId . '"%');
                        }
                    }
                })->count(),
            ],
            'poetry' => $poetry,
            'poets' => $poets
        ]);
    }

    private function formatPoetry($p, $lang)
    {
        $userId = auth('sanctum')->id();
        $trans = $p->translations->first() ?? $p->translations()->first();
        $catDetail = $p->category ? ($p->category->details->where('lang', $lang)->first() ?? $p->category->details->first()) : null;
        $poetDetail = $p->poet ? ($p->poet->all_details->where('lang', $lang)->first() ?? $p->poet->all_details->first()) : null;

        return [
            'id' => $p->id,
            'title' => $trans->title ?? 'Untitled',
            'slug' => $p->poetry_slug,
            'poet_slug' => $p->poet->poet_slug ?? '',
            'cat_slug' => $p->category->slug ?? '',
            'category' => $catDetail->cat_name ?? 'Uncategorized',
            'author' => $poetDetail->poet_laqab ?? $poetDetail->poet_name ?? 'Unknown',
            'author_avatar' => $p->poet->poet_pic ?: null,
            'date' => $p->created_at->format('d M Y'),
            'readTime' => '2 min read',
            'likes' => $p->likes_count ?? 0,
            'is_liked' => $userId ? $p->likes()->where('user_id', $userId)->exists() : false,
            'is_bookmarked' => $userId ? $p->bookmarks()->where('user_id', $userId)->exists() : false,
            'cover' => $p->cover_image ?? null,
            'content_style' => $p->content_style,
        ];
    }

    private function formatPoet($poet, $lang)
    {
        $detail = $poet->all_details->where('lang', $lang)->first() ?? $poet->all_details->first();
        $detailEn = $poet->all_details->where('lang', 'en')->first() ?? $detail;
        $detailSd = $poet->all_details->where('lang', 'sd')->first() ?? $detail;

        return [
            'id' => $poet->id,
            'slug' => $poet->poet_slug,
            'avatar' => $poet->poet_pic ?: null,
            'name_en' => $detailEn->poet_laqab ?? $detailEn->poet_name ?? 'N/A',
            'name_sd' => $detailSd->poet_laqab ?? $detailSd->poet_name ?? 'N/A',
            'bio_en' => strip_tags($detailEn->poet_bio ?? ''),
            'bio_sd' => strip_tags($detailSd->poet_bio ?? ''),
            'entries_count' => $poet->poetry_count ?? 0,
        ];
    }
}
