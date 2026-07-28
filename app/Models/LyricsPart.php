<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LyricsPart extends Model
{
    use SoftDeletes;

    protected $table = 'lyrics_parts';

    protected $fillable = [
        'lyrics_id',
        'sort_order',
        'kind',
        'role',
        'relation',
        'poet_id',
        'poetry_id',
        'couplet_id',
        'source_lyrics_id',
        'source_part_id',
        'text_sd',
        'text_roman',
    ];

    public function lyrics()
    {
        return $this->belongsTo(Lyrics::class, 'lyrics_id');
    }

    public function poet()
    {
        return $this->belongsTo(Poets::class, 'poet_id');
    }

    public function poetry()
    {
        return $this->belongsTo(Poetry::class, 'poetry_id');
    }

    public function couplet()
    {
        return $this->belongsTo(Couplets::class, 'couplet_id');
    }

    public function sourceLyrics()
    {
        return $this->belongsTo(Lyrics::class, 'source_lyrics_id');
    }

    public function sourcePart()
    {
        return $this->belongsTo(self::class, 'source_part_id');
    }
}
