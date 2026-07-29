<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Districts extends Model
{
    use SoftDeletes;

    protected $table = 'location_districts';

    protected $fillable = [
        'user_id',
        'province_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function details()
    {
        return $this->hasMany(DistrictDetails::class, 'district_id');
    }

    public function province()
    {
        return $this->belongsTo(Provinces::class, 'province_id');
    }

    public function talukas()
    {
        return $this->hasMany(Talukas::class, 'district_id');
    }

    public function cities()
    {
        return $this->hasMany(Cities::class, 'district_id');
    }
}
