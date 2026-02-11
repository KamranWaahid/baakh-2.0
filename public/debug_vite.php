<?php
// debug_vite.php - Deep dive into how Laravel resolves Vite assets
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

echo "<h1>Vite Resolution Debugger</h1>";
echo "<pre>";

echo "APP_ENV: " . config('app.env') . "\n";
echo "APP_URL: " . config('app.url') . "\n";
echo "APP_KEY: " . (config('app.key') ? "SET (starts with " . substr(config('app.key'), 0, 10) . "...)" : "NOT SET") . "\n";
echo "Public Path: " . public_path() . "\n";
echo "Base Path: " . base_path() . "\n";

$manifestPath = public_path('build/manifest.json');
echo "Expected Manifest Path: $manifestPath\n";
echo "Manifest Exists: " . (file_exists($manifestPath) ? "YES" : "NO") . "\n";

if (file_exists($manifestPath)) {
    echo "Manifest Permissions: " . substr(sprintf('%o', fileperms($manifestPath)), -4) . "\n";
}

echo "\n--- Testing Vite Helper ---\n";
try {
    $vite = app(\Illuminate\Foundation\Vite::class);

    echo "Testing @vite(['resources/js/web/main.jsx']): \n";
    // We use reflection or just call the __invoke or tags method if possible
    // But @vite usually calls app(Vite::class)(...)
    $tags = $vite(['resources/js/web/main.jsx', 'resources/css/app.css']);
    echo "Generated Tags:\n";
    echo htmlspecialchars($tags) . "\n";

    if (empty($tags)) {
        echo "⚠️ WARNING: Vite returned NO tags.\n";
    }

} catch (\Exception $e) {
    echo "❌ ERROR during Vite resolution: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo "</pre>";
?>