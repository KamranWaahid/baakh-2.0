<?php

namespace App\Console\Commands;

use App\Services\MokhiiResolverService;
use Illuminate\Console\Command;

class MokhiiFixCommand extends Command
{
    protected $signature = 'mokhii:fix';
    protected $description = 'Auto-resolve detected GEO issues using Mokhii resolver';

    public function handle(MokhiiResolverService $resolver): int
    {
        $this->info('Resolving GEO issues...');

        $stats = $resolver->resolveAll();

        $this->newLine();
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Issues Found', number_format($stats['total_issues'])],
                ['Issues Resolved', number_format($stats['issues_fixed'])],
                ['Pages Fixed', number_format($stats['pages_fixed'])],
            ]
        );

        if (!empty($stats['fixes_by_type'])) {
            $this->newLine();
            $this->info('Fixes by type:');
            $rows = [];
            foreach ($stats['fixes_by_type'] as $type => $count) {
                $rows[] = [$type, $count];
            }
            $this->table(['Issue Type', 'Fixed'], $rows);
        }

        $this->newLine();
        $this->info('✓ Mokhii resolved all fixable issues.');

        return self::SUCCESS;
    }
}
