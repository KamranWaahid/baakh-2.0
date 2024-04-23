<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Couplets extends Model
{
    use SoftDeletes;

    protected $table = "poetry_couplets";

    protected $fillable = [
        'poetry_id',
        'poet_id',
        'couplet_slug',
        'couplet_text',
        'couplet_tags',
        'lang'
    ];

    public function poetry()
    {
        return $this->belongsTo(Poetry::class, 'poetry_id');
    }

    public function poet()
    {
        return $this->belongsTo(Poets::class, 'poet_id');
    }
  
    public function language()
    {
        return $this->belongsTo(Languages::class, 'lang', 'lang_code');
    }

    public function info()
    {
        return $this->hasOne(PoetryTranslations::class, 'poetry_id', 'id');
    }
    

    public function media()
    {
        return $this->hasMany(Media::class, 'poetry_id');
    }

   
    /**
     * New For Poet Details Only
     */
    public function poet_details()
    {
        return $this->hasOne(PoetsDetail::class, 'poet_id', 'poet_id');
    }
 


    
}
