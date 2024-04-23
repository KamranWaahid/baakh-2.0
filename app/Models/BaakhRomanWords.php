<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BaakhRomanWords extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'word_sd',
        'word_roman',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
