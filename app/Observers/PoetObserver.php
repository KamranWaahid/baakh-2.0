<?php

namespace App\Observers;

use App\Models\Poets;
use App\Models\Search\UnifiedPoets;
use App\Traits\SQLiteTrait;
use App\Services\StaticCacheService;
use Illuminate\Support\Facades\Cache;

class PoetObserver
{
    use SQLiteTrait;

    protected function invalidateCache(?Poets $poet = null)
    {
        $cache = app(StaticCacheService::class);
        $cache->forget('admin_poetry_create_data');
        $cache->forget('homepage_data_sd');
        $cache->forget('homepage_data_en');
        $cache->forget('poets_list_sd');
        $cache->forget('poets_list_en');
        $cache->forget('explore_topics_sd');
        $cache->forget('explore_topics_en');

        if ($poet) {
            $cache->forget("poet_detail_{$poet->poet_slug}_sd");
            $cache->forget("poet_detail_{$poet->poet_slug}_en");
        }

        Cache::forget('admin_all_poets_sd');
        Cache::forget('admin_poets_ids');
    }

    /**
     * Handle the Poets "created" event.
     */
    public function created(Poets $poets): void
    {
        $this->invalidateCache($poets);
    }

    /**
     * Handle the Poets "updated" event.
     */
    public function updated(Poets $poets): void
    {
        $this->updatePoet($poets->id);
        $this->invalidateCache($poets);
    }

    /**
     * Handle the Poets "deleted" event.
     */
    public function deleted(Poets $poets): void
    {
        $this->invalidateCache($poets);
    }

    /**
     * Handle the Poets "restored" event.
     */
    public function restored(Poets $poets): void
    {
        $this->invalidateCache();
    }

    /**
     * Handle the Poets "force deleted" event.
     */
    public function forceDeleted(Poets $poets): void
    {
        UnifiedPoets::where('poet_id', $poets->id)->delete();
        $this->invalidateCache();
    }

    /**
     * Forget the poets cache.
     *
     * @return void
     */
    protected function forgetPoetsCache()
    {
        $this->invalidateCache();
    }
}
