<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Lyrics extends Model
{
    use SoftDeletes;

    protected $table = 'lyrics';

    protected $fillable = [
        'singer_id',
        'band_id',
        'genre_id',
        'poetry_id',
        'user_id',
        'lyrics_slug',
        'lyrics_tags',
        'visibility',
        'is_featured',
        'content_style',
        'music_url',
        'music_title',
        'music_type',
        'listen_links',
        'cover_image',
    ];

    protected $casts = [
        'visibility' => 'boolean',
        'is_featured' => 'boolean',
        'lyrics_tags' => 'array',
        'listen_links' => 'array',
    ];

    public function singer()
    {
        return $this->belongsTo(Singer::class, 'singer_id');
    }

    public function band()
    {
        return $this->belongsTo(Band::class, 'band_id');
    }

    public function collaborators()
    {
        return $this->hasMany(LyricsCollaborator::class, 'lyrics_id')->orderBy('sort_order');
    }

    public function genre()
    {
        return $this->belongsTo(LyricsGenre::class, 'genre_id');
    }

    public function poetry()
    {
        return $this->belongsTo(Poetry::class, 'poetry_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function translations()
    {
        return $this->hasMany(LyricsTranslation::class, 'lyrics_id', 'id');
    }

    public function info()
    {
        return $this->hasOne(LyricsTranslation::class, 'lyrics_id', 'id');
    }

    public function parts()
    {
        return $this->hasMany(LyricsPart::class, 'lyrics_id')->orderBy('sort_order');
    }

    protected static function booted()
    {
        static::creating(function ($lyrics) {
            if (empty($lyrics->user_id)) {
                $lyrics->user_id = Auth::id();
            }
        });
    }
}
