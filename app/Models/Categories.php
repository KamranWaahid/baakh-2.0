<?php

namespace App\Models;

use App\Observers\CategoryObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Categories extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'user_id',
        'slug',
        'is_featured',
        'content_style',
    ];

    // protected $appends = ['sindhi_name'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function poetry()
    {
        return $this->hasMany(Poetry::class, 'category_id');
    }

    public function details()
    {
        return $this->hasMany(CategoryDetails::class, 'cat_id');
    }

    public function detail()
    {
        return $this->hasOne(CategoryDetails::class, 'cat_id');
    }

    public function shortDetail() {
        return $this->hasOne(CategoryDetails::class, 'cat_id', 'id')->where('lang', 'sd');
    }

    public function getCategoryNameAttribute() {
        return $this->hasOne(CategoryDetails::class, 'cat_id', 'id')->where('lang', app()->getLocale())->value('cat_name');
    }

    protected static function booted()
    {
        static::observe(CategoryObserver::class);
    }
}
