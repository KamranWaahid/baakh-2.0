<?php

namespace App\Observers;

use App\Models\Tags;
use Illuminate\Support\Facades\Cache;

class TagObserver
{
    /**
     * Handle the Tags "created" event.
     */
    public function created(Tags $tags): void
    {
        $this->forgetCache();
    }

    /**
     * Handle the Tags "updated" event.
     */
    public function updated(Tags $tags): void
    {
        $this->forgetCache();
    }

    /**
     * Handle the Tags "deleted" event.
     */
    public function deleted(Tags $tags): void
    {
        $this->forgetCache();
    }

    /**
     * Handle the Tags "restored" event.
     */
    public function restored(Tags $tags): void
    {
        $this->forgetCache();
    }

    /**
     * Handle the Tags "force deleted" event.
     */
    public function forceDeleted(Tags $tags): void
    {
        $this->forgetCache();
    }

    protected function forgetCache() 
    {
        Cache::forget('admin_all_tags_sd');
    }
}
