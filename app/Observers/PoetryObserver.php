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
        $this->invalidateCache();
    }

    /**
     * Handle the Poetry "updated" event.
     */
    public function updated(Poetry $poetry): void
    {
        $this->updatePoetry($poetry->id);
        $this->invalidateCache();
    }

    /**
     * Handle the Poetry "deleted" event.
     */
    public function deleted(Poetry $poetry): void
    {
        $this->invalidateCache();
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

    protected function invalidateCache()
    {
        $this->cache->forget('homepage_data_sd');
        $this->cache->forget('homepage_data_en');
        $this->cache->forget('feed_page_1_sd');
        $this->cache->forget('feed_page_1_en');
    }
}
