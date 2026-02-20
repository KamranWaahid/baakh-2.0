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
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['completion_percentage', 'page_segments'];

    /**
     * Get completion percentage
     */
    public function getCompletionPercentageAttribute(): float
    {
        if ($this->total_pages <= 0)
            return 0;

        $completedPagesCount = PoetBookPage::where('book_id', $this->id)
            ->where('is_completed', true)
            ->count();

        return round(($completedPagesCount / $this->total_pages) * 100, 2);
    }

    /**
     * Get page segments for interactive progress bar
     */
    public function getPageSegmentsAttribute(): array
    {
        if ($this->total_pages <= 0)
            return [];

        $pages = PoetBookPage::where('book_id', $this->id)
            ->orderBy('page_number', 'asc')
            ->get();

        if ($pages->isEmpty()) {
            return [
                [
                    'start' => 1,
                    'end' => $this->total_pages,
                    'type' => 'pending',
                    'is_completed' => false,
                    'title' => null,
                    'count' => $this->total_pages,
                    'width_percent' => 100
                ]
            ];
        }

        $segments = [];
        $currentSegment = null;

        foreach ($pages as $page) {
            $pageData = [
                'type' => $page->type,
                'is_completed' => (bool) $page->is_completed,
                'title' => $page->title,
            ];

            if (!$currentSegment) {
                $currentSegment = array_merge($pageData, [
                    'start' => $page->page_number,
                    'end' => $page->page_number,
                    'count' => 1
                ]);
            } elseif (
                $currentSegment['type'] === $pageData['type'] &&
                $currentSegment['is_completed'] === $pageData['is_completed'] &&
                $currentSegment['title'] === $pageData['title']
            ) {
                $currentSegment['end'] = $page->page_number;
                $currentSegment['count']++;
            } else {
                $currentSegment['width_percent'] = round(($currentSegment['count'] / $this->total_pages) * 100, 4);
                $segments[] = $currentSegment;
                $currentSegment = array_merge($pageData, [
                    'start' => $page->page_number,
                    'end' => $page->page_number,
                    'count' => 1
                ]);
            }
        }

        if ($currentSegment) {
            $currentSegment['width_percent'] = round(($currentSegment['count'] / $this->total_pages) * 100, 4);
            $segments[] = $currentSegment;
        }

        return $segments;
    }
}
