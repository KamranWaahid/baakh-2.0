<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PoetryTranslations extends Model
{
    use HasFactory;
    protected $fillable = ['poetry_id', 'title', 'info', 'source', 'lang'];

    public function all_couplets() : HasMany
    {
        return $this->HasMany(Couplets::class, 'poetry_id', 'poetry_id');
    }

    public function language() {
        return $this->hasOne(Languages::class, 'lang_code' , 'lang'); 
    }

}
