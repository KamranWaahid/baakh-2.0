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
    $mode = $_GET['mode'] ?? 'normal';
    echo "<p>Mode: " . htmlspecialchars($mode) . "</p>";
    echo "<pre>";

    if ($mode === 'targeted') {
        // Specifically run the migrations added recently to fix the 500 error
        $newMigrations = [
            'database/migrations/2026_02_14_114355_create_system_errors_table.php',
            'database/migrations/2026_02_14_180000_create_mokhii_tables.php',
            'database/migrations/2026_02_14_195748_add_status_to_reports_table.php',
            'database/migrations/2026_02_14_230000_add_mokhii_fixes_column.php',
            'database/migrations/2026_02_14_235700_add_is_default_to_languages.php',
            'database/migrations/2026_02_15_000100_create_admin_notifications_table.php',
        ];

        foreach ($newMigrations as $path) {
            echo "Running: $path\n";
            try {
                Artisan::call('migrate', [
                    '--path' => $path,
                    '--force' => true
                ]);
                echo Artisan::output();
            } catch (Exception $innerE) {
                echo "Error in $path: " . $innerE->getMessage() . "\n";
            }
            echo "---------------------------------\n";
        }
    } else {
        // Command: migrate --force
        Artisan::call('migrate', ['--force' => true]);
        echo Artisan::output();
    }

    echo "\n\n--- Migration process completed ---";
    echo "</pre>";

    echo "<h2>Options</h2>";
    echo "<ul>";
    echo "<li><a href='?secret=$secretKey&mode=normal'>Run Normal (All)</a></li>";
    echo "<li><a href='?secret=$secretKey&mode=targeted'>Run Targeted (New Only)</a></li>";
    echo "</ul>";

    echo "<p style='color: red;'><strong>IMPORTANT: Delete this file (/public/run-migrations.php) immediately after use.</strong></p>";

} catch (Exception $e) {
    echo "<h1>Migration Failed</h1>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
