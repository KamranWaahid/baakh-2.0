<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class LughatExpressionComponent extends Model
{
    use HasPublicId;

    protected $table = 'lughat_expression_components';

    protected string $publicIdPrefix = 'blexc';

    protected $fillable = [
        'public_id',
        'expression_id',
        'position',
        'lemma_id',
        'word_form_id',
        'surface_form',
        'normalized_form',
        'connector',
        'role',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    public function expression()
    {
        return $this->belongsTo(LughatExpression::class, 'expression_id');
    }

    public function lemma()
    {
        return $this->belongsTo(LughatLemma::class, 'lemma_id');
    }

    public function wordForm()
    {
        return $this->belongsTo(LughatWordForm::class, 'word_form_id');
    }
}
