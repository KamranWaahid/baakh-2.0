<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Poets;
use App\Models\Tags;
use App\Models\Categories;
use Illuminate\Http\Request;

class PoetController extends Controller
{
    public function index(Request $request)
    {
        $query = Poets::query()->with('all_details')
            ->withCount('poetry')
            ->where('visibility', 1); // Only visible poets

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('all_details', function ($q) use ($search) {
                $q->where('poet_name', 'like', "%{$search}%")
                    ->orWhere('poet_laqab', 'like', "%{$search}%");
            });
        }

        if ($request->has('tag')) {
            $tag = $request->tag;
            // JSON Search in poet_tags column
            $query->where('poet_tags', 'like', '%"' . $tag . '"%');
        }

        // Filter by category if needed (Assuming 'category' logic exists, but for now simple list)
        // If categories are tags or separate table, implementation varies.
        // User asked for "Real data", so listing all active poets is primary.

        $query->orderBy('created_at', 'desc');

        $perPage = $request->get('per_page', 20);
        $poets = $query->paginate($perPage);

        $poets->through(function ($poet) {
            // Helper to get detail by lang
            $getDetail = function ($lang) use ($poet) {
                return $poet->all_details->where('lang', $lang)->first();
            };

            $detailSd = $getDetail('sd');
            $detailEn = $getDetail('en');
            // Fallbacks
            $defaultDetail = $poet->all_details->first() ?? (object) [];

            return [
                'id' => $poet->id,
                'slug' => $poet->poet_slug,
                'avatar' => $poet->poet_pic, // Full URL expected if accessor exists or handled in frontend
                // English Data
                'name_en' => $detailEn->poet_laqab ?? $detailEn->poet_name ?? $detailSd->poet_laqab ?? $detailSd->poet_name ?? 'N/A',
                'bio_en' => strip_tags($detailEn->poet_bio ?? $detailSd->poet_bio ?? ''),
                // Sindhi Data
                'name_sd' => $detailSd->poet_laqab ?? $detailSd->poet_name ?? $detailEn->poet_laqab ?? $detailEn->poet_name ?? 'N/A',
                'bio_sd' => strip_tags($detailSd->poet_bio ?? $detailEn->poet_bio ?? ''),

                'entries_count' => $poet->poetry_count ?? 0,

                // Extra metadata
                'followers' => '0', // Placeholder or real relation count
                'category' => 'all', // Dynamic categorization if available
            ];
        });

