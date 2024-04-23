<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Countries extends Model
{
    use SoftDeletes;
    
    protected $table = "location_countries";

    protected $fillable = [
        'user_id',
        'countryName',
        'Abbreviation',
        'countryDesc',
        'Continent',
        'capital_city',
        'lang',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
