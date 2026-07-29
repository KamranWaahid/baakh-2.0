<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class LughatInflection extends Model
{
    use HasPublicId;

    protected $table = 'lughat_inflections';

    protected string $publicIdPrefix = 'blinfl';

    protected $fillable = [
        'public_id',
        'lemma_id',
        'word_form_id',
        'form',
        'normalized_form',
        'romanization',
        'form_type',
        'gender',
        'number',
        'case_name',
        'person',
        'honorificity',
        'degree',
        'tense',
        'aspect',
        'mood',
        'voice',
        'polarity',
        'stem',
        'suffix',
        'analysis_json',
        'confidence',
        'description',
        'source',
        'review_status',
    ];

    protected $casts = [
        'analysis_json' => 'array',
        'confidence' => 'integer',
    ];

    public function lemma()
    {
        return $this->belongsTo(LughatLemma::class, 'lemma_id');
    }

    public function wordForm()
    {
        return $this->belongsTo(LughatWordForm::class, 'word_form_id');
    }
}
