<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class LughatMorphology extends Model
{
    use HasPublicId;

    protected $table = 'lughat_morphologies';

    protected string $publicIdPrefix = 'blmorph';

    protected $fillable = [
        'public_id',
        'lemma_id',
        'root',
        'pattern',
        'gender',
        'number',
        'case',
        'aspect',
        'tense',
        'review_status',
    ];

    public function lemma()
    {
        return $this->belongsTo(LughatLemma::class, 'lemma_id');
    }
}
