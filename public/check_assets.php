<?php
// check_assets.php - Check if Vite assets are available and reachable
echo "<h1>Asset Check</h1>";
echo "<pre>";

$manifestPath = __DIR__ . '/build/manifest.json';
if (file_exists($manifestPath)) {
    echo "✅ manifest.json found at: $manifestPath\n";
    $manifest = json_decode(file_get_contents($manifestPath), true);
    echo "Manifest content (first 2 items):\n";
    print_r(array_slice($manifest, 0, 2));
} else {
    echo "❌ manifest.json NOT FOUND at: $manifestPath\n";
    echo "Checked build directory: " . (is_dir(__DIR__ . '/build') ? "Exists" : "MISSING") . "\n";
}

echo "\n--- Environment Check ---\n";
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile);
    foreach ($lines as $line) {
        if (strpos($line, 'APP_URL') === 0)
            echo "Found " . trim($line) . "\n";
        if (strpos($line, 'APP_ENV') === 0)
            echo "Found " . trim($line) . "\n";
    }
}
echo "</pre>";
?>