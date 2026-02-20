<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Poetry;
use App\Models\Poets;
use App\Models\Tags;
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
            return [
                'title' => 'New Poetry Added',
                'message' => "A new poem has been published.",
                'icon' => 'BookOpen',
                'color' => 'blue',
                'link' => "/sd/poetry", // Defaulting to Sindhi for content links
            ];
        }

        if ($model instanceof Poets) {
            return [
                'title' => 'New Poet Added',
                'message' => "A new poet has been added to our collection.",
                'icon' => 'Feather',
                'color' => 'purple',
                'link' => "/sd/poets",
            ];
        }

        if ($model instanceof Tags) {
            return [
                'title' => 'New Topic/Tag',
                'message' => "A new tag has been added.",
                'icon' => 'Tags',
                'color' => 'cyan',
                'link' => "/sd/explore",
            ];
        }

        if ($model instanceof TopicCategory) {
            return [
                'title' => 'New Category',
                'message' => "A new topic category has been created.",
                'icon' => 'Layers',
                'color' => 'indigo',
                'link' => "/sd/explore",
            ];
        }

        return null;
    }
}
