<?php

namespace App\Http\Controllers;

use App\Models\Bundles;
use App\Models\Couplets;
use App\Models\Doodle;
use App\Models\Languages;
use App\Models\Poetry;
use App\Models\Poets;
use App\Models\Sliders;
use App\Models\Tags;
use App\Models\TodaysModule;
use App\Traits\BaakhSeoTrait;
use App\Services\StaticCacheService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Collection;
use Yajra\DataTables\Facades\DataTables;

class HomeController extends UserController
{
    use BaakhSeoTrait;
    public function __construct()
    {
        parent::__construct();

        // Avoid expensive daily-update logic on every API request.
        if (!app()->runningInConsole()) {
            $path = request()->path();
            if (!str_starts_with($path, 'api/') && !str_starts_with($path, 'v1/')) {
                Cache::remember('ghazal_of_day_updated_' . now()->format('Ymd'), 3600, function () {
                    $this->updateGhazalOfTheDay();
                    return true;
                });
            }
        }

    }

    /**
     * Show the application home page.
     *
     */
    public function index()
    {
        $today = Carbon::today();
        $locale = app()->getLocale();
        $doodles = Doodle::first();

        $cache = app(StaticCacheService::class);
        $cachedData = $cache->get("homepage_data_{$locale}");

        if ($cachedData) {
            $sliders = collect($cachedData['sliders'])->map(fn($s) => new Sliders($s));
            $famous_poet = collect($cachedData['famous_poets'])->map(fn($p) => new Poets($p));
            $ghazal_of_day = $cachedData['ghazal_of_day'] ? new Poetry($cachedData['ghazal_of_day']) : null;
            $ghazal_of_day_poet = $ghazal_of_day?->poet;
            $tags = collect($cachedData['tags'])->map(fn($t) => new Tags($t));
            $poet_tags = collect($cachedData['poet_tags'])->map(fn($t) => new Tags($t));
            $doodles = $cachedData['doodles'] ? new Doodle($cachedData['doodles']) : null;

            // Keep dynamic sections cached for guests to reduce repeated heavy queries.
            if (auth()->check()) {
                $quiz_couplet = $this->getQuizCouplet($locale);
                $quiz_poets = $this->getQuizPoets($quiz_couplet, $locale);
                $random_poetry = $this->showRandomPoetry(10, $locale);
            } else {
                $quizData = $this->getCachedHomepageQuizData($locale);
                $quiz_couplet = $quizData['quiz_couplet'];
                $quiz_poets = $quizData['quiz_poets'];
                $random_poetry = Cache::remember("homepage_random_poetry_html_{$locale}", 300, fn() => $this->showRandomPoetry(10, $locale));
            }
            $bundles = null;
        } else {
            $sliders = Sliders::where(['lang' => $locale, 'visibility' => 1])->get();
            $famous_poet = $this->getFamousPoets($locale);
            $ghazal_of_day = $this->getGhazalOfDay($locale);
            $bundles = null;
            if (auth()->check()) {
                $quiz_couplet = $this->getQuizCouplet($locale);
                $quiz_poets = $this->getQuizPoets($quiz_couplet, $locale);
                $random_poetry = $this->showRandomPoetry(10, $locale);
            } else {
                $quizData = $this->getCachedHomepageQuizData($locale);
                $quiz_couplet = $quizData['quiz_couplet'];
                $quiz_poets = $quizData['quiz_poets'];
                $random_poetry = Cache::remember("homepage_random_poetry_html_{$locale}", 300, fn() => $this->showRandomPoetry(10, $locale));
            }
            $poet_tags = Tags::where(['type' => 'poets', 'lang' => $locale])->get();
            $tags = Tags::where('lang', $locale)->limit(18)->get();
            $ghazal_of_day_poet = $ghazal_of_day?->poet;
        }

        // SEO 
        $title = ($locale == 'sd') ? 'باک - سنڌي شاعريءَ جو خزانو' : 'Baakh - Treasure of Sindhi Poetry';
        $desc = ($locale == 'sd') ? 'باک، شاعريءَ جي ھڪ قديم دور کان جديد ۽ ٽيڪنالاجيءَ واري دور ڏانھن ھڪ سفر آھي. ھِن پورٽل ۾ جديد توڙي قديم شاعريءَ کي سھيڙي ھڪ ئي ھنڌ سھڻي نموني رکيو ويو آھي. باک ۾ مشھور شاعرن جي شاعري، سنڌي ۽ رومن رسم الخط ۾ پڙھي سگهو ٿا.' : 'Baakh: A comprehensive web portal dedicated to preserving and promoting Sindhi poetry. Features include multi-lingual support, auto transliteration, and a rich history section. Bakkh celebrating Sindhi poetry heritage and fostering a global community of poetry enthusiasts.';

        $this->SEO_General($title, $desc);

        $ghazal_of_day_poet = $ghazal_of_day?->poet;

        $compact = compact(
            'doodles',
            'poet_tags',
            'ghazal_of_day',
            'ghazal_of_day_poet',
            'famous_poet',
            'random_poetry',
            'quiz_couplet',
            'quiz_poets',
            'tags'
        );

        return view('web.home.home', $compact);
    }


