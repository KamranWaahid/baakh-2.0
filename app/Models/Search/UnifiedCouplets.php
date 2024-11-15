<?php

namespace App\Models\Search;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnifiedCouplets extends Model
{
    protected $connection = 'slqite';

    protected $table = 'unified_couplets';

    protected $fillable = [
        'couplet_id',
        'poet_id',
        'poetry_id',
        'couplet_slug',
        'couplet_text',
        'lang'
    ];

    public function poet()
    {
        return $this->belongsTo(UnifiedPoets::class, 'poet_id', 'poet_id')
            ->where('lang', $this->lang);
    }

    public function poetry()
    {
        $this->belongsTo(UnifiedPoetry::class, 'poetry_id', 'poetry_id')->where('lang', $this->lang);
    }
}
