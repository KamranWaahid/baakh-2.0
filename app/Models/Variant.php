<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class Variant extends Model
{
    use HasPublicId;

    protected $table = 'lemma_variants';
    protected string $publicIdPrefix = 'var';
    public const TYPES = [
        'dialectal',
        'misspelling',
        'historical',
        'diacritic',
        'spelling',
        'normalized',
        'short_vowel_variant',
        'fully_voweled_variant',
        'fatha_variant',
    ];

    protected $fillable = [
        'public_id',
        'lemma_id',
        'variant',
        'normalized_variant',
        'type',
        'romanization',
        'dialect',
        'note',
        'source',
        'source_entry_id',
        'review_status',
    ];

    public function lemma()
    {
        return $this->belongsTo(Lemma::class);
    }
}
