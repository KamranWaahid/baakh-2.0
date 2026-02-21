<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Lemma extends Model
{
    use Searchable;

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

    public function lemmaRelations()
    {
        return $this->hasMany(LemmaRelation::class, 'lemma_id');
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray()
    {
        $array = $this->toArray();

        // Include senses (definitions)
        $array['senses'] = $this->senses->map(function ($sense) {
            return [
                'definition' => $sense->definition,
                'domain' => $sense->domain,
            ];
        })->toArray();

        // Include variants
        $array['variants'] = $this->variants->pluck('variant')->toArray();

        return $array;
    }
}
