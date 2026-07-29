<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LyricsGenre extends Model
{
    protected $table = 'lyrics_genres';

    protected $fillable = [
        'slug',
        'sort_order',
        'visibility',
    ];

    protected $casts = [
        'visibility' => 'boolean',
    ];

    public function details()
    {
        return $this->hasMany(LyricsGenreDetail::class, 'lyrics_genre_id');
    }

    public function lyrics()
    {
        return $this->hasMany(Lyrics::class, 'genre_id');
    }
}
