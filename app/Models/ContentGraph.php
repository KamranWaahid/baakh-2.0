<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentGraph extends Model
{
    protected $table = 'content_graph';

    protected $fillable = [
        'source_id',
        'source_type',
        'target_id',
        'target_type',
        'relation_type',
        'relation_weight',
        'semantic_score',
    ];

    protected $casts = [
        'relation_weight' => 'decimal:3',
        'semantic_score' => 'decimal:3',
    ];

    /**
     * Get edges from a specific source entity.
     */
    public function scopeFrom($query, string $type, int $id)
    {
        return $query->where('source_type', $type)->where('source_id', $id);
    }

    /**
     * Get edges pointing to a specific target entity.
     */
    public function scopeTo($query, string $type, int $id)
    {
        return $query->where('target_type', $type)->where('target_id', $id);
    }

    /**
     * Get edges of a specific relation type.
     */
    public function scopeRelation($query, string $type)
    {
        return $query->where('relation_type', $type);
    }
}
