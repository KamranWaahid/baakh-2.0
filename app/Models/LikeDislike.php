<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LikeDislike extends Model
{
    use HasFactory;
    
    protected $table = "likes_dislikes";
    protected $fillable = [
        'likable_id',
        'likable_type',
        'is_like'
    ];
    public function likable()
    {
        return $this->morphTo();
    }

    public function couplets()
    {
        return $this->hasMany(Couplets::class, 'id', 'likable_id');
    }

    public function poetry()
    {
        return $this->hasMany(Poetry::class, 'id', 'likable_id');
    }

    public function tags()
    {
        return $this->hasMany(Tags::class, 'id', 'likable_id');
    }

    public function bundles()
    {
        return $this->hasMany(Bundles::class, 'id', 'likable_id');
    }

    public function coupletsBySlug()
    {
        return $this->hasMany(Couplets::class, 'couplet_slug', 'likable_id');
    }

    
 
}
