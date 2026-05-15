<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class LemmaInflection extends Model
{
    use HasPublicId;

    protected string $publicIdPrefix = 'infl';

    protected $fillable = [
        'public_id',
        'lemma_id',
        'form',
        'romanization',
        'description',
        'source',
        'review_status',
    ];

    public function lemma()
    {
        return $this->belongsTo(Lemma::class);
    }
}
