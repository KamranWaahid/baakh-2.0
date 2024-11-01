<?php

namespace App\Observers;

use App\Models\Categories;
use Illuminate\Support\Facades\Cache;

class CategoryObserver
{
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
        $this->forgetCache();
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
        $this->forgetCache();
    }

    protected function forgetCache() {
        Cache::forget('admin_all_categories_sd');
    }
}
