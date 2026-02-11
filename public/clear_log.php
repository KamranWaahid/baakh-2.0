<?php
// clear_log.php - Clear the Laravel log file to start fresh
$logFile = __DIR__ . '/../storage/logs/laravel.log';

echo "<h1>Log Clearer</h1>";
echo "<pre>";

if (file_exists($logFile)) {
    if (file_put_contents($logFile, '') !== false) {
        echo "✅ Log file cleared successfully.";
    } else {
        echo "❌ FAILED to clear log file.";
    }
} else {
    echo "❌ Log file NOT found.";
}

echo "</pre>";
?>