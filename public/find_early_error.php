<?php
// find_early_error.php - Deep scan for the very first ERROR in the log
error_reporting(E_ALL);
ini_set('display_errors', 1);

$logFile = __DIR__ . '/../storage/logs/laravel.log';

echo "<h1>Early Error Scanner</h1>";
echo "<pre>";

if (file_exists($logFile)) {
    $content = file_get_contents($logFile);

    // Look for the last "production.ERROR" that ISN'T a ReflectionException or BindingResolutionException
    // This should skip the noise of the error handler failing.
    $pattern = '/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] production\.ERROR: (?!ReflectionException|BindingResolutionException)(.*?)\n/s';
    preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);

    if (!empty($matches[0])) {
        $lastMatch = end($matches[0]);
        $offset = $lastMatch[1];
        echo "Found potentially ORIGINAL Error at offset $offset. Showing block:\n\n";
        echo htmlspecialchars(substr($content, $offset, 5000));
    } else {
        echo "❌ No suitable 'production.ERROR' found with filtered search. Showing last 10000 characters of total log:\n\n";
        echo htmlspecialchars(substr($content, -10000));
    }
} else {
    echo "❌ Log file NOT found.";
}

echo "</pre>";
?>