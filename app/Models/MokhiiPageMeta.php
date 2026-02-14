<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MokhiiPageMeta extends Model
{
    protected $table = 'mokhii_page_meta';

    protected $fillable = [
        'url',
        'entity_id',
        'entity_type',
        'priority_score',
        'internal_link_count',
        'graph_weight',
        'freshness_score',
        'engagement_score',
        'suggested_meta',
        'canonical_url',
        'mokhii_fixes',
        'computed_at',
    ];

    protected $casts = [
        'priority_score' => 'decimal:4',
        'graph_weight' => 'decimal:3',
        'freshness_score' => 'decimal:3',
        'engagement_score' => 'decimal:3',
        'mokhii_fixes' => 'array',
        'computed_at' => 'datetime',
    ];

    /**
     * Get the linked entity (polymorphic by convention).
     */
    public function entity()
    {
        return $this->morphTo('entity', 'entity_type', 'entity_id');
    }

    /**
     * Compute priority using the Mokhii formula.
     */
    public function computePriority(): float
    {
        return round(
            ($this->freshness_score * 0.35) +
            (min($this->internal_link_count / 100, 1.0) * 0.25) +
            ($this->graph_weight * 0.25) +
            ($this->engagement_score * 0.15),
            4
        );
    }
}
