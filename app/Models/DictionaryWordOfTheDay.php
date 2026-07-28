<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DictionaryWordOfTheDay extends Model
{
    public const STATUS_CURRENT = 'current';
    public const STATUS_SKIPPED = 'skipped';
    public const STATUS_COMPLETED = 'completed';

    protected $table = 'dictionary_word_of_the_day';

    protected $fillable = [
        'selection_date',
        'lemma_id',
        'status',
        'priority_score',
        'selected_by',
        'skipped_at',
        'completed_at',
    ];

    protected $casts = [
        'selection_date' => 'date',
        'skipped_at' => 'datetime',
        'completed_at' => 'datetime',
        'priority_score' => 'integer',
    ];

    public function lemma(): BelongsTo
    {
        return $this->belongsTo(Lemma::class);
    }

    public function selectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'selected_by');
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('selection_date', $date);
    }

    public function scopeCurrent($query)
    {
        return $query->where('status', self::STATUS_CURRENT);
    }
}
