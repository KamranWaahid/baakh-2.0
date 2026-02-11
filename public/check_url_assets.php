<?php
// check_url_assets.php - Check if assets are reachable via URL
echo "<h1>Asset URL Accessibility Check</h1>";
echo "<pre>";

$assets = [
    '/build/manifest.json',
    '/build/assets/main-7ea7b0b5.js',
    '/build/assets/app-f173dcf8.css'
];

$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";

foreach ($assets as $asset) {
    $url = $baseUrl . $asset;
    echo "Testing URL: $url\n";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true); // Just check headers
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    if ($code === 200) {
        echo "✅ SUCCESS: Code $code ($type)\n";
    } else {
        echo "❌ FAILED: Code $code\n";
    }
    echo "\n";
}

echo "</pre>";
?>