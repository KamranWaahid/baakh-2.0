<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Morphology extends Model
{
    protected $table = 'morphologies';
    protected $fillable = ['lemma_id', 'root', 'pattern', 'gender', 'number', 'case', 'aspect', 'tense'];

    public function lemma()
    {
        return $this->belongsTo(Lemma::class);
    }
}
