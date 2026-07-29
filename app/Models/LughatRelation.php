<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class LughatRelation extends Model
{
    use HasPublicId;

    protected $table = 'lughat_relations';

    protected string $publicIdPrefix = 'blrel';

    protected $fillable = [
        'public_id',
        'lemma_id',
        'relation_type',
        'related_word',
        'romanization',
        'note',
        'gloss',
        'part_of_speech',
        'related_lemma_id',
        'source',
    ];

    public function lemma()
    {
        return $this->belongsTo(LughatLemma::class, 'lemma_id');
    }

    public function relatedLemma()
    {
        return $this->belongsTo(LughatLemma::class, 'related_lemma_id');
    }
}
