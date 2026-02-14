<?php

namespace App\Console\Commands;

use App\Jobs\CrawlPageJob;
use App\Services\MokhiiCrawlerService;
use Illuminate\Console\Command;

class MokhiiCrawlCommand extends Command
{
    protected $signature = 'mokhii:crawl
                            {--url= : Crawl a single URL instead of the full sitemap}
                            {--sync : Run synchronously instead of dispatching jobs}
                            {--limit=0 : Limit number of URLs to crawl (0 = all)}';

    protected $description = 'Crawl all sitemap URLs (or a single URL) and run SEO audits';

    public function handle(MokhiiCrawlerService $crawler): int
    {
        $singleUrl = $this->option('url');
        $sync = $this->option('sync');
        $limit = (int) $this->option('limit');

        if ($singleUrl) {
            $this->info("Crawling single URL: {$singleUrl}");
            $audit = $crawler->crawl($singleUrl);
            $this->displayResult($audit);
            return self::SUCCESS;
        }

        $this->info('Fetching URLs from sitemap...');
        $urls = $crawler->getSitemapUrls();
        $total = count($urls);

        if ($total === 0) {
            $this->warn('No URLs found in sitemap. Is the server running?');
            return self::FAILURE;
        }

        if ($limit > 0) {
            $urls = array_slice($urls, 0, $limit);
            $total = count($urls);
        }

        $this->info("Found {$total} URLs to crawl.");

        if ($sync) {
            $bar = $this->output->createProgressBar($total);
            $bar->start();

            foreach ($urls as $url) {
                $crawler->crawl($url);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);
        } else {
            foreach ($urls as $url) {
                CrawlPageJob::dispatch($url);
            }
            $this->info("Dispatched {$total} crawl jobs to the queue.");
        }

        $this->info('✓ Mokhii crawl complete.');
        return self::SUCCESS;
    }

    private function displayResult($audit): void
    {
        $this->newLine();
        $this->table(
            ['Metric', 'Value'],
            [
                ['URL', $audit->url],
                ['Status', $audit->status_code],
                ['Response Time', $audit->response_time_ms . 'ms'],
                ['Has H1', $audit->has_h1 ? '✓' : '✗'],
                ['Has Meta Desc', $audit->has_meta_description ? '✓' : '✗'],
                ['Has Schema', $audit->has_schema ? '✓' : '✗'],
                ['Score', $audit->score . '/100'],
                ['Issues', implode(', ', $audit->issues ?? [])],
            ]
        );
    }
}
