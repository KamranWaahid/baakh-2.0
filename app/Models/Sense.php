<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class Sense extends Model
{
    use HasPublicId;

    protected string $publicIdPrefix = 'sen';

    protected $fillable = [
        'public_id',
        'lemma_id',
        'lexical_id',
        'entry_id',
        'sense_order',
        'definition',
        'definition_en',
        'english_equivalents',
        'definition_sd',
        'short_gloss',
        'full_definition',
        'usage_notes',
        'usage_label',
        'part_of_speech',
        'word_variant',
        'domain',
        'register',
        'dialect',
        'confidence',
        'language_direction',
        'source_dictionary',
        'source',
        'source_entry_id',
        'publisher',
        'license',
        'import_version',
        'normalized_definition',
        'extra',
        'status',
        'review_status',
    ];

    protected $casts = [
        'confidence' => 'integer',
        'sense_order' => 'integer',
        'english_equivalents' => 'array',
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
