<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TopicCategoryDetail extends Model
{
    use HasFactory;

    protected $fillable = ['topic_category_id', 'lang', 'name'];

    protected $touches = ['topicCategory'];

    public function topicCategory()
    {
        return $this->belongsTo(TopicCategory::class);
    }
}
