<?php
$logFile = '../storage/logs/laravel.log';
echo "<h1>Latest Laravel Logs</h1>";
echo "<pre>";
if (file_exists($logFile)) {
    echo shell_exec('tail -n 50 ' . escapeshellarg($logFile));
} else {
    echo "Log file not found at $logFile";
}
echo "</pre>";
