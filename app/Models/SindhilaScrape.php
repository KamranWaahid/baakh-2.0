<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SindhilaScrape extends Model
{
    use HasFactory;

    protected $fillable = ['word', 'scraped_data', 'status'];

    protected $casts = [
        'scraped_data' => 'array',
    ];
}
