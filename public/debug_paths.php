<?php
// debug_paths.php - Simple path and error debugger (No Laravel Bootstrap)
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Path Debugger</h1>";
echo "<pre>";

$rootDir = realpath(__DIR__ . '/..');
echo "Current Directory: " . __DIR__ . "\n";
echo "Project Root (Inferred): " . $rootDir . "\n";

$filesToCheck = [
    '../vendor/autoload.php',
    '../bootstrap/app.php',
    '../.env',
    'build/manifest.json',
    '../storage/logs/laravel.log'
];

foreach ($filesToCheck as $file) {
    $fullPath = __DIR__ . '/' . $file;
    echo "Checking [$file]: ";
    if (file_exists($fullPath)) {
        echo "✅ EXISTS (" . size_format(filesize($fullPath)) . ")";
        if (strpos($file, '.env') !== false) {
            // Check if readable
            echo is_readable($fullPath) ? " [READABLE]" : " [NOT READABLE]";
        }
    } else {
        echo "❌ MISSING";
    }
    echo "\n";
}

function size_format($bytes)
{
    $units = ['B', 'KB', 'MB', 'GB'];
    for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++)
        $bytes /= 1024;
    return round($bytes, 2) . ' ' . $units[$i];
}

echo "\n--- System Info ---\n";
echo "PHP Version: " . phpversion() . "\n";
echo "User: " . get_current_user() . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";

echo "</pre>";
?>