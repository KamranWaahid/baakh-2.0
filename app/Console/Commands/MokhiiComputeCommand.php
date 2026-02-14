<?php

namespace App\Console\Commands;

use App\Services\ContentGraphService;
use Illuminate\Console\Command;

class MokhiiComputeCommand extends Command
{
    protected $signature = 'mokhii:compute';
    protected $description = 'Rebuild the knowledge graph and compute page priority scores';

    public function handle(ContentGraphService $service): int
    {
        $this->info('Building knowledge graph...');

        $startTime = microtime(true);
        $stats = $service->buildGraph();
        $elapsed = round(microtime(true) - $startTime, 2);

        $this->newLine();
        $this->table(
            ['Metric', 'Value'],
            [
                ['Graph Edges Created', number_format($stats['edges_created'])],
                ['Pages Computed', number_format($stats['pages_computed'])],
                ['Time Elapsed', "{$elapsed}s"],
            ]
        );

        $this->info('✓ Mokhii knowledge graph computed successfully.');
        return self::SUCCESS;
    }
}
