<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Variant extends Model
{
    protected $table = 'lemma_variants';
    protected $fillable = ['lemma_id', 'variant', 'type', 'dialect'];

    public function lemma()
    {
        return $this->belongsTo(Lemma::class);
    }
}
