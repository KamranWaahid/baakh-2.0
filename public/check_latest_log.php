<?php
// check_latest_log.php - Read the very last 20 lines of the log
error_reporting(E_ALL);
ini_set('display_errors', 1);

$logFile = __DIR__ . '/../storage/logs/laravel.log';

echo "<h1>Latest Log Entries</h1>";
echo "<pre>";

if (file_exists($logFile)) {
    $lines = file($logFile);
    $lastLines = array_slice($lines, -20);
    foreach ($lastLines as $line) {
        echo htmlspecialchars($line);
    }
} else {
    echo "❌ Log file NOT found.";
}

echo "\n\n--- End of Log ---";
echo "</pre>";
?>