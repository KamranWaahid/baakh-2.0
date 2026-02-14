<?php

namespace App\Observers;

use App\Models\Search\UnifiedTags;
use App\Models\Tags;
use App\Traits\SQLiteTrait;
use App\Services\StaticCacheService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TagObserver
{
    use SQLiteTrait;

    protected function invalidateCache(?Tags $tag = null)
    {
        $cache = app(StaticCacheService::class);
        $cache->forget('admin_poetry_create_data');
        $cache->forget('homepage_data_sd');
        $cache->forget('homepage_data_en');
        $cache->forget('explore_topics_sd');
        $cache->forget('explore_topics_en');

        if ($tag) {
            $cache->forget("tag_detail_{$tag->slug}_sd");
            $cache->forget("tag_detail_{$tag->slug}_en");
        }

        Cache::forget('admin_all_tags_sd');
    }
    /**
     * Handle the Tags "created" event.
     */
    public function created(Tags $tags): void
    {
        $this->invalidateCache($tags);
    }

    /**
     * Handle the Tags "updated" event.
     */
    public function updated(Tags $tags): void
    {
        $this->updateTag($tags->id);
        $this->invalidateCache();
    }

    /**
     * Handle the Tags "deleted" event.
     */
    public function deleted(Tags $tags): void
    {
        $this->invalidateCache($tags);
    }

    /**
     * Handle the Tags "restored" event.
     */
    public function restored(Tags $tags): void
    {
        $this->invalidateCache($tags);
    }

    /**
     * Handle the Tags "force deleted" event.
     */
    public function forceDeleted(Tags $tags): void
    {
        $this->invalidateCache($tags);
    }

    protected function forgetCache()
    {
        $this->invalidateCache();
    }
}
