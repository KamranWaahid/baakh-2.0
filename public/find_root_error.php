<?php
// find_root_error.php - Search for the original TypeError in the log
error_reporting(E_ALL);
ini_set('display_errors', 1);

$logFile = __DIR__ . '/../storage/logs/laravel.log';

echo "<h1>Root Error Finder</h1>";
echo "<pre>";

if (file_exists($logFile)) {
    $content = file_get_contents($logFile);
    // Find the last occurrence of TypeError
    $pos = strrpos($content, 'TypeError');
    if ($pos !== false) {
        echo "Found TypeError at position $pos. Snippet:\n\n";
        echo htmlspecialchars(substr($content, $pos - 200, 2000));
    } else {
        echo "❌ No 'TypeError' found in the log file. Showing last 5000 characters instead:\n\n";
        echo htmlspecialchars(substr($content, -5000));
    }
} else {
    echo "❌ Log file NOT found.";
}

echo "</pre>";
?>