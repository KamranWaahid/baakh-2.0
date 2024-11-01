<?php

namespace App\Observers;

use App\Models\Poets;
use Illuminate\Support\Facades\Cache;

class PoetObserver
{
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
    }
}
