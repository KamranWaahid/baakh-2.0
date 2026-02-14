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

        if ($type === 'all' || $type === 'homepage') {
            $this->info('Updating Homepage Cache...');
            $this->updateHomepageCache('sd');
            $this->updateHomepageCache('en');
        }

        if ($type === 'all' || $type === 'feed') {
            $this->info('Updating Feed Cache...');
            $this->updateFeedCache('sd');
            $this->updateFeedCache('en');
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

        // This mirrors HomeController@index logic but returns raw data for caching
        $data = [
            'sliders' => Sliders::where(['lang' => $locale, 'visibility' => 1])->get(),
            'famous_poets' => Poets::with(['details' => function ($q) use ($locale) {
                $q->where('lang', $locale); }])
                ->where('visibility', '1')
                ->whereHas('details', function ($query) use ($locale) {
                    $query->where('lang', $locale); })
                ->inRandomOrder()->limit(5)->get(),
            'ghazal_of_day' => (new TodaysModule())->ghazal($locale),
            'tags' => Tags::where('lang', $locale)->limit(18)->get(),
            'poet_tags' => Tags::where(['type' => 'poets', 'lang' => $locale])->get(),
            'doodles' => Doodle::first(),
        ];

        $this->cache->set("homepage_data_{$locale}", $data);
    }

    protected function updateFeedCache($locale)
    {
        // Mirrors HomeController@feed logic for page 1
        $query = Poetry::with([
            'info' => function ($query) use ($locale) {
                $query->where('lang', $locale); },
            'poet_details' => function ($query) use ($locale) {
                $query->where('lang', $locale); },
            'poet',
            'category_detail' => function ($query) use ($locale) {
                $query->where('lang', $locale); },
            'media' => function ($query) use ($locale) {
                $query->where('media_type', 'image')->where('lang', $locale)->limit(1);
            }
        ])
            ->withCount('likes')
            ->where('visibility', 1)
            ->whereHas('poet', function ($q) {
                $q->where('visibility', 1); })
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

    protected function updateAdminCreateCache()
    {
        $poets = Poets::where('visibility', 1)->with(['details' => function ($q) {
            $q->where('lang', 'sd'); }])
            ->select('id', 'poet_slug')->get()->map(function ($poet) {
                return ['id' => $poet->id, 'name' => $poet->details?->poet_laqab ?? $poet->poet_slug];
            });

        $categories = Categories::with(['detail' => function ($q) {
            $q->where('lang', 'sd'); }])
            ->select('id', 'slug')->get()->map(function ($cat) {
                return ['id' => $cat->id, 'name' => $cat->detail?->cat_name ?? $cat->slug];
            });

        $tags = Tags::with(['details' => function ($q) {
            $q->where('lang', 'sd'); }])
            ->get()->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'tag' => $tag->details->first()?->name ?? $tag->slug,
                    'type' => $tag->type
                ];
            })->groupBy('type');

        $topicCategories = TopicCategory::with(['details' => function ($q) {
            $q->where('lang', 'sd'); }])
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
