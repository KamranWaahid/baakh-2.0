<?php

namespace App\Observers;

use App\Models\Categories;
use App\Models\Search\UnifiedCategories;
use App\Traits\SQLiteTrait;
use App\Services\StaticCacheService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CategoryObserver
{
    use SQLiteTrait;

    protected function invalidateCache()
    {
        $cache = app(StaticCacheService::class);
        $cache->forget('admin_poetry_create_data');
        $cache->forget('homepage_data_sd');
        $cache->forget('homepage_data_en');
        $cache->forget('categories_list_sd');
        $cache->forget('categories_list_en');

        Cache::forget('admin_all_categories_sd');
    }
    /**
     * Handle the Categories "created" event.
     */
    public function created(Categories $categories): void
    {
        $this->forgetCache();
    }

    /**
     * Handle the Categories "updated" event.
     */
    public function updated(Categories $categories): void
    {
        $this->updateCategory($categories->id);
        $this->invalidateCache();
    }

    /**
     * Handle the Categories "deleted" event.
     */
    public function deleted(Categories $categories): void
    {
        $this->forgetCache();
    }

    /**
     * Handle the Categories "restored" event.
     */
    public function restored(Categories $categories): void
    {
        $this->forgetCache();
    }

    /**
     * Handle the Categories "force deleted" event.
     */
    public function forceDeleted(Categories $categories): void
    {
        try {
            UnifiedCategories::find($categories->id)->delete();
        } catch (\Throwable $th) {
            Log::warning("Error while deleting category \n $th");
        }
        $this->forgetCache();
    }

    protected function forgetCache()
    {
        $this->invalidateCache();
    }
}
