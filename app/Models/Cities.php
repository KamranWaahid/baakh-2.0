<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cities extends Model
{
    use SoftDeletes;

    protected $table = "location_cities";
    protected $fillable = [
        'user_id',
        'geo_lat',
        'geo_long',
        'province_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function details()
    {
        return $this->hasMany(CityDetails::class, 'city_id');
    }

    public function province()
    {
        return $this->belongsTo(Provinces::class);
    }
}
