<?php

namespace App\Models\Search;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnifiedPoetry extends Model
{
    protected $connection = 'slqite';

    protected $table = 'unified_poetry';

    protected $fillable = ['word', 'correct'];
}
