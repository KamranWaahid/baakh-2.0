<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class LughatVariant extends Model
{
    use HasPublicId;

    protected $table = 'lughat_variants';

    protected string $publicIdPrefix = 'blvar';

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
        return $this->belongsTo(LughatLemma::class, 'lemma_id');
    }
}
