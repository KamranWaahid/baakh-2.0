<?php

namespace App\Models;

use App\Observers\TagObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tags extends Model
{
    use SoftDeletes;
    protected $table ="baakh_tags";
    protected $fillable = [
        'tag',
        'slug',
        'type',
        'lang'
    ];

    public function language()
    {
        return $this->hasOne(Languages::class, 'lang_code', 'lang');
    }

    protected static function booted()
    {
        static::observe(TagObserver::class);
    }
}
