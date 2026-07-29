<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class LughatPoetrySenseAnnotation extends Model
{
    use HasPublicId;

    protected $table = 'lughat_poetry_sense_annotations';

    protected string $publicIdPrefix = 'blpsa';

    protected $fillable = [
        'public_id',
        'poetry_id',
        'couplet_id',
        'couplet_index',
        'token_index',
        'surface_form',
        'normalized_form',
        'lemma_id',
        'sense_id',
        'note',
        'promoted',
    ];

    protected $casts = [
        'couplet_index' => 'integer',
        'token_index' => 'integer',
        'promoted' => 'boolean',
    ];

    public function poetry()
    {
        return $this->belongsTo(Poetry::class, 'poetry_id');
    }

    public function couplet()
    {
        return $this->belongsTo(Couplets::class, 'couplet_id');
    }

    public function lemma()
    {
        return $this->belongsTo(LughatLemma::class, 'lemma_id');
    }

    public function sense()
    {
        return $this->belongsTo(LughatSense::class, 'sense_id');
    }
}
