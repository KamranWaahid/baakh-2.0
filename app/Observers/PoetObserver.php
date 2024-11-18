<?php

namespace App\Observers;

use App\Models\Poets;
use App\Models\Search\UnifiedPoets;
use App\Traits\SQLiteTrait;
use Illuminate\Support\Facades\Cache;

class PoetObserver
{
    use SQLiteTrait;
    /**
     * Handle the Poets "created" event.
     */
    public function created(Poets $poets): void
    {
        $this->forgetPoetsCache();
    }

    /**
     * Handle the Poets "updated" event.
     */
    public function updated(Poets $poets): void
    {
        $this->updatePoet($poets->id);
        $this->forgetPoetsCache();
    }

    /**
     * Handle the Poets "deleted" event.
     */
    public function deleted(Poets $poets): void
    {
        $this->forgetPoetsCache();
    }

    /**
     * Handle the Poets "restored" event.
     */
    public function restored(Poets $poets): void
    {
        $this->forgetPoetsCache();
    }

    /**
     * Handle the Poets "force deleted" event.
     */
    public function forceDeleted(Poets $poets): void
    {
        UnifiedPoets::where('poet_id', $poets->id)->delete();
        $this->forgetPoetsCache();
    }

    /**
     * Forget the poets cache.
     *
     * @return void
     */
    protected function forgetPoetsCache()
    {
        Cache::forget('admin_all_poets_sd'); // AdminPoetryController used
        Cache::forget('admin_poets_ids');
    }
}
