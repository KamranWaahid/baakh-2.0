<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Talukas extends Model
{
    use SoftDeletes;

    protected $table = 'location_talukas';

    protected $fillable = [
        'user_id',
        'district_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function details()
    {
        return $this->hasMany(TalukaDetails::class, 'taluka_id');
    }

    public function district()
    {
        return $this->belongsTo(Districts::class, 'district_id');
    }

    public function cities()
    {
        return $this->hasMany(Cities::class, 'taluka_id');
    }
}
