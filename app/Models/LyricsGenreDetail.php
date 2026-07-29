<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LyricsGenreDetail extends Model
{
    protected $table = 'lyrics_genre_details';

    protected $fillable = [
        'lyrics_genre_id',
        'lang',
        'name',
    ];

    public function genre()
    {
        return $this->belongsTo(LyricsGenre::class, 'lyrics_genre_id');
    }
}
