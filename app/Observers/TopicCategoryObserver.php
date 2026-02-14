<?php

namespace App\Observers;

use App\Models\TopicCategory;
use App\Services\StaticCacheService;

class TopicCategoryObserver
{
    /**
     * Handle the TopicCategory "created" event.
     */
    public function created(TopicCategory $topicCategory): void
    {
        $this->invalidateCache();
    }

    /**
     * Handle the TopicCategory "updated" event.
     */
    public function updated(TopicCategory $topicCategory): void
    {
        $this->invalidateCache();
    }

    /**
     * Handle the TopicCategory "deleted" event.
     */
    public function deleted(TopicCategory $topicCategory): void
    {
        $this->invalidateCache();
    }

    /**
     * Handle the TopicCategory "restored" event.
     */
    public function restored(TopicCategory $topicCategory): void
    {
        $this->invalidateCache();
    }

    /**
     * Handle the TopicCategory "force deleted" event.
     */
    public function forceDeleted(TopicCategory $topicCategory): void
    {
        $this->invalidateCache();
    }

    protected function invalidateCache()
    {
        app(StaticCacheService::class)->forget('admin_poetry_create_data');
    }
}
