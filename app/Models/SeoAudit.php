<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoAudit extends Model
{
    protected $table = 'seo_audits';

    protected $fillable = [
        'url',
        'status_code',
        'response_time_ms',
        'has_h1',
        'has_meta_description',
        'meta_title',
        'meta_description',
        'has_schema',
        'schema_types',
        'broken_links',
        'issues',
        'score',
        'crawled_at',
    ];

    protected $casts = [
        'has_h1' => 'boolean',
        'has_meta_description' => 'boolean',
        'has_schema' => 'boolean',
        'schema_types' => 'array',
        'broken_links' => 'array',
        'issues' => 'array',
        'score' => 'decimal:1',
        'crawled_at' => 'datetime',
    ];

    /**
     * Get the most recent audit for a URL.
     */
    public function scopeForUrl($query, string $url)
    {
        return $query->where('url', $url)->latest('crawled_at');
    }

    /**
     * Get audits with critical issues.
     */
    public function scopeCritical($query)
    {
        return $query->where('score', '<', 50);
    }
}
