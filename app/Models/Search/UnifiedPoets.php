<?php

namespace App\Models\Search;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnifiedPoets extends Model
{
    protected $connection = 'slqite';

    protected $table = 'unified_poets';

    protected $fillable = ['poet_id', 'poet_slug' , 'poet_name', 'poet_laqab', 'lang'];


}
