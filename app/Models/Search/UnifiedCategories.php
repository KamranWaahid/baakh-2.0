<?php

namespace App\Models\Search;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnifiedCategories extends Model
{
    protected $connection = 'sqlite';

    protected $table = 'unified_categories';

    protected $fillable = [
        'category_id',
        'slug',
        'gender',
        'cat_name',
        'cat_name_plural',
        'lang',
    ];



}
