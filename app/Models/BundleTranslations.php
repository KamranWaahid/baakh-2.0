<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BundleTranslations extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['bundle_id', 'lang_code', 'title', 'description'];
}
