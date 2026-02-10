<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TopicCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug'];

    public function poetry()
    {
        return $this->hasMany(Poetry::class);
    }
}
