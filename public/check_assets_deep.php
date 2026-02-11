<?php
// check_assets_deep.php - Deep verification of static assets
echo "<h1>Deep Asset Verification</h1>";
echo "<pre>";

$assets = [
    'build/manifest.json',
    'build/assets/app-f173dcf8.css',
    'build/assets/main-7ea7b0b5.js',
    'build/assets/textarea-32d99254.js'
];

foreach ($assets as $asset) {
    $path = __DIR__ . '/' . $asset;
    echo "Checking: $asset\n";
    if (file_exists($path)) {
        $size = filesize($path);
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        echo "   ✅ Found. Size: " . number_format($size) . " bytes. Perms: $perms\n";

        // Check if we can read the first byte
        $f = fopen($path, 'r');
        if ($f) {
            fread($f, 1);
            fclose($f);
            echo "   ✅ Readable.\n";
        } else {
            echo "   ❌ NOT READABLE.\n";
        }
    } else {
        echo "   ❌ NOT FOUND at $path\n";
    }
}

echo "\nServer Protocol: " . $_SERVER['SERVER_PROTOCOL'] . "\n";
echo "HTTP2 Support? " . (isset($_SERVER['HTTP2']) ? "Yes" : "Maybe (check headers)") . "\n";

echo "</pre>";
?>