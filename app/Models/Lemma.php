<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lemma extends Model
{
    protected $fillable = ['lemma', 'transliteration', 'pos', 'frequency', 'status'];

    public function senses()
    {
        return $this->hasMany(Sense::class);
    }

    public function morphology()
    {
        return $this->hasOne(Morphology::class);
    }

    public function variants()
    {
        return $this->hasMany(Variant::class);
    }
}