    private function getFamousPoets($locale)
    {
        return Cache::remember("homepage_famous_poets_{$locale}", 300, function () use ($locale) {
            $baseQuery = Poets::query()
                ->where('visibility', '1')
                ->whereHas('details', function ($query) use ($locale) {
                    $query->where('lang', $locale);
                });

            $sampleIds = $this->sampleIdsFromQuery($baseQuery, 'id', 5);
            if ($sampleIds->isEmpty()) {
                return collect();
            }

            $poets = Poets::with([
                'details' => function ($q) use ($locale) {
                    $q->where('lang', $locale);
                }
            ])
                ->whereIn('id', $sampleIds)
                ->get()
                ->keyBy('id');

            return $sampleIds->map(fn($id) => $poets->get($id))->filter()->values();
        });
    }

    private function getGhazalOfDay($locale)
    {
        $todayModule = new TodaysModule();
        return $todayModule->ghazal($locale);
    }

    private function getQuizCouplet($locale)
    {
        $baseQuery = Couplets::query()
            ->whereRaw('LENGTH(couplet_text) - LENGTH(REPLACE(couplet_text, "\n", "")) = 1')
            ->whereHas('poet', function ($query) {
                $query->whereNull('deleted_at');
            })
            ->where('lang', $locale);

        $id = $this->sampleIdsFromQuery($baseQuery, 'id', 1)->first();
        if (!$id) {
            return null;
        }

        return Couplets::with([
            'poet.details' => function ($query) use ($locale) {
                $query->where('poets_detail.lang', $locale);
            }
        ])
            ->where('id', $id)
            ->first();
    }

    private function getQuizPoets($quiz_couplet, $locale)
    {
        if (!$quiz_couplet) {
            return collect();
        }

        $baseQuery = Poets::query()
            ->where('visibility', '1')
            ->whereHas('details', function ($query) use ($locale) {
                $query->where('lang', $locale);
            })
            ->where('id', '!=', $quiz_couplet->poet_id);

        $sampleIds = $this->sampleIdsFromQuery($baseQuery, 'id', 2);
        $random_poets = Poets::with([
            'details' => function ($q) use ($locale) {
                $q->where('lang', $locale);
            }
        ])->whereIn('id', $sampleIds)->get();

        $quiz_poets = $random_poets->push($quiz_couplet->poet);
        $quiz_poets = $quiz_poets->shuffle();
        return $quiz_poets;
    }

    private function getCachedHomepageQuizData(string $locale): array
    {
        return Cache::remember("homepage_quiz_data_{$locale}", 300, function () use ($locale) {
            $quizCouplet = $this->getQuizCouplet($locale);
            return [
                'quiz_couplet' => $quizCouplet,
                'quiz_poets' => $this->getQuizPoets($quizCouplet, $locale),
            ];
        });
    }

