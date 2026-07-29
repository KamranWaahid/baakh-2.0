<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use App\Support\DictionaryText;
use Illuminate\Database\Eloquent\Model;

/**
 * Baakh Lughat headword — poetic dictionary (separate from general Lemma).
 */
class LughatLemma extends Model
{
    use HasPublicId;

    public const COMPLETION_PENDING = 'pending';
    public const COMPLETION_COMPLETE = 'complete';

    protected $table = 'lughat_lemmas';

    protected string $publicIdPrefix = 'blug';

    protected $fillable = [
        'public_id',
        'lemma',
        'normalized_lemma',
        'homograph_number',
        'language',
        'transliteration',
        'romanization_status',
        'ipa',
        'phonetic',
        'pronunciation_simple',
        'audio_url',
        'syllabification',
        'pos',
        'etymology',
        'notes',
        'source_confidence',
        'search_keywords_json',
        'metadata_json',
        'frequency',
        'token_frequency',
        'poem_frequency',
        'couplet_frequency',
        'status',
        'completion_status',
        'completed_at',
        'completed_by',
        'completion_notes',
        'completion_score',
        'checklist_json',
        'variants_reviewed',
        'examples_reviewed',
        'morphology_reviewed',
        'pronunciation_reviewed',
        'poetry_id',
        'couplet_id',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'checklist_json' => 'array',
        'variants_reviewed' => 'boolean',
        'examples_reviewed' => 'boolean',
        'morphology_reviewed' => 'boolean',
        'pronunciation_reviewed' => 'boolean',
        'completion_score' => 'integer',
        'source_confidence' => 'float',
        'search_keywords_json' => 'array',
        'metadata_json' => 'array',
        'homograph_number' => 'integer',
        'token_frequency' => 'integer',
        'poem_frequency' => 'integer',
        'couplet_frequency' => 'integer',
    ];

    public function senses()
    {
        return $this->hasMany(LughatSense::class, 'lemma_id')->orderBy('sense_order')->orderBy('id');
    }

    public function morphology()
    {
        return $this->hasOne(LughatMorphology::class, 'lemma_id');
    }

    public function variants()
    {
        return $this->hasMany(LughatVariant::class, 'lemma_id');
    }

    public function lemmaRelations()
    {
        return $this->hasMany(LughatRelation::class, 'lemma_id');
    }

    public function inflections()
    {
        return $this->hasMany(LughatInflection::class, 'lemma_id')->orderBy('id');
    }

    public function wordForms()
    {
        return $this->hasMany(LughatWordForm::class, 'lemma_id');
    }

    public function occurrences()
    {
        return $this->hasMany(LughatOccurrence::class, 'lemma_id');
    }

    public function idiomaticExpressions()
    {
        return $this->hasMany(LughatIdiomaticExpression::class, 'lemma_id')->orderBy('id');
    }

    /**
     * Multiword poetic expressions where this lemma is a component (e.g. جام in جامِ محبت).
     */
    public function expressionComponents()
    {
        return $this->hasMany(LughatExpressionComponent::class, 'lemma_id');
    }

    public function expressions()
    {
        return $this->belongsToMany(
            LughatExpression::class,
            'lughat_expression_components',
            'lemma_id',
            'expression_id'
        )->distinct();
    }

    public function poetry()
    {
        return $this->belongsTo(Poetry::class, 'poetry_id');
    }

    public function couplet()
    {
        return $this->belongsTo(Couplets::class, 'couplet_id');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function scopeCompletionStatus($query, ?string $status)
    {
        if (in_array($status, [self::COMPLETION_PENDING, self::COMPLETION_COMPLETE], true)) {
            $query->where('completion_status', $status);
        }

        return $query;
    }

    public function scopeComplete($query)
    {
        return $query->where('completion_status', self::COMPLETION_COMPLETE);
    }

    public function scopePendingCompletion($query)
    {
        return $query->where('completion_status', self::COMPLETION_PENDING);
    }

    public static function defaultNormalizedLemma(string $lemma): string
    {
        return DictionaryText::normalizeForLookup($lemma);
    }
}
