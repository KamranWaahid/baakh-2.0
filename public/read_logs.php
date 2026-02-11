<?php
// read_logs.php - Standalone log reader using standard PHP functions
$logFile = __DIR__ . '/../storage/logs/laravel.log';
echo "<h1>Latest Laravel Logs</h1>";
echo "<pre style='background: #f4f4f4; padding: 10px; border: 1px solid #ddd; overflow: auto;'>";

if (file_exists($logFile)) {
    $content = file($logFile);
    if ($content) {
        $lastLines = array_slice($content, -100);
        foreach ($lastLines as $line) {
            echo htmlspecialchars($line);
        }
    } else {
        echo "Log file is empty or unreadable.";
    }
} else {
    echo "Log file not found at: " . realpath($logFile);
}
echo "</pre>";
?>