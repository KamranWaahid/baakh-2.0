<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoetBookProgress extends Model
{
    protected $table = 'poet_book_progress';

    protected $fillable = [
        'book_id',
        'last_page',
        'last_poetry_id',
        'last_couplet_id',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(PoetBook::class, 'book_id');
    }
}
