<?php

namespace App\Models\Search;

use Illuminate\Database\Eloquent\Model;

class UnifiedPoetry extends Model
{
    protected $connection = 'sqlite';

    protected $table = 'unified_poetry';

    protected $fillable = ['poetry_id', 'category_id' , 'slug', 'poet_id', 'title', 'title_original' ,  'lang'];

    public function poet()
    {
        return $this->belongsTo(UnifiedPoets::class, 'poet_id', 'poet_id');
    }

    public function category()
    {
        return $this->belongsTo(UnifiedCategories::class, 'category_id', 'category_id');
    }

    public function category_name()
    {
        return $this->belongsTo(UnifiedCategories::class, 'category_id', 'category_id')
        ->pluck('cat_name');
    }

    public function getCategorySlugAttribute()
    {
        return $this->category()->slug ?? '';
    }
}
