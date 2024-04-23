<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bundles extends Model
{
    use SoftDeletes;
    //protected $table = "poetry_bundles";
    protected $fillable = [
        'user_id',
        'slug',
        'title',
        'bundle_thumbnail',
        'bundle_cover',
        'bundle_layout',
        'description',
        'is_visible',
        'is_featured',
    ];

    public function reference()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(BundleItems::class, 'bundle_id');
    }

    public function translations()
    {
        return $this->hasMany(BundleTranslations::class, 'bundle_id');
    }

    /* public function couplet()
    {
        return $this->hasOne(BundleItems::class, 'bundle_id');
    } */


     // Set the user_id attribute to the ID of the logged-in user
     public static function boot()
     {
         parent::boot();
 
         static::creating(function ($bundle) {
             if (!isset($bundle->user_id)) {
                 $bundle->user_id = auth()->id();
             }
         });
     }
}
