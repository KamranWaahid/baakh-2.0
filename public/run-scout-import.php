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
// Usage: yourdomain.com/run-scout-import.php?token=baakh_secret_123
$secret = 'baakh_secret_123';

if (!isset($_GET['token']) || $_GET['token'] !== $secret) {
    die('Unauthorized access.');
}

// 2. Boot Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

// 3. Define Models to Import
$models = [
    'App\Models\Poets',
    'App\Models\Poetry',
    'App\Models\Lemma',
    'App\Models\CorpusSentence'
];

echo "<pre>Starting Meilisearch Indexing...\n";
echo "---------------------------------\n";

foreach ($models as $model) {
    echo "Importing {$model}... ";

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
}

echo "---------------------------------\n";
echo "Indexing process finished.\n";
echo "</pre>";
