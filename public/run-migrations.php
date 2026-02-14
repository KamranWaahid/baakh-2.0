<?php

use Illuminate\Support\Facades\Artisan;

/**
 * Migration Script for Shared Hosting
 * This script allows running migrations via a URL.
 * SECURITY: Requires a 'secret' query parameter.
 */

// 1. Define the secret key
$secretKey = 'baakh_migration_2026';

// 2. Check for the secret key
if (!isset($_GET['secret']) || $_GET['secret'] !== $secretKey) {
    header('HTTP/1.1 403 Forbidden');
    die('Unauthorized access. Please provide the correct secret key.');
}

// 3. Load Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

// 4. Execute migrations
try {
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $response = $kernel->handle(
        $request = Illuminate\Http\Request::capture()
    );

    echo "<h1>Baakh Database Migration</h1>";
    echo "<pre>";

    // Command: migrate --force
    Artisan::call('migrate', ['--force' => true]);
    echo Artisan::output();

    echo "\n\n--- Migration completed successfully ---";
    echo "</pre>";

    echo "<p style='color: red;'><strong>IMPORTANT: Delete this file (/public/run-migrations.php) immediately after use.</strong></p>";

} catch (Exception $e) {
    echo "<h1>Migration Failed</h1>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