    /**
     * About Page
     */
    public function about()
    {
        $locale = app()->getLocale();
        $title = ($locale == 'sd') ? ' باک جي باري ۾' : 'About Baakh';
        $desc = ($locale == 'sd') ? 'باک، شاعريءَ جي ھڪ قديم دور کان جديد ۽ ٽيڪنالاجيءَ واري دور ڏانھن ھڪ سفر آھي. ھِن پورٽل ۾ جديد توڙي قديم شاعريءَ کي سھيڙي ھڪ ئي ھنڌ سھڻي نموني رکيو ويو آھي. باک ۾ مشھور شاعرن جي شاعري، سنڌي ۽ رومن رسم الخط ۾ پڙھي سگهو ٿا.' : 'Baakh: A comprehensive web portal dedicated to preserving and promoting Sindhi poetry. Features include multi-lingual support, auto transliteration, and a rich history section. Bakkh celebrating Sindhi poetry heritage and fostering a global community of poetry enthusiasts.';
        $this->SEO_General($title, $desc);
        return view('web.about');
    }

    /**
     * Contact Page
     */
    public function contact()
    {
        $locale = app()->getLocale();
        $title = ($locale == 'sd') ? 'رابطو' : 'Contact Us';
        $desc = ($locale == 'sd') ? 'باک، شاعريءَ جي ھڪ قديم دور کان جديد ۽ ٽيڪنالاجيءَ واري دور ڏانھن ھڪ سفر آھي. ھِن پورٽل ۾ جديد توڙي قديم شاعريءَ کي سھيڙي ھڪ ئي ھنڌ سھڻي نموني رکيو ويو آھي. باک ۾ مشھور شاعرن جي شاعري، سنڌي ۽ رومن رسم الخط ۾ پڙھي سگهو ٿا.' : 'Baakh: A comprehensive web portal dedicated to preserving and promoting Sindhi poetry. Features include multi-lingual support, auto transliteration, and a rich history section. Bakkh celebrating Sindhi poetry heritage and fostering a global community of poetry enthusiasts.';
        $this->SEO_General($title, $desc);
        return view('web.contact');
    }

    private function showRandomPoetry($limit, $locale)
    {
        $baseQuery = Couplets::query()
            ->where('lang', $locale)
            ->whereNotNull('couplet_slug')
            ->whereRaw('LENGTH(poetry_couplets.couplet_text) - LENGTH(REPLACE(poetry_couplets.couplet_text, "\n", "")) = 1')
            ->whereHas('poet', function ($query) {
                $query->whereNull('deleted_at');
            });

        $sampleIds = $this->sampleIdsFromQuery($baseQuery, 'id', $limit);
        $random_poetry = Couplets::with('poetry')->whereIn('id', $sampleIds)->get()->keyBy('id');
        $random_poetry = $sampleIds->map(fn($id) => $random_poetry->get($id))->filter()->values();

        $user = auth()->user();
        $likedIds = collect();
        if ($user && $random_poetry->isNotEmpty()) {
            $likedIds = $user->likesDislikes()
                ->where('likable_type', 'Couplets')
                ->whereIn('likable_id', $random_poetry->pluck('id'))
                ->pluck('likable_id')
                ->flip();
        }

        $allTagSlugs = $random_poetry
            ->pluck('couplet_tags')
            ->filter()
            ->flatMap(function ($tagJson) {
                $decoded = json_decode($tagJson, true);
                return is_array($decoded) ? $decoded : [];
            })
            ->unique()
            ->values();

        $tagsBySlug = $allTagSlugs->isNotEmpty()
            ? Tags::where('lang', $locale)->whereIn('slug', $allTagSlugs)->pluck('tag', 'slug')->toArray()
            : [];

        $html = '';
        foreach ($random_poetry as $item) {
            $liked = $likedIds->has($item->id) ? '-fill text-baakh' : '';
            if ($item->couplet_tags != NULL) {
                $decodeTags = json_decode($item->couplet_tags, true);
                if (is_array($decodeTags) && !empty($decodeTags)) {
                    $usedTags = array_intersect_key($tagsBySlug, array_flip($decodeTags));
                } else {
                    $usedTags = null;
                }
                $html .= view('web.home.random-poetry', ['item' => $item, 'liked' => $liked, 'usedTags' => $usedTags]);
            } else {
                $html .= view('web.home.random-poetry', ['item' => $item, 'liked' => $liked]);
            }
        }
        return $html;
    }

