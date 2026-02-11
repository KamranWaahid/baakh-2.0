<?php
// debug_boot_order.php - Find exactly when 'url' is resolved
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Laravel Resolution Tracker</h1>";
echo "<pre>";

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$app->resolving('url', function ($url, $app) {
    echo "!!! 'url' is being resolved NOW !!!\n";
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
    foreach ($backtrace as $i => $step) {
        echo "#$i " . ($step['class'] ?? '') . ($step['type'] ?? '') . $step['function'] . " in " . ($step['file'] ?? 'unknown') . ":" . ($step['line'] ?? '') . "\n";
    }
});

echo "Step 1: Starting Kernel handle process...\n";
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "Step 2: Capturing Request...\n";
$request = Illuminate\Http\Request::capture();

echo "Step 3: Calling Kernel->handle()...\n";
try {
    $kernel->handle($request);
    echo "✅ Success\n";
} catch (\Throwable $e) {
    echo "❌ CRASH: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>