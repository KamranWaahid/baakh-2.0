<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class Variant extends Model
{
    use HasPublicId;

    protected $table = 'lemma_variants';
    protected string $publicIdPrefix = 'var';
    public const TYPES = ['dialectal', 'misspelling', 'historical', 'diacritic', 'spelling', 'normalized'];

    protected $fillable = ['public_id', 'lemma_id', 'variant', 'type', 'dialect', 'source', 'source_entry_id', 'review_status'];

    public function lemma()
    {
        return $this->belongsTo(Lemma::class);
    }
}
