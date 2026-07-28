<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Singer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'singer_slug',
        'singer_pic',
        'date_of_birth',
        'date_of_death',
        'visibility',
        'is_featured',
    ];

    protected $casts = [
        'visibility' => 'boolean',
        'is_featured' => 'boolean',
        'date_of_birth' => 'date',
        'date_of_death' => 'date',
    ];

    public function details()
    {
        return $this->hasOne(SingerDetail::class, 'singer_id', 'id');
    }

    public function allDetails()
    {
        return $this->hasMany(SingerDetail::class, 'singer_id', 'id');
    }

    public function lyrics()
    {
        return $this->hasMany(Lyrics::class, 'singer_id');
    }
}
