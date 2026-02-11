<?php
// deep_clean.php - Aggressively remove all Laravel cache files
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Aggressive Cache Cleaner</h1>";
echo "<pre>";

$files = [
    __DIR__ . '/../bootstrap/cache/config.php',
    __DIR__ . '/../bootstrap/cache/routes-v7.php',
    __DIR__ . '/../bootstrap/cache/services.php',
    __DIR__ . '/../bootstrap/cache/packages.php',
    __DIR__ . '/../bootstrap/cache/events.php',
];

foreach ($files as $file) {
    if (file_exists($file)) {
        if (unlink($file)) {
            echo "✅ Deleted: " . basename($file) . "\n";
        } else {
            echo "❌ FAILED to delete: " . basename($file) . "\n";
        }
    } else {
        echo "ℹ️ Not found: " . basename($file) . "\n";
    }
}

// Clear compiled views
$viewDir = __DIR__ . '/../storage/framework/views';
if (is_dir($viewDir)) {
    echo "Cleaning views...\n";
    foreach (glob("$viewDir/*.php") as $view) {
        unlink($view);
    }
    echo "✅ Views cleared.\n";
}

echo "\nDone.";
echo "</pre>";
?>