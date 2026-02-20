<?php

namespace App\Models;

use App\Observers\PoetObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Poets extends Model
{
    use SoftDeletes, Searchable;


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

    public function shortDetail()
    {
        return $this->hasOne(PoetsDetail::class, 'poet_id', 'id')->where('lang', 'sd');
    }

    public function getPoetLaqabAttribute()
    {
        return $this->hasOne(PoetsDetail::class, 'poet_id', 'id')->where('lang', app()->getLocale())->value('poet_laqab');
    }

    public function poetry()
    {
        return $this->hasMany(Poetry::class, 'poet_id');
    }

    public function books()
    {
        return $this->hasMany(PoetBook::class, 'poet_id');
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray()
    {
        $array = $this->toArray();

        // Add all details (translations) to the search index
        $details = $this->all_details->map(function ($detail) {
            return [
                'lang' => $detail->lang,
                'poet_name' => $detail->poet_name,
                'poet_laqab' => $detail->poet_laqab,
                'poet_bio' => $detail->poet_bio,
            ];
        })->toArray();

        $array['details'] = $details;
        $array['poet_tags'] = $this->poet_tags;

        return $array;
    }

    protected static function booted()
    {
        static::observe(PoetObserver::class);
    }
}
