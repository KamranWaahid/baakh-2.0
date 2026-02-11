<?php
// find_root_error_v2.php - Find the start of the TypeError block
error_reporting(E_ALL);
ini_set('display_errors', 1);

$logFile = __DIR__ . '/../storage/logs/laravel.log';

echo "<h1>Root Error Finder v2</h1>";
echo "<pre>";

if (file_exists($logFile)) {
    $content = file_get_contents($logFile);

    // Find ALL occurrences of TypeError and show the last few blocks
    preg_match_all('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] production\.ERROR: TypeError: (.*?)\n/s', $content, $matches, PREG_OFFSET_CAPTURE);

    if (!empty($matches[0])) {
        $lastMatch = end($matches[0]);
        $offset = $lastMatch[1];
        echo "Found TypeError at offset $offset. Showing block:\n\n";
        // Show 3000 chars from the start of the match
        echo htmlspecialchars(substr($content, $offset, 3000));
    } else {
        echo "❌ No regex match for TypeError. Falling back to simple search...\n";
        $pos = strrpos($content, 'TypeError:');
        if ($pos !== false) {
            echo "Found 'TypeError:' at position $pos. Snippet:\n\n";
            echo htmlspecialchars(substr($content, $pos, 2000));
        } else {
            echo "❌ No 'TypeError:' found. Showing last 5000 chars of log:\n\n";
            echo htmlspecialchars(substr($content, -5000));
        }
    }
} else {
    echo "❌ Log file NOT found.";
}

echo "</pre>";
?>