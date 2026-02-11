<?php
// check_manifest_server.php - Compare server manifest with expected state
$manifestPath = __DIR__ . '/build/manifest.json';
echo "<h1>Server Manifest Check</h1>";
echo "<pre>";
if (file_exists($manifestPath)) {
    echo "✅ Manifest found.\n";
    $manifest = json_decode(file_get_contents($manifestPath), true);
    echo "Entries count: " . count($manifest) . "\n";
    echo "Snapshot of entries:\n";
    foreach (array_slice($manifest, 0, 5) as $key => $val) {
        echo "  $key => " . $val['file'] . "\n";
    }
} else {
    echo "❌ Manifest NOT found at $manifestPath\n";
}
echo "</pre>";
?>