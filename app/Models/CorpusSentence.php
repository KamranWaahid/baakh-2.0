<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorpusSentence extends Model
{
    use HasFactory;

    protected $fillable = [
        'sentence',
        'source',
        'category',
        'tokens',
        'token_count',
        'external_id',
    ];

    protected $casts = [
        'tokens' => 'array',
    ];
}
