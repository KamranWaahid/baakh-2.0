<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Romanizer extends Model
{
    use SoftDeletes;

    protected $table = "baakh_roman_words";
    
    protected $fillable = ['user_id', 'word_sd', 'word_roman', 'approved'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
