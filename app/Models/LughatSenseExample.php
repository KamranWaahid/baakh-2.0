<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class LughatSenseExample extends Model
{
    use HasPublicId;

    protected $table = 'lughat_sense_examples';

    protected string $publicIdPrefix = 'blex';

    protected $fillable = [
        'public_id',
        'sense_id',
        'example_type',
        'generated_by',
        'model',
        'prompt_version',
        'generated_at',
        'human_reviewed_at',
        'sentence',
        'romanization',
        'translation',
        'source',
        'citation',
        'quality_flag',
        'review_status',
        'poetry_id',
        'couplet_id',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'human_reviewed_at' => 'datetime',
    ];

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
