<?php
// clear_cache_manual.php - Manually delete Laravel bootstrap cache files
echo "<h1>Manual Cache Clear</h1>";
echo "<pre>";

$cacheFiles = [
    __DIR__ . '/../bootstrap/cache/config.php',
    __DIR__ . '/../bootstrap/cache/routes-v7.php',
    __DIR__ . '/../bootstrap/cache/services.php',
    __DIR__ . '/../bootstrap/cache/packages.php'
];

foreach ($cacheFiles as $file) {
    if (file_exists($file)) {
        if (unlink($file)) {
            echo "✅ DELETED: " . basename($file) . "\n";
        } else {
            echo "❌ FAILED to delete: " . basename($file) . " (Check permissions)\n";
        }
    } else {
        echo "ℹ️ NOT FOUND: " . basename($file) . "\n";
    }
}

echo "\nDone.";
echo "</pre>";
?>