    /**
     * Sample random IDs without ORDER BY RAND() full-table sort.
     */
    private function sampleIdsFromQuery($baseQuery, string $idColumn, int $limit): Collection
    {
        if ($limit <= 0) {
            return collect();
        }

        $count = (clone $baseQuery)->count();
        if ($count === 0) {
            return collect();
        }

        $window = min(max($limit * 25, 100), 1000);
        $window = min($window, $count);
        $maxOffset = max(0, $count - $window);
        $offset = $maxOffset > 0 ? random_int(0, $maxOffset) : 0;

        return (clone $baseQuery)
            ->orderBy($idColumn)
            ->skip($offset)
            ->take($window)
            ->pluck($idColumn)
            ->shuffle()
            ->take($limit)
            ->values();
    }

    /**
     * Check Quiz Answer
     */
    public function quizCheck(Request $request)
    {
        $post_data = $request->validate([
            'couplet' => 'required',
            'main_id' => 'required',
            'poet' => 'required'
        ]);

        // get poet by main ID
        $correct_poet = Couplets::with('poet')->findOrFail($post_data['couplet']);
        if ($post_data['poet'] == $correct_poet->poet_id) {
            // answer yes
            $message = [
                'message' => trans('labels.quiz_msg_correct_answer'),
                'correct_poet' => $correct_poet->poet_id,
                'type' => 'success'
            ];
        } else {
            // answer no
            $poet_name = $correct_poet->poet->poet_laqab;
            $message = [
                'message' => trans('labels.quiz_msg_wrong_answer', ['poetName' => $poet_name]),
                'correct_poet' => $correct_poet->poet_id,
                'type' => 'error'
            ];
        }

        return response()->json($message);
    }


    // method for updating today ghazal date
    public function updateGhazalOfTheDay()
    {
        $thisday = Carbon::now()->format('Y-m-d');
        $result = TodaysModule::where('table_name', 'poetry_main')->first();

        if ($result && $result->date_today != $thisday) {
            $currentPoetId = Poetry::where('poetry_slug', $result->table_id)->value('poet_id');
            $baseQuery = Poetry::query()
                ->where('category_id', 1)
                ->where('visibility', 1)
                ->when($currentPoetId, function ($query) use ($currentPoetId) {
                    $query->where('poet_id', '!=', $currentPoetId);
                });

            $sampleSlug = $this->sampleIdsFromQuery($baseQuery, 'poetry_slug', 1)->first();
            if (!$sampleSlug) {
                return;
            }

            $data = [
                'table_id' => $sampleSlug,
                'date_today' => $thisday,
            ];

            $result->update($data);
        }
    }

    /**
     * Testing Function
     */

