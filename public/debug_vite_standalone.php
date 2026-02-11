<?php
// debug_vite_standalone.php - Test Vite resolution on the server
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Vite Directive Debugger</h1>";
echo "<pre>";

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

echo "1. Public Path: " . public_path() . "\n";
echo "2. Manifest Path: " . public_path('build/manifest.json') . "\n";

if (file_exists(public_path('build/manifest.json'))) {
    echo "✅ Manifest.json EXISTS.\n";
    $content = file_get_contents(public_path('build/manifest.json'));
    echo "   Size: " . strlen($content) . " bytes\n";
} else {
    echo "❌ Manifest.json MISSING!\n";
}

echo "3. Testing Vite Helper... ";
try {
    $vite = app(\Illuminate\Foundation\Vite::class);
    echo "✅ Helper Instance Created.\n";

    echo "4. Attempting to resolve assets (resources/css/app.css)...\n";
    // We simulate what @vite does
    $tags = $vite(['resources/css/app.css', 'resources/js/web/main.jsx']);
    echo "✅ SUCCESS! Tags generated:\n" . htmlspecialchars($tags) . "\n";

} catch (\Throwable $e) {
    echo "❌ FAILED!\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "</pre>";
?>