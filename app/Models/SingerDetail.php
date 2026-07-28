<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SingerDetail extends Model
{
    protected $table = 'singers_detail';

    protected $fillable = [
        'singer_id',
        'lang',
        'singer_name',
        'singer_laqab',
        'tagline',
        'birth_place',
        'death_place',
        'singer_bio',
    ];

    public function singer()
    {
        return $this->belongsTo(Singer::class, 'singer_id');
    }
}