    public function _test_fun(Request $request)
    {

        $lang = $request->input('lang');
        $poet = $request->input('poet_id');


        $columns = ['id'];

        $query = Poetry::with([
            'info' => function ($query) use ($lang) {
                $query->select('poetry_id', 'title')->where('lang', $lang);
            },
            'poet_details' => function ($query) use ($lang) {
                $query->select('poet_id', 'poet_laqab')->where('lang', $lang);
            },
            'user' => function ($q) { // belongsTo relation with User model
                $q->select('id', 'name', 'name_sd', 'role'); // showing NULL 
            },
            'category.detail' => function ($cat_query) use ($lang) {
                $cat_query->select('cat_id', 'cat_name')->where('lang', $lang);
            },
            'translations' => function ($query) {
                $query->with([
                    'language' => function ($lang_query) {
                        $lang_query->select('lang_code', 'lang_title');
                    }
                ])->select('poetry_id', 'lang');
            },
        ])
            ->limit(20)
            ->get();


        if ($request->has('search')) {
            $searchValue = '%' . $request->search . '%';

            $query->where(function ($q) use ($searchValue) {
                $q->orWhere('info.title', 'like', $searchValue)
                    ->orWhereHas('poet_details', function ($q) use ($searchValue) {
                        $q->where('poet_laqab', 'like', $searchValue);
                    });
            });
        }




        return response()->Json($query);
        // Implement search
        /* if ($request->has('search') && !empty($request->search['value'])) {
            $searchValue = '%' . $request->search['value'] . '%';

            $query->where('info.title', 'like', $searchValue)
            ->orWhere('poet_details.laqab', 'like', $searchValue);
        } */


        // Implement filtering by language and poet_id
        if ($poet != 0 || $poet != '0') {
            $query->where('poet_id', $poet);
        }

        // Implement ordering based on request
        if ($request->has('order')) {
            $column = $columns[$request->order[0]['column']];
            $direction = $request->order[0]['dir'];
            $query->orderBy($column, $direction);
        }

        /* $data = DataTables::eloquent($query)
        ->addColumn('actions', function ($row) {
            $mediaCreateUrl = route('admin.media.create', $row->id);
            $editUrl = route('admin.poetry.edit', $row->id);
            $duplicateUrl = route('admin.poetry.duplicate', $row->id);
            $toggleVisibilityUrl = route('admin.poetry.toggle-visibility', ['id' => $row->id]);
            $deleteUrl = route('admin.poetry.destroy', ['id' => $row->id]);

            return '<a href="' . $mediaCreateUrl . '" class="btn btn-xs btn-success mr-1" data-toggle="tooltip" data-placement="top" title="Poetry Media"><i class="fa fa-video"></i></a>' .
                   '<a href="' . $editUrl . '" class="btn btn-xs btn-warning mr-1" data-toggle="tooltip" data-placement="top" title="Update Poetry"><i class="fa fa-edit"></i></a>' .
                   '<a href="' . $duplicateUrl . '" class="btn btn-xs btn-default mr-1" data-toggle="tooltip" data-placement="top" title="Duplicate Poetry"><i class="fa fa-copy"></i></a>' .
                   '<button type="button" data-id="' . $row->id . '" data-url="' . $toggleVisibilityUrl . '" data-toggle="tooltip" data-placement="top" title="' . ($row->visibility == 1 ? 'Hide' : 'Show') . ' Poetry" class="btn btn-xs btn-info mr-1 btn-visible-poetry"><i class="fa fa-' . ($row->visibility == 1 ? 'eye' : 'eye-slash') . '"></i></button>' .
                   '<button type="button" data-id="' . $row->id . '" data-url="' . $deleteUrl . '" data-toggle="tooltip" data-placement="top" title="Delete Poetry" class="btn btn-xs btn-danger mr-1 btn-delete-poetry"><i class="fa fa-trash"></i></button>';
        })
        ->rawColumns(['actions', 'information'])
        ->toJson(); */

    }

