<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sliders extends Model
{
    use SoftDeletes;
    
    protected $table = 'baakh_sliders';

    protected $fillable = [
        'title',
        'image',
        'link_url',
        'category',
        'visibility',
        'lang'
    ];

    public function language(){
        return $this->belongsTo(Languages::class, 'lang', 'lang_code');
    }
}
