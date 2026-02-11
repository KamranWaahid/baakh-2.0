<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class CorpusSentence extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'sentence',
        'source',
        'category',
        'tokens',
        'token_count',
        'external_id',
    ];

    protected $casts = [
        'tokens' => 'array',
    ];

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'sentence' => $this->sentence,
            'source' => $this->source,
            'category' => $this->category,
        ];
    }
}