    public function feed(Request $request)
    {
        $lang = $request->get('lang', app()->getLocale());
        $page = $request->get('page', 1);
        $filter = $request->get('filter');
        $perPage = 10;
        $userId = auth('sanctum')->id();

        if ($page == 1 && !$filter && !$request->has('period_id')) {
            $cache = app(StaticCacheService::class);
            $cachedFeed = $cache->get("feed_page_1_{$lang}");
            if ($cachedFeed) {
                return response()->json([
                    'data' => $cachedFeed,
                    'current_page' => 1,
                    'last_page' => 2, // Mock for simple load
                    'total' => 100
                ]);
            }
        }

        $query = Poetry::with([
            'info' => function ($query) use ($lang) {
                $query->where('lang', $lang);
            },
            'poet_details' => function ($query) use ($lang) {
                $query->where('lang', $lang);
            },
            'poet',
            'category_detail' => function ($query) use ($lang) {
                $query->where('lang', $lang);
            },
            'media' => function ($query) use ($lang) {
                $query->where('media_type', 'image')->where('lang', $lang)->limit(1);
            }
        ])
            ->withCount('likes')
            ->where('visibility', 1)
            ->whereHas('poet', function ($q) {
                $q->where('visibility', 1);
            });

        if ($userId) {
            $query->withExists([
                'likes as is_liked' => function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                },
                'bookmarks as is_bookmarked' => function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                },
            ]);
        }

        if ($filter === 'featured') {
            $query->where('is_featured', 1);
        }

        if ($filter === 'bookmarked') {
            if ($userId) {
                $query->whereHas('bookmarks', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                });
            } else {
                // If not logged in, return empty
                $query->whereRaw('1 = 0');
            }
        }

        if ($request->has('period_id')) {
            $period = \App\Models\Period::find($request->period_id);
            if ($period) {
                $range = explode('-', $period->date_range);
                $startYear = trim($range[0]);
                $endYearRaw = trim($range[1]);
                $endYear = ($endYearRaw === 'Present') ? date('Y') : $endYearRaw;

                $query->whereHas('poet', function ($q) use ($startYear, $endYear) {
                    $q->whereYear('date_of_birth', '<=', $endYear)
                        ->where(function ($sq) use ($startYear) {
                            $sq->whereYear('date_of_death', '>=', $startYear)
                                ->orWhereNull('date_of_death');
                        });
                });
            }
        }

        $poetry = $query->latest()->paginate($perPage);

        $poetry->getCollection()->transform(function ($p) use ($userId) {
            return [
                'id' => $p->id,
                'title' => $p->info?->title ?? $p->poetry_title,
                'slug' => $p->poetry_slug,
                'author' => $p->poet_details?->poet_laqab ?? 'Unknown',
                'author_avatar' => $this->resolvePoetAvatar($p->poet?->poet_pic),
                'cover' => $p->media->first()?->media_url,
                'date' => format_iso8601($p->created_at),
                'date_human' => $p->created_at?->diffForHumans(),
                'readTime' => '5 min read', // Mock for now
                'category' => $p->category_detail?->cat_name ?? $p->category?->slug ?? 'General',
                'cat_slug' => $p->category?->slug,
                'poet_slug' => $p->poet?->poet_slug,
                'likes' => $p->likes_count ?? 0,
                'is_liked' => $userId ? (bool) ($p->is_liked ?? false) : false,
                'is_bookmarked' => $userId ? (bool) ($p->is_bookmarked ?? false) : false,
            ];
        });

        return response()->json($poetry);
    }

    private function resolvePoetAvatar(?string $avatar): ?string
    {
        if (!$avatar) {
            return null;
        }
        if (str_starts_with($avatar, 'http://') || str_starts_with($avatar, 'https://')) {
            return $avatar;
        }

        $relative = ltrim($avatar, '/');
        if ($relative === '') {
            return null;
        }
        if (File::exists(public_path($relative))) {
            return '/' . $relative;
        }

        $candidates = $this->avatarPathCandidates($relative);
        $resolvedCloudUrl = $this->resolveFirstReachableCloudUrl($relative, $candidates);
        if ($resolvedCloudUrl) {
            return $resolvedCloudUrl;
        }

        return null;
    }

    private function resolveFirstReachableCloudUrl(string $relative, array $candidates): ?string
    {
        $cloudBaseUrl = rtrim((string) config('filesystems.disks.s3.url', ''), '/');
        if ($cloudBaseUrl === '') {
            return null;
        }
        // Never block API responses on remote avatar existence checks.
        // Build a deterministic URL from prioritized candidates and let the
        // client/image layer handle missing assets gracefully.
        $orderedCandidates = array_values(array_unique(array_filter([
            $relative,
            ...$candidates,
        ])));
        if (empty($orderedCandidates)) {
            return null;
        }

        return $cloudBaseUrl . '/' . ltrim($orderedCandidates[0], '/');
    }

    private function avatarPathCandidates(string $relative): array
    {
        $relative = ltrim($relative, '/');
        $fileName = basename($relative);
        $dir = trim(dirname($relative), '.');
        $baseName = pathinfo($fileName, PATHINFO_FILENAME);

        $legacyBase = preg_replace('/_[a-f0-9]{8,}_opt$/i', '', $baseName) ?? $baseName;
        $legacyBase = preg_replace('/_opt$/i', '', $legacyBase) ?? $legacyBase;

        $isOptimizedVariant = str_contains(strtolower($baseName), '_opt');

        $nameCandidates = array_values(array_unique([
            $isOptimizedVariant ? ($legacyBase . '_small.jpg') : $fileName,
            $fileName,
            $legacyBase . '_small.jpg',
            $legacyBase . '.jpg',
            $legacyBase . '.jpeg',
            $legacyBase . '.png',
            $legacyBase . '.webp',
        ]));

        $dirCandidates = array_values(array_unique(array_filter([
            $isOptimizedVariant ? 'Images' : null,
            $dir !== '' ? $dir : null,
            'assets/images/poets',
            'assets/Images/poets',
            'Images',
            'images',
        ])));

        $paths = [$relative];
        foreach ($dirCandidates as $dirCandidate) {
            foreach ($nameCandidates as $nameCandidate) {
                $paths[] = trim($dirCandidate, '/') . '/' . $nameCandidate;
            }
        }

        return array_values(array_unique($paths));
    }
}
