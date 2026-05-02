<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Poetry;
use App\Models\Poets;
use App\Models\Tags;
use App\Models\Categories;
use App\Models\TopicCategory;
use App\Notifications\NewContentNotification;
use Illuminate\Support\Facades\Notification;

class ContentNotificationObserver
{
    /**
     * Handle the created event.
     */
    public function created($model)
    {
        $metadata = $this->getNotificationMetadata($model);

        if ($metadata) {
            // Get all active users to notify
            $users = User::where('status', 'active')->get();

            Notification::send($users, new NewContentNotification($metadata));
        }
    }

    /**
     * Map model type to notification metadata.
     */
    protected function getNotificationMetadata($model): ?array
    {
        if ($model instanceof Poetry) {
            $poetSlug = $model->poet?->poet_slug;
            $categorySlug = $model->category?->slug;
            $poetrySlug = $model->poetry_slug;
            $title = $model->translations()
                ->where('lang', 'sd')
                ->value('title')
                ?? $model->translations()->value('title')
                ?? $model->poetry_slug;
            $poetName = $model->poet?->all_details()
                ->where('lang', 'sd')
                ->value('poet_laqab')
                ?? $model->poet?->all_details()->value('poet_laqab')
                ?? $model->poet?->poet_slug
                ?? 'اڻڄاتل شاعر';

            $link = '/sd/poetry';
            if ($poetSlug && $categorySlug && $poetrySlug) {
                $link = "/sd/poet/{$poetSlug}/{$categorySlug}/{$poetrySlug}";
            }

            return [
                'title' => 'New Poetry Added',
                'message' => "\"{$title}\" by {$poetName} has been published.",
                'icon' => 'BookOpen',
                'color' => 'blue',
                'link' => $link,
                'entity_type' => 'poetry',
                'entity_name' => $title,
                'poet_name' => $poetName,
            ];
        }

        if ($model instanceof Poets) {
            $poetName = $model->all_details()
                ->where('lang', 'sd')
                ->value('poet_laqab')
                ?? $model->all_details()->value('poet_laqab')
                ?? $model->poet_slug;

            return [
                'title' => 'New Poet Added',
                'message' => "\"{$poetName}\" has been added to our collection.",
                'icon' => 'Feather',
                'color' => 'purple',
                'link' => $model->poet_slug ? "/sd/poet/{$model->poet_slug}" : '/sd/poets',
                'entity_type' => 'poet',
                'entity_name' => $poetName,
            ];
        }

        if ($model instanceof Tags) {
            $tagName = $model->details()
                ->where('lang', 'sd')
                ->value('name')
                ?? $model->details()->value('name')
                ?? $model->slug;

            return [
                'title' => 'New Topic/Tag',
                'message' => "\"{$tagName}\" tag has been added.",
                'icon' => 'Tags',
                'color' => 'cyan',
                'link' => $model->slug ? "/sd/tag/{$model->slug}" : '/sd/explore',
                'entity_type' => 'tag',
                'entity_name' => $tagName,
            ];
        }

        if ($model instanceof TopicCategory) {
            $topicName = $model->details()
                ->where('lang', 'sd')
                ->value('name')
                ?? $model->details()->value('name')
                ?? $model->slug;

            return [
                'title' => 'New Category',
                'message' => "\"{$topicName}\" topic category has been created.",
                'icon' => 'Layers',
                'color' => 'indigo',
                'link' => $model->slug ? "/sd/topic/{$model->slug}" : '/sd/explore',
                'entity_type' => 'topic_category',
                'entity_name' => $topicName,
            ];
        }

        if ($model instanceof Categories) {
            $categoryName = $model->details()
                ->where('lang', 'sd')
                ->value('cat_name')
                ?? $model->details()->value('cat_name')
                ?? $model->slug;

            return [
                'title' => 'New Poetry Category',
                'message' => "\"{$categoryName}\" category has been added.",
                'icon' => 'Layers',
                'color' => 'indigo',
                'link' => '/sd/poetry',
                'entity_type' => 'category',
                'entity_name' => $categoryName,
            ];
        }

        return null;
    }
}
