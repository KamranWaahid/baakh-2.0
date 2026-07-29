<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class LughatIdiomaticExpression extends Model
{
    use HasPublicId;

    protected $table = 'lughat_idiomatic_expressions';

    protected string $publicIdPrefix = 'blidiom';

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
        return $this->belongsTo(LughatLemma::class, 'lemma_id');
    }
}
