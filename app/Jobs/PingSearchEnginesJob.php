<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PingSearchEnginesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 15;

    /**
     * Rate limit: max 1 ping per 10 minutes.
     */
    private const RATE_LIMIT_KEY = 'mokhii:search_ping_last';
    private const RATE_LIMIT_SECONDS = 600;

    public function handle(): void
    {
        // Rate limiting
        if (Cache::has(self::RATE_LIMIT_KEY)) {
            Log::info('Mokhii: Search engine ping rate-limited, skipping.');
            return;
        }

        $sitemapUrl = rtrim(config('app.url'), '/') . '/sitemap.xml';

        $endpoints = [
            'Google' => "https://www.google.com/ping?sitemap=" . urlencode($sitemapUrl),
            'Bing' => "https://www.bing.com/indexnow?url=" . urlencode($sitemapUrl),
        ];

        foreach ($endpoints as $engine => $url) {
            try {
                $response = Http::timeout(10)->get($url);
                Log::info("Mokhii: Pinged {$engine} — Status: {$response->status()}");
            } catch (\Exception $e) {
                Log::warning("Mokhii: Failed to ping {$engine} — " . $e->getMessage());
            }
        }

        Cache::put(self::RATE_LIMIT_KEY, true, self::RATE_LIMIT_SECONDS);
    }
}