        return response()->json($poets);
    }

    public function tags(Request $request)
    {
        $lang = $request->get('lang', 'sd');

        $tags = Tags::where('type', 'poets')
            ->where('lang', $lang)
            ->select('tag', 'slug')
            ->get();

        return response()->json($tags);
    }

    public function show($slug)
    {
        $poet = Poets::where('poet_slug', $slug)
            ->where('visibility', 1)
            ->with([
                'all_details',
                'poetry' => function ($q) {
                    // Fetch recent poetry, limited
                    $q->latest()->take(10);
                }
            ])
            ->withCount('poetry')
            ->firstOrFail();

        // Helper for details
        $getDetail = function ($lang) use ($poet) {
            return $poet->all_details->where('lang', $lang)->first();
        };

        $detailSd = $getDetail('sd');
        $detailEn = $getDetail('en');

        // Helper to get location string
        $getLocation = function ($cityId, $lang) {
            if (!$cityId)
                return null;
            $city = \App\Models\Cities::with([
                'details' => function ($q) use ($lang) {
                    $q->where('lang', $lang);
                },
                'province.details' => function ($q) use ($lang) {
                    $q->where('lang', $lang);
                },
                'province.country.details' => function ($q) use ($lang) {
                    $q->where('lang', $lang);
                }
            ])->find($cityId);

            if (!$city)
                return null;

            $cName = $city->details->first()->city_name ?? '';
            $pName = $city->province->details->first()->province_name ?? '';
            $coName = $city->province->country->details->first()->country_name ?? '';

            $parts = array_filter([$cName, $pName, $coName]);
            return implode(', ', $parts);
        };

        // Suggested Poets (Random 3, unique)
        $suggested = Poets::where('id', '!=', $poet->id)
            ->where('visibility', 1)
            ->with('all_details')
            ->inRandomOrder()
            ->take(3)
            ->get()
            ->map(function ($p) {
                $dSd = $p->all_details->where('lang', 'sd')->first();
                $dEn = $p->all_details->where('lang', 'en')->first();
                return [
                    'name_en' => $dEn->poet_laqab ?? $dEn->poet_name ?? $dSd->poet_laqab ?? $dSd->poet_name ?? 'N/A',
                    'name_sd' => $dSd->poet_laqab ?? $dSd->poet_name ?? $dEn->poet_laqab ?? $dEn->poet_name ?? 'N/A',
                    'slug' => $p->poet_slug,
                    'avatar' => $p->poet_pic,
                ];
            });

        $data = [
            'id' => $poet->id,
            'slug' => $poet->poet_slug,
            'avatar' => $poet->poet_pic,
            'dob' => $poet->date_of_birth,
            'dod' => $poet->date_of_death,

            // English Data
            'name_en' => $detailEn->poet_name ?? $detailSd->poet_name ?? 'N/A',
            'laqab_en' => $detailEn->poet_laqab ?? $detailEn->poet_name ?? 'N/A',
            'pen_name_en' => $detailEn->pen_name ?? null,
            'bio_en' => strip_tags($detailEn->poet_bio ?? $detailSd->poet_bio ?? ''),
            'birth_location_en' => $getLocation($detailEn->birth_place ?? $detailSd->birth_place ?? null, 'en'),
            'death_location_en' => $getLocation($detailEn->death_place ?? $detailSd->death_place ?? null, 'en'),

            // Sindhi Data
            'name_sd' => $detailSd->poet_name ?? $detailEn->poet_name ?? 'N/A',
            'laqab_sd' => $detailSd->poet_laqab ?? $detailSd->poet_name ?? 'N/A',
            'pen_name_sd' => $detailSd->pen_name ?? null,
            'bio_sd' => strip_tags($detailSd->poet_bio ?? $detailEn->poet_bio ?? ''),
            'birth_location_sd' => $getLocation($detailSd->birth_place ?? $detailEn->birth_place ?? null, 'sd'),
            'death_location_sd' => $getLocation($detailSd->death_place ?? $detailEn->death_place ?? null, 'sd'),

            'entries_count' => $poet->poetry_count ?? 0,
            'suggested' => $suggested,
            // Categories/Menu would usually come from aggregating poetry types, 
            // but for now we'll return a static list or derived from actual poetry if complex query allowed.
            // Simplified for this step.
        ];

        return response()->json($data);
    }
    public function getPoetry(Request $request, $slug)
    {
        $lang = $request->get('lang', 'sd');
        $catSlug = $request->get('category');
        $poet = Poets::where('poet_slug', $slug)->firstOrFail();

        $query = \App\Models\Poetry::where('poet_id', $poet->id)
            ->where('visibility', 1);

        if ($catSlug) {
            $query->whereHas('category', function ($q) use ($catSlug) {
                $q->where('slug', $catSlug);
            });
        }

        $poetry = $query->with([
            'translations' => function ($q) use ($lang) {
                $q->where('lang', $lang);
            },
            'category',
            'category.details' => function ($q) use ($lang) {
                $q->where('lang', $lang);
            },
            'poet_details' => function ($q) use ($lang) {
                $q->where('lang', $lang);
            }
        ])
            ->latest()
            ->paginate(10);

        $poetry->through(function ($p) use ($lang, $poet) {
            $trans = $p->translations->first();
            // Fallback to any translation if specific lang missing (optional, but good for UX)
            if (!$trans)
                $trans = $p->translations()->first();

            $catDetail = $p->category ? $p->category->details->where('lang', $lang)->first() : null;
            if (!$catDetail && $p->category)
                $catDetail = $p->category->details->first();

            $poetDetail = $poet->all_details->where('lang', $lang)->first();
            if (!$poetDetail)
                $poetDetail = $poet->all_details->first();

            return [
                'id' => $p->id,
                'title' => $trans->title ?? 'Untitled',
                'excerpt' => \Illuminate\Support\Str::limit(strip_tags($trans->info ?? ''), 150),
                'slug' => $p->poetry_slug,
                'poet_slug' => $poet->poet_slug,
                'cat_slug' => $p->category->slug ?? '',
                'category' => $catDetail->cat_name ?? 'Uncategorized',
                'author' => $poetDetail->poet_laqab ?? $poetDetail->poet_name ?? 'Unknown',
                'author_avatar' => $poet->poet_pic,
                'date' => $p->created_at->format('d M Y'),
                'readTime' => '2 min read', // Placeholder logic
                'likes' => 0, // Placeholder
                'cover' => $p->cover_image ?? null, // If exists
                'content_style' => $p->content_style,
            ];
        });

        return response()->json($poetry);
    }

    public function getCouplets(Request $request, $slug)
    {
        $lang = $request->get('lang', 'sd');
        $poet = Poets::where('poet_slug', $slug)->firstOrFail();

        // Couplets are where poetry_id is null? Or a specific category?
        // Wait, looking at Couplets model, it has 'couplet_text'.
        // Is it related to Poetry? 'poetry_id'.
        // If 'couplets' are stand-alone, maybe poetry_id is null?
        // Or maybe Couplets are just rows in 'poetry_couplets' table.

        // Let's assume we fetch from 'Couplets' model directly where 'poet_id' matches.

        $couplets = \App\Models\Couplets::where('poet_id', $poet->id)
            ->where('lang', $lang)
            ->whereRaw("(LENGTH(TRIM(REPLACE(couplet_text, '\r', ''))) - LENGTH(REPLACE(TRIM(REPLACE(couplet_text, '\r', '')), '\n', ''))) <= 1")
            ->latest()
            ->paginate(20);

        $couplets->through(function ($c) use ($poet, $lang) {
            $poetDetail = $poet->all_details->where('lang', $lang)->first();
            if (!$poetDetail)
                $poetDetail = $poet->all_details->first();

            return [
                'id' => $c->id,
                'title' => 'Couplet', // Or use first few words
                'excerpt' => $c->couplet_text, // Full text for "excerpt" to show in card
                'slug' => $c->couplet_slug, // If it has a slug
                'poet_slug' => $poet->poet_slug,
                'cat_slug' => 'couplets', // Dummy
                'category' => 'Couplet',
                'author' => $poetDetail->poet_laqab ?? $poetDetail->poet_name ?? 'Unknown',
                'author_avatar' => $poet->poet_pic,
                'date' => $c->created_at->format('d M Y'),
                'readTime' => '',
                'likes' => 0,
                'cover' => null,
                'is_couplet' => true, // Flag for frontend styling
            ];
        });

        return response()->json($couplets);
    }

    public function getCategories(Request $request, $slug)
    {
        $lang = $request->get('lang', 'sd');
        $poet = Poets::where('poet_slug', $slug)->firstOrFail();

        $categories = \App\Models\Categories::whereHas('poetry', function ($q) use ($poet) {
            $q->where('poet_id', $poet->id)->where('visibility', 1);
        })
            ->with([
                'details' => function ($q) use ($lang) {
                    $q->where('lang', $lang);
                }
            ])
            ->get()
            ->map(function ($cat) use ($lang) {
                $detail = $cat->details->where('lang', $lang)->first() ?? $cat->details->first();
                return [
                    'id' => $cat->id,
                    'slug' => $cat->slug,
                    'name' => $detail->cat_name ?? $cat->slug,
                ];
            });

        return response()->json($categories);
    }
}
