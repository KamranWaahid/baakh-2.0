<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Couplets;
use App\Models\Tags;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\StaticCacheService;

class CoupletController extends Controller
{
    protected $cache;

    public function __construct(StaticCacheService $cache)
    {
        $this->cache = $cache;
    }

    public function index(Request $request)
    {
        $lang = $request->get('lang', app()->getLocale());

        // Prefer static cache if no tag filtering and page 1
        if ((!$request->has('tag') || $request->tag === 'all') && $request->get('page', 1) == 1) {
            $cached = $this->cache->get("couplets_list_{$lang}");
            if ($cached) {
                return response()->json([
                    'data' => $cached,
                    'current_page' => 1,
                    'last_page' => 1,
                    'total' => count($cached)
                ]);
            }
        }

        $tag = $request->get('tag');
        $perPage = 10;

        $query = Couplets::whereHas('poet', function ($q) {
            $q->where('visibility', 1);
        })
            ->with([
                'poet.all_details' => function ($q) use ($lang) {
                    $q->where('lang', $lang);
                }
            ])
            ->where('lang', $lang);

        if ($tag && $tag !== 'all') {
            $query->where('couplet_tags', 'like', '%"' . $tag . '"%');
        }

        // Filter only those with at most 2 lines (1 newline)
        // Using raw DB count for efficiency, handling \r\n and trailing newlines
        $query->whereRaw("(LENGTH(TRIM(REPLACE(couplet_text, '\r', ''))) - LENGTH(REPLACE(TRIM(REPLACE(couplet_text, '\r', '')), '\n', ''))) <= 1");

        $couplets = $query->latest()->paginate($perPage);

        $couplets->getCollection()->transform(function ($c) use ($lang) {
            $poetDetail = $c->poet->all_details->where('lang', $lang)->first() ?? $c->poet->all_details->first();

            return [
                'id' => $c->id,
                'title' => 'Couplet',
                'excerpt' => $c->couplet_text,
                'slug' => $c->couplet_slug,
                'poet_slug' => $c->poet->poet_slug,
                'cat_slug' => 'couplets',
                'author' => $poetDetail->poet_laqab ?? $poetDetail->poet_name ?? 'Unknown',
                'author_avatar' => $c->poet->poet_pic,
                'date' => $c->created_at->toIso8601String(),
                'date_human' => $c->created_at->diffForHumans(),
                'is_couplet' => true,
                'likes' => 0,
            ];
        });

        return response()->json($couplets);
    }

    public function tags(Request $request)
    {
        $lang = $request->get('lang', app()->getLocale());

        // Fetch unique tags from poetry_couplets table
        $tagSlugs = \App\Models\Couplets::where('lang', $lang)
            ->whereNotNull('couplet_tags')
            ->where('couplet_tags', '!=', '[]')
            ->pluck('couplet_tags')
            ->flatMap(function ($tags) {
                return json_decode($tags) ?: [];
            })
            ->unique()
            ->values();

        // Get tags with their details for the requested language
        $tags = Tags::whereIn('slug', $tagSlugs)
            ->with([
                'details' => function ($q) use ($lang) {
                    $q->where('lang', $lang);
                }
            ])
            ->get()
            ->map(function ($tag) {
                $detail = $tag->details->first();
                return [
                    'name' => $detail ? $detail->name : $tag->slug,
                    'slug' => $tag->slug,
                ];
            });

        return response()->json($tags);
    }
}
