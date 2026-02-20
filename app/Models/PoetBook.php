<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\PoetBookPage;
use App\Models\Poetry;
use App\Models\Couplets;

class PoetBook extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'poet_id',
        'slug',
        'title',
        'title_sd',
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

    public function pages(): HasMany
    {
        return $this->hasMany(PoetBookPage::class, 'book_id');
    }

    /**
     * Get completion percentage
     */
    public function getCompletionPercentageAttribute(): float
    {
        if ($this->total_pages <= 0)
            return 0;

        // Count unique pages that are either marked as completed in poet_book_pages
        // OR have poetry/couplets linked to them.
        $completedPagesCount = PoetBookPage::where('book_id', $this->id)
            ->where('is_completed', true)
            ->count();

        // Also get unique pages from poetry/couplets that might not be in the pages table yet
        // This is a safety fallback for now, though eventually we want all pages to be synced.
        $poetryPages = Poetry::where('book_id', $this->id)
            ->get()
            ->flatMap(fn($p) => range($p->page_start, $p->page_end))
            ->unique();

        $coupletPages = Couplets::where('book_id', $this->id)
            ->get()
            ->flatMap(fn($c) => range($c->page_start, $c->page_end))
            ->unique();

        $digitizedPages = $poetryPages->concat($coupletPages)->unique();

        // Combine: count pages that are in the PoetBookPage table as completed 
        // PLUS any pages from digitized content that aren't explicitly in that table.
        // For simplicity for now, let's just use the PoetBookPage table as the source of truth
        // and ensure it's synced.

        return round(($completedPagesCount / $this->total_pages) * 100, 2);
    }
}
