<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Poets extends Model
{
    use SoftDeletes;
    
  
    protected $fillable = [
        'poet_slug', 
        'poet_pic', 
        'date_of_birth', 
        'date_of_death',
        'visibility', 
        'is_featured', 
        'poet_tags'
    ];


    public function details()
    {
        return $this->hasOne(PoetsDetail::class, 'poet_id', 'id');
    }

    

     

}
