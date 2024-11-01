<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Poetry extends Model
{
    use SoftDeletes;
    protected $table = 'poetry_main';

    protected $fillable = [
        'poet_id',
        'category_id',
        'user_id',
        'poetry_slug',
        'poetry_tags',
        'visibility',
        'is_featured',
        'content_style',
    ];

    public function getPoetLaqabAttribute() {
        return PoetsDetail::where('poet_id', $this->poet_id)
        ->where('lang', app()->getLocale())
        ->value('poet_laqab');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function poet()
    {
        return $this->belongsTo(Poets::class, 'poet_id');
    }

    public function category()
    {
        return $this->belongsTo(Categories::class, 'category_id');
    }
    
    public function language(){
        return $this->belongsTo(Languages::class, 'lang', 'lang_code');
    }

    public function couplets()
    {
        return $this->hasOne(Couplets::class, 'poetry_id');
    }

    public function all_couplets()
    {
        return $this->HasMany(Couplets::class, 'poetry_id', 'id');
    }

    public function translations() 
    {
        return $this->hasMany(PoetryTranslations::class, 'poetry_id', 'id');
    }

    public function info()
    {
        return $this->hasOne(PoetryTranslations::class, 'poetry_id', 'id');
    }
    

    public function media()
    {
        return $this->hasMany(Media::class, 'poetry_id');
    }

   
    /**
     * New For Poet Details Only
     */
    public function poet_details()
    {
        return $this->hasOne(PoetsDetail::class, 'poet_id', 'poet_id');
    }

    public function category_detail() {
        return $this->hasOne(CategoryDetails::class, 'cat_id', 'category_id');
    }

    // boot to add current user's ID as user_id
    

    protected static function booted()
    {
        static::creating(function ($poetry) {
            $poetry->user_id = Auth::id();
        });

        static::updating(function ($poetry) {
            if (empty($poetry->user_id)) {
                $poetry->user_id = Auth::id();
            }
        });
    }

}
