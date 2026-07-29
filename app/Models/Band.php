<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Band extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'band_slug',
        'band_pic',
        'formed_year',
        'visibility',
        'is_featured',
        'listen_links',
    ];

    protected $casts = [
        'visibility' => 'boolean',
        'is_featured' => 'boolean',
        'formed_year' => 'integer',
        'listen_links' => 'array',
    ];

    public function details()
    {
        return $this->hasOne(BandDetail::class, 'band_id', 'id');
    }

    public function allDetails()
    {
        return $this->hasMany(BandDetail::class, 'band_id', 'id');
    }

    public function singers()
    {
        return $this->belongsToMany(Singer::class, 'band_singer')
            ->withPivot(['role', 'sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function lyrics()
    {
        return $this->hasMany(Lyrics::class, 'band_id');
    }
}
