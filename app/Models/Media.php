<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Media extends Model
{
    use SoftDeletes;
    protected $table = 'baakh_media';
    protected $fillable = [
        'media_type',
        'media_title',
        'media_url',
        'poetry_id',
        'is_visible',
        'lang',
    ];

    public function poetry()
    {
        return $this->belongsTo(Poetry::class);
    }
}
