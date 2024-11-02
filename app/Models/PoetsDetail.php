<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PoetsDetail extends Model
{
    use SoftDeletes;

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

    public function birthPlace()
    {
        return $this->belongsTo(Cities::class, 'birth_place');
    }

    public function deathPlace()
    {
        return $this->belongsTo(Cities::class, 'death_place');
    }

    public function poet()
    {
        return $this->belongsTo(Poets::class, 'poet_id');
    }

    public function birthCityCurrentLang()
    {
        return $this->belongsTo(Cities::class, 'birth_place')
                    ->where('lang', app()->getLocale());
    }

    public function deathCityCurrentLang()
    {
        return $this->belongsTo(Cities::class, 'death_place')
                    ->where('lang', app()->getLocale());
    }

    /**
     * Birth Places 
     * CityName, ProvinceName, CountryName
     * relation from birth_place
     */
    public function birthPlaceComplete()
    {
        // Load birth city with related province and country, applying language filter
        $city = $this->birthCityCurrentLang()->with(['province' => function($query) {
            $query->where('lang', app()->getLocale())
                  ->with(['country' => function($query) {
                      $query->where('lang', app()->getLocale());
                  }]);
        }])->first();

        return [
            'cityName' => $city->city_name ?? null,
            'provinceName' => $city->province->province_name ?? null,
            'countryName' => $city->province->country->countryName ?? null,
        ];
    }

    public function deathPlaceComplete()
    {
        $city = $this->deathCityCurrentLang()->with(['province' => function($query) {
            $query->where('lang', app()->getLocale())
                  ->with(['country' => function($query) {
                      $query->where('lang', app()->getLocale());
                  }]);
        }])->first();

        return [
            'cityName' => $city->city_name ?? null,
            'provinceName' => $city->province->province_name ?? null,
            'countryName' => $city->province->country->countryName ?? null,
        ];
    }
}
