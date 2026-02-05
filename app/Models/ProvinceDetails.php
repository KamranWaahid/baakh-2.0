<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProvinceDetails extends Model
{
    use HasFactory;

    protected $table = 'location_province_details';

    protected $fillable = [
        'province_id',
        'province_name',
        'lang',
    ];

    public function province()
    {
        return $this->belongsTo(Provinces::class, 'province_id');
    }
}
