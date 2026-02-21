<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CorpusStat extends Model
{
    protected $table = 'corpus_stats';
    protected $fillable = ['word', 'frequency'];
}
