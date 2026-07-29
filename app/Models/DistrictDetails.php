<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DistrictDetails extends Model
{
    protected $table = 'location_district_details';

    protected $fillable = [
        'district_id',
        'district_name',
        'lang',
    ];

    public function district()
    {
        return $this->belongsTo(Districts::class, 'district_id');
    }
}
