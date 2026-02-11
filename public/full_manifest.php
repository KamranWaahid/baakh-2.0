<?php
// full_manifest.php - Dump the entire manifest.json for inspection
echo "<h1>Full Vite Manifest</h1>";
echo "<pre>";

$manifestPath = __DIR__ . '/build/manifest.json';
if (file_exists($manifestPath)) {
    echo htmlspecialchars(file_get_contents($manifestPath));
} else {
    echo "❌ manifest.json NOT FOUND at $manifestPath";
}

echo "</pre>";
?>