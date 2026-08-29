<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class StaticCacheService
{
    public const FEED_TTL_SECONDS = 300;

    protected $disk = 'public';
    protected $baseDir = 'static_cache/';

    /**
     * Get data from cache or null if not exists/expired
     */
    public function get(string $key, ?int $maxAgeSeconds = null)
    {
        $path = $this->getPath($key);

        try {
            if (Storage::disk($this->disk)->exists($path)) {
                $content = Storage::disk($this->disk)->get($path);
                $decoded = json_decode($content, true);
                if ($maxAgeSeconds !== null && !$this->isFresh($decoded, $maxAgeSeconds)) {
                    return null;
                }

                return $decoded;
            }
        } catch (\Exception $e) {
            Log::warning("StaticCacheService: Failed to read cache for {$key}: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Homepage “For you” page-1 payload, or null when missing (and stale, unless allowed).
     *
     * @return array{data: list<array>, current_page: int, last_page: int, total: int}|null
     */
    public function getFeedPage(string $locale, bool $allowStale = false): ?array
    {
        $cached = $this->get("feed_page_1_{$locale}", $allowStale ? null : self::FEED_TTL_SECONDS);
        if (!is_array($cached) || !isset($cached['data']) || !is_array($cached['data']) || $cached['data'] === []) {
            return null;
        }

        return [
            'data' => array_values($cached['data']),
            'current_page' => (int) ($cached['current_page'] ?? 1),
            'last_page' => max(1, (int) ($cached['last_page'] ?? 1)),
            'total' => (int) ($cached['total'] ?? count($cached['data'])),
        ];
    }

    /**
     * Cached poets index, or null when missing.
     *
     * @return list<array>|null
     */
    public function getPoetsList(string $locale): ?array
    {
        $cached = $this->get("poets_list_{$locale}");
        if (!is_array($cached) || $cached === []) {
            return null;
        }

        return array_values($cached);
    }

    public function putFeedPage(string $locale, array $items, int $lastPage, int $total): void
    {
        $this->set("feed_page_1_{$locale}", [
            'cached_at' => time(),
            'data' => array_values($items),
            'current_page' => 1,
            'last_page' => max(1, $lastPage),
            'total' => max(0, $total),
        ]);
    }

    /**
     * @param  mixed  $decoded
     */
    protected function isFresh($decoded, int $maxAgeSeconds): bool
    {
        if (!is_array($decoded) || !isset($decoded['cached_at']) || !is_numeric($decoded['cached_at'])) {
            return false;
        }

        return (time() - (int) $decoded['cached_at']) <= $maxAgeSeconds;
    }

    /**
     * Set data to cache
     */
    public function set(string $key, $data)
    {
        $path = $this->getPath($key);
        $content = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        try {
            Storage::disk($this->disk)->put($path, $content);
            return true;
        } catch (\Exception $e) {
            Log::error("StaticCacheService: Failed to write cache for {$key}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Forget a specific cache key
     */
    public function forget(string $key)
    {
        $path = $this->getPath($key);
        if (Storage::disk($this->disk)->exists($path)) {
            return Storage::disk($this->disk)->delete($path);
        }
        return true;
    }

    /**
     * Clear all static cache
     */
    public function clear()
    {
        try {
            return Storage::disk($this->disk)->deleteDirectory($this->baseDir);
        } catch (\Exception $e) {
            Log::error("StaticCacheService: Failed to clear cache: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get full path for a key
     */
    protected function getPath(string $key): string
    {
        return $this->baseDir . $key . '.json';
    }
}
