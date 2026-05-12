<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class LemmaRelation extends Model
{
    use HasPublicId;

    protected $table = 'lemma_relations';
    protected string $publicIdPrefix = 'rel';
    protected $fillable = ['public_id', 'lemma_id', 'relation_type', 'related_word', 'related_lemma_id', 'source'];

    public function lemma()
    {
        return $this->belongsTo(Lemma::class, 'lemma_id');
    }

    public function relatedLemma()
    {
        return $this->belongsTo(Lemma::class, 'related_lemma_id');
    }
}
