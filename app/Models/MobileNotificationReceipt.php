<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobileNotificationReceipt extends Model
{
    public const STATUSES = ['pending', 'delivered', 'failed', 'opened'];

    protected $fillable = [
        'mobile_notification_id',
        'user_id',
        'mobile_device_id',
        'status',
        'sent_at',
        'read_at',
        'opened_at',
        'error',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
        'opened_at' => 'datetime',
    ];

    public function notification(): BelongsTo
    {
        return $this->belongsTo(MobileNotification::class, 'mobile_notification_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(MobileDevice::class, 'mobile_device_id');
    }
}
