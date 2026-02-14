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
        $this->invalidateCache($topicCategory);
    }

    /**
     * Handle the TopicCategory "updated" event.
     */
    public function updated(TopicCategory $topicCategory): void
    {
        $this->invalidateCache($topicCategory);
    }

    /**
     * Handle the TopicCategory "deleted" event.
     */
    public function deleted(TopicCategory $topicCategory): void
    {
        $this->invalidateCache($topicCategory);
    }

    /**
     * Handle the TopicCategory "restored" event.
     */
    public function restored(TopicCategory $topicCategory): void
    {
        $this->invalidateCache($topicCategory);
    }

    /**
     * Handle the TopicCategory "force deleted" event.
     */
    public function forceDeleted(TopicCategory $topicCategory): void
    {
        $this->invalidateCache($topicCategory);
    }

    protected function invalidateCache(?TopicCategory $cat = null)
    {
        $cache = app(StaticCacheService::class);
        $cache->forget('admin_poetry_create_data');
        $cache->forget('explore_topics_sd');
        $cache->forget('explore_topics_en');

        if ($cat) {
            $cache->forget("category_detail_{$cat->slug}_sd");
            $cache->forget("category_detail_{$cat->slug}_en");
        }
    }
}
