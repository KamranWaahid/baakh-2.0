<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class StaticCacheService
{
    protected $disk = 'public';
    protected $baseDir = 'static_cache/';

    /**
     * Get data from cache or null if not exists/expired
     */
    public function get(string $key)
    {
        $path = $this->getPath($key);

        if (Storage::disk($this->disk)->exists($path)) {
            $content = Storage::disk($this->disk)->get($path);
            return json_decode($content, true);
        }

        return null;
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
