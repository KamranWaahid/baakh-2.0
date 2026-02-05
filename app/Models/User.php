<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @method \Illuminate\Database\Eloquent\Relations\MorphMany likes()
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'phone',
        'role',
        'password',
        'whatsapp',
        'avatar',
        'name_sd',
        'status',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function likesDislikes()
    {
        return $this->hasMany(LikeDislike::class);
    }

    public function comments()
    {
        return $this->hasMany(UserComments::class, 'user_id', 'id');
    }


    public static function getPermissionGroups()
    {
        $permission_groups = DB::table('permissions')->select('group_name')
            ->groupBy('group_name')->get();
        return $permission_groups;
    }

    public static function getPermissionsByGroupName($group)
    {
        $permission_groups = DB::table('permissions')
            ->where('group_name', $group)
            ->select('id', 'name')
            ->get();
        return $permission_groups;
    }

    public static function roleHasPermissions($role, $permissions)
    {
        $hasPermission = true;
        foreach ($permissions as $perm) {
            if (!$role->hasPermissionTo($perm->name)) {
                $hasPermission = false;
            }
        }
        return $hasPermission;
    }

    /**
     * Get all likes for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Like[] $likes
     */
    public function likes()
    {
        return $this->hasMany(UserLikes::class);
    }

    /**
     * @method bool hasLiked(mixed $likeable) Check if the user has liked a given likeable entity
     */
    public function hasLiked($likeable)
    {
        return $this->likes()
            ->where('likeable_id', $likeable->id)
            ->where('likeable_type', get_class($likeable))
            ->exists();
    }

    /**
     * Get teams where user is owner.
     */
    public function ownedTeams()
    {
        return $this->hasMany(Team::class, 'owner_id');
    }

    /**
     * Get all teams the user belongs to.
     */
    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_members')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    /**
     * Get team memberships.
     */
    public function teamMemberships()
    {
        return $this->hasMany(TeamMember::class);
    }

    /**
     * Get activity logs for this user.
     */
    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Update last login timestamp.
     */
    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }
}
