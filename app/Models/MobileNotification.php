<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MobileNotification extends Model
{
    public const TYPES = [
        'daily_verse',
        'new_poetry',
        'new_poet',
        'featured',
        'word_of_the_day',
        'new_lyrics',
        'app_update',
        'reminder',
        'announcement',
        'event',
        'bookmark_nudge',
    ];

    public const TYPE_LABELS = [
        'daily_verse' => 'Daily Verse',
        'new_poetry' => 'New Poetry',
        'new_poet' => 'New Poet',
        'featured' => 'Featured Pick',
        'word_of_the_day' => 'Word of the Day',
        'new_lyrics' => 'New Lyrics',
        'app_update' => 'App Update',
        'reminder' => 'Reading Reminder',
        'announcement' => 'Announcement',
        'event' => 'Literary Event',
        'bookmark_nudge' => 'Continue Reading',
    ];

    public const TYPE_META = [
        'daily_verse' => ['icon' => 'BookOpen', 'color' => 'amber'],
        'new_poetry' => ['icon' => 'Feather', 'color' => 'blue'],
        'new_poet' => ['icon' => 'UserPlus', 'color' => 'purple'],
        'featured' => ['icon' => 'Star', 'color' => 'gold'],
        'word_of_the_day' => ['icon' => 'Book', 'color' => 'teal'],
        'new_lyrics' => ['icon' => 'Music2', 'color' => 'rose'],
        'app_update' => ['icon' => 'Sparkles', 'color' => 'indigo'],
        'reminder' => ['icon' => 'Clock', 'color' => 'orange'],
        'announcement' => ['icon' => 'Megaphone', 'color' => 'sky'],
        'event' => ['icon' => 'Calendar', 'color' => 'violet'],
        'bookmark_nudge' => ['icon' => 'Bookmark', 'color' => 'cyan'],
    ];

    public const PLATFORMS = ['android', 'ios'];

    public const AUDIENCES = ['everyone', 'signed_in', 'guests'];

    public const STATUSES = ['draft', 'scheduled', 'published', 'archived'];

    public const PRIORITIES = ['normal', 'high'];

    public const RECURRENCES = ['once', 'daily', 'weekly'];

    protected $fillable = [
        'type',
        'title_sd',
        'title_en',
        'body_sd',
        'body_en',
        'cta_sd',
        'cta_en',
        'image_url',
        'icon',
        'color',
        'platforms',
        'audience',
        'audience_filter',
        'deep_link',
        'web_path',
        'linkable_type',
        'linkable_id',
        'priority',
        'status',
        'is_active',
        'schedule_at',
        'recurrence',
        'recurrence_time',
        'expires_at',
        'sent_at',
        'sent_count',
        'open_count',
        'data',
        'created_by',
    ];

    protected $casts = [
        'platforms' => 'array',
        'audience_filter' => 'array',
        'data' => 'array',
        'is_active' => 'boolean',
        'schedule_at' => 'datetime',
        'expires_at' => 'datetime',
        'sent_at' => 'datetime',
        'sent_count' => 'integer',
        'open_count' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(MobileNotificationReceipt::class);
    }

    public function scopeVisibleToApp($query, ?string $platform = null, ?string $audience = null)
    {
        $now = now();

        $query->where('is_active', true)
            ->whereIn('status', ['published', 'scheduled'])
            ->where(function ($q) use ($now) {
                $q->whereNull('schedule_at')->orWhere('schedule_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            });

        if (in_array($platform, self::PLATFORMS, true)) {
            $query->where('platforms', 'like', '%"'.$platform.'"%');
        }

        if ($audience === 'signed_in') {
            $query->whereIn('audience', ['everyone', 'signed_in']);
        } elseif ($audience === 'guests') {
            $query->whereIn('audience', ['everyone', 'guests']);
        }

        return $query;
    }

    public function localized(?string $lang = 'sd'): array
    {
        $lang = $lang === 'en' ? 'en' : 'sd';
        $fallbackLang = $lang === 'en' ? 'sd' : 'en';

        $title = $this->{"title_{$lang}"} ?: $this->{"title_{$fallbackLang}"};
        $body = $this->{"body_{$lang}"} ?: $this->{"body_{$fallbackLang}"};
        $cta = $this->{"cta_{$lang}"} ?: $this->{"cta_{$fallbackLang}"};

        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $title,
            'body' => $body,
            'cta' => $cta,
            'image_url' => $this->image_url,
            'icon' => $this->icon ?: (self::TYPE_META[$this->type]['icon'] ?? 'Bell'),
            'color' => $this->color ?: (self::TYPE_META[$this->type]['color'] ?? 'blue'),
            'platforms' => $this->platforms ?: self::PLATFORMS,
            'audience' => $this->audience,
            'deep_link' => $this->deep_link,
            'web_path' => $this->web_path,
            'priority' => $this->priority,
            'data' => $this->data,
            'created_at' => $this->created_at,
            'schedule_at' => $this->schedule_at,
        ];
    }

    public static function metaOptions(): array
    {
        return [
            'types' => collect(self::TYPES)->map(fn ($type) => [
                'value' => $type,
                'label' => self::TYPE_LABELS[$type] ?? $type,
                'icon' => self::TYPE_META[$type]['icon'] ?? 'Bell',
                'color' => self::TYPE_META[$type]['color'] ?? 'blue',
            ])->values(),
            'platforms' => self::PLATFORMS,
            'audiences' => self::AUDIENCES,
            'statuses' => self::STATUSES,
            'priorities' => self::PRIORITIES,
            'recurrences' => self::RECURRENCES,
        ];
    }
}
