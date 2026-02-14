<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\StaticCacheService;
use App\Models\Poetry;
use App\Models\Poets;
use App\Models\Categories;
use App\Models\Tags;
use App\Models\TopicCategory;
use App\Models\Sliders;
use App\Models\Doodle;
use App\Models\TodaysModule;
use App\Models\Couplets;
use Illuminate\Support\Facades\App;

class UpdateStaticCache extends Command
{
    protected $signature = 'cache:static-update {--type=all}';
    protected $description = 'Update static JSON cache files for performance';

    protected $cache;

    public function __construct(StaticCacheService $cache)
    {
        parent::__construct();
        $this->cache = $cache;
    }

    public function handle()
    {
        $type = $this->option('type');

        $locales = ['sd', 'en'];

        foreach ($locales as $lang) {
            if ($type === 'all' || $type === 'homepage') {
                $this->info("Updating Homepage Cache ({$lang})...");
                $this->updateHomepageCache($lang);
            }

            if ($type === 'all' || $type === 'feed') {
                $this->info("Updating Feed Cache ({$lang})...");
                $this->updateFeedCache($lang);
            }

            if ($type === 'all' || $type === 'poets') {
                $this->info("Updating Poets List Cache ({$lang})...");
                $this->updatePoetsListCache($lang);
            }

            if ($type === 'all' || $type === 'couplets') {
                $this->info("Updating Couplets List Cache ({$lang})...");
                $this->updateCoupletsListCache($lang);
            }

            if ($type === 'all' || $type === 'categories') {
                $this->info("Updating Categories List Cache ({$lang})...");
                $this->updateCategoriesListCache($lang);
            }

            if ($type === 'all' || $type === 'periods') {
                $this->info("Updating Periods Cache ({$lang})...");
                $this->updatePeriodsCache($lang);
            }

            if ($type === 'all' || $type === 'prosody') {
                $this->info("Updating Prosody Cache ({$lang})...");
                $this->updateProsodyCache($lang);
            }

            if ($type === 'all' || $type === 'explore') {
                $this->info("Updating Explore Topics Cache ({$lang})...");
                $this->updateExploreTopicsCache($lang);
            }
        }

        if ($type === 'all' || $type === 'details') {
            $this->info('Updating Details Cache (Poetry, Poets, Topics)...');
            $this->updateDetailsCache();
        }

        if ($type === 'all' || $type === 'admin') {
            $this->info('Updating Admin Create Data Cache...');
            $this->updateAdminCreateCache();
        }

        $this->info('Static cache updated successfully!');
    }

    protected function updateHomepageCache($locale)
    {
        App::setLocale($locale);
        $data = [
            'sliders' => Sliders::where(['lang' => $locale, 'visibility' => 1])->get(),
            'famous_poets' => Poets::with([
                'details' => function ($q) use ($locale) {
                    $q->where('lang', $locale);
                }
            ])
                ->where('visibility', '1')
                ->whereHas('details', function ($query) use ($locale) {
                    $query->where('lang', $locale);
                })
                ->inRandomOrder()->limit(5)->get(),
            'ghazal_of_day' => (new TodaysModule())->ghazal($locale),
            'tags' => Tags::limit(18)->get(),
            'poet_tags' => Tags::where('type', 'poets')->get(),
            'doodles' => Doodle::first(),
        ];
        $this->cache->set("homepage_data_{$locale}", $data);
    }

    protected function updateFeedCache($locale)
    {
        $query = Poetry::with([
            'info' => function ($query) use ($locale) {
                $query->where('lang', $locale);
            },
            'poet_details' => function ($query) use ($locale) {
                $query->where('lang', $locale);
            },
            'poet',
            'category_detail' => function ($query) use ($locale) {
                $query->where('lang', $locale);
            },
            'media' => function ($query) use ($locale) {
                $query->where('media_type', 'image')->where('lang', $locale)->limit(1);
            }
        ])
            ->withCount('likes')
            ->where('visibility', 1)
            ->whereHas('poet', function ($q) {
                $q->where('visibility', 1);
            })
            ->latest()
            ->limit(10)
            ->get();

        $transformed = $query->map(function ($p) use ($locale) {
            return [
                'id' => $p->id,
                'title' => $p->info?->title ?? $p->poetry_title,
                'slug' => $p->poetry_slug,
                'author' => $p->poet_details?->poet_laqab ?? 'Unknown',
                'author_avatar' => $p->poet?->poet_pic,
                'cover' => $p->media->first()?->media_url,
                'date' => $p->created_at->toIso8601String(),
                'date_human' => $p->created_at->diffForHumans(),
                'readTime' => '5 min read',
                'category' => $p->category_detail?->cat_name ?? $p->category?->slug ?? 'General',
                'cat_slug' => $p->category?->slug,
                'poet_slug' => $p->poet?->poet_slug,
                'likes' => $p->likes_count ?? 0,
            ];
        });

        $this->cache->set("feed_page_1_{$locale}", $transformed);
    }

