<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TagDetail extends Model
{
    use HasFactory;

    protected $table = 'baakh_tag_details';
    protected $fillable = ['tag_id', 'lang', 'name'];

    protected $touches = ['tag'];

    public function tag()
    {
        return $this->belongsTo(Tags::class, 'tag_id');
    }
}
