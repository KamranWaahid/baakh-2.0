<?php

namespace App\Models;

use App\Observers\TagObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tags extends Model
{
    use SoftDeletes;
    protected $table = "baakh_tags";

    const TYPES = ['Theme', 'Emotion', 'Time Layer', 'Occasion', 'Status'];

    const TYPE_LABELS = [
        'Theme' => 'موضوع',
        'Emotion' => 'جذبات',
        'Time Layer' => 'وقت جو پرت',
        'Occasion' => 'موقعو',
        'Status' => 'حالت',
    ];

    protected $fillable = [
        'slug',
        'type'
    ];

    public function details()
    {
        return $this->hasMany(TagDetail::class, 'tag_id');
    }

    public function language()
    {
        return $this->hasOne(Languages::class, 'lang_code', 'lang');
    }

    protected static function booted()
    {
        static::observe(TagObserver::class);
    }
}
