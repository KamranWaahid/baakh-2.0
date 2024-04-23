<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'password',
        'whatsapp',
        'avatar',
        'name_sd'
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
        ->select('id','name')
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

}
