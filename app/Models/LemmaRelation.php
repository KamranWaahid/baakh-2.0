<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LemmaRelation extends Model
{
    protected $table = 'lemma_relations';
    protected $fillable = ['lemma_id', 'relation_type', 'related_word', 'related_lemma_id'];

    public function lemma()
    {
        return $this->belongsTo(Lemma::class, 'lemma_id');
    }

    public function relatedLemma()
    {
        return $this->belongsTo(Lemma::class, 'related_lemma_id');
    }
}
