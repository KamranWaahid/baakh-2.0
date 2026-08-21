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
        'lookup_base',
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
        return DictionaryText::normalizeForIdentity($lemma);
    }

    public static function defaultLookupBase(string $lemma): string
    {
        return DictionaryText::lookupBase($lemma);
    }

    /**
     * Another headword that would collide with $incomingLemma (unique key or same lookup_base).
     * رُئيندي and unmarked رئيندي are the same word when only one catalog row exists.
     */
    public static function findConflictingHeadword(
        int $exceptId,
        string $incomingLemma,
        ?string $incomingNormalized = null,
        ?string $language = 'sd',
        ?int $homograph = 1
    ): ?self {
        $incomingLemma = trim($incomingLemma);
        if ($incomingLemma === '') {
            return null;
        }

        $identity = DictionaryText::normalizeForIdentity($incomingLemma);
        $normalizedCandidates = [];
        foreach ([$identity, $incomingNormalized] as $value) {
            if (!is_string($value)) {
                continue;
            }
            $norm = DictionaryText::normalizeForIdentity($value);
            if ($norm !== '') {
                $normalizedCandidates[] = $norm;
            }
        }
        $normalizedCandidates = array_values(array_unique($normalizedCandidates));

        $query = static::query()->whereKeyNot($exceptId);
        if (filled($language) && \Illuminate\Support\Facades\Schema::hasColumn((new static)->getTable(), 'language')) {
            $query->where('language', $language);
        }

        $exact = (clone $query)->where(function ($q) use ($incomingLemma, $normalizedCandidates) {
            $q->whereRaw(DictionaryText::binaryEquals('lemma'), [$incomingLemma]);
            foreach ($normalizedCandidates as $norm) {
                $q->orWhereRaw(DictionaryText::binaryEquals('normalized_lemma'), [$norm]);
            }
        });
        if ($homograph && \Illuminate\Support\Facades\Schema::hasColumn((new static)->getTable(), 'homograph_number')) {
            $exact->where('homograph_number', $homograph);
        }
        $found = $exact->orderBy('id')->first();
        if ($found) {
            return $found;
        }

        $matches = static::findByLookupBase(DictionaryText::lookupBase($incomingLemma), 8)
            ->where('id', '!=', $exceptId)
            ->values();

        if ($matches->count() === 1) {
            return $matches->first();
        }

        if ($matches->count() > 1 && DictionaryText::hasDiacritics($incomingLemma)) {
            $unmarked = $matches->filter(function (self $row) {
                return !DictionaryText::hasDiacritics((string) $row->lemma)
                    && !DictionaryText::hasDiacritics((string) ($row->normalized_lemma ?? ''));
            })->values();
            if ($unmarked->count() === 1) {
                return $unmarked->first();
            }
        }

        return null;
    }

    /**
     * Lemmas whose stripped headword equals $base (ڪھي ↔ ڪَھي).
     * Prefers the indexed lookup_base column; falls back to SQL airab-strip.
     *
     * @return \Illuminate\Support\Collection<int, static>
     */
    public static function findByLookupBase(string $base, int $limit = 8)
    {
        $base = DictionaryText::lookupBase($base);
        if ($base === '') {
            return collect();
        }

        $query = static::query()->orderBy('homograph_number');

        if (\Illuminate\Support\Facades\Schema::hasColumn((new static)->getTable(), 'lookup_base')) {
            $indexed = (clone $query)->where('lookup_base', $base)->limit($limit)->get();
            if ($indexed->isNotEmpty()) {
                return $indexed;
            }
        }

        $strippedLemma = DictionaryText::sqlLookupBase('lemma');

        return $query->where(function ($q) use ($base, $strippedLemma) {
            $q->where('normalized_lemma', $base)
                ->orWhereRaw("{$strippedLemma} = ?", [$base]);
        })->limit($limit)->get();
    }
}
