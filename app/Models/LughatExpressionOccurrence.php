<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class LughatExpressionOccurrence extends Model
{
    use HasPublicId;

    protected $table = 'lughat_expression_occurrences';

    protected string $publicIdPrefix = 'blexo';

    protected $fillable = [
        'public_id',
        'expression_id',
        'poetry_id',
        'couplet_id',
        'start_token_index',
        'end_token_index',
        'surface_text',
        'normalized_text',
        'sense_id',
        'confidence',
        'detection_method',
        'review_status',
    ];

    protected $casts = [
        'start_token_index' => 'integer',
        'end_token_index' => 'integer',
        'confidence' => 'integer',
    ];

    public function expression()
    {
        return $this->belongsTo(LughatExpression::class, 'expression_id');
    }

    public function poetry()
    {
        return $this->belongsTo(Poetry::class, 'poetry_id');
    }

    public function couplet()
    {
        return $this->belongsTo(Couplets::class, 'couplet_id');
    }

    public function sense()
    {
        return $this->belongsTo(LughatSense::class, 'sense_id');
    }
}
