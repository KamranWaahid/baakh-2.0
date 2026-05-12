<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class LemmaIdiomaticExpression extends Model
{
    use HasPublicId;

    protected string $publicIdPrefix = 'idiom';

    protected $fillable = [
        'public_id',
        'lemma_id',
        'phrase',
        'romanization',
        'english_gloss',
        'example_sindhi',
        'example_english',
        'source',
        'review_status',
    ];

    public function lemma()
    {
        return $this->belongsTo(Lemma::class);
    }
}
