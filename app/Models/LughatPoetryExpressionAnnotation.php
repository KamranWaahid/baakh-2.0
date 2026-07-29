<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class LughatPoetryExpressionAnnotation extends Model
{
    use HasPublicId;

    protected $table = 'lughat_poetry_expression_annotations';

    protected string $publicIdPrefix = 'blpea';

    protected $fillable = [
        'public_id',
        'poetry_id',
        'couplet_id',
        'couplet_index',
        'start_token_index',
        'end_token_index',
        'surface_text',
        'normalized_text',
        'expression_id',
        'expression_type',
        'note',
    ];

    protected $casts = [
        'couplet_index' => 'integer',
        'start_token_index' => 'integer',
        'end_token_index' => 'integer',
    ];

    public function poetry()
    {
        return $this->belongsTo(Poetry::class, 'poetry_id');
    }

    public function couplet()
    {
        return $this->belongsTo(Couplets::class, 'couplet_id');
    }

    public function expression()
    {
        return $this->belongsTo(LughatExpression::class, 'expression_id');
    }
}
