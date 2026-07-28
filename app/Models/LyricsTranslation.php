<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LyricsTranslation extends Model
{
    protected $table = 'lyrics_translations';

    protected $fillable = [
        'lyrics_id',
        'lang',
        'title',
        'info',
        'source',
    ];

    public function lyrics()
    {
        return $this->belongsTo(Lyrics::class, 'lyrics_id');
    }
}
