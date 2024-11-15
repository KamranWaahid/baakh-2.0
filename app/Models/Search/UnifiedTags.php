<?php

namespace App\Models\Search;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnifiedTags extends Model
{
    protected $connection = 'slqite';

    protected $table = 'unified_tags';

    protected $fillable = [
        'id',
        'tag',
        'slug',
        'type',
        'lang',
    ];
}
