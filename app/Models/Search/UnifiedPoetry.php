<?php

namespace App\Models\Search;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnifiedPoetry extends Model
{
    protected $connection = 'slqite';

    protected $table = 'unified_poetry';

    protected $fillable = ['poetry_id', 'category_id' , 'poet_id', 'title', 'lang'];

    public function poet()
    {
        $this->belongsTo(UnifiedPoets::class, 'poet_id', 'poet_id')->where('lang', $this->lang);
    }

    public function category()
    {
        $this->belongsTo(UnifiedCategories::class, 'category_id', 'category_id')->where('lang', $this->lang);
    }

    public function category_name()
    {
        $this->belongsTo(UnifiedCategories::class, 'category_id', 'category_id')->where('lang', $this->lang)
        ->pluck('cat_name');
    }
}
