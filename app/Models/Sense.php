<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sense extends Model
{
    protected $fillable = [
        'lemma_id',
        'lexical_id',
        'entry_id',
        'definition',
        'definition_en',
        'definition_sd',
        'part_of_speech',
        'word_variant',
        'domain',
        'language_direction',
        'source_dictionary',
        'normalized_definition',
        'extra',
        'status',
    ];

    public function lemma()
    {
        return $this->belongsTo(Lemma::class);
    }

    public function examples()
    {
        return $this->hasMany(SenseExample::class);
    }
}
