<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MobileDevice extends Model
{
    public const PLATFORMS = ['android', 'ios'];

    public const PROVIDERS = ['fcm', 'apns'];

    protected $fillable = [
        'user_id',
        'platform',
        'provider',
        'token',
        'device_id',
        'app_version',
        'locale',
        'push_enabled',
        'last_seen_at',
    ];

    protected $casts = [
        'push_enabled' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(MobileNotificationReceipt::class);
    }

    public function scopePushable($query)
    {
        return $query->where('push_enabled', true);
    }
}
