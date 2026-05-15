<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Poets;
use App\Support\PoetImageUrl;
use App\Models\Tags;
use App\Models\Categories;
use Illuminate\Http\Request;
use App\Services\StaticCacheService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PoetController extends Controller
{
    protected $cache;

    public function __construct(StaticCacheService $cache)
    {
        $this->cache = $cache;
    }

    public function index(Request $request)
    {
        $lang = resolve_request_locale($request->get('lang', $request->header('Accept-Language')), 'sd');
        try {
            // Prefer static cache if no search/tag filtering
            if (!$request->has('search') && (!$request->has('tag') || $request->tag === 'all')) {
                $cached = $this->cache->get("poets_list_{$lang}");
                if ($cached) {
                    // Manual Pagination from Cache
                    $page = (int) $request->get('page', 1);
                    $perPage = (int) $request->get('per_page', 20);
                    $offset = ($page - 1) * $perPage;
                    $total = count($cached);
                    $sliced = array_slice($cached, $offset, $perPage);

                    return response()->json([
                        'data' => $sliced,
                        'current_page' => $page,
                        'last_page' => (int) ceil($total / $perPage),
                        'total' => $total,
                        'per_page' => $perPage,
                        'from' => $offset + 1,
                        'to' => min($offset + $perPage, $total)
                    ]);
                }
            }

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

            $query->orderBy('created_at', 'desc');

            $perPage = $request->get('per_page', 20);
            /** @var \Illuminate\Pagination\LengthAwarePaginator $poets */
            $poets = $query->paginate($perPage);

            $poets->through(function ($poet) {
                // Helper to get detail by lang
                $getDetail = function ($lang) use ($poet) {
                    return $poet->all_details->where('lang', $lang)->first();
                };

                $detailSd = $getDetail('sd');
                $detailEn = $getDetail('en');
                $defaultDetail = $poet->all_details->first();

                $nameEn = $detailEn?->poet_name ?: $detailSd?->poet_name ?: $defaultDetail?->poet_name ?: 'N/A';
                $nameSd = $detailSd?->poet_name ?: $detailEn?->poet_name ?: $defaultDetail?->poet_name ?: 'N/A';
                $laqabEn = $detailEn?->poet_laqab ?: $detailEn?->poet_name ?: $detailSd?->poet_laqab ?: $detailSd?->poet_name ?: $defaultDetail?->poet_laqab ?: $defaultDetail?->poet_name ?: 'N/A';
                $laqabSd = $detailSd?->poet_laqab ?: $detailSd?->poet_name ?: $detailEn?->poet_laqab ?: $detailEn?->poet_name ?: $defaultDetail?->poet_laqab ?: $defaultDetail?->poet_name ?: 'N/A';
                $bioEn = strip_tags($detailEn?->poet_bio ?: $detailSd?->poet_bio ?: $defaultDetail?->poet_bio ?: '');
                $bioSd = strip_tags($detailSd?->poet_bio ?: $detailEn?->poet_bio ?: $defaultDetail?->poet_bio ?: '');

                return [
                    'id' => $poet->id,
                    'slug' => $poet->poet_slug,
                    'avatar' => PoetImageUrl::resolve($poet->poet_pic),
                    // English Data
                    'name_en' => $nameEn,
                    'name_sd' => $nameSd,
                    'laqab_en' => $laqabEn,
                    'laqab_sd' => $laqabSd,
                    'bio_en' => $bioEn,
                    'bio_sd' => $bioSd,
                    'entries_count' => $poet->poetry_count ?? 0,
                    'followers' => '0',
                    'category' => 'all',
                ];
            });

            return response()->json($poets);
        } catch (\Throwable $e) {
            Log::error('PoetController@index failed', [
                'message' => $e->getMessage(),
                'lang' => $lang,
                'search' => $request->get('search'),
                'tag' => $request->get('tag'),
            ]);

            $page = (int) $request->get('page', 1);
            $perPage = (int) $request->get('per_page', 20);
            try {
                // Safety fallback: return minimal poet cards instead of empty dataset.
                /** @var \Illuminate\Pagination\LengthAwarePaginator $fallback */
                $fallback = Poets::query()
                    ->where('visibility', 1)
                    ->orderBy('created_at', 'desc')
                    ->paginate($perPage, ['id', 'poet_slug', 'poet_pic'], 'page', $page);

                $fallback->through(function ($poet) {
                    $name = trim((string) str_replace('-', ' ', $poet->poet_slug));
                    return [
                        'id' => $poet->id,
                        'slug' => $poet->poet_slug,
                        'avatar' => PoetImageUrl::resolve($poet->poet_pic),
                        'name_en' => ucfirst($name),
                        'name_sd' => ucfirst($name),
                        'laqab_en' => ucfirst($name),
                        'laqab_sd' => ucfirst($name),
                        'bio_en' => '',
                        'bio_sd' => '',
                        'entries_count' => 0,
                        'followers' => '0',
                        'category' => 'all',
                    ];
                });

                return response()->json($fallback);
            } catch (\Throwable $fallbackError) {
                Log::error('PoetController@index fallback failed', [
                    'message' => $fallbackError->getMessage(),
                    'lang' => $lang,
                ]);

                return response()->json([
                    'data' => [],
                    'current_page' => $page,
                    'last_page' => 1,
                    'total' => 0,
                    'per_page' => $perPage,
                    'from' => null,
                    'to' => null,
                ]);
            }
        }
    }

    public function tags(Request $request)
    {
        $lang = resolve_request_locale($request->get('lang', $request->header('Accept-Language')), 'sd');
        try {
            $usedTagSlugs = $this->getUsedPoetTagSlugs();
            $baseTags = $this->getBasePoetTags($lang);

            $selected = $usedTagSlugs->isNotEmpty()
                ? $baseTags->whereIn('slug', $usedTagSlugs->all())->values()
                : $baseTags;

            // If canonical tags are missing/incomplete, fall back to used slugs from poets.
            $tags = ($selected->isEmpty() && $usedTagSlugs->isNotEmpty())
                ? $this->mapSlugsToTags($usedTagSlugs)
                : $this->mapTagsToResponse($selected, $lang);

            return response()->json($tags);
        } catch (\Throwable $e) {
            Log::error('PoetController@tags failed', [
                'message' => $e->getMessage(),
                'lang' => $lang,
            ]);
            return response()->json([]);
        }
    }

    public function show(Request $request, $slug)
    {
        $lang = $request->get('lang', app()->getLocale());
        // Avoid stale/corrupt cache payloads breaking poet detail responses.
        try {
            $this->cache->forget("poet_detail_{$slug}_{$lang}");
        } catch (\Throwable) {
            // Ignore cache backend issues and continue with live query.
        }

        try {
            $poet = Poets::where('poet_slug', $slug)
                ->where('visibility', 1)
                ->with([
                    'all_details',
                    'books' => function ($q) {
                        $q->where('visibility', 1)->with('progress');
                    },
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
        $detailAny = $poet->all_details->first();

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

            $cName = $city->details->first()?->city_name ?? '';
            $pName = $city->province?->details->first()?->province_name ?? '';
            $coName = $city->province?->country?->details->first()?->country_name ?? '';

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
                $dAny = $p->all_details->first();
                return [
                    'name_en' => $dEn?->poet_laqab ?? $dEn?->poet_name ?? $dSd?->poet_laqab ?? $dSd?->poet_name ?? $dAny?->poet_laqab ?? $dAny?->poet_name ?? 'N/A',
                    'name_sd' => $dSd?->poet_laqab ?? $dSd?->poet_name ?? $dEn?->poet_laqab ?? $dEn?->poet_name ?? $dAny?->poet_laqab ?? $dAny?->poet_name ?? 'N/A',
                    'slug' => $p->poet_slug,
                    'avatar' => PoetImageUrl::resolve($p->poet_pic),
                ];
            });

        $coupletsCount = \App\Models\Couplets::where('poet_id', $poet->id)
            ->where('lang', $lang)
            ->where(function ($q) {
                $q->whereNull('poetry_id')->orWhere('poetry_id', 0);
            })
            ->count();

        $data = [
            'id' => $poet->id,
            'slug' => $poet->poet_slug,
            'avatar' => PoetImageUrl::resolve($poet->poet_pic),
            'dob' => $poet->date_of_birth,
            'dod' => $poet->date_of_death,

            // English Data
            'name_en' => $detailEn?->poet_name ?: ($detailSd?->poet_name ?: ($detailAny?->poet_name ?: 'N/A')),
            'laqab_en' => $detailEn?->poet_laqab ?: ($detailEn?->poet_name ?: ($detailSd?->poet_laqab ?: ($detailSd?->poet_name ?: ($detailAny?->poet_laqab ?: ($detailAny?->poet_name ?: 'N/A'))))),
            'pen_name_en' => $detailEn?->pen_name,
            'bio_en' => strip_tags($detailEn?->poet_bio ?: ($detailSd?->poet_bio ?: ($detailAny?->poet_bio ?: ''))),
            'birth_location_en' => $getLocation($detailEn?->birth_place ?? $detailSd?->birth_place ?? $detailAny?->birth_place, 'en'),
            'death_location_en' => $getLocation($detailEn?->death_place ?? $detailSd?->death_place ?? $detailAny?->death_place, 'en'),

            // Sindhi Data
            'name_sd' => $detailSd?->poet_name ?: ($detailEn?->poet_name ?: ($detailAny?->poet_name ?: 'N/A')),
            'laqab_sd' => $detailSd?->poet_laqab ?: ($detailSd?->poet_name ?: ($detailEn?->poet_laqab ?: ($detailEn?->poet_name ?: ($detailAny?->poet_laqab ?: ($detailAny?->poet_name ?: 'N/A'))))),
            'pen_name_sd' => $detailSd?->pen_name,
            'bio_sd' => strip_tags($detailSd?->poet_bio ?: ($detailEn?->poet_bio ?: ($detailAny?->poet_bio ?: ''))),
            'birth_location_sd' => $getLocation($detailSd?->birth_place ?? $detailEn?->birth_place ?? $detailAny?->birth_place, 'sd'),
            'death_location_sd' => $getLocation($detailSd?->death_place ?? $detailEn?->death_place ?? $detailAny?->death_place, 'sd'),

            'entries_count' => $poet->poetry_count ?? 0,
            'couplets_count' => $coupletsCount ?? 0,
            'suggested' => $suggested,
            'books' => $poet->books()->where('visibility', 1)->with('progress')->get()->map(function ($book) {
                return [
                    'id' => $book->id,
                    'title' => $book->title,
                    'title_sd' => $book->title_sd,
                    'cover_image' => $book->cover_image,
                    'total_pages' => $book->total_pages,
                    'pages_completed' => $book->completed_pages_count,
                    'percentage' => $book->completion_percentage,
                    'segments' => $book->page_segments,
                ];
            }),
        ];

            return response()->json(
                $data,
                200,
                [],
                JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
            );
        } catch (\Throwable $e) {
            Log::error('PoetController@show failed', [
                'slug' => $slug,
                'lang' => $lang,
                'message' => $e->getMessage(),
            ]);

            $fallbackPoet = Poets::where('poet_slug', $slug)->with('all_details')->first();
            if (!$fallbackPoet) {
                return response()->json(['message' => 'Poet not found'], 404);
            }

            $dSd = $fallbackPoet->all_details->where('lang', 'sd')->first();
            $dEn = $fallbackPoet->all_details->where('lang', 'en')->first();
            $dAny = $fallbackPoet->all_details->first();

            return response()->json([
                'id' => $fallbackPoet->id,
                'slug' => $fallbackPoet->poet_slug,
                'avatar' => PoetImageUrl::resolve($fallbackPoet->poet_pic),
                'dob' => $fallbackPoet->date_of_birth,
                'dod' => $fallbackPoet->date_of_death,
                'name_en' => $dEn?->poet_name ?? $dSd?->poet_name ?? $dAny?->poet_name ?? 'N/A',
                'laqab_en' => $dEn?->poet_laqab ?? $dEn?->poet_name ?? $dSd?->poet_laqab ?? $dSd?->poet_name ?? $dAny?->poet_laqab ?? $dAny?->poet_name ?? 'N/A',
                'pen_name_en' => $dEn?->pen_name,
                'bio_en' => strip_tags($dEn?->poet_bio ?? $dSd?->poet_bio ?? $dAny?->poet_bio ?? ''),
                'birth_location_en' => null,
                'death_location_en' => null,
                'name_sd' => $dSd?->poet_name ?? $dEn?->poet_name ?? $dAny?->poet_name ?? 'N/A',
                'laqab_sd' => $dSd?->poet_laqab ?? $dSd?->poet_name ?? $dEn?->poet_laqab ?? $dEn?->poet_name ?? $dAny?->poet_laqab ?? $dAny?->poet_name ?? 'N/A',
                'pen_name_sd' => $dSd?->pen_name,
                'bio_sd' => strip_tags($dSd?->poet_bio ?? $dEn?->poet_bio ?? $dAny?->poet_bio ?? ''),
                'birth_location_sd' => null,
                'death_location_sd' => null,
                'entries_count' => 0,
                'couplets_count' => 0,
                'suggested' => [],
                'books' => [],
            ], 200, [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        }
    }
    public function getPoetry(Request $request, $slug)
    {
        $lang = resolve_request_locale($request->get('lang', $request->header('Accept-Language')), 'sd');
        $catSlug = $request->get('category');
        $poet = Poets::where('poet_slug', $slug)->firstOrFail();

        $query = \App\Models\Poetry::where('poet_id', $poet->id)
            ->where('visibility', 1);

        if ($catSlug) {
            $query->whereHas('category', function ($q) use ($catSlug) {
                $q->where('slug', $catSlug);
            });
        }

        /** @var \Illuminate\Pagination\LengthAwarePaginator $poetry */
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
            ->withCount('likes')
            ->latest()
            ->paginate(10);

        $userId = auth('sanctum')->id();

        $poetry->through(function ($p) use ($lang, $poet, $userId) {
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
                'slug' => $p->poetry_slug,
                'poet_slug' => $poet->poet_slug,
                'cat_slug' => $p->category->slug ?? '',
                'category' => $catDetail->cat_name ?? 'Uncategorized',
                'author' => $poetDetail->poet_laqab ?? $poetDetail->poet_name ?? 'Unknown',
                'author_avatar' => PoetImageUrl::resolve($poet->poet_pic),
                'date' => $p->created_at->format('d M Y'),
                'readTime' => '2 min read', // Placeholder logic
                'likes' => $p->likes_count ?? 0,
                'is_liked' => $userId ? $p->likes()->where('user_id', $userId)->exists() : false,
                'is_bookmarked' => $userId ? $p->bookmarks()->where('user_id', $userId)->exists() : false,
                'cover' => $p->cover_image ?? null, // If exists
                'content_style' => $p->content_style,
            ];
        });

        return response()->json($poetry);
    }

    public function getCouplets(Request $request, $slug)
    {
        $lang = resolve_request_locale($request->get('lang', $request->header('Accept-Language')), 'sd');
        $poet = Poets::where('poet_slug', $slug)->firstOrFail();

        // Couplets are where poetry_id is null? Or a specific category?
        // Wait, looking at Couplets model, it has 'couplet_text'.
        // Is it related to Poetry? 'poetry_id'.
        // If 'couplets' are stand-alone, maybe poetry_id is null?
        // Or maybe Couplets are just rows in 'poetry_couplets' table.

        // Let's assume we fetch from 'Couplets' model directly where 'poet_id' matches.

        /** @var \Illuminate\Pagination\LengthAwarePaginator $couplets */
        $couplets = \App\Models\Couplets::where('poet_id', $poet->id)
            ->where('lang', $lang)
            ->where(function ($q) {
                $q->whereNull('poetry_id')->orWhere('poetry_id', 0);
            })
            ->whereRaw("(LENGTH(TRIM(REPLACE(couplet_text, '\r', ''))) - LENGTH(REPLACE(TRIM(REPLACE(couplet_text, '\r', '')), '\n', ''))) <= 1")
            ->withCount('likes')
            ->latest()
            ->paginate(20);

        $userId = auth('sanctum')->id();

        $couplets->through(function ($c) use ($poet, $lang, $userId) {
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
                'author_avatar' => PoetImageUrl::resolve($poet->poet_pic),
                'date' => $c->created_at->format('d M Y'),
                'readTime' => '',
                'likes' => $c->likes_count ?? 0,
                'is_liked' => $userId ? $c->likes()->where('user_id', $userId)->exists() : false,
                'is_bookmarked' => $userId ? $c->bookmarks()->where('user_id', $userId)->exists() : false,
                'cover' => null,
                'is_couplet' => true, // Flag for frontend styling
            ];
        });

        return response()->json($couplets);
    }

    public function getCategories(Request $request, $slug)
    {
        $lang = resolve_request_locale($request->get('lang', $request->header('Accept-Language')), 'sd');
        $poet = Poets::where('poet_slug', $slug)->firstOrFail();

        $categories = Categories::whereHas('poetry', function ($q) use ($poet) {
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

    /**
     * Get unique tag slugs currently used by visible poets.
     */
    private function getUsedPoetTagSlugs()
    {
        return Poets::query()
            ->where('visibility', 1)
            ->whereNotNull('poet_tags')
            ->pluck('poet_tags')
            ->flatMap(function ($value) {
                $decoded = json_decode((string) $value, true);
                return is_array($decoded) ? $decoded : [];
            })
            ->map(fn($slug) => trim((string) $slug))
            ->filter()
            ->unique()
            ->values();
    }

    /**
     * Fetch canonical poet tags with localized details.
     */
    private function getBasePoetTags(string $lang)
    {
        return Tags::query()
            ->when(
                \Illuminate\Support\Facades\Schema::hasColumn('baakh_tags', 'type'),
                fn($q) => $q->where('type', 'poets')
            )
            ->with([
                'details' => function ($q) use ($lang) {
                    $q->where('lang', $lang);
                }
            ])
            ->get();
    }

    /**
     * Map raw slugs to a consistent tag response structure.
     */
    private function mapSlugsToTags($slugs)
    {
        return $slugs->map(function ($slug) {
            return [
                'tag' => Str::of($slug)->replace('-', ' ')->title()->value(),
                'slug' => $slug,
            ];
        })->values();
    }

    /**
     * Map Tag models to a consistent response structure with localized labels.
     */
    private function mapTagsToResponse($tags, string $lang)
    {
        return $tags->map(function ($tag) {
            $label = $tag->details->first()?->name
                ?? $tag->details()->where('lang', 'en')->value('name')
                ?? $tag->details()->value('name')
                ?? Str::of($tag->slug)->replace('-', ' ')->title()->value();

            return [
                'tag' => $label,
                'slug' => $tag->slug
            ];
        })->values();
    }
}
