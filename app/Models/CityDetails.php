<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CityDetails extends Model
{
    use HasFactory;

    protected $table = 'location_city_details';

    protected $fillable = [
        'city_id',
        'city_name',
        'lang',
    ];

    public function city()
    {
        return $this->belongsTo(Cities::class, 'city_id');
    }
}
