<?php

namespace App\Models;
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

}
