<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class LughatExpression extends Model
{
    use HasPublicId;

    public const TYPES = [
        'izafat',
        'compound',
        'collocation',
        'idiom',
        'metaphor',
        'fixed_phrase',
        'formulaic_phrase',
        'reduplicative',
        'name_or_title',
        'other',
    ];

    protected $table = 'lughat_expressions';

    protected string $publicIdPrefix = 'blexp';

    protected $fillable = [
        'public_id',
        'expression',
        'normalized_expression',
        'compact_search_key',
        'romanization',
        'expression_type',
        'definition_sd',
        'definition_en',
        'literal_gloss',
        'poetic_gloss',
        'register',
        'status',
        'confidence',
        'review_status',
        'metadata_json',
    ];

    protected $casts = [
        'metadata_json' => 'array',
        'confidence' => 'integer',
    ];

    public function components()
    {
        return $this->hasMany(LughatExpressionComponent::class, 'expression_id')->orderBy('position');
    }

    public function expressionOccurrences()
    {
        return $this->hasMany(LughatExpressionOccurrence::class, 'expression_id');
    }
}
