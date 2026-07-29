<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TalukaDetails extends Model
{
    protected $table = 'location_taluka_details';

    protected $fillable = [
        'taluka_id',
        'taluka_name',
        'lang',
    ];

    public function taluka()
    {
        return $this->belongsTo(Talukas::class, 'taluka_id');
    }
}
