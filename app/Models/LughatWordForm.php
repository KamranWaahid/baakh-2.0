<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

/**
 * Surface / citation form in Baakh Lughat (may link to a lemma later).
 */
class LughatWordForm extends Model
{
    use HasPublicId;

    public const TYPE_UNANALYZED = 'unanalyzed';
    public const TYPE_LEMMA = 'lemma';
    public const TYPE_INFLECTED = 'inflected';
    public const TYPE_VARIANT = 'variant';
    public const TYPE_CLITIC = 'clitic';
    public const TYPE_MWU = 'mwu';

    public const STATUS_PENDING = 'pending';
    public const STATUS_LINKED = 'linked';
    public const STATUS_REJECTED = 'rejected';

    protected $table = 'lughat_word_forms';

    protected string $publicIdPrefix = 'blwf';

    protected $fillable = [
        'public_id',
        'lemma_id',
        'form',
        'normalized_form',
        'romanization',
        'form_type',
        'morph_features_json',
        'status',
        'confidence',
        'source',
    ];

    protected $casts = [
        'morph_features_json' => 'array',
        'confidence' => 'integer',
    ];

    public function lemma()
    {
        return $this->belongsTo(LughatLemma::class, 'lemma_id');
    }

    public function occurrences()
    {
        return $this->hasMany(LughatOccurrence::class, 'word_form_id');
    }

    public function inflections()
    {
        return $this->hasMany(LughatInflection::class, 'word_form_id');
    }
}
