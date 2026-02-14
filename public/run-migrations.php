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

// 4. Handle Actions
try {
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $response = $kernel->handle(
        $request = Illuminate\Http\Request::capture()
    );

    $action = $_GET['action'] ?? 'menu';
    $mode = $_GET['mode'] ?? 'normal';

    echo "<html><head><title>Baakh Admin Tools</title><style>body{font-family:sans-serif;padding:20px;line-height:1.5;} nav{margin-bottom:20px;} a{margin-right:15px;text-decoration:none;color:#007bff;} pre{background:#f8f9fa;padding:15px;border:1px solid #ddd;overflow:auto;max-height:400px;}</style></head><body>";
    echo "<h1>Baakh Admin Tools</h1>";
    echo "<nav>
        <a href='?secret=$secretKey&action=menu'>🏠 Menu</a>
        <a href='?secret=$secretKey&action=migrate&mode=targeted'>🚀 Run Targeted Migrations (Fix 500)</a>
        <a href='?secret=$secretKey&action=diagnose'>🔍 Diagnose Data</a>
        <a href='?secret=$secretKey&action=clear-cache'>🧹 Clear Cache</a>
        <a href='?secret=$secretKey&action=repair-permissions'>🔑 Repair Admin Permissions</a>
    </nav><hr>";

    if ($action === 'migrate') {
        echo "<h2>Database Migrations (Mode: $mode)</h2>";
        echo "<pre>";
        if ($mode === 'targeted') {
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
                    Artisan::call('migrate', ['--path' => $path, '--force' => true]);
                    echo Artisan::output();
                } catch (Exception $innerE) {
                    if (str_contains($innerE->getMessage(), 'already exists')) {
                        echo "SKIPPED: Table already exists. This is expected if the migration was partially run.\n";
                    } else {
                        echo "Error in $path: " . $innerE->getMessage() . "\n";
                    }
                }
                echo "---------------------------------\n";
            }
        } else {
            Artisan::call('migrate', ['--force' => true]);
            echo Artisan::output();
        }
        echo "</pre>";
    } elseif ($action === 'repair-permissions') {
        echo "<h2>Repairing Admin Permissions</h2>";
        try {
            echo "1. Clearing permission cache...<br>";
            Artisan::call('permission:cache-reset');
            echo "<pre>" . Artisan::output() . "</pre>";

            echo "2. Seeding Roles and Permissions...<br>";
            Artisan::call('db:seed', ['--class' => 'RolesAndPermissionsSeeder', '--force' => true]);
            echo "<pre>" . Artisan::output() . "</pre>";

            echo "Success: Permissions have been reset and seeded.";
        } catch (Exception $e) {
            echo "<span style='color:red;'>Error: " . $e->getMessage() . "</span><br>";
        }
    } elseif ($action === 'diagnose') {
        echo "<h2>Environment Diagnostics</h2>";
        echo "<ul>";
        echo "<li><strong>PHP Version:</strong> " . phpversion() . "</li>";
        echo "<li><strong>Environment:</strong> " . app()->environment() . "</li>";
        echo "<li><strong>DB Connection:</strong> " . config('database.default') . "</li>";
        try {
            $conn = DB::connection();
            $dbName = "";
            if ($conn->getDriverName() === 'mysql') {
                $dbName = $conn->getDatabaseName();
            } else if ($conn->getDriverName() === 'sqlite') {
                $dbName = config('database.connections.sqlite.database');
            }
            echo "<li><strong>Active Database:</strong> " . $dbName . " (" . $conn->getDriverName() . ")</li>";
        } catch (Exception $e) {
            echo "<li style='color:red;'><strong>DB Error:</strong> " . $e->getMessage() . "</li>";
        }
        echo "</ul>";

        echo "<h2>Database Table Counts</h2>";
        $tables = ['users', 'poets', 'poetry_main', 'baakh_tags', 'activity_logs', 'reports', 'feedback', 'mokhii_page_meta', 'admin_notifications'];
        echo "<table border='1' cellpadding='10' style='border-collapse:collapse;width:100%;'>";
        echo "<tr style='background:#eee;'><th>Table</th><th>Count</th><th>Status</th></tr>";
        foreach ($tables as $table) {
            try {
                $count = DB::table($table)->count();
                echo "<tr><td>$table</td><td>$count</td><td style='color:green;'>OK</td></tr>";
            } catch (Exception $e) {
                echo "<tr><td>$table</td><td>-</td><td style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
            }
        }
        echo "</table>";

        echo "<h2>Latest Logs (storage/logs/laravel.log)</h2>";
        $logPath = storage_path('logs/laravel.log');
        if (file_exists($logPath)) {
            $lines = file($logPath);
            $lastLines = array_slice($lines, -30);
            echo "<pre>" . htmlspecialchars(implode('', $lastLines)) . "</pre>";
        } else {
            echo "<p>No log file found at $logPath</p>";
        }
    } elseif ($action === 'clear-cache') {
        echo "<h2>Clearing Caches</h2>";

        echo "<strong>1. Artisan optimize:clear...</strong><br>";
        try {
            Artisan::call('optimize:clear');
            echo "<pre>" . Artisan::output() . "</pre>";
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "<br>";
        }

        echo "<strong>2. Admin Dashboard Stats cache clearing...</strong><br>";
        Illuminate\Support\Facades\Cache::forget('admin_dashboard_stats');
        echo "Done.<br><br>";

        echo "<strong>3. Re-running Dashboard Stats Job (Sync)...</strong><br>";
        try {
            \App\Jobs\UpdateDashboardStats::dispatchSync();
            echo "Success: Dashboard stats regenerated.<br>";
        } catch (Exception $e) {
            echo "<span style='color:red;'>Error regenerating stats: " . $e->getMessage() . "</span><br>";
        }
    } else {
        echo "<h2>Menu</h2>";
        echo "<p>Please select an action from the menu above to troubleshoot your server.</p>";
        echo "<p><strong>Direct Links:</strong></p>";
        echo "<ul>";
        echo "<li><a href='?secret=$secretKey&action=migrate&mode=targeted'>🚀 STEP 1: Fix 500 Error (Targeted Migration)</a></li>";
        echo "<li><a href='?secret=$secretKey&action=repair-permissions'>🔑 STEP 2: Fix Admin Panel Data (Repair Permissions)</a></li>";
        echo "<li><a href='?secret=$secretKey&action=clear-cache'>🧹 STEP 3: Clear Cache</a></li>";
        echo "</ul>";
    }

    echo "<hr><p style='color: red;'><strong>🛡️ SECURITY: Delete this file (/public/run-migrations.php) immediately after use.</strong></p>";
    echo "</body></html>";

} catch (Exception $e) {
    echo "<h1>Critical Error</h1>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
