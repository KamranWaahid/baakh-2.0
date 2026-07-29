<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

/**
 * One poetry token occurrence — source of truth for provenance & frequency.
 */
class LughatOccurrence extends Model
{
    use HasPublicId;

    public const ANALYSIS_UNANALYZED = 'unanalyzed';
    public const ANALYSIS_LINKED = 'linked';
    public const ANALYSIS_AMBIGUOUS = 'ambiguous';
    public const ANALYSIS_REJECTED = 'rejected';

    public const TOKENIZATION_VERSION = 'sd_ws_v1';
    public const NORMALIZATION_VERSION = 'sd_lookup_v1';

    protected $table = 'lughat_occurrences';

    protected string $publicIdPrefix = 'bloc';

    protected $fillable = [
        'public_id',
        'lemma_id',
        'word_form_id',
        'inflection_id',
        'sense_id',
        'poetry_id',
        'couplet_id',
        'poet_id',
        'surface_form',
        'normalized_form',
        'token_index',
        'character_start',
        'character_end',
        'context_before',
        'context_after',
        'full_couplet_snapshot',
        'language',
        'has_diacritics',
        'tokenization_version',
        'normalization_version',
        'analysis_status',
        'analysis_confidence',
    ];

    protected $casts = [
        'has_diacritics' => 'boolean',
        'token_index' => 'integer',
        'character_start' => 'integer',
        'character_end' => 'integer',
        'analysis_confidence' => 'integer',
    ];

    public function lemma()
    {
        return $this->belongsTo(LughatLemma::class, 'lemma_id');
    }

    public function wordForm()
    {
        return $this->belongsTo(LughatWordForm::class, 'word_form_id');
    }

    public function inflection()
    {
        return $this->belongsTo(LughatInflection::class, 'inflection_id');
    }

    public function sense()
    {
        return $this->belongsTo(LughatSense::class, 'sense_id');
    }

    public function poetry()
    {
        return $this->belongsTo(Poetry::class, 'poetry_id');
    }

    public function couplet()
    {
        return $this->belongsTo(Couplets::class, 'couplet_id');
    }
}
