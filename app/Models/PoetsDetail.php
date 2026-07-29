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
        return $this->placeComplete($this->birth_place);
    }

    public function deathPlaceComplete()
    {
        return $this->placeComplete($this->death_place);
    }

    /**
     * Resolve city/province/country labels for a place id.
     * Must never throw — incomplete geo FKs or missing locale rows used to 500 poet SEO pages.
     */
    private function placeComplete(mixed $placeId): array
    {
        $empty = ['cityName' => null, 'provinceName' => null, 'countryName' => null];

        if ($placeId === null || $placeId === '' || $placeId === 0 || $placeId === '0') {
            return $empty;
        }

        try {
            $locale = app()->getLocale();
            $city = Cities::with([
                'details' => fn ($q) => $q->where('lang', $locale),
                'province.details' => fn ($q) => $q->where('lang', $locale),
                'province.country.details' => fn ($q) => $q->where('lang', $locale),
            ])->find($placeId);

            if (!$city) {
                return $empty;
            }

            return [
                'cityName' => $city->details->first()?->city_name,
                'provinceName' => $city->province?->details->first()?->province_name,
                'countryName' => $city->province?->country?->details->first()?->country_name,
            ];
        } catch (\Throwable) {
            return $empty;
        }
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
