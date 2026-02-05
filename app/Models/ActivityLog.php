<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    const UPDATED_AT = null; // Only use created_at

    protected $fillable = [
        'user_id',
        'team_id',
        'action',
        'description',
        'ip_address',
        'user_agent',
        'properties',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the team this activity belongs to.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Log an activity.
     */
    public static function log(string $action, ?User $user = null, ?Team $team = null, ?string $description = null, array $properties = []): self
    {
        return self::create([
            'user_id' => $user?->id,
            'team_id' => $team?->id,
            'action' => $action,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'properties' => $properties,
        ]);
    }
}
