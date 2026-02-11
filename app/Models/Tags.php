<?php

namespace App\Models;

use App\Observers\TagObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Tags extends Model
{
    use SoftDeletes, Searchable;
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
        'type',
        'topic_category_id'
    ];

    public function details()
    {
        return $this->hasMany(TagDetail::class, 'tag_id');
    }

    public function topicCategory()
    {
        return $this->belongsTo(TopicCategory::class, 'topic_category_id');
    }

    public function language()
    {
        return $this->hasOne(Languages::class, 'lang_code', 'lang');
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray()
    {
        $array = $this->toArray();

        // Include translations
        $array['details'] = $this->details->map(function ($detail) {
            return [
                'lang' => $detail->lang,
                'name' => $detail->name,
            ];
        })->toArray();

        return $array;
    }

    protected static function booted()
    {
        static::observe(TagObserver::class);
    }
}
