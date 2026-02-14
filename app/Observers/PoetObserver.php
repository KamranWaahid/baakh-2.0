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

    protected function invalidateCache()
    {
        Cache::forget('admin_all_poets_sd');
        Cache::forget('admin_poets_ids');
        app(StaticCacheService::class)->forget('admin_poetry_create_data');
        app(StaticCacheService::class)->forget('homepage_data_sd');
        app(StaticCacheService::class)->forget('homepage_data_en');
    }

    /**
     * Handle the Poets "created" event.
     */
    public function created(Poets $poets): void
    {
        $this->invalidateCache();
    }

    /**
     * Handle the Poets "updated" event.
     */
    public function updated(Poets $poets): void
    {
        $this->updatePoet($poets->id);
        $this->invalidateCache();
    }

    /**
     * Handle the Poets "deleted" event.
     */
    public function deleted(Poets $poets): void
    {
        $this->invalidateCache();
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
