<?php

namespace App\Observers;

use App\Models\AdminNotification;
use App\Models\User;
use App\Models\Poetry;
use App\Models\Poets;
use App\Models\Tags;
use App\Models\Categories;
use App\Models\TopicCategory;
use App\Notifications\NewContentNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class ContentNotificationObserver
{
    /**
     * Handle the created event after the surrounding DB transaction commits,
     * so related translations/details exist when we build the message.
     */
    public function created($model)
    {
        $modelId = $model->getKey();
        $modelClass = get_class($model);

        DB::afterCommit(function () use ($modelClass, $modelId) {
            $fresh = $modelClass::query()->find($modelId);
            if (!$fresh) {
                return;
            }

            $metadata = $this->getNotificationMetadata($fresh);
            if (!$metadata) {
                return;
            }

            $users = User::where('status', 'active')->get();
            if ($users->isNotEmpty()) {
                Notification::send($users, new NewContentNotification($metadata));
            }

            // Mirror a readable copy into the admin bell.
            try {
                AdminNotification::create([
                    'type' => 'created_' . ($metadata['entity_type'] ?? 'content'),
                    'title' => $metadata['title'],
                    'message' => $metadata['message'],
                    'icon' => $metadata['icon'] ?? 'Bell',
                    'color' => $metadata['color'] ?? 'blue',
                    'link' => $this->adminLinkFor($metadata),
                    'data' => [
                        'entity_type' => $metadata['entity_type'] ?? null,
                        'entity_id' => $metadata['entity_id'] ?? null,
                        'entity_name' => $metadata['entity_name'] ?? null,
                        'poet_name' => $metadata['poet_name'] ?? null,
                    ],
                ]);
            } catch (\Throwable $e) {
                // Admin notifications are non-critical.
            }
        });
    }

    protected function adminLinkFor(array $metadata): ?string
    {
        return match ($metadata['entity_type'] ?? null) {
            'poetry' => isset($metadata['entity_id']) ? '/admin/poetry/' . $metadata['entity_id'] . '/edit' : '/admin/poetry',
            'poet' => isset($metadata['entity_id']) ? '/admin/poets/' . $metadata['entity_id'] . '/edit' : '/admin/poets',
            'tag' => '/admin/tags',
            'topic_category' => '/admin/topic-categories',
            'category' => '/admin/categories',
            default => null,
        };
    }

    /**
     * Map model type to notification metadata.
     */
    protected function getNotificationMetadata($model): ?array
    {
        if ($model instanceof Poetry) {
            $model->loadMissing(['poet', 'category', 'translations']);

            $poetSlug = $model->poet?->poet_slug;
            $categorySlug = $model->category?->slug;
            $poetrySlug = $model->poetry_slug;

            $title = $this->poetryTitle($model);
            $poetName = $this->poetDisplayName($model->poet);

            $link = '/{lang}/poetry';
            if ($poetSlug && $categorySlug && $poetrySlug) {
                $link = "/{lang}/poet/{$poetSlug}/{$categorySlug}/{$poetrySlug}";
            }

            return [
                'title' => 'New Poetry',
                'message' => $poetName ? "{$title} · {$poetName}" : $title,
                'icon' => 'BookOpen',
                'color' => 'blue',
                'link' => $link,
                'entity_type' => 'poetry',
                'entity_id' => $model->id,
                'entity_name' => $title,
                'poet_name' => $poetName,
            ];
        }

        if ($model instanceof Poets) {
            $poetName = $this->poetDisplayName($model);

            return [
                'title' => 'New Poet',
                'message' => $poetName,
                'icon' => 'Feather',
                'color' => 'purple',
                'link' => $model->poet_slug ? "/{lang}/poet/{$model->poet_slug}" : '/{lang}/poets',
                'entity_type' => 'poet',
                'entity_id' => $model->id,
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
                'title' => 'New Tag',
                'message' => $tagName,
                'icon' => 'Tags',
                'color' => 'cyan',
                'link' => $model->slug ? "/{lang}/tag/{$model->slug}" : '/{lang}/explore',
                'entity_type' => 'tag',
                'entity_id' => $model->id,
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
                'title' => 'New Topic',
                'message' => $topicName,
                'icon' => 'Layers',
                'color' => 'indigo',
                'link' => $model->slug ? "/{lang}/topic/{$model->slug}" : '/{lang}/explore',
                'entity_type' => 'topic_category',
                'entity_id' => $model->id,
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
                'title' => 'New Form',
                'message' => $categoryName,
                'icon' => 'Layers',
                'color' => 'indigo',
                'link' => '/{lang}/poetry',
                'entity_type' => 'category',
                'entity_id' => $model->id,
                'entity_name' => $categoryName,
            ];
        }

        return null;
    }

    private function poetryTitle(Poetry $poetry): string
    {
        $sd = $poetry->translations->firstWhere('lang', 'sd')?->title;
        $any = $poetry->translations->first()?->title;

        $title = trim((string) ($sd ?: $any ?: ''));
        if ($title !== '') {
            return $title;
        }

        // Last resort: avoid showing raw slug if possible.
        return $poetry->poetry_slug
            ? str_replace('-', ' ', $poetry->poetry_slug)
            : 'Untitled poem';
    }

    private function poetDisplayName(?Poets $poet): ?string
    {
        if (!$poet) {
            return null;
        }

        $sd = $poet->all_details()->where('lang', 'sd')->value('poet_laqab')
            ?: $poet->all_details()->where('lang', 'sd')->value('poet_name');
        $any = $poet->all_details()->value('poet_laqab')
            ?: $poet->all_details()->value('poet_name');

        $name = trim((string) ($sd ?: $any ?: ''));
        if ($name !== '') {
            return $name;
        }

        return $poet->poet_slug
            ? str_replace('-', ' ', $poet->poet_slug)
            : null;
    }
}
