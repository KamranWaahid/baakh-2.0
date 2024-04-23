<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryDetails extends Model
{
    use HasFactory;
    protected $table = "category_details";
    protected $fillable = [
        'cat_id',
        'cat_name',
        'cat_detail',
        'lang',
    ];

    public function main()
    {
        return $this->belongsTo(Categories::class, 'cat_id', 'id');
    }

    public function language(){
        return $this->belongsTo(Languages::class, 'lang', 'lang_code');
    }
}


