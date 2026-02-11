<?php
// read_error_log.php - Read the last few entries from the Laravel log
error_reporting(E_ALL);
ini_set('display_errors', 1);

$logFile = __DIR__ . '/../storage/logs/laravel.log';

echo "<h1>Laravel Log Reader</h1>";
echo "<pre>";

if (file_exists($logFile)) {
    echo "Log file found. Reading last 50 lines...\n\n";
    $lines = file($logFile);
    $lastLines = array_slice($lines, -50);
    foreach ($lastLines as $line) {
        echo htmlspecialchars($line);
    }
} else {
    echo "❌ Log file NOT found at $logFile";
}

echo "\n\n--- End of Log ---";
echo "</pre>";
?>