<?php
// check_vite_entries.php - Deep dive into manifest and asset accessibility
echo "<h1>Detailed Vite Check</h1>";
echo "<pre>";

$manifestPath = __DIR__ . '/build/manifest.json';
if (file_exists($manifestPath)) {
    $manifest = json_decode(file_get_contents($manifestPath), true);

    $requiredEntries = ['resources/css/app.css', 'resources/js/web/main.jsx'];

    foreach ($requiredEntries as $entry) {
        if (isset($manifest[$entry])) {
            $file = $manifest[$entry]['file'];
            $fullUrl = "/build/" . $file;
            echo "✅ FOUND ENTRY: $entry -> $file\n";
            echo "   URL should be: $fullUrl\n";

            // Check if file actually exists on disk
            if (file_exists(__DIR__ . '/build/' . $file)) {
                echo "   ✅ FILE EXISTS ON DISK\n";
            } else {
                echo "   ❌ FILE MISSING ON DISK\n";
            }
        } else {
            echo "❌ MISSING ENTRY: $entry\n";
            echo "   Available entries in manifest (first 10):\n";
            print_r(array_keys(array_slice($manifest, 0, 10)));
        }
        echo "\n";
    }
} else {
    echo "❌ manifest.json NOT FOUND at $manifestPath\n";
}

echo "--- Directory Scan --- \n";
echo "Public folder: " . __DIR__ . "\n";
echo "Build folder: " . (is_dir(__DIR__ . '/build') ? "Exists" : "MISSING") . "\n";
if (is_dir(__DIR__ . '/build/assets')) {
    echo "Assets folder: Exists\n";
    $files = scandir(__DIR__ . '/build/assets');
    echo "Random files in assets: " . implode(', ', array_slice($files, 2, 5)) . "\n";
}

echo "</pre>";
?>