<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class PoetBook extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'poet_id',
        'slug',
        'title',
        'total_pages',
        'edition',
        'publisher',
        'published_year',
        'isbn',
        'cover_image',
        'notes',
        'visibility',
        'is_featured',
    ];

    public function poet(): BelongsTo
    {
        return $this->belongsTo(Poets::class, 'poet_id');
    }

    public function progress(): HasOne
    {
        return $this->hasOne(PoetBookProgress::class, 'book_id');
    }

    public function poetry(): HasMany
    {
        return $this->hasMany(Poetry::class, 'book_id');
    }

    public function couplets(): HasMany
    {
        return $this->hasMany(Couplets::class, 'book_id');
    }

    /**
     * Get completion percentage
     */
    public function getCompletionPercentageAttribute(): float
    {
        if ($this->total_pages <= 0)
            return 0;

        $lastPage = $this->progress ? $this->progress->last_page : 0;
        return round(($lastPage / $this->total_pages) * 100, 2);
    }
}
