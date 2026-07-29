<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BandDetail extends Model
{
    protected $table = 'bands_detail';

    protected $fillable = [
        'band_id',
        'lang',
        'band_name',
        'tagline',
        'band_bio',
    ];

    public function band()
    {
        return $this->belongsTo(Band::class, 'band_id');
    }
}
