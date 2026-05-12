<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class Morphology extends Model
{
    use HasPublicId;

    protected $table = 'morphologies';
    protected string $publicIdPrefix = 'morph';
    protected $fillable = ['public_id', 'lemma_id', 'root', 'pattern', 'gender', 'number', 'case', 'aspect', 'tense', 'review_status'];

    public function lemma()
    {
        return $this->belongsTo(Lemma::class);
    }
}
