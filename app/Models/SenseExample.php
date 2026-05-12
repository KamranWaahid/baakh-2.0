<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class SenseExample extends Model
{
    use HasPublicId;

    protected string $publicIdPrefix = 'ex';

    protected $fillable = [
        'public_id',
        'sense_id',
        'sentence',
        'translation',
        'source',
        'citation',
        'quality_flag',
        'review_status',
        'corpus_sentence_id',
    ];

    public function sense()
    {
        return $this->belongsTo(Sense::class);
    }
}
