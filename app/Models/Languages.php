<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Languages extends Model
{
    protected $table = 'languages'; // Specify the table name
    
    protected $fillable = [
        'lang_title',
        'lang_code', // like 'sd', 'en', 'ur'
        'lang_dir', // direction 'rtl', 'ltr'
        'lang_folder', // folder name where language files are stored
        'is_default',
    ];
}
