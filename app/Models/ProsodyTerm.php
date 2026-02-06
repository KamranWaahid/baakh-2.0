<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProsodyTerm extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title_sd',
        'title_en',
        'desc_sd',
        'desc_en',
        'tech_detail_sd',
        'tech_detail_en',
        'logic_type',
        'icon',
        'order',
    ];
}