    protected function updatePoetsListCache($locale)
    {
        $query = Poets::query()->with('all_details')
            ->withCount('poetry')
            ->where('visibility', 1)
            ->orderBy('created_at', 'desc')
            ->get();

        $transformed = $query->map(function ($poet) use ($locale) {
            $detailSd = $poet->all_details->where('lang', 'sd')->first();
            $detailEn = $poet->all_details->where('lang', 'en')->first();
            $detail = $poet->all_details->where('lang', $locale)->first() ?? $poet->all_details->first() ?? (object) [];

            return [
                'id' => $poet->id,
                'slug' => $poet->poet_slug,
                'avatar' => $poet->poet_pic ?: null,
                'name_en' => $detailEn->poet_laqab ?? $detailEn->poet_name ?? $detailSd->poet_laqab ?? $detailSd->poet_name ?? 'N/A',
                'bio_en' => strip_tags($detailEn->poet_bio ?? $detailSd->poet_bio ?? ''),
                'name_sd' => $detailSd->poet_laqab ?? $detailSd->poet_name ?? $detailEn->poet_laqab ?? $detailEn->poet_name ?? 'N/A',
                'bio_sd' => strip_tags($detailSd->poet_bio ?? $detailEn->poet_bio ?? ''),
                'name' => $detail->poet_laqab ?? $detail->poet_name ?? 'N/A',
                'entries_count' => $poet->poetry_count ?? 0,
                'followers' => '0',
                'category' => 'all',
            ];
        });

        $this->cache->set("poets_list_{$locale}", $transformed);
    }

