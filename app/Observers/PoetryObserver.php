<?php

namespace App\Observers;

use App\Models\Poetry;
use App\Services\StaticCacheService;
use App\Traits\SQLiteTrait;

class PoetryObserver
{
    use SQLiteTrait;

    protected $cache;

    public function __construct(StaticCacheService $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Handle the Poetry "created" event.
     */
    public function created(Poetry $poetry): void
    {
        $this->invalidateCache($poetry);
    }

    /**
     * Handle the Poetry "updated" event.
     */
    public function updated(Poetry $poetry): void
    {
        $this->updatePoetry($poetry->id);
        $this->invalidateCache($poetry);
    }

    /**
     * Handle the Poetry "deleted" event.
     */
    public function deleted(Poetry $poetry): void
    {
        $this->invalidateCache($poetry);
    }

    /**
     * Handle the Poetry "restored" event.
     */
    public function restored(Poetry $poetry): void
    {
        $this->invalidateCache();
    }

    /**
     * Handle the Poetry "force deleted" event.
     */
    public function forceDeleted(Poetry $poetry): void
    {
        $this->invalidateCache();
    }

    protected function invalidateCache(?Poetry $p = null)
    {
        $this->cache->forget('homepage_data_sd');
        $this->cache->forget('homepage_data_en');
        $this->cache->forget('feed_page_1_sd');
        $this->cache->forget('feed_page_1_en');
        $this->cache->forget('poets_list_sd');
        $this->cache->forget('poets_list_en');
        $this->cache->forget('explore_topics_sd');
        $this->cache->forget('explore_topics_en');

        if ($p) {
            $this->cache->forget("poetry_detail_{$p->poetry_slug}_sd");
            $this->cache->forget("poetry_detail_{$p->poetry_slug}_en");
            if ($p->poet) {
                $this->cache->forget("poet_detail_{$p->poet->poet_slug}_sd");
                $this->cache->forget("poet_detail_{$p->poet->poet_slug}_en");
            }
        }
    }
}
