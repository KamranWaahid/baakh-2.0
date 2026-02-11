<?php

namespace App\Models;

use App\Traits\SQLiteTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PoetsDetail extends Model
{
    use SoftDeletes, SQLiteTrait;

    protected $table = 'poets_detail';

    protected $fillable = [
        'poet_id',
        'poet_name',
        'poet_laqab',
        'pen_name',
        'tagline',
        'poet_bio',
        'birth_place',
        'death_place',
        'lang',
    ];

    public function birthCity()
    {
        return $this->belongsTo(Cities::class, 'birth_place');
    }

    public function deathCity()
    {
        return $this->belongsTo(Cities::class, 'death_place');
    }

    public function poet()
    {
        return $this->belongsTo(Poets::class, 'poet_id');
    }

    // Generic relations removed because they were incorrect. 
    // We will use eager loading in helper methods instead.

    /**
     * Birth Places 
     * CityName, ProvinceName, CountryName
     * relation from birth_place
     */
    public function birthPlaceComplete()
    {
        $locale = app()->getLocale();
        $city = Cities::with([
            'details' => fn($q) => $q->where('lang', $locale),
            'province.details' => fn($q) => $q->where('lang', $locale),
            'province.country.details' => fn($q) => $q->where('lang', $locale)
        ])->find($this->birth_place);

        if (!$city)
            return ['cityName' => null, 'provinceName' => null, 'countryName' => null];

        return [
            'cityName' => $city->details->first()->city_name ?? null,
            'provinceName' => $city->province->details->first()->province_name ?? null,
            'countryName' => $city->province->country->details->first()->country_name ?? null,
        ];
    }

    public function deathPlaceComplete()
    {
        $locale = app()->getLocale();
        $city = Cities::with([
            'details' => fn($q) => $q->where('lang', $locale),
            'province.details' => fn($q) => $q->where('lang', $locale),
            'province.country.details' => fn($q) => $q->where('lang', $locale)
        ])->find($this->death_place);

        if (!$city)
            return ['cityName' => null, 'provinceName' => null, 'countryName' => null];

        return [
            'cityName' => $city->details->first()->city_name ?? null,
            'provinceName' => $city->province->details->first()->province_name ?? null,
            'countryName' => $city->province->country->details->first()->country_name ?? null,
        ];
    }


    protected static function booted()
    {
        static::created(function ($model) {
            $model->updatePoet($model->poet_id); // coming from SQLiteTrait
        });
        static::updated(function ($model) {
            $model->updatePoet($model->poet_id); // coming from SQLiteTrait
        });
        static::deleted(function ($model) {
            $model->updatePoet($model->poet_id); // coming from SQLiteTrait
        });
    }
}
