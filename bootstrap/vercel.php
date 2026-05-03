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

/*
| Always use stderr + /tmp-backed fallbacks — storage/* is read-only on Vercel Lambda.
*/
putenv('LOG_CHANNEL=stderr');
$_ENV['LOG_CHANNEL'] = 'stderr';
$_SERVER['LOG_CHANNEL'] = 'stderr';
putenv('LOG_STACK=stderr');
$_ENV['LOG_STACK'] = 'stderr';
$_SERVER['LOG_STACK'] = 'stderr';
putenv('LOG_EMERGENCY_PATH=/tmp/laravel.log');
$_ENV['LOG_EMERGENCY_PATH'] = '/tmp/laravel.log';
$_SERVER['LOG_EMERGENCY_PATH'] = '/tmp/laravel.log';
putenv('LOG_SINGLE_PATH=/tmp/laravel.log');
$_ENV['LOG_SINGLE_PATH'] = '/tmp/laravel.log';
$_SERVER['LOG_SINGLE_PATH'] = '/tmp/laravel.log';
putenv('LOG_DAILY_PATH=/tmp/laravel.log');
$_ENV['LOG_DAILY_PATH'] = '/tmp/laravel.log';
$_SERVER['LOG_DAILY_PATH'] = '/tmp/laravel.log';

// Force Laravel cached files into writable /tmp and avoid stale bootstrap/cache artifacts.
putenv('APP_CONFIG_CACHE=/tmp/config.php');
$_ENV['APP_CONFIG_CACHE'] = '/tmp/config.php';
$_SERVER['APP_CONFIG_CACHE'] = '/tmp/config.php';
putenv('APP_EVENTS_CACHE=/tmp/events.php');
$_ENV['APP_EVENTS_CACHE'] = '/tmp/events.php';
$_SERVER['APP_EVENTS_CACHE'] = '/tmp/events.php';
putenv('APP_PACKAGES_CACHE=/tmp/packages.php');
$_ENV['APP_PACKAGES_CACHE'] = '/tmp/packages.php';
$_SERVER['APP_PACKAGES_CACHE'] = '/tmp/packages.php';
putenv('APP_ROUTES_CACHE=/tmp/routes.php');
$_ENV['APP_ROUTES_CACHE'] = '/tmp/routes.php';
$_SERVER['APP_ROUTES_CACHE'] = '/tmp/routes.php';
putenv('APP_SERVICES_CACHE=/tmp/services.php');
$_ENV['APP_SERVICES_CACHE'] = '/tmp/services.php';
$_SERVER['APP_SERVICES_CACHE'] = '/tmp/services.php';
