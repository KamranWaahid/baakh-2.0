<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Lemma extends Model
{
    use HasPublicId;
    use Searchable;

    public const COMPLETION_PENDING = 'pending';
    public const COMPLETION_COMPLETE = 'complete';

    protected string $publicIdPrefix = 'lem';

    protected $fillable = [
        'public_id',
        'lemma',
        'normalized_lemma',
        'transliteration',
        'ipa',
        'phonetic',
        'audio_url',
        'syllabification',
        'pos',
        'frequency',
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
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'checklist_json' => 'array',
        'variants_reviewed' => 'boolean',
        'examples_reviewed' => 'boolean',
        'morphology_reviewed' => 'boolean',
        'pronunciation_reviewed' => 'boolean',
        'completion_score' => 'integer',
    ];

    public function senses()
    {
        return $this->hasMany(Sense::class)->orderBy('sense_order')->orderBy('id');
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
        $array['completion_status'] = $this->completion_status;
        $array['completion_score'] = $this->completion_score;

        return $array;
    }
}
