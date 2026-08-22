<?php

namespace Tests\Unit;

use App\Services\StaticCacheService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StaticCacheServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_get_with_ttl_rejects_legacy_payloads_without_cached_at(): void
    {
        $cache = app(StaticCacheService::class);
        $cache->set('feed_page_1_sd', [
            ['id' => 1, 'slug' => 'old-poem'],
        ]);

        $this->assertNull($cache->get('feed_page_1_sd', StaticCacheService::FEED_TTL_SECONDS));
        $this->assertNull($cache->getFeedPage('sd'));
    }

    public function test_feed_page_expires_after_ttl(): void
    {
        $cache = app(StaticCacheService::class);
        $cache->set('feed_page_1_en', [
            'cached_at' => time() - StaticCacheService::FEED_TTL_SECONDS - 10,
            'data' => [['id' => 9, 'slug' => 'stale']],
            'current_page' => 1,
            'last_page' => 4,
            'total' => 40,
        ]);

        $this->assertNull($cache->getFeedPage('en'));
    }

    public function test_put_feed_page_is_readable_within_ttl(): void
    {
        $cache = app(StaticCacheService::class);
        $cache->putFeedPage('sd', [
            ['id' => 12, 'slug' => 'fresh-poem'],
        ], 3, 22);

        $payload = $cache->getFeedPage('sd');
        $this->assertNotNull($payload);
        $this->assertSame('fresh-poem', $payload['data'][0]['slug']);
        $this->assertSame(3, $payload['last_page']);
        $this->assertSame(22, $payload['total']);
    }
}
