<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminNotification extends Model
{
    protected $fillable = [
        'type',
        'title',
        'message',
        'icon',
        'color',
        'link',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Create a notification from an activity log action.
     */
    public static function fromActivity(string $action, string $description, ?string $userName = null): ?self
    {
        $map = self::getActionMap();

        // Find matching notification type
        foreach ($map as $pattern => $config) {
            if (str_contains($action, $pattern)) {
                $title = str_replace('{user}', $userName ?? 'System', $config['title']);
                $message = $description ?: $title;

                return self::create([
                    'type' => $action,
                    'title' => $title,
                    'message' => $message,
                    'icon' => $config['icon'],
                    'color' => $config['color'],
                    'link' => $config['link'] ?? null,
                    'data' => ['user' => $userName, 'action' => $action],
                ]);
            }
        }

        return null;
    }

    /**
     * Map activity actions to notification metadata.
     */
    private static function getActionMap(): array
    {
        return [
            // Content
            'created_poetry' => ['title' => 'New Poetry Added', 'icon' => 'BookOpen', 'color' => 'blue', 'link' => '/admin/poetry'],
            'updated_poetry' => ['title' => 'Poetry Updated', 'icon' => 'BookOpen', 'color' => 'sky', 'link' => '/admin/poetry'],
            'deleted_poetry' => ['title' => 'Poetry Deleted', 'icon' => 'Trash2', 'color' => 'red', 'link' => '/admin/poetry'],

            // Poets
            'created_poet' => ['title' => 'New Poet Added', 'icon' => 'Feather', 'color' => 'purple', 'link' => '/admin/poets'],
            'updated_poet' => ['title' => 'Poet Updated', 'icon' => 'Feather', 'color' => 'violet', 'link' => '/admin/poets'],
            'deleted_poet' => ['title' => 'Poet Deleted', 'icon' => 'Trash2', 'color' => 'red', 'link' => '/admin/poets'],

            // Users
            'user_registered' => ['title' => 'New User Registered', 'icon' => 'UserPlus', 'color' => 'green', 'link' => '/admin/users'],
            'user_login' => ['title' => '{user} Logged In', 'icon' => 'LogIn', 'color' => 'gray'],
            'role_assigned' => ['title' => 'Role Assigned', 'icon' => 'Shield', 'color' => 'amber', 'link' => '/admin/roles'],

            // System
            'system_error' => ['title' => 'System Error Captured', 'icon' => 'Bug', 'color' => 'red', 'link' => '/admin/system/errors'],
            'system_error_updated' => ['title' => 'Error Status Updated', 'icon' => 'Bug', 'color' => 'orange', 'link' => '/admin/system/errors'],
            'system_errors_cleared' => ['title' => 'Errors Cleared', 'icon' => 'CheckCircle', 'color' => 'green', 'link' => '/admin/system/errors'],
            'system_errors_verified' => ['title' => 'Errors Verified', 'icon' => 'ShieldCheck', 'color' => 'emerald', 'link' => '/admin/system/errors'],

            // Languages
            'created_language' => ['title' => 'Language Added', 'icon' => 'Globe', 'color' => 'teal', 'link' => '/admin/languages'],
            'updated_language' => ['title' => 'Language Updated', 'icon' => 'Globe', 'color' => 'teal', 'link' => '/admin/languages'],
            'deleted_language' => ['title' => 'Language Deleted', 'icon' => 'Trash2', 'color' => 'red', 'link' => '/admin/languages'],

            // Categories / Tags
            'created_category' => ['title' => 'Category Created', 'icon' => 'Layers', 'color' => 'indigo', 'link' => '/admin/categories'],
            'created_tag' => ['title' => 'Tag Created', 'icon' => 'Tags', 'color' => 'cyan', 'link' => '/admin/tags'],

            // Mokhii
            'mokhii_crawl' => ['title' => 'Mokhii Crawl Complete', 'icon' => 'Bot', 'color' => 'fuchsia', 'link' => '/admin/mokhii'],
            'mokhii_fix' => ['title' => 'Mokhii Issues Fixed', 'icon' => 'Bot', 'color' => 'emerald', 'link' => '/admin/mokhii'],

            // Deployment
            'deployment' => ['title' => 'Deployment Event', 'icon' => 'Rocket', 'color' => 'blue', 'link' => '/admin/system/server'],

            // Feedback
            'feedback' => ['title' => 'New Feedback Received', 'icon' => 'MessageSquare', 'color' => 'amber', 'link' => '/admin/feedback'],
        ];
    }
}