    protected function updateCoupletsListCache($locale)
    {
        $query = \App\Models\Couplets::whereHas('poet', function ($q) {
            $q->where('visibility', 1);
        })
            ->with([
                'poet.all_details' => function ($q) use ($locale) {
                    $q->where('lang', $locale);
                }
            ])
            ->where('lang', $locale)
            ->whereRaw("(LENGTH(TRIM(REPLACE(couplet_text, '\r', ''))) - LENGTH(REPLACE(TRIM(REPLACE(couplet_text, '\r', '')), '\n', ''))) <= 1")
            ->latest()
            ->limit(20)
            ->get();

        $transformed = $query->map(function ($c) use ($locale) {
            $poetDetail = $c->poet->all_details->where('lang', $locale)->first() ?? $c->poet->all_details->first();
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

        $this->cache->set("couplets_list_{$locale}", $transformed);
    }

    protected function updateCategoriesListCache($locale)
    {
        $categories = Categories::whereHas('poetry', function ($q) {
            $q->where('visibility', 1);
        })
            ->withCount([
                'poetry' => function ($q) {
                    $q->where('visibility', 1);
                }
            ])
            ->with(['details'])
            ->get()
            ->map(function ($cat) use ($locale) {
                $detail = $cat->details->where('lang', $locale)->first() ?? $cat->details->first();
                $enDetail = $cat->details->where('lang', 'en')->first() ?? $cat->details->first();
                $sdDetail = $cat->details->where('lang', 'sd')->first() ?? $cat->details->first();

                return [
                    'id' => $cat->id,
                    'slug' => $cat->slug,
                    'name' => $detail?->cat_name ?? $cat->slug,
                    'sd_name' => $sdDetail?->cat_name ?? $cat->slug,
                    'en_name' => $enDetail?->cat_name ?? $cat->slug,
                    'desc' => $detail?->cat_detail ?? '',
                    'count' => $cat->poetry_count ?? 0,
                ];
            });

        $this->cache->set("categories_list_{$locale}", $categories);
    }

    protected function updatePeriodsCache($locale)
    {
        $periods = \App\Models\Period::orderBy('order', 'asc')->get();
        $this->cache->set("periods_list_{$locale}", $periods);
    }

    protected function updateProsodyCache($locale)
    {
        $terms = \App\Models\ProsodyTerm::orderBy('order', 'asc')->get()->map(function ($term) use ($locale) {
            return [
                'id' => $term->id,
                'title' => $locale === 'sd' ? $term->title_sd : $term->title_en,
                'subtitle' => $locale === 'sd' ? $term->title_en : $term->title_sd,
                'description' => $locale === 'sd' ? $term->desc_sd : $term->desc_en,
                'technical_detail' => $locale === 'sd' ? $term->tech_detail_sd : $term->tech_detail_en,
                'logic_type' => $term->logic_type,
                'icon' => $term->icon,
            ];
        });
        $this->cache->set("prosody_list_{$locale}", $terms);
    }

    protected function updateExploreTopicsCache($locale)
    {
        App::setLocale($locale);

        $usedTagIds = Poetry::where('visibility', 1)
            ->whereNotNull('poetry_tags')
            ->pluck('poetry_tags')
            ->flatMap(function ($tagsJson) {
                $tags = json_decode($tagsJson, true);
                return is_array($tags) ? $tags : [];
            })->unique()->values()->all();

        $categories = TopicCategory::with([
            'details' => function ($q) use ($locale) {
                $q->where('lang', $locale);
            },
            'tags' => function ($q) use ($usedTagIds) {
                $q->whereIn('id', $usedTagIds);
            },
            'tags.details' => function ($q) use ($locale) {
                $q->where('lang', $locale);
            }
        ])
            ->get()
            ->map(function ($category) use ($locale) {
                $catDetail = $category->details->first() ?? $category->details()->where('lang', 'sd')->first() ?? $category->details()->first();
                return [
                    'id' => $category->id,
                    'slug' => $category->slug,
                    'name' => $catDetail->name ?? $category->slug,
                    'tags' => $category->tags->map(function ($tag) use ($locale) {
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
            ->filter(function ($category) {
                return $category['tags']->isNotEmpty();
            })
            ->values();

        $recommended = Tags::whereIn('id', $usedTagIds)
            ->with([
                'details' => function ($q) use ($locale) {
                    $q->where('lang', $locale);
                }
            ])
            ->whereHas('details')
            ->inRandomOrder()->take(10)->get()
            ->map(function ($tag) use ($locale) {
                $tagDetail = $tag->details->first() ?? $tag->details()->where('lang', 'sd')->first() ?? $tag->details()->first();
                return [
                    'id' => $tag->id,
                    'slug' => $tag->slug,
                    'name' => $tagDetail->name ?? $tag->slug,
                    'type' => $tag->type,
                ];
            });

        $data = ['categories' => $categories, 'recommended' => $recommended];
        $this->cache->set("explore_topics_{$locale}", $data);
    }

    protected function updateDetailsCache()
    {
        $locales = ['sd', 'en'];

        // 1. Poetry Details
        $poetry = Poetry::where('visibility', 1)->get();
        foreach ($poetry as $p) {
            foreach ($locales as $locale) {
                $this->info("Caching Poetry Detail: {$p->poetry_slug} ({$locale})");
                $this->cachePoetryDetail($p, $locale);
            }
        }

        // 2. Poets Details
        $poets = Poets::where('visibility', 1)->get();
        foreach ($poets as $poet) {
            foreach ($locales as $locale) {
                $this->info("Caching Poet Detail: {$poet->poet_slug} ({$locale})");
                $this->cachePoetDetail($poet, $locale);
            }
        }

        // 3. Topic/Tag Details
        $tags = Tags::all();
        foreach ($tags as $tag) {
            foreach ($locales as $locale) {
                $this->info("Caching Tag Detail: {$tag->slug} ({$locale})");
                $this->cacheTagDetail($tag, $locale);
            }
        }

        // 4. Category Details
        $categories = TopicCategory::all();
        foreach ($categories as $cat) {
            foreach ($locales as $locale) {
                $this->info("Caching Category Detail: {$cat->slug} ({$locale})");
                $this->cacheCategoryDetail($cat, $locale);
            }
        }
    }

    protected function cachePoetryDetail($p, $locale)
    {
        App::setLocale($locale);
        $p->load([
            'info' => function ($query) use ($locale) {
                $query->where('lang', $locale);
            },
            'translations' => function ($query) use ($locale) {
                $query->where('lang', $locale);
            },
            'poet_details' => function ($query) use ($locale) {
                $query->where('lang', $locale);
            },
            'poet',
            'category',
            'category_detail' => function ($query) use ($locale) {
                $query->where('lang', $locale);
            },
            'media' => function ($query) use ($locale) {
                $query->where('lang', $locale);
            },
        ]);

        $info = $p->info ?? $p->translations->first();
        if (!$info)
            $info = $p->translations()->first();

        $data = [
            'id' => $p->id,
            'title' => $info->title ?? $p->poetry_title,
            'slug' => $p->poetry_slug,
            'content' => $info->poetry_text ?? $p->poetry_content,
            'content_style' => $p->content_style,
            'poet' => [
                'name' => $p->poet_details->poet_laqab ?? $p->poet->poet_slug,
                'slug' => $p->poet->poet_slug,
                'avatar' => $p->poet->poet_pic,
            ],
            'category' => [
                'name' => $p->category_detail->cat_name ?? $p->category->slug ?? 'General',
                'slug' => $p->category->slug ?? 'general',
            ],
            'media' => $p->media->map(function ($m) {
                return ['type' => $m->media_type, 'url' => $m->media_url];
            }),
            'counts' => ['likes' => $p->likes()->count(), 'views' => $p->views_count ?? 0],
        ];
        $this->cache->set("poetry_detail_{$p->poetry_slug}_{$locale}", $data);
    }

    protected function cachePoetDetail($poet, $locale)
    {
        App::setLocale($locale);
        $poet->load([
            'all_details',
            'poetry' => function ($q) {
                $q->latest()->take(10);
            }
        ]);
        $getDetail = function ($lang) use ($poet) {
            return $poet->all_details->where('lang', $lang)->first();
        };
        $detailSd = $getDetail('sd');
        $detailEn = $getDetail('en');
        $detail = $getDetail($locale) ?? $poet->all_details->first();

        // Suggested (Simplified for cache command to avoid overhead)
        $suggested = Poets::where('id', '!=', $poet->id)->where('visibility', 1)->inRandomOrder()->take(3)->get()->map(function ($p) {
            $dSd = $p->all_details->where('lang', 'sd')->first();
            $dEn = $p->all_details->where('lang', 'en')->first();
            return [
                'name_en' => $dEn->poet_laqab ?? $dEn->poet_name ?? $dSd->poet_laqab ?? $dSd->poet_name ?? 'N/A',
                'name_sd' => $dSd->poet_laqab ?? $dSd->poet_name ?? $dEn->poet_laqab ?? $dEn->poet_name ?? 'N/A',
                'slug' => $p->poet_slug,
                'avatar' => $p->poet_pic ?: null,
            ];
        });

        $data = [
            'id' => $poet->id,
            'slug' => $poet->poet_slug,
            'avatar' => $poet->poet_pic ?: null,
            'dob' => $poet->date_of_birth,
            'dod' => $poet->date_of_death,
            'name_en' => $detailEn->poet_name ?? $detailSd->poet_name ?? 'N/A',
            'name_sd' => $detailSd->poet_name ?? $detailEn->poet_name ?? 'N/A',
            'bio_en' => strip_tags($detailEn->poet_bio ?? $detailSd->poet_bio ?? ''),
            'bio_sd' => strip_tags($detailSd->poet_bio ?? $detailEn->poet_bio ?? ''),
            'entries_count' => $poet->poetry_count ?? 0,
            'suggested' => $suggested,
        ];
        $this->cache->set("poet_detail_{$poet->poet_slug}_{$locale}", $data);
    }

    protected function cacheTagDetail($tag, $locale)
    {
        App::setLocale($locale);
        $topicCategory = $tag->topicCategory;
        $catName = 'Unknown';
        $catSlug = '';
        if ($topicCategory) {
            $catDetail = $topicCategory->details->where('lang', $locale)->first() ?? $topicCategory->details->first();
            $catName = $catDetail->name ?? $topicCategory->slug;
            $catSlug = $topicCategory->slug;
        }
        $tagDetail = $tag->details->where('lang', $locale)->first() ?? $tag->details->first();
        $tagName = $tagDetail->name ?? $tag->slug;

        $poetry = Poetry::where('visibility', 1)->where('poetry_tags', 'like', '%"' . $tag->id . '"%')
            ->with([
                'translations' => function ($q) use ($locale) {
                    $q->where('lang', $locale);
                },
                'category',
                'category.details' => function ($q) use ($locale) {
                    $q->where('lang', $locale);
                },
                'poet',
                'poet.all_details'
            ])
            ->withCount('likes')->latest()->take(10)->get()
            ->map(function ($p) use ($locale) {
                return $this->formatPoetryForTopic($p, $locale);
            });

        $poets = Poets::where('visibility', 1)->where('poet_tags', 'like', '%"' . $tag->id . '"%')->with('all_details')->withCount('poetry')->latest()->take(10)->get()
            ->map(function ($poet) use ($locale) {
                return $this->formatPoetForTopic($poet, $locale);
            });

        $data = [
            'type' => 'tag',
            'data' => ['id' => $tag->id, 'slug' => $tag->slug, 'name' => $tagName, 'type' => $tag->type],
            'parent' => ['slug' => $catSlug, 'name' => $catName, 'type' => 'category'],
            'counts' => [
                'poetry' => Poetry::where('visibility', 1)->where('poetry_tags', 'like', '%"' . $tag->id . '"%')->count(),
                'poets' => Poets::where('visibility', 1)->where('poet_tags', 'like', '%"' . $tag->id . '"%')->count(),
            ],
            'poetry' => $poetry,
            'poets' => $poets
        ];
        $this->cache->set("tag_detail_{$tag->slug}_{$locale}", $data);
    }

    protected function cacheCategoryDetail($category, $locale)
    {
        App::setLocale($locale);
        $catDetail = $category->details->where('lang', $locale)->first() ?? $category->details->first();
        $catName = $catDetail->name ?? $category->slug;
        $categoryTagIds = $category->tags()->pluck('id')->toArray();

        $poetry = Poetry::where('visibility', 1)->where(function ($query) use ($category, $categoryTagIds) {
            $query->where('topic_category_id', $category->id);
            if (!empty($categoryTagIds)) {
                foreach ($categoryTagIds as $tagId) {
                    $query->orWhere('poetry_tags', 'like', '%"' . $tagId . '"%');
                }
            }
        })
            ->with([
                'translations' => function ($q) use ($locale) {
                    $q->where('lang', $locale);
                },
                'category',
                'category.details' => function ($q) use ($locale) {
                    $q->where('lang', $locale);
                },
                'poet',
                'poet.all_details'
            ])
            ->withCount('likes')->latest()->take(10)->get()
            ->map(function ($p) use ($locale) {
                return $this->formatPoetryForTopic($p, $locale);
            });

        $poets = Poets::where('visibility', 1)->whereHas('poetry', function ($q) use ($category) {
            $q->where('topic_category_id', $category->id)->where('visibility', 1);
        })
            ->with('all_details')->withCount('poetry')->take(10)->get()
            ->map(function ($poet) use ($locale) {
                return $this->formatPoetForTopic($poet, $locale);
            });

        $data = [
            'type' => 'category',
            'data' => ['id' => $category->id, 'slug' => $category->slug, 'name' => $catName],
            'parent' => null,
            'counts' => [
                'poetry' => Poetry::where('visibility', 1)->where(function ($query) use ($category, $categoryTagIds) {
                    $query->where('topic_category_id', $category->id);
                    if (!empty($categoryTagIds)) {
                        foreach ($categoryTagIds as $tagId) {
                            $query->orWhere('poetry_tags', 'like', '%"' . $tagId . '"%');
                        }
                    }
                })->count(),
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
        ];
        $this->cache->set("category_detail_{$category->slug}_{$locale}", $data);
    }

    private function formatPoetryForTopic($p, $lang)
    {
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
            'is_liked' => false,
            'is_bookmarked' => false,
            'cover' => $p->cover_image ?? null,
            'content_style' => $p->content_style,
        ];
    }

    private function formatPoetForTopic($poet, $lang)
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

    protected function updateAdminCreateCache()
    {
        $poets = Poets::where('visibility', 1)->with([
            'details' => function ($q) {
                $q->where('lang', 'sd');
            }
        ])
            ->select('id', 'poet_slug')->get()->map(function ($poet) {
                return ['id' => $poet->id, 'name' => $poet->details?->poet_laqab ?? $poet->poet_slug];
            });

        $categories = Categories::with([
            'detail' => function ($q) {
                $q->where('lang', 'sd');
            }
        ])
            ->select('id', 'slug')->get()->map(function ($cat) {
                return ['id' => $cat->id, 'name' => $cat->detail?->cat_name ?? $cat->slug];
            });

        $tags = Tags::with([
            'details' => function ($q) {
                $q->where('lang', 'sd');
            }
        ])
            ->get()->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'tag' => $tag->details->first()?->name ?? $tag->slug,
                    'type' => $tag->type
                ];
            })->groupBy('type');

        $topicCategories = TopicCategory::with([
            'details' => function ($q) {
                $q->where('lang', 'sd');
            }
        ])
            ->get()->map(function ($cat) {
                return ['id' => $cat->id, 'name' => $cat->details->first()?->name ?? $cat->slug];
            });

        $data = [
            'poets' => $poets,
            'categories' => $categories,
            'topic_categories' => $topicCategories,
            'tags' => $tags,
            'content_styles' => ['justified', 'center', 'start', 'end']
        ];

        $this->cache->set("admin_poetry_create_data", $data);
    }
}
