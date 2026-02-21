<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sense extends Model
{
    protected $fillable = ['lemma_id', 'definition', 'definition_en', 'definition_sd', 'domain', 'status'];

    public function lemma()
    {
        return $this->belongsTo(Lemma::class);
    }

    public function examples()
    {
        return $this->hasMany(SenseExample::class);
    }
}
