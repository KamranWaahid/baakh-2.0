<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CountryDetails extends Model
{
    use HasFactory;

    protected $table = 'location_country_details';

    protected $fillable = [
        'country_id',
        'countryName',
        'countryDesc',
        'lang',
    ];

    public function country()
    {
        return $this->belongsTo(Countries::class, 'country_id');
    }
}
