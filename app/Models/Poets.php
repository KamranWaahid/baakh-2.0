<?php

namespace App\Models;

use App\Observers\PoetObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Poets extends Model
{
    use SoftDeletes;
    
  
    protected $fillable = [
        'poet_slug', 
        'poet_pic', 
        'date_of_birth', 
        'date_of_death',
        'visibility', 
        'is_featured', 
        'poet_tags'
    ];


    public function details()
    {
        return $this->hasOne(PoetsDetail::class, 'poet_id', 'id');
    }

    public function all_details()
    {
        return $this->hasMany(PoetsDetail::class, 'poet_id', 'id');
    }

    public function shortDetail() {
        return $this->hasOne(PoetsDetail::class, 'poet_id', 'id')->where('lang', 'sd');
    }

    public function getPoetLaqabAttribute()
    {
        return $this->hasOne(PoetsDetail::class, 'poet_id', 'id')->where('lang', app()->getLocale())->value('poet_laqab');
    }

    protected static function booted()
    {
        static::observe(PoetObserver::class);
    }
}
