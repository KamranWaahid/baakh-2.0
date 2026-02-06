<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Couplets;
use App\Models\Tags;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CoupletController extends Controller
{
    public function index(Request $request)
    {
        $lang = $request->get('lang', app()->getLocale());
        $tag = $request->get('tag');
        $perPage = 10;

        $query = Couplets::whereHas('poet', function ($q) {
            $q->where('visibility', 1);
        })
            ->with([
                'poet.details' => function ($q) use ($lang) {
                    $q->where('lang', $lang);
                }
            ])
            ->where('lang', $lang);

        if ($tag && $tag !== 'all') {
            $query->where('couplet_tags', 'like', '%"' . $tag . '"%');
        }

        // Filter only those with at most 2 lines (1 newline)
        // Note: Using raw DB count for efficiency
        $query->whereRaw("(LENGTH(couplet_text) - LENGTH(REPLACE(couplet_text, '\n', ''))) <= 1");

        $couplets = $query->latest()->paginate($perPage);

        $transformed = $couplets->through(function ($c) use ($lang) {
            $poetDetail = $c->poet->details->where('lang', $lang)->first() ?? $c->poet->details->first();

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

        return response()->json($transformed);
    }

    public function tags(Request $request)
    {
        $lang = $request->get('lang', app()->getLocale());

        // Fetch unique tags from poetry_couplets table
        $tagSlugs = Couplets::where('lang', $lang)
            ->whereNotNull('couplet_tags')
            ->where('couplet_tags', '!=', '[]')
            ->pluck('couplet_tags')
            ->flatMap(function ($tags) {
                return json_decode($tags) ?: [];
            })
            ->unique()
            ->values();

        $tags = Tags::whereIn('slug', $tagSlugs)
            ->where('lang', $lang)
            ->select('tag as name', 'slug')
            ->get();

        return response()->json($tags);
    }
}
