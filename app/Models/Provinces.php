<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Provinces extends Model
{
    use SoftDeletes;

    protected $table = "location_provinces";

    protected $fillable = [
        'user_id',
        'province_name',
        'country_id',
        'lang',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function country()
    {
        return $this->belongsTo(Countries::class);
    }

    public function cities()
    {
        return $this->hasMany(Cities::class);
    }
}
