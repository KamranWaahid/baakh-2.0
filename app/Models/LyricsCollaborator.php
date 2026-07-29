<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LyricsCollaborator extends Model
{
    protected $table = 'lyrics_collaborators';

    protected $fillable = [
        'lyrics_id',
        'collaborator_type',
        'collaborator_id',
        'role',
        'sort_order',
    ];

    public function lyrics()
    {
        return $this->belongsTo(Lyrics::class, 'lyrics_id');
    }
}
