<?php

namespace App\Jobs;

use App\Services\MokhiiCrawlerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CrawlPageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 30;

    public function __construct(
        public readonly string $url
    ) {
    }

    public function handle(MokhiiCrawlerService $crawler): void
    {
        Log::info("Mokhii: Crawling {$this->url}");
        $audit = $crawler->crawl($this->url);
        Log::info("Mokhii: Crawled {$this->url} — Score: {$audit->score}");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Mokhii: CrawlPageJob failed for {$this->url} — " . $exception->getMessage());
    }
}
