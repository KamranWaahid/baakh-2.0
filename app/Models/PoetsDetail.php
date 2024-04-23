<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PoetsDetail extends Model
{
    use SoftDeletes;

    protected $table = 'poets_detail';

    protected $fillable = [
        'poet_id',
        'poet_name',
        'poet_laqab',
        'pen_name',
        'tagline',
        'poet_bio',
        'birth_place',
        'death_place',
        'lang',
    ];

    public function birthPlace()
    {
        return $this->belongsTo(Cities::class, 'birth_place');
    }

    public function deathPlace()
    {
        return $this->belongsTo(Cities::class, 'death_place');
    }

    public function poet()
    {
        return $this->belongsTo(Poets::class, 'poet_id');
    }
}
