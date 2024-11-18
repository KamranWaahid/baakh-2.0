<?php

namespace App\Observers;

use App\Models\Search\UnifiedTags;
use App\Models\Tags;
use App\Traits\SQLiteTrait;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TagObserver
{
    use SQLiteTrait;
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
        $this->updateTag($tags->id);
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
        try {
            UnifiedTags::find($tags)->delete();
        } catch (\Throwable $th) {
            Log::warning("Error while deleting Tag from SQLite \n $th");
        }
        $this->forgetCache();
    }

    protected function forgetCache() 
    {
        Cache::forget('admin_all_tags_sd');
    }
}
