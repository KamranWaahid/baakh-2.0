<?php

/**
 * Apply before Composer autoload so Laravel reads corrected $_ENV / getenv().
 * Vercel sets VERCEL=1; Lambda filesystem is not writable for file sessions/cache.
 */
if (!getenv('VERCEL')) {
    return;
}

$apply = static function (string $key, string $value): void {
    $current = getenv($key);
    if ($current !== false && $current !== '') {
        return;
    }
    putenv("{$key}={$value}");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
};

$apply('SESSION_DRIVER', 'cookie');
$apply('CACHE_DRIVER', 'array');
$apply('LOG_CHANNEL', 'stderr');
