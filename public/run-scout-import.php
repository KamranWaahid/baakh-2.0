<?php

/**
 * Baakh - Meilisearch Indexing Trigger
 * -----------------------------------
 * This script runs 'php artisan scout:import' commands via a web request.
 * 
 * SECURITY WARNING: 
 * Delete this file immediately after use or protect it with a secret token.
 */

// 1. Secret token to prevent unauthorized access
$secret = 'baakh_secret_123';

if (!isset($_GET['token']) || $_GET['token'] !== $secret) {
    die('Unauthorized access.');
}

// 2. Increase limits for long running process
set_time_limit(0);
ini_set('memory_limit', '512M');

// 3. Boot Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

// 4. Define Models to Import
$models = [
    'App\Models\Poets',
    'App\Models\Poetry',
    'App\Models\Lemma',
    'App\Models\CorpusSentence'
];

echo "<pre>Starting Meilisearch Indexing...\n";
echo "---------------------------------\n";
echo "Configuration: Time Limit=Infinite, Memory Limit=512M\n\n";

// Disable output buffering to show progress in real-time
if (ob_get_level()) {
    ob_end_flush();
}
ob_implicit_flush(true);

foreach ($models as $model) {
    echo "Importing {$model}... ";
    flush();

    try {
        // Using Artisan facade/call
        $exitCode = Illuminate\Support\Facades\Artisan::call('scout:import', [
            'model' => $model
        ]);

        if ($exitCode === 0) {
            echo "[DONE]\n";
        } else {
            echo "[FAILED] Error Code: {$exitCode}\n";
            echo Illuminate\Support\Facades\Artisan::output() . "\n";
        }
    } catch (\Exception $e) {
        echo "[EXCEPTION] " . $e->getMessage() . "\n";
    }
    flush();
}

echo "---------------------------------\n";
echo "Indexing process finished.\n";
echo "</pre>";
