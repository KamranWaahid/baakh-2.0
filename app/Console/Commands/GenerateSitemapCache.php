<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GenerateSitemapCache extends Command
{
    protected $signature = 'sitemap:cache {--clear : Clear all sitemap caches without regenerating}';
    protected $description = 'Pre-warm (or clear) all sitemap caches for faster responses';

    public function handle(): int
    {
        // Clear existing caches first
        $this->info('Clearing existing sitemap caches...');
        $this->clearSitemapCaches();

        if ($this->option('clear')) {
            $this->info('✓ All sitemap caches cleared.');
            return self::SUCCESS;
        }

        $this->info('Warming sitemap caches...');

        $baseUrl = rtrim(config('app.url'), '/');

        $endpoints = [
            '/sitemap.xml',
            '/sitemap/pages.xml',
            '/sitemap/poets.xml',
            '/sitemap/poetry.xml',
            '/sitemap/couplets.xml',
            '/sitemap/categories.xml',
            '/sitemap/tags.xml',
            '/sitemap/topics.xml',
        ];

        $bar = $this->output->createProgressBar(count($endpoints));
        $bar->start();

        foreach ($endpoints as $endpoint) {
            try {
                Http::timeout(30)->get($baseUrl . $endpoint);
                $bar->advance();
            } catch (\Exception $e) {
                $this->newLine();
                $this->warn("  ⚠ Failed to warm {$endpoint}: " . $e->getMessage());
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('✓ Sitemap caches warmed successfully.');

        return self::SUCCESS;
    }

    private function clearSitemapCaches(): void
    {
        // Clear all known sitemap cache keys by pattern
        $prefixes = [
            'sitemap:index',
            'sitemap:pages',
            'sitemap:poets',
            'sitemap:poetry',
            'sitemap:couplets',
            'sitemap:categories',
            'sitemap:tags',
            'sitemap:topics',
        ];

        foreach ($prefixes as $key) {
            Cache::forget($key);
        }

        // Also clear monthly caches (brute-force reasonable year range)
        $currentYear = (int) date('Y');
        for ($year = 2020; $year <= $currentYear + 1; $year++) {
            for ($month = 1; $month <= 12; $month++) {
                Cache::forget("sitemap:poets:{$year}:{$month}");
                Cache::forget("sitemap:tags:{$year}:{$month}");
                for ($page = 1; $page <= 20; $page++) {
                    Cache::forget("sitemap:poetry:{$year}:{$month}:{$page}");
                    Cache::forget("sitemap:couplets:{$year}:{$month}:{$page}");
                }
            }
        }
    }
}
