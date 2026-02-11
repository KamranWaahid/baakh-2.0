<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SenseExample extends Model
{
    protected $fillable = ['sense_id', 'sentence', 'source', 'corpus_sentence_id'];

    public function sense()
    {
        return $this->belongsTo(Sense::class);
    }
}
