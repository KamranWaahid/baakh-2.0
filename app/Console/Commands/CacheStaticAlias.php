<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Alias for cache:static-update so `php artisan cache:static [type]` works.
 */
class CacheStaticAlias extends Command
{
    protected $signature = 'cache:static {type=all : Cache type (all|feed|poets|poetry|prosody|...)}';

    protected $description = 'Alias for cache:static-update (accepts optional type argument)';

    public function handle(): int
    {
        $type = (string) $this->argument('type');

        return $this->call('cache:static-update', [
            '--type' => $type ?: 'all',
        ]);
    }
}